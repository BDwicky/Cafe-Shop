<?php

namespace App\Http\Controllers;

use App\Models\MusicRequest;
use App\Models\Order;
use App\Services\MusicService;
use App\Services\PrayerTimeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class MusicRequestController extends Controller
{
    public function __construct(
        protected MusicService $musicService
    ) {}

    /**
     * Halaman form request musik untuk pelanggan (Scan QR / Akses Web).
     */
    public function index(Request $request): View
    {
        $code = strtoupper(trim((string) $request->query('code', '')));
        $initialValidation = null;

        if ($code !== '') {
            $initialValidation = $this->musicService->validateMusicCode($code);
        }

        $playerState = $this->musicService->getPlayerState();
        $playback = Cache::get('soundstation_playback_state');

        if ($playback && (! isset($playback['duration']) || (float) $playback['duration'] <= 0)) {
            if (! empty($playerState['now_playing']['duration_seconds'])) {
                $playback['duration'] = (float) $playerState['now_playing']['duration_seconds'];
            }
        }

        return view('music.request', compact('code', 'initialValidation', 'playerState', 'playback'));
    }

    /**
     * Validasi kode struk via AJAX / Alpine.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $result = $this->musicService->validateMusicCode($request->code);

        return response()->json($result, $result['valid'] ? 200 : 422);
    }

    /**
     * Cari pratinjau lagu via URL YouTube atau judul lagu.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));
        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $youtubeId = $this->musicService->extractYouTubeId($query);

        // Jika input berupa URL atau ID YouTube langsung
        if ($youtubeId) {
            $details = $this->musicService->fetchYouTubeDetails($youtubeId);

            return response()->json([
                'results' => [
                    $details,
                ],
            ]);
        }

        // Jika bukan URL langsung, coba cari video relevan dari YouTube
        try {
            $searchRes = Http::timeout(3)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                ->get('https://www.youtube.com/results?search_query='.urlencode($query));

            if ($searchRes->successful()) {
                $html = $searchRes->body();
                if (preg_match('/"videoId":"([a-zA-Z0-9_-]{11})"/', $html, $mId)) {
                    $foundId = $mId[1];
                    $details = $this->musicService->fetchYouTubeDetails($foundId);

                    return response()->json([
                        'results' => [
                            $details,
                        ],
                    ]);
                }
            }
        } catch (Exception) {
            // Fallback jika pencarian jaringan bermasalah
        }

        // Fallback jika tidak ditemukan
        return response()->json([
            'results' => [
                [
                    'youtube_id' => $query,
                    'title' => $query,
                    'artist' => 'Pencarian YouTube',
                    'thumbnail_url' => null,
                    'duration_seconds' => null,
                    'duration_formatted' => '--:--',
                    'is_valid_duration' => true,
                    'duration_error' => null,
                    'is_search_query' => true,
                ],
            ],
        ]);
    }

    /**
     * Submit request lagu oleh pelanggan.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'song_title' => ['nullable', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            'youtube_id' => ['required', 'string', 'max:500'],
            'thumbnail_url' => ['nullable', 'url', 'max:500'],
            'duration_seconds' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:100'],
        ]);

        $val = $this->musicService->validateMusicCode($validated['code']);
        $isOwner = ! empty($val['is_owner']);

        if (! $val['valid'] || (! $isOwner && ! isset($val['order']))) {
            return response()->json([
                'message' => $val['message'] ?? 'Kode transaksi tidak valid atau sudah digunakan.',
            ], 422);
        }

        $rawYt = trim((string) $validated['youtube_id']);
        $youtubeId = $this->musicService->extractYouTubeId($rawYt) ?: $rawYt;

        $title = ! empty($validated['song_title']) ? trim((string) $validated['song_title']) : '';
        $artist = ! empty($validated['artist']) ? trim((string) $validated['artist']) : null;
        $thumbnailUrl = $validated['thumbnail_url'] ?? null;
        $durationSeconds = $validated['duration_seconds'] ?? null;

        // Jika judul tidak diinput secara manual, otomatis ambil judul & detail dari YouTube
        if ($title === '') {
            $details = $this->musicService->fetchYouTubeDetails($youtubeId);
            $title = $details['title'] ?? "Lagu YouTube ({$youtubeId})";
            if (empty($artist) && ! empty($details['artist']) && $details['artist'] !== 'YouTube') {
                $artist = $details['artist'];
            }
            if (empty($thumbnailUrl) && ! empty($details['thumbnail_url'])) {
                $thumbnailUrl = $details['thumbnail_url'];
            }
            if (empty($durationSeconds) && ! empty($details['duration_seconds'])) {
                $durationSeconds = $details['duration_seconds'];
            }
        }

        try {
            $musicRequest = $this->musicService->submitRequest($val['order'] ?? null, [
                'song_title' => $title,
                'artist' => $artist,
                'youtube_id' => $youtubeId,
                'thumbnail_url' => $thumbnailUrl,
                'duration_seconds' => $durationSeconds,
                'customer_name' => $validated['customer_name'] ?? null,
            ], $isOwner);

            // Hitung posisi antrean saat ini
            $queuePosition = MusicRequest::queued()->where('id', '<=', $musicRequest->id)->count();

            return response()->json([
                'message' => $isOwner
                    ? '👑 Lagu Owner berhasil masuk antrean musik kafe (Akses Unlimited)!'
                    : 'Lagu berhasil dimasukkan ke antrean musik kafe!',
                'request' => $musicRequest,
                'queue_position' => $queuePosition,
                'is_owner' => $isOwner,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * API Status antrean publik & lagu yang sedang diputar (polling).
     */
    public function status(): JsonResponse
    {
        $state = $this->musicService->getPlayerState();
        $readyOrders = Order::prepReady()->select('id', 'code', 'customer_name', 'order_type', 'prep_status')->get();
        $playback = Cache::get('soundstation_playback_state');

        if ($playback && (! isset($playback['duration']) || (float) $playback['duration'] <= 0)) {
            if (! empty($state['now_playing']['duration_seconds'])) {
                $playback['duration'] = (float) $state['now_playing']['duration_seconds'];
            }
        }

        $voiceSettings = Cache::get('soundstation_voice_settings', []);
        $adzanDuration = (int) ($voiceSettings['adzan_duration_minutes'] ?? 5);
        $prayerScheduleData = PrayerTimeService::getSchedule(null, $adzanDuration);
        $manualAdzan = Cache::get('soundstation_manual_adzan');

        if ($manualAdzan && ! empty($manualAdzan['active'])) {
            $prayerScheduleData['active_prayer'] = [
                'name' => $manualAdzan['prayer'] ?? 'Adzan',
                'time' => date('H:i', $manualAdzan['started_at'] ?? time()),
                'duration_minutes' => (int) ($manualAdzan['duration_minutes'] ?? $adzanDuration),
                'is_manual' => true,
            ];
        }

        return response()->json([
            'now_playing' => $state['now_playing'],
            'now_playing_type' => $state['now_playing_type'] ?? 'default',
            'queue' => $state['queue'],
            'queue_count' => $state['queue_count'],
            'request_queue_count' => $state['request_queue_count'] ?? $state['queue_count'],
            'default_tracks_count' => $state['default_tracks_count'] ?? 0,
            'total_queue_count' => $state['total_queue_count'] ?? count($state['queue']),
            'ready_orders' => $readyOrders,
            'playback' => $playback,
            'server_time' => (int) round(microtime(true) * 1000),
            'prayer_times' => $prayerScheduleData,
            'adzan_settings' => [
                'enabled' => ! empty($voiceSettings['adzan_mode_enabled'] ?? true),
                'target_volume' => (int) ($voiceSettings['adzan_target_volume'] ?? 10),
                'duration_minutes' => $adzanDuration,
            ],
        ]);
    }

    /**
     * Layar Display TV Publik (Now Playing & Antrean Selanjutnya).
     */
    public function display(): View
    {
        $playerState = $this->musicService->getPlayerState();
        $readyOrders = Order::prepReady()->select('id', 'code', 'customer_name', 'order_type', 'prep_status')->get();

        return view('music.display', compact('playerState', 'readyOrders'));
    }
}
