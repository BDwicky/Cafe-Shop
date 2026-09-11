<?php

namespace App\Services;

use App\Models\MusicDefaultTrack;
use App\Models\MusicRequest;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class MusicService
{
    /**
     * Kode Khusus Master Owner: Tanpa limit request lagu (Unlimited requests).
     */
    public const OWNER_CODE = '123123';

    /**
     * Cek apakah kode merupakan kode owner.
     */
    public function isOwnerCode(string $rawCode): bool
    {
        return trim($rawCode) === self::OWNER_CODE;
    }

    /**
     * Validasi apakah kode musik dari struk transaksi valid dan bisa digunakan.
     *
     * @return array{valid: bool, is_owner?: bool, message: string, order?: Order|null, request?: MusicRequest|null, already_used?: bool}
     */
    public function validateMusicCode(string $rawCode): array
    {
        $code = strtoupper(trim($rawCode));

        if (empty($code)) {
            return [
                'valid' => false,
                'is_owner' => false,
                'message' => 'Silakan masukkan kode musik dari struk transaksi Anda.',
            ];
        }

        // Cek Kode Master Owner (Bypass batas 1x transaksi, request tanpa batas)
        if ($this->isOwnerCode($rawCode)) {
            return [
                'valid' => true,
                'is_owner' => true,
                'order' => null,
                'message' => '👑 Kode Master Owner Aktif! Anda memiliki akses VIP request lagu tanpa batas (Unlimited).',
            ];
        }

        /** @var Order|null $order */
        $order = Order::with('musicRequest')
            ->where('music_code', $code)
            ->orWhere('code', $code)
            ->first();

        if (! $order) {
            return [
                'valid' => false,
                'is_owner' => false,
                'message' => 'Kode transaksi tidak ditemukan. Pastikan kode diketik persis seperti pada struk.',
            ];
        }

        if ($order->status !== 'paid') {
            return [
                'valid' => false,
                'is_owner' => false,
                'message' => 'Transaksi ini berstatus '.strtoupper($order->status).' dan tidak memenuhi syarat request musik.',
            ];
        }

        if (! $order->canRequestMusic()) {
            $existing = $order->musicRequest;
            $songName = $existing?->song_title ? " \"{$existing->song_title}\"" : '';

            return [
                'valid' => false,
                'is_owner' => false,
                'already_used' => true,
                'order' => $order,
                'request' => $existing,
                'message' => "Kode struk ini sudah digunakan untuk me-request lagu{$songName}. Setiap transaksi hanya berlaku untuk 1x request.",
            ];
        }

        return [
            'valid' => true,
            'is_owner' => false,
            'order' => $order,
            'message' => 'Kode struk valid! Silakan cari atau pilih lagu yang ingin diputar di kafe.',
        ];
    }

    /**
     * Batas maksimal durasi 1 lagu (7 menit = 420 detik).
     * Mencegah pemutaran kompilasi 1 jam, podcast, atau audio tidak masuk akal yang memonopoli pemutar kafe.
     */
    public const MAX_SONG_DURATION_SECONDS = 420;

    /**
     * Batas minimal durasi 1 lagu (45 detik) untuk mencegah troll prank atau audio klip ringtone.
     */
    public const MIN_SONG_DURATION_SECONDS = 45;

    /**
     * Format durasi detik ke tampilan waktu rapi (misal: 03:45 atau 01:15:20).
     */
    public function formatDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '--:--';
        }

        if ($seconds >= 3600) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $secs = $seconds % 60;

            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }

        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * Validasi apakah durasi lagu memenuhi aturan kafe.
     *
     * @return array{valid: bool, duration_seconds: int|null, duration_formatted: string, message: string|null}
     */
    public function validateTrackDuration(?int $durationSeconds): array
    {
        if ($durationSeconds === null || $durationSeconds <= 0) {
            return [
                'valid' => true,
                'duration_seconds' => null,
                'duration_formatted' => '--:--',
                'message' => null,
            ];
        }

        $formatted = $this->formatDuration($durationSeconds);

        if ($durationSeconds > self::MAX_SONG_DURATION_SECONDS) {
            return [
                'valid' => false,
                'duration_seconds' => $durationSeconds,
                'duration_formatted' => $formatted,
                'message' => "Durasi lagu terlalu panjang ({$formatted}). Batas maksimal lagu kafe adalah 7 menit demi menjaga kenyamanan bersama dan memberi giliran pengunjung lain.",
            ];
        }

        if ($durationSeconds < self::MIN_SONG_DURATION_SECONDS) {
            return [
                'valid' => false,
                'duration_seconds' => $durationSeconds,
                'duration_formatted' => $formatted,
                'message' => "Durasi lagu terlalu pendek ({$formatted}). Durasi minimal lagu adalah 45 detik.",
            ];
        }

        return [
            'valid' => true,
            'duration_seconds' => $durationSeconds,
            'duration_formatted' => $formatted,
            'message' => null,
        ];
    }

    /**
     * Ambil metadata lengkap video YouTube (judul, channel/artist, thumbnail, dan durasi) dengan cache.
     *
     * @return array{
     *     youtube_id: string,
     *     title: string,
     *     artist: string,
     *     thumbnail_url: string,
     *     duration_seconds: int|null,
     *     duration_formatted: string,
     *     is_valid_duration: bool,
     *     duration_error: string|null
     * }
     */
    public function fetchYouTubeDetails(string $youtubeId): array
    {
        $cacheKey = "youtube_details_{$youtubeId}";

        $raw = Cache::remember($cacheKey, now()->addHours(24), function () use ($youtubeId) {
            $title = "Lagu YouTube ({$youtubeId})";
            $artist = 'YouTube';
            $thumbnailUrl = "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg";
            $durationSeconds = null;

            // 1. Coba scraping halaman video YouTube untuk ekstraksi durasi & judul
            try {
                $response = Http::timeout(4)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'])
                    ->get("https://www.youtube.com/watch?v={$youtubeId}");

                if ($response->successful()) {
                    $html = $response->body();

                    // Ekstraksi durasi
                    if (preg_match('/"lengthSeconds":"([0-9]+)"/', $html, $mSeconds)) {
                        $durationSeconds = (int) $mSeconds[1];
                    } elseif (preg_match('/"approxDurationMs":"([0-9]+)"/', $html, $mMs)) {
                        $durationSeconds = (int) round(((int) $mMs[1]) / 1000);
                    }

                    // Ekstraksi judul
                    if (preg_match('/<meta property="og:title" content="([^"]+)"/', $html, $mTitle)) {
                        $title = html_entity_decode($mTitle[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    } elseif (preg_match('/<title>([^<]+)<\/title>/', $html, $mTitle)) {
                        $cleanTitle = preg_replace('/ - YouTube$/', '', $mTitle[1]);
                        $title = html_entity_decode($cleanTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }

                    // Ekstraksi artis / channel
                    if (preg_match('/<link itemprop="name" content="([^"]+)"/', $html, $mAuthor)) {
                        $artist = html_entity_decode($mAuthor[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    } elseif (preg_match('/"ownerChannelName":"([^"]+)"/', $html, $mChan)) {
                        $artist = html_entity_decode($mChan[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }
            } catch (Throwable) {
                // Lanjut ke fallback oEmbed jika scraping gagal
            }

            // 2. Fallback YouTube oEmbed jika judul masih default
            if ($title === "Lagu YouTube ({$youtubeId})") {
                try {
                    $oembedRes = Http::timeout(3)->get('https://www.youtube.com/oembed', [
                        'url' => "https://www.youtube.com/watch?v={$youtubeId}",
                        'format' => 'json',
                    ]);

                    if ($oembedRes->successful()) {
                        $oembed = $oembedRes->json();
                        if (! empty($oembed['title'])) {
                            $title = $oembed['title'];
                        }
                        if (! empty($oembed['author_name'])) {
                            $artist = $oembed['author_name'];
                        }
                        if (! empty($oembed['thumbnail_url'])) {
                            $thumbnailUrl = $oembed['thumbnail_url'];
                        }
                    }
                } catch (Throwable) {
                    // Abaikan kesalahan koneksi
                }
            }

            return [
                'youtube_id' => $youtubeId,
                'title' => $title,
                'artist' => $artist,
                'thumbnail_url' => $thumbnailUrl,
                'duration_seconds' => $durationSeconds,
            ];
        });

        $durationCheck = $this->validateTrackDuration($raw['duration_seconds']);

        return [
            'youtube_id' => $raw['youtube_id'],
            'title' => $raw['title'],
            'artist' => $raw['artist'],
            'thumbnail_url' => $raw['thumbnail_url'],
            'duration_seconds' => $raw['duration_seconds'],
            'duration_formatted' => $durationCheck['duration_formatted'],
            'is_valid_duration' => $durationCheck['valid'],
            'duration_error' => $durationCheck['message'],
        ];
    }

    /**
     * Ekstrak YouTube Video ID dari URL atau string.
     */
    public function extractYouTubeId(string $input): ?string
    {
        $trimmed = trim($input);

        // Jika langsung 11 karakter ID YouTube standar
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $trimmed)) {
            return $trimmed;
        }

        // Regex komprehensif URL YouTube standar, shortlink, embed, & music
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';

        if (preg_match($pattern, $trimmed, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Submit request lagu baru oleh customer atau owner kafe.
     *
     * @param  array{customer_name?: string|null, song_title: string, artist?: string|null, youtube_id: string, thumbnail_url?: string|null, duration_seconds?: int|null}  $data
     *
     * @throws InvalidArgumentException
     */
    public function submitRequest(?Order $order, array $data, bool $isOwner = false): MusicRequest
    {
        if (! $isOwner) {
            if (! $order || ! $order->canRequestMusic()) {
                throw new InvalidArgumentException('Struk transaksi ini sudah pernah digunakan untuk me-request lagu.');
            }
        }

        $youtubeId = $this->extractYouTubeId($data['youtube_id']);
        if (! $youtubeId) {
            throw new InvalidArgumentException('Tautan atau ID YouTube tidak valid.');
        }

        // Validasi aturan durasi lagu kafe (anti lagu 10 menit / 1 jam)
        $duration = isset($data['duration_seconds']) && is_numeric($data['duration_seconds'])
            ? (int) $data['duration_seconds']
            : null;

        if ($duration === null || $duration <= 0) {
            $details = $this->fetchYouTubeDetails($youtubeId);
            if (! empty($details['duration_seconds'])) {
                $duration = (int) $details['duration_seconds'];
            }
        }

        if ($duration !== null && $duration > 0) {
            $durationCheck = $this->validateTrackDuration($duration);
            if (! $durationCheck['valid']) {
                throw new InvalidArgumentException($durationCheck['message']);
            }
        }

        return DB::transaction(function () use ($order, $data, $youtubeId, $duration, $isOwner) {
            // Kunci order hanya jika bukan owner (1 transaksi = 1 lagu untuk pelanggan biasa)
            if ($order && ! $isOwner) {
                $order->update(['music_request_used_at' => now()]);
            }

            $thumb = ! empty($data['thumbnail_url'])
                ? $data['thumbnail_url']
                : "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg";

            $customerName = ! empty($data['customer_name'])
                ? trim($data['customer_name'])
                : ($order?->customer_name ?: ($isOwner ? '👑 Owner' : 'Pelanggan'));

            return MusicRequest::create([
                'order_id' => $order?->id,
                'customer_name' => $customerName,
                'song_title' => trim($data['song_title']),
                'artist' => ! empty($data['artist']) ? trim($data['artist']) : null,
                'youtube_id' => $youtubeId,
                'thumbnail_url' => $thumb,
                'duration_seconds' => $duration,
                'status' => 'queued',
            ]);
        });
    }

    /**
     * Ambil data status pemutar musik saat ini (Lagu aktif & antrean).
     *
     * @return array{
     *     now_playing: MusicRequest|MusicDefaultTrack|null,
     *     now_playing_type: 'request'|'default'|'none',
     *     queue: Collection<int, MusicRequest>,
     *     queue_count: int,
     *     default_tracks_count: int
     * }
     */
    public function getPlayerState(): array
    {
        /** @var MusicRequest|null $playingRequest */
        $playingRequest = MusicRequest::playing()->latest('played_at')->first();

        $playbackState = Cache::get('soundstation_playback_state');
        $cachedCurrentTrack = Cache::get('soundstation_current_track');

        $nowPlaying = null;

        // 1. Prioritaskan request pelanggan yang sedang berstatus 'playing'
        if ($playingRequest) {
            $nowPlaying = [
                'type' => 'customer_request',
                'id' => $playingRequest->id,
                'title' => $playingRequest->song_title,
                'song_title' => $playingRequest->song_title,
                'artist' => $playingRequest->artist,
                'youtube_id' => $playingRequest->youtube_id,
                'thumbnail_url' => $playingRequest->thumbnail_url ?: "https://img.youtube.com/vi/{$playingRequest->youtube_id}/hqdefault.jpg",
                'duration_seconds' => $playingRequest->duration_seconds,
                'customer_name' => $playingRequest->customer_name,
                'request_id' => $playingRequest->id,
            ];
            Cache::put('soundstation_current_track', $nowPlaying, now()->addHours(8));
        } else {
            // 2. Jika tidak ada request customer yang playing, gunakan track aktif dari playback state atau cache
            $currentTrack = null;
            if (! empty($playbackState['current_track']) && is_array($playbackState['current_track']) && ! empty($playbackState['current_track']['title'])) {
                $currentTrack = $playbackState['current_track'];
            } elseif (! empty($cachedCurrentTrack['title'])) {
                $currentTrack = $cachedCurrentTrack;
            }

            if ($currentTrack) {
                $nowPlaying = $currentTrack;
                if (! isset($nowPlaying['song_title']) && isset($nowPlaying['title'])) {
                    $nowPlaying['song_title'] = $nowPlaying['title'];
                }
                if (empty($nowPlaying['thumbnail_url']) && ! empty($nowPlaying['youtube_id'])) {
                    $nowPlaying['thumbnail_url'] = "https://img.youtube.com/vi/{$nowPlaying['youtube_id']}/hqdefault.jpg";
                }
            } else {
                $defaultTrack = MusicDefaultTrack::active()->orderBy('sort_order')->first();
                if ($defaultTrack) {
                    $nowPlaying = [
                        'type' => 'default_track',
                        'id' => $defaultTrack->id,
                        'title' => $defaultTrack->title,
                        'song_title' => $defaultTrack->title,
                        'artist' => $defaultTrack->artist,
                        'youtube_id' => $defaultTrack->youtube_id,
                        'thumbnail_url' => "https://img.youtube.com/vi/{$defaultTrack->youtube_id}/hqdefault.jpg",
                        'duration_seconds' => $defaultTrack->duration_seconds,
                        'customer_name' => null,
                        'request_id' => null,
                    ];
                    Cache::put('soundstation_current_track', $nowPlaying, now()->addHours(8));
                }
            }
        }

        /** @var Collection<int, MusicRequest> $queue */
        $queue = MusicRequest::queued()->get();

        $defaultTracksCount = MusicDefaultTrack::active()->count();

        return [
            'now_playing' => $nowPlaying,
            'now_playing_type' => ($nowPlaying && ($nowPlaying['type'] ?? '') === 'customer_request') ? 'request' : 'default',
            'queue' => $queue,
            'queue_count' => $queue->count(),
            'default_tracks_count' => $defaultTracksCount,
        ];
    }

    /**
     * Dapatkan lagu berikutnya untuk diputar:
     * - Menandai lagu sebelumnya sebagai 'played' (jika ada)
     * - Jika ada request pelanggan di antrean -> prioritaskan putar request pelanggan
     * - Jika antrean kosong -> putar lagu berikutnya dari playlist bawaan kafe
     *
     * @return array{
     *     type: 'customer_request'|'default_track'|'empty',
     *     id: int|null,
     *     title: string,
     *     artist: string|null,
     *     youtube_id: string|null,
     *     thumbnail_url: string|null,
     *     customer_name: string|null,
     *     request_id: int|null,
     *     has_queue: bool,
     *     resume_position?: int|null
     * }
     */
    public function transitionToNextTrack(
        ?int $finishRequestId = null,
        ?int $lastDefaultTrackId = null,
        bool $wasBlocked = false,
        ?string $blockedReason = null,
        ?int $resumeDefaultTrackId = null,
        ?int $resumePosition = null
    ): array {
        $result = DB::transaction(function () use (
            $finishRequestId,
            $lastDefaultTrackId,
            $wasBlocked,
            $blockedReason,
            $resumeDefaultTrackId,
            $resumePosition
        ) {
            // 1. Selesaikan request yang sedang berjalan sebelumnya
            if ($finishRequestId) {
                MusicRequest::where('id', $finishRequestId)
                    ->whereIn('status', ['playing', 'queued'])
                    ->update([
                        'status' => $wasBlocked ? 'skipped' : 'played',
                        'notes' => $wasBlocked
                            ? ($blockedReason ?: 'Otomatis dilewati: Video diblokir atau tidak dapat diputar di YouTube.')
                            : null,
                        'played_at' => now(),
                    ]);
            }

            // Pastikan tidak ada lagu lama yang menggantung di status 'playing'
            MusicRequest::where('status', 'playing')
                ->when($finishRequestId, fn ($q) => $q->where('id', '!=', $finishRequestId))
                ->update([
                    'status' => 'played',
                    'played_at' => now(),
                ]);

            // 2. Periksa apakah ada antrean request dari customer
            /** @var MusicRequest|null $nextCustomerRequest */
            $nextCustomerRequest = MusicRequest::queued()->first();

            if ($nextCustomerRequest) {
                $nextCustomerRequest->update([
                    'status' => 'playing',
                    'played_at' => now(),
                ]);

                return [
                    'type' => 'customer_request',
                    'id' => $nextCustomerRequest->id,
                    'title' => $nextCustomerRequest->song_title,
                    'song_title' => $nextCustomerRequest->song_title,
                    'artist' => $nextCustomerRequest->artist,
                    'youtube_id' => $nextCustomerRequest->youtube_id,
                    'thumbnail_url' => $nextCustomerRequest->thumbnail_url ?: "https://img.youtube.com/vi/{$nextCustomerRequest->youtube_id}/hqdefault.jpg",
                    'customer_name' => $nextCustomerRequest->customer_name,
                    'request_id' => $nextCustomerRequest->id,
                    'has_queue' => true,
                    'resume_position' => null,
                ];
            }

            // 3. Antrean customer kosong -> Ambil lagu berikutnya dari playlist bawaan pemilik/kasir
            $activeTracks = MusicDefaultTrack::active()
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($activeTracks->isEmpty()) {
                return [
                    'type' => 'empty',
                    'id' => null,
                    'title' => 'Belum ada lagu bawaan',
                    'song_title' => 'Belum ada lagu bawaan',
                    'artist' => null,
                    'youtube_id' => null,
                    'thumbnail_url' => null,
                    'customer_name' => null,
                    'request_id' => null,
                    'has_queue' => false,
                    'resume_position' => null,
                ];
            }

            // Prioritaskan melanjutkan (resume) lagu panjang kasir yang sempat ter-pause oleh request pelanggan
            $nextDefault = null;
            $resumedPos = null;

            if ($resumeDefaultTrackId) {
                $foundTrack = $activeTracks->firstWhere('id', $resumeDefaultTrackId);
                if ($foundTrack) {
                    $nextDefault = $foundTrack;
                    $resumedPos = $resumePosition !== null && $resumePosition >= 0 ? $resumePosition : 0;
                }
            }

            // Jika bukan resume, pilih lagu bawaan berikutnya secara circular (Track 1 -> 2 -> ... -> N -> 1)
            if (! $nextDefault && $lastDefaultTrackId) {
                $currentIndex = $activeTracks->search(fn ($track) => (int) $track->id === (int) $lastDefaultTrackId);
                if ($currentIndex !== false) {
                    $nextIndex = ($currentIndex + 1) % $activeTracks->count();
                    $nextDefault = $activeTracks->get($nextIndex);
                }
            }

            if (! $nextDefault) {
                $nextDefault = $activeTracks->first();
            }

            return [
                'type' => 'default_track',
                'id' => $nextDefault->id,
                'title' => $nextDefault->title,
                'song_title' => $nextDefault->title,
                'artist' => $nextDefault->artist,
                'youtube_id' => $nextDefault->youtube_id,
                'thumbnail_url' => "https://img.youtube.com/vi/{$nextDefault->youtube_id}/hqdefault.jpg",
                'customer_name' => null,
                'request_id' => null,
                'has_queue' => false,
                'resume_position' => $resumedPos,
            ];
        });

        if ($result && ! empty($result['title'])) {
            Cache::put('soundstation_current_track', $result, now()->addHours(8));

            $existingPlayback = Cache::get('soundstation_playback_state', []);
            $existingPlayback['current_track'] = $result;
            $existingPlayback['current_time'] = $result['resume_position'] ?? 0;
            $existingPlayback['duration'] = $result['duration_seconds'] ?? ($existingPlayback['duration'] ?? 0);
            $existingPlayback['is_playing'] = true;
            $existingPlayback['updated_at'] = (int) round(microtime(true) * 1000);
            Cache::put('soundstation_playback_state', $existingPlayback, now()->addMinutes(2));
        }

        return $result;
    }

    /**
     * Lewati (skip) lagu customer yang sedang diputar atau diantrekan.
     */
    public function skipRequest(MusicRequest $musicRequest): void
    {
        $musicRequest->update([
            'status' => 'skipped',
            'played_at' => now(),
        ]);
    }

    /**
     * Tolak request pelanggan (misal: konten tidak sopan/tidak cocok untuk kafe).
     */
    public function rejectRequest(MusicRequest $musicRequest, ?string $reason = null): void
    {
        $musicRequest->update([
            'status' => 'rejected',
            'notes' => $reason ?? 'Ditolak oleh kasir kafe.',
            'played_at' => now(),
        ]);
    }
}
