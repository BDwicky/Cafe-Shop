<?php

namespace App\Http\Controllers;

use App\Models\MusicRequest;
use App\Models\Order;
use App\Services\MusicService;
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

        return view('music.request', compact('code', 'initialValidation', 'playerState'));
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
            'song_title' => ['required', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            'youtube_id' => ['required', 'string', 'max:255'],
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

        try {
            $musicRequest = $this->musicService->submitRequest($val['order'] ?? null, [
                'song_title' => $validated['song_title'],
                'artist' => $validated['artist'] ?? null,
                'youtube_id' => $validated['youtube_id'],
                'thumbnail_url' => $validated['thumbnail_url'] ?? null,
                'duration_seconds' => $validated['duration_seconds'] ?? null,
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

        return response()->json([
            'now_playing' => $state['now_playing'],
            'queue' => $state['queue'],
            'queue_count' => $state['queue_count'],
            'ready_orders' => $readyOrders,
            'playback' => $playback,
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
