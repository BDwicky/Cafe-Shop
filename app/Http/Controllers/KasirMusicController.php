<?php

namespace App\Http\Controllers;

use App\Models\MusicDefaultTrack;
use App\Models\MusicRequest;
use App\Models\Order;
use App\Services\KitchenService;
use App\Services\MusicService;
use App\Services\PrayerTimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class KasirMusicController extends Controller
{
    public function __construct(
        protected MusicService $musicService
    ) {}

    /**
     * Halaman Pemutar Musik (Sound Station) Kasir.
     */
    public function index(): View
    {
        $state = $this->musicService->getPlayerState();
        $defaultTracks = MusicDefaultTrack::orderBy('sort_order')->orderBy('id')->get();
        $recentHistory = MusicRequest::whereIn('status', ['played', 'skipped', 'rejected'])
            ->latest('updated_at')
            ->limit(30)
            ->get();

        return view('kasir.music', compact('state', 'defaultTracks', 'recentHistory'));
    }

    /**
     * Halaman Pop-up Mini Player (Sound Station Mini)
     * Untuk dibuka di jendela terpisah agar musik & voice announcer tetap aktif saat kasir pindah tab / menu di POS.
     */
    public function mini(): View
    {
        $state = $this->musicService->getPlayerState();

        return view('kasir.music-mini', compact('state'));
    }

    /**
     * API untuk Player Browser mengambil lagu berikutnya secara otomatis:
     * - Jika ada antrean request pelanggan, putar lagu tersebut.
     * - Jika antrean kosong, lanjut putar lagu berikutnya dari playlist bawaan.
     */
    public function nextTrack(Request $request): JsonResponse
    {
        $finishRequestId = $request->integer('finish_request_id') ?: null;
        $lastDefaultTrackId = $request->integer('last_default_track_id') ?: null;
        $wasBlocked = $request->boolean('was_blocked');
        $blockedReason = $request->string('blocked_reason')->toString() ?: null;
        $resumeDefaultTrackId = $request->integer('resume_default_track_id') ?: null;
        $resumePosition = $request->has('resume_position') ? $request->integer('resume_position') : null;

        $next = $this->musicService->transitionToNextTrack(
            $finishRequestId,
            $lastDefaultTrackId,
            $wasBlocked,
            $blockedReason,
            $resumeDefaultTrackId,
            $resumePosition
        );

        return response()->json($next);
    }

    /**
     * Kasir melewati (skip) lagu customer yang sedang diputar atau mengantre.
     */
    public function skip(MusicRequest $musicRequest): JsonResponse|RedirectResponse
    {
        $this->musicService->skipRequest($musicRequest);

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Lagu berhasil dilewati (skip).']);
        }

        return back()->with('success', 'Lagu berhasil dilewati.');
    }

    /**
     * Kasir menolak request lagu (misal: lagu tidak pantas/sara).
     */
    public function reject(Request $request, MusicRequest $musicRequest): JsonResponse|RedirectResponse
    {
        $reason = $request->input('reason', 'Tidak sesuai dengan suasana kafe');
        $this->musicService->rejectRequest($musicRequest, $reason);

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Lagu request berhasil ditolak.']);
        }

        return back()->with('success', 'Lagu request berhasil ditolak.');
    }

    /**
     * Masukkan lagu dari riwayat request pelanggan ke dalam playlist bawaan kafe.
     */
    public function addRequestToDefault(MusicRequest $musicRequest): JsonResponse|RedirectResponse
    {
        if (! $musicRequest->youtube_id) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lagu request tidak memiliki ID YouTube yang valid.',
                ], 422);
            }

            return back()->withErrors(['message' => 'Lagu request tidak memiliki ID YouTube yang valid.']);
        }

        // Cek apakah lagu ini sudah ada di playlist bawaan
        $existing = MusicDefaultTrack::where('youtube_id', $musicRequest->youtube_id)->first();
        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'already_exists' => true,
                    'message' => "Lagu \"{$existing->title}\" sudah ada di playlist bawaan (status aktif).",
                    'track' => $existing,
                ]);
            }

            return back()->with('info', "Lagu \"{$existing->title}\" sudah ada di playlist bawaan.");
        }

        $maxOrder = MusicDefaultTrack::max('sort_order') ?? 0;

        $track = MusicDefaultTrack::create([
            'title' => $musicRequest->song_title ?: 'Lagu Pilihan',
            'artist' => $musicRequest->artist,
            'youtube_id' => $musicRequest->youtube_id,
            'duration_seconds' => $musicRequest->duration_seconds ?? 0,
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'already_exists' => false,
                'message' => "Lagu \"{$track->title}\" berhasil ditambahkan ke playlist bawaan.",
                'track' => $track,
            ]);
        }

        return back()->with('success', "Lagu \"{$track->title}\" berhasil ditambahkan ke playlist bawaan.");
    }

    /**
     * Tambah lagu baru ke playlist bawaan kafe.
     * Judul dan artis otomatis diekstraksi dari metadata video YouTube jika tidak diinput manual.
     */
    public function storeDefaultTrack(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            'youtube_url' => ['required', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $youtubeId = $this->musicService->extractYouTubeId($data['youtube_url']);
        if (! $youtubeId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tautan atau ID YouTube tidak valid.',
                ], 422);
            }

            return back()->withErrors(['youtube_url' => 'Tautan atau ID YouTube tidak valid.'])->withInput();
        }

        $details = $this->musicService->fetchYouTubeDetails($youtubeId);

        // Kasir memiliki pengecualian durasi (bebas memutar lagu panjang, mix 1 jam, atau kompilasi santai)
        // Otomatis isi judul dari YouTube jika kasir tidak menginput judul secara manual
        $title = ! empty($data['title']) ? trim($data['title']) : $details['title'];
        $artist = ! empty($data['artist'])
            ? trim($data['artist'])
            : ($details['artist'] !== 'YouTube' ? $details['artist'] : null);

        $track = MusicDefaultTrack::create([
            'title' => $title,
            'artist' => $artist,
            'youtube_id' => $youtubeId,
            'duration_seconds' => $details['duration_seconds'] ?? 0,
            'sort_order' => $data['sort_order'] ?? (MusicDefaultTrack::count() + 1),
            'is_active' => true,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Lagu \"{$title}\" berhasil ditambahkan ke playlist bawaan.",
                'track' => $track,
            ]);
        }

        return back()->with('success', "Lagu \"{$title}\" berhasil ditambahkan ke playlist bawaan.");
    }

    /**
     * Ambil daftar lagu bawaan kafe dalam format JSON.
     */
    public function defaultTracksJson(): JsonResponse
    {
        $tracks = MusicDefaultTrack::orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'tracks' => $tracks,
        ]);
    }

    /**
     * Hitung badge real-time untuk navigasi sidebar kasir (KDS & Antrean Musik).
     */
    public function sidebarCounts(): JsonResponse
    {
        return response()->json([
            'kds_count' => Order::prepActive()->count(),
            'music_queue_count' => MusicRequest::queued()->count(),
        ]);
    }

    /**
     * Inspeksi tautan YouTube secara instan untuk mengisi judul, artis, durasi, dan cover secara otomatis.
     */
    public function inspectLink(Request $request): JsonResponse
    {
        $url = (string) $request->input('url', '');
        $youtubeId = $this->musicService->extractYouTubeId($url);

        if (! $youtubeId) {
            return response()->json([
                'valid' => false,
                'message' => 'Tautan atau ID video YouTube tidak valid.',
            ], 422);
        }

        $details = $this->musicService->fetchYouTubeDetails($youtubeId);

        return response()->json([
            'valid' => true,
            'youtube_id' => $details['youtube_id'],
            'title' => $details['title'],
            'artist' => $details['artist'] !== 'YouTube' ? $details['artist'] : '',
            'duration_seconds' => $details['duration_seconds'],
            'duration_formatted' => $details['duration_formatted'],
            'thumbnail_url' => $details['thumbnail_url'],
            'is_valid_duration' => $details['is_valid_duration'],
            'duration_error' => $details['duration_error'],
        ]);
    }

    /**
     * Import banyak lagu sekaligus dari sekumpulan tautan YouTube (satu tautan per baris).
     */
    public function storeBatchDefaultTracks(Request $request): JsonResponse|RedirectResponse
    {
        $rawLinks = (string) $request->input('youtube_urls', '');
        $lines = preg_split('/[\r\n,]+/', $rawLinks);

        $importedCount = 0;
        $rejectedDurationCount = 0;
        $invalidCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $youtubeId = $this->musicService->extractYouTubeId($line);
            if (! $youtubeId) {
                $invalidCount++;

                continue;
            }

            // Hindari duplikasi di playlist bawaan
            if (MusicDefaultTrack::where('youtube_id', $youtubeId)->exists()) {
                continue;
            }

            $details = $this->musicService->fetchYouTubeDetails($youtubeId);

            MusicDefaultTrack::create([
                'title' => $details['title'],
                'artist' => $details['artist'] !== 'YouTube' ? $details['artist'] : null,
                'youtube_id' => $youtubeId,
                'duration_seconds' => $details['duration_seconds'] ?? 0,
                'sort_order' => MusicDefaultTrack::count() + 1,
                'is_active' => true,
            ]);

            $importedCount++;
        }

        $msg = "Berhasil mengimpor {$importedCount} lagu ke playlist bawaan.";
        if ($invalidCount > 0) {
            $msg .= " ({$invalidCount} tautan tidak valid).";
        }

        if ($request->expectsJson()) {
            $tracks = MusicDefaultTrack::orderBy('sort_order')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => $msg,
                'imported_count' => $importedCount,
                'tracks' => $tracks,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Perbarui urutan (sort_order) lagu-lagu di playlist bawaan kafe secara massal (Drag and Drop).
     */
    public function reorderDefaultTracks(Request $request): JsonResponse
    {
        $data = $request->validate([
            'track_ids' => ['required', 'array'],
            'track_ids.*' => ['integer', 'exists:music_default_tracks,id'],
        ]);

        foreach ($data['track_ids'] as $index => $id) {
            MusicDefaultTrack::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Urutan playlist bawaan berhasil disimpan.',
        ]);
    }

    /**
     * Aktifkan / Nonaktifkan lagu bawaan kafe.
     */
    public function toggleDefaultTrack(Request $request, MusicDefaultTrack $track): JsonResponse|RedirectResponse
    {
        $track->update(['is_active' => ! $track->is_active]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status lagu bawaan berhasil diperbarui.',
                'track' => $track,
            ]);
        }

        return back()->with('success', 'Status lagu bawaan berhasil diperbarui.');
    }

    /**
     * Perbarui data lagu bawaan kafe (judul, artis, ID YouTube, urutan).
     */
    public function updateDefaultTrack(Request $request, MusicDefaultTrack $track): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            'youtube_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $updatePayload = [
            'title' => trim($data['title']),
            'artist' => ! empty($data['artist']) ? trim($data['artist']) : null,
            'sort_order' => $data['sort_order'] ?? $track->sort_order,
        ];

        if (! empty($data['youtube_url'])) {
            $youtubeId = $this->musicService->extractYouTubeId($data['youtube_url']);
            if ($youtubeId && $youtubeId !== $track->youtube_id) {
                $updatePayload['youtube_id'] = $youtubeId;
                $details = $this->musicService->fetchYouTubeDetails($youtubeId);
                if (! empty($details['duration_seconds'])) {
                    $updatePayload['duration_seconds'] = $details['duration_seconds'];
                }
            }
        }

        $track->update($updatePayload);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Lagu bawaan \"{$track->title}\" berhasil diperbarui.",
                'track' => $track,
            ]);
        }

        return back()->with('success', "Lagu bawaan \"{$track->title}\" berhasil diperbarui.");
    }

    /**
     * Hapus lagu dari playlist bawaan kafe.
     */
    public function destroyDefaultTrack(Request $request, MusicDefaultTrack $track): JsonResponse|RedirectResponse
    {
        $id = $track->id;
        $title = $track->title;
        $track->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Lagu \"{$title}\" berhasil dihapus dari playlist bawaan.",
                'deleted_id' => $id,
            ]);
        }

        return back()->with('success', 'Lagu berhasil dihapus dari playlist bawaan.');
    }

    /**
     * Ambil pesanan yang berstatus 'ready' dan perlu dipanggil suaranya via Sound Station.
     */
    public function pendingAnnouncements(KitchenService $kitchenService): JsonResponse
    {
        $orders = $kitchenService->getPendingAnnouncements();

        $data = $orders->map(fn ($o) => [
            'id' => $o->id,
            'code' => $o->code,
            'music_code' => $o->music_code,
            'customer_name' => $o->customer_name,
            'order_type' => $o->order_type,
        ]);

        return response()->json(['orders' => $data]);
    }

    /**
     * Tandai pesanan telah diumumkan suaranya oleh Sound Station.
     */
    public function markAnnounced(Order $order, KitchenService $kitchenService): JsonResponse
    {
        $kitchenService->markAnnounced($order);

        return response()->json(['message' => "Pesanan {$order->code} telah diumumkan."]);
    }

    /**
     * Simpan status playback realtime (detik & durasi) dari browser kasir ke cache
     * agar display TV di perangkat lain (Smart TV / TV Box) tersinkronisasi secara akurat.
     */
    public function syncPlayback(Request $request): JsonResponse
    {
        $clientId = $request->string('client_id')->toString();
        $master = Cache::get('soundstation_master_host');

        // Proteksi: Hanya Master Host yang diizinkan memperbarui detik playback ke server!
        // Jika ada perangkat remote yang mencoba syncPlayback, abaikan agar display TV tidak reset ke 0.
        if ($master && ! empty($master['client_id']) && ! empty($clientId) && $master['client_id'] !== $clientId) {
            return response()->json(['status' => 'ignored', 'message' => 'Hanya master yang dapat memperbarui playback']);
        }

        $currentTime = $request->float('current_time', 0);
        $duration = $request->float('duration', 0);
        $isPlaying = $request->boolean('is_playing');
        $currentTrack = $request->input('current_track');

        $existing = Cache::get('soundstation_playback_state', []);

        if ($duration <= 0 && is_array($currentTrack) && ! empty($currentTrack['duration_seconds'])) {
            $duration = (float) $currentTrack['duration_seconds'];
        }

        $state = [
            'current_time' => $currentTime,
            'duration' => $duration > 0 ? $duration : ($existing['duration'] ?? 0),
            'is_playing' => $isPlaying,
            'current_track' => $currentTrack ?: ($existing['current_track'] ?? null),
            'updated_at' => (int) round(microtime(true) * 1000),
            'client_id' => $clientId ?: ($master['client_id'] ?? null),
        ];

        Cache::put('soundstation_playback_state', $state, now()->addMinutes(2));

        if ($currentTrack && is_array($currentTrack)) {
            if (! isset($currentTrack['song_title']) && isset($currentTrack['title'])) {
                $currentTrack['song_title'] = $currentTrack['title'];
            }
            if (empty($currentTrack['thumbnail_url']) && ! empty($currentTrack['youtube_id'])) {
                $currentTrack['thumbnail_url'] = "https://img.youtube.com/vi/{$currentTrack['youtube_id']}/hqdefault.jpg";
            }
            Cache::put('soundstation_current_track', $currentTrack, now()->addHours(8));

            if (! empty($currentTrack['type']) && $currentTrack['type'] === 'default_track') {
                MusicRequest::playing()->update(['status' => 'played']);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Klaim status Master Host (pemutar audio utama).
     * Jika perangkat lain memaksa (force) atau memiliki prioritas lebih tinggi (halaman dedicated /kasir/music),
     * status master dipindahkan ke perangkat pemanggil.
     */
    public function claimMasterHost(Request $request): JsonResponse
    {
        $clientId = $request->string('client_id')->toString();
        $deviceId = $request->string('device_id')->toString();
        $deviceName = $request->string('device_name', 'Perangkat Lain')->toString();
        $pageTitle = $request->string('page_title', 'Sound Station')->toString();
        $priority = $request->integer('priority', 10);
        $force = $request->boolean('force');

        if (empty($clientId)) {
            return response()->json(['status' => 'error', 'message' => 'client_id wajib diisi'], 422);
        }

        $existingMaster = Cache::get('soundstation_master_host');
        $now = now()->timestamp;

        // Cek jika master sebelumnya masih aktif
        if ($existingMaster && ! empty($existingMaster['client_id']) && $existingMaster['client_id'] !== $clientId) {
            $isFresh = ($now - ($existingMaster['updated_at'] ?? 0)) < 20;

            if ($isFresh && ! $force) {
                $existingPriority = $existingMaster['priority'] ?? 10;
                if ($existingPriority >= $priority) {
                    $playerState = $this->musicService->getPlayerState();
                    $playbackState = Cache::get('soundstation_playback_state');

                    return response()->json([
                        'status' => 'rejected',
                        'message' => 'Master host sedang dipegang oleh perangkat lain.',
                        'current_master' => $existingMaster,
                        'playback_state' => $playbackState,
                        'now_playing' => $playerState['now_playing'],
                        'queue_count' => $playerState['queue_count'],
                        'queue' => $playerState['queue'],
                    ]);
                }
            }
        }

        $newMaster = [
            'client_id' => $clientId,
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'page_title' => $pageTitle,
            'priority' => $priority,
            'claimed_at' => $now,
            'updated_at' => $now,
        ];

        Cache::put('soundstation_master_host', $newMaster, now()->addSeconds(30));

        $playerState = $this->musicService->getPlayerState();
        $playbackState = Cache::get('soundstation_playback_state');

        return response()->json([
            'status' => 'granted',
            'master' => $newMaster,
            'playback_state' => $playbackState,
            'now_playing' => $playerState['now_playing'],
            'queue_count' => $playerState['queue_count'],
            'queue' => $playerState['queue'],
        ]);
    }

    /**
     * Heartbeat dari Master Host pemutar audio.
     * Mengirimkan detak playback (waktu & status) sekaligus memeriksa
     * apakah hak master telah diambil alih oleh perangkat lain (preempted).
     * Juga mengambil antrean perintah remote jika ada.
     */
    public function masterHeartbeat(Request $request): JsonResponse
    {
        $clientId = $request->string('client_id')->toString();
        if (empty($clientId)) {
            return response()->json(['status' => 'error', 'message' => 'client_id wajib diisi'], 422);
        }

        $now = now()->timestamp;
        $existingMaster = Cache::get('soundstation_master_host');

        // Jika master di cache bukan tab/client ini, maka tab ini telah diambil alih (preempted)
        if ($existingMaster && ! empty($existingMaster['client_id']) && $existingMaster['client_id'] !== $clientId) {
            return response()->json([
                'status' => 'preempted',
                'message' => 'Pemutar audio utama telah diambil alih oleh perangkat lain.',
                'current_master' => $existingMaster,
                'playback_state' => Cache::get('soundstation_playback_state'),
            ]);
        }

        // Perbarui masa aktif master host
        if (! $existingMaster || empty($existingMaster['client_id'])) {
            $existingMaster = [
                'client_id' => $clientId,
                'device_id' => $request->string('device_id')->toString(),
                'device_name' => $request->string('device_name', 'Perangkat Kasir')->toString(),
                'page_title' => $request->string('page_title', 'Sound Station')->toString(),
                'priority' => $request->integer('priority', 10),
                'claimed_at' => $now,
            ];
        }

        $existingMaster['updated_at'] = $now;
        Cache::put('soundstation_master_host', $existingMaster, now()->addSeconds(30));

        // Perbarui playback state jika dikirim
        if ($request->has('current_time') || $request->has('current_track')) {
            $currentTrack = $request->input('current_track');
            $state = [
                'current_time' => $request->float('current_time', 0),
                'duration' => $request->float('duration', 0),
                'is_playing' => $request->boolean('is_playing'),
                'current_track' => $currentTrack,
                'updated_at' => (int) round(microtime(true) * 1000),
                'client_id' => $clientId,
            ];
            Cache::put('soundstation_playback_state', $state, now()->addMinutes(2));

            if ($currentTrack && is_array($currentTrack)) {
                if (! isset($currentTrack['song_title']) && isset($currentTrack['title'])) {
                    $currentTrack['song_title'] = $currentTrack['title'];
                }
                if (empty($currentTrack['thumbnail_url']) && ! empty($currentTrack['youtube_id'])) {
                    $currentTrack['thumbnail_url'] = "https://img.youtube.com/vi/{$currentTrack['youtube_id']}/hqdefault.jpg";
                }
                Cache::put('soundstation_current_track', $currentTrack, now()->addHours(8));

                if (! empty($currentTrack['type']) && $currentTrack['type'] === 'default_track') {
                    MusicRequest::playing()->update(['status' => 'played']);
                }
            }
        }

        // Ambil dan bersihkan perintah remote (pending commands)
        $commands = Cache::pull('soundstation_pending_commands', []);

        return response()->json([
            'status' => 'ok',
            'commands' => is_array($commands) ? array_values($commands) : [],
        ]);
    }

    /**
     * Dapatkan status Master Host saat ini (untuk perangkat remote & display).
     */
    public function masterStatus(): JsonResponse
    {
        $master = Cache::get('soundstation_master_host');
        $playbackState = Cache::get('soundstation_playback_state');

        $isMasterAlive = false;
        if ($master && ! empty($master['updated_at'])) {
            $isMasterAlive = (now()->timestamp - $master['updated_at']) < 20;
        }

        $playerState = $this->musicService->getPlayerState();

        if (! $playbackState || empty($playbackState['current_track'])) {
            if (! empty($playerState['now_playing'])) {
                if (! $playbackState) {
                    $playbackState = [
                        'current_time' => 0,
                        'duration' => $playerState['now_playing']['duration_seconds'] ?? 0,
                        'is_playing' => true,
                    ];
                }
                $playbackState['current_track'] = $playerState['now_playing'];
            }
        }

        return response()->json([
            'has_master' => $isMasterAlive,
            'master' => $isMasterAlive ? $master : null,
            'playback_state' => $playbackState,
            'now_playing' => $playerState['now_playing'],
            'queue_count' => $playerState['queue_count'],
            'queue' => $playerState['queue'],
        ]);
    }

    /**
     * Kirim remote control command dari perangkat remote (misal HP / Tablet) ke Master Host.
     */
    public function sendRemoteCommand(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'command' => 'required|string|max:50',
            'data' => 'nullable|array',
        ]);

        $commands = Cache::get('soundstation_pending_commands', []);
        if (! is_array($commands)) {
            $commands = [];
        }

        $commands[] = [
            'id' => uniqid('cmd_', true),
            'command' => $validated['command'],
            'data' => $validated['data'] ?? [],
            'created_at' => now()->timestamp,
        ];

        // Batasi maksimal 20 perintah antrean dan simpan selama 30 detik
        $commands = array_slice($commands, -20);
        Cache::put('soundstation_pending_commands', $commands, now()->addSeconds(30));

        return response()->json([
            'status' => 'queued',
            'message' => 'Perintah remote berhasil dikirim ke pemutar utama.',
        ]);
    }

    /**
     * Lepaskan status Master Host (misal saat tab ditutup / kasir logout).
     */
    public function releaseMasterHost(Request $request): JsonResponse
    {
        $clientId = $request->string('client_id')->toString();
        $existingMaster = Cache::get('soundstation_master_host');

        if ($existingMaster && (! empty($clientId) && ($existingMaster['client_id'] ?? null) === $clientId)) {
            Cache::forget('soundstation_master_host');
        }

        return response()->json(['status' => 'released']);
    }

    /**
     * Endpoint Audio Text-To-Speech (Model "Mbak Google" Indonesia Wanita Resmi).
     * Menghasilkan audio MP3 jernih berlogat bahasa Indonesia asli.
     */
    public function tts(Request $request): Response
    {
        $text = trim((string) $request->query('text', ''));
        if ($text === '') {
            abort(400, 'Teks suara wajib diisi.');
        }

        $lang = trim((string) $request->query('lang', 'id'));
        if (! in_array($lang, ['id', 'jv', 'su', 'en', 'ja'], true)) {
            $lang = 'id';
        }

        // Batasi panjang maksimal 200 karakter
        $text = mb_substr($text, 0, 200);

        $cacheKey = 'tts_google_voice_'.$lang.'_'.md5($text);
        $audioData = Cache::get($cacheKey);

        if (! $audioData) {
            $url = 'https://translate.google.com/translate_tts?ie=UTF-8&tl='.$lang.'&client=tw-ob&q='.urlencode($text);
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Referer' => 'https://translate.google.com/',
                ])->timeout(7)->get($url);

                if ($response->successful() && strlen($response->body()) > 100) {
                    $audioData = $response->body();
                    Cache::put($cacheKey, $audioData, 86400 * 7);
                }
            } catch (\Throwable $e) {
                // Jangan simpan null di cache jika terjadi gangguan sementara
            }
        }

        if ($audioData) {
            return response($audioData, 200, [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Access-Control-Allow-Origin' => '*',
                'Accept-Ranges' => 'bytes',
            ]);
        }

        abort(502, 'Gagal mengambil audio Google TTS.');
    }

    /**
     * Konfigurasi bawaan untuk suara announcer.
     */
    public static function getDefaultAnnouncerSettings(): array
    {
        return [
            'voice_model' => 'mbak_google', // mbak_google, ms_gadis, ms_ardi, google_local, english_cafe, device_voice
            'device_voice_name' => '',
            'template_type' => 'concise', // concise, formal, airport, english, custom
            'custom_template' => 'Pesanan Kak {name}, siap diambil di kasir.',
            'chime_style' => 'ding_dong', // ding_dong, airport, bell, none
            'rate' => 1.0,
            'pitch' => 1.05,
            'duck_volume' => 12,
            'adzan_mode_enabled' => true,
            'adzan_target_volume' => 10,
            'adzan_duration_minutes' => 5, // Cukup selama adzan berlangsung (~5 menit)
        ];
    }

    /**
     * Halaman Pengaturan Suara & Aksen Announcer Kasir.
     */
    public function announcerSettings(Request $request): View
    {
        $settings = array_merge(
            self::getDefaultAnnouncerSettings(),
            Cache::get('soundstation_voice_settings', [])
        );

        $prayerSchedule = PrayerTimeService::getSchedule(null, (int) ($settings['adzan_duration_minutes'] ?? 5));

        return view('kasir.announcer-settings', compact('settings', 'prayerSchedule'));
    }

    /**
     * Simpan Pengaturan Suara & Aksen Announcer.
     */
    public function saveAnnouncerSettings(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'voice_model' => ['required', 'string', 'in:mbak_google,ms_gadis,ms_ardi,google_local,english_cafe,device_voice'],
            'device_voice_name' => ['nullable', 'string', 'max:150'],
            'template_type' => ['required', 'string', 'in:concise,formal,airport,english,custom'],
            'custom_template' => ['nullable', 'string', 'max:250'],
            'chime_style' => ['required', 'string', 'in:ding_dong,airport,bell,none'],
            'rate' => ['required', 'numeric', 'min:0.5', 'max:1.5'],
            'pitch' => ['required', 'numeric', 'min:0.5', 'max:1.5'],
            'duck_volume' => ['nullable', 'integer', 'min:0', 'max:50'],
            'adzan_mode_enabled' => ['nullable', 'boolean'],
            'adzan_target_volume' => ['nullable', 'integer', 'min:0', 'max:50'],
            'adzan_duration_minutes' => ['nullable', 'integer', 'min:2', 'max:15'],
        ]);

        $settings = array_merge(self::getDefaultAnnouncerSettings(), $validated);
        // Pastikan boolean adzan_mode_enabled tersimpan dengan benar jika tidak tercentang pada form biasa
        if (! $request->has('adzan_mode_enabled') && ! $request->expectsJson()) {
            $settings['adzan_mode_enabled'] = false;
        }

        Cache::forever('soundstation_voice_settings', $settings);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengaturan suara announcer & mode adzan berhasil disimpan.',
                'settings' => $settings,
            ]);
        }

        return redirect()->route('kasir.announcer.settings')->with('success', 'Pengaturan suara announcer & mode adzan berhasil disimpan.');
    }

    /**
     * Ambil pengaturan suara announcer aktif dalam format JSON.
     */
    public function announcerSettingsJson(): JsonResponse
    {
        $settings = array_merge(
            self::getDefaultAnnouncerSettings(),
            Cache::get('soundstation_voice_settings', [])
        );

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Endpoint API Jadwal Sholat Surabaya & Sidoarjo untuk widget dan TV.
     */
    public function prayerTimes(Request $request): JsonResponse
    {
        $voiceSettings = array_merge(
            self::getDefaultAnnouncerSettings(),
            Cache::get('soundstation_voice_settings', [])
        );
        $duration = (int) ($voiceSettings['adzan_duration_minutes'] ?? 5);

        return response()->json([
            'success' => true,
            'data' => PrayerTimeService::getSchedule(null, $duration),
            'settings' => [
                'enabled' => ! empty($voiceSettings['adzan_mode_enabled']),
                'target_volume' => (int) ($voiceSettings['adzan_target_volume'] ?? 10),
                'duration_minutes' => $duration,
            ],
        ]);
    }
}
