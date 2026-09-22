<?php

namespace Tests\Feature;

use App\Http\Controllers\KasirMusicController;
use App\Models\MusicDefaultTrack;
use App\Models\MusicRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\MusicService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MusicRequestTest extends TestCase
{
    public function test_order_automatically_generates_unique_music_code_upon_creation(): void
    {
        $order = Order::factory()->create();

        $this->assertNotNull($order->music_code);
        $this->assertStringStartsWith('MK-', $order->music_code);
        $this->assertTrue($order->canRequestMusic());
    }

    public function test_receipt_contains_music_code_and_music_request_qr(): void
    {
        $order = Order::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->get("/kasir/orders/{$order->id}/receipt");

        $response->assertOk()
            ->assertSee($order->music_code)
            ->assertSee('REQUEST MUSIK KAFE')
            ->assertSee('data:image/png;base64');
    }

    public function test_customer_can_validate_fresh_music_code(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        $response = $this->postJson(route('music.validate_code'), [
            'code' => $order->music_code,
        ]);

        $response->assertOk()
            ->assertJsonPath('valid', true);
    }

    public function test_customer_cannot_use_music_code_from_voided_order(): void
    {
        $order = Order::factory()->create(['status' => 'voided']);

        $response = $this->postJson(route('music.validate_code'), [
            'code' => $order->music_code,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('valid', false);
    }

    public function test_customer_can_submit_valid_music_request_and_code_is_locked(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        $response = $this->postJson(route('music.store'), [
            'code' => $order->music_code,
            'song_title' => 'Rayuan Perempuan Gila',
            'artist' => 'Nadin Amizah',
            'youtube_id' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'customer_name' => 'Meja 5 Budi',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('request.song_title', 'Rayuan Perempuan Gila')
            ->assertJsonPath('request.youtube_id', 'dQw4w9WgXcQ')
            ->assertJsonPath('request.status', 'queued');

        $this->assertDatabaseHas('music_requests', [
            'order_id' => $order->id,
            'song_title' => 'Rayuan Perempuan Gila',
            'youtube_id' => 'dQw4w9WgXcQ',
            'status' => 'queued',
        ]);

        $this->assertNotNull($order->fresh()->music_request_used_at);
        $this->assertFalse($order->fresh()->canRequestMusic());

        // Coba request kedua kali dengan struk yang sama -> HARUS DITOLAK
        $secondAttempt = $this->postJson(route('music.store'), [
            'code' => $order->music_code,
            'song_title' => 'Lagu Kedua',
            'youtube_id' => 'dQw4w9WgXcQ',
        ]);

        $secondAttempt->assertStatus(422);
    }

    public function test_player_transition_prioritizes_customer_request(): void
    {
        $user = User::factory()->create();

        // 1. Setup default track
        $defaultTrack = MusicDefaultTrack::factory()->create([
            'title' => 'Default Jazz',
            'youtube_id' => 'DEFAULT_01',
            'is_active' => true,
        ]);

        // 2. Customer membuat request lagu
        $order = Order::factory()->create(['status' => 'paid']);
        $request = MusicRequest::factory()->create([
            'order_id' => $order->id,
            'song_title' => 'Customer Request Pop',
            'youtube_id' => 'CUSTOMER_01',
            'status' => 'queued',
        ]);

        // 3. Kasir sound station meminta next track setelah lagu selesai
        $response = $this->actingAs($user)
            ->postJson(route('kasir.music.next'), [
                'finish_request_id' => null,
            ]);

        $response->assertOk()
            ->assertJsonPath('type', 'customer_request')
            ->assertJsonPath('youtube_id', 'CUSTOMER_01')
            ->assertJsonPath('title', 'Customer Request Pop');

        $this->assertSame('playing', $request->fresh()->status);

        // 4. Setelah customer request selesai, transisi berikutnya (jika antrean habis) harus kembali ke default track
        $response2 = $this->actingAs($user)
            ->postJson(route('kasir.music.next'), [
                'finish_request_id' => $request->id,
            ]);

        $response2->assertOk()
            ->assertJsonPath('type', 'default_track')
            ->assertJsonPath('youtube_id', 'DEFAULT_01');

        $this->assertSame('played', $request->fresh()->status);
    }

    public function test_kasir_can_skip_or_reject_request(): void
    {
        $user = User::factory()->create();
        $musicRequest = MusicRequest::factory()->create(['status' => 'queued']);

        // Kasir menolak lagu tidak pantas
        $this->actingAs($user)
            ->postJson(route('kasir.music.reject', $musicRequest), [
                'reason' => 'Mengandung lirik tidak pantas',
            ])
            ->assertOk();

        $this->assertSame('rejected', $musicRequest->fresh()->status);
        $this->assertSame('Mengandung lirik tidak pantas', $musicRequest->fresh()->notes);
    }

    public function test_kasir_can_add_and_toggle_default_tracks(): void
    {
        $user = User::factory()->create();

        Cache::put('youtube_details_VALID000001', [
            'youtube_id' => 'VALID000001',
            'title' => 'Chill Coffee Vibes',
            'artist' => 'Lofi Producer',
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'duration_seconds' => 180,
            'duration_formatted' => '03:00',
            'is_valid_duration' => true,
            'duration_error' => null,
        ], now()->addHour());

        $this->actingAs($user)
            ->post(route('kasir.music.default.store'), [
                'title' => 'Chill Coffee Vibes',
                'artist' => 'Lofi Producer',
                'youtube_url' => 'https://youtu.be/VALID000001',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('music_default_tracks', [
            'title' => 'Chill Coffee Vibes',
            'youtube_id' => 'VALID000001',
            'duration_seconds' => 180,
        ]);

        $track = MusicDefaultTrack::where('youtube_id', 'VALID000001')->first();

        // Toggle status
        $this->actingAs($user)
            ->patch(route('kasir.music.default.toggle', $track))
            ->assertRedirect();

        $this->assertFalse((bool) $track->fresh()->is_active);

        // Update default track data
        $this->actingAs($user)
            ->put(route('kasir.music.default.update', $track), [
                'title' => 'Updated Chill Coffee',
                'artist' => 'Lofi Barista',
                'youtube_url' => 'https://youtu.be/VALID000002',
                'sort_order' => 5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('music_default_tracks', [
            'id' => $track->id,
            'title' => 'Updated Chill Coffee',
            'artist' => 'Lofi Barista',
            'youtube_id' => 'VALID000002',
            'sort_order' => 5,
        ]);
    }

    public function test_kasir_can_access_music_mini_popup_player(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('kasir.music.mini'))
            ->assertOk()
            ->assertSee('Sound Station Mini');
    }

    public function test_customer_cannot_request_song_exceeding_max_duration_limit_7_minutes(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        // Request lagu 10 menit (600 detik) atau 1 jam (3600 detik) -> HARUS DITOLAK
        $response = $this->postJson(route('music.store'), [
            'code' => $order->music_code,
            'song_title' => '1 Hour Lofi Study Mix',
            'youtube_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 3600,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'terlalu panjang') && str_contains($msg, '7 menit'));

        $this->assertDatabaseMissing('music_requests', [
            'order_id' => $order->id,
            'song_title' => '1 Hour Lofi Study Mix',
        ]);

        // Struk belanja tidak boleh terkunci jika request gagal karena durasi tidak valid
        $this->assertTrue($order->fresh()->canRequestMusic());
    }

    public function test_customer_cannot_request_song_below_min_duration_limit_45_seconds(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        // Request suara troll / prank berdurasi 15 detik -> HARUS DITOLAK
        $response = $this->postJson(route('music.store'), [
            'code' => $order->music_code,
            'song_title' => 'Short Meme Sound',
            'youtube_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 15,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'terlalu pendek') && str_contains($msg, '45 detik'));

        $this->assertDatabaseMissing('music_requests', [
            'order_id' => $order->id,
        ]);
    }

    public function test_customer_can_request_song_with_valid_duration_and_stores_seconds(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        // Lagu standar 3 menit 33 detik (213 detik) -> DITERIMA
        $response = $this->postJson(route('music.store'), [
            'code' => $order->music_code,
            'song_title' => 'Until I Found You',
            'artist' => 'Stephen Sanchez',
            'youtube_id' => 'GxldQ9GyXfk',
            'duration_seconds' => 177,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('request.duration_seconds', 177);

        $this->assertDatabaseHas('music_requests', [
            'order_id' => $order->id,
            'song_title' => 'Until I Found You',
            'duration_seconds' => 177,
        ]);
    }

    public function test_kasir_can_add_default_track_exceeding_7_minutes_due_to_cashier_exception(): void
    {
        $user = User::factory()->create();

        // Siapkan mock cache untuk metadata video 10 jam (11 karakter ID YouTube)
        Cache::put('youtube_details_LONG0000001', [
            'youtube_id' => 'LONG0000001',
            'title' => '10 Hours Ambient Coffee Shop',
            'artist' => 'Lofi Station',
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'duration_seconds' => 36000,
            'duration_formatted' => '10:00:00',
            'is_valid_duration' => false,
            'duration_error' => 'Durasi lagu terlalu panjang (10:00:00). Batas maksimal lagu kafe adalah 7 menit.',
        ], now()->addHour());

        // Kasir memiliki pengecualian durasi (bebas memutar lagu panjang, mix 1 jam, dll)
        $response = $this->actingAs($user)
            ->from(route('kasir.music.index'))
            ->post(route('kasir.music.default.store'), [
                'title' => '10 Hours Ambient Coffee Shop',
                'artist' => 'Lofi Station',
                'youtube_url' => 'https://youtu.be/LONG0000001',
            ]);

        $response->assertRedirect(route('kasir.music.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('music_default_tracks', [
            'youtube_id' => 'LONG0000001',
            'title' => '10 Hours Ambient Coffee Shop',
            'duration_seconds' => 36000,
        ]);
    }

    public function test_kasir_can_add_default_track_without_inputting_title_or_artist(): void
    {
        $user = User::factory()->create();

        Cache::put('youtube_details_AUTOTITLE01', [
            'youtube_id' => 'AUTOTITLE01',
            'title' => 'Tulus - Manusia Kuat (Official)',
            'artist' => 'Tulus',
            'thumbnail_url' => 'https://example.com/tulus.jpg',
            'duration_seconds' => 205,
            'duration_formatted' => '03:25',
            'is_valid_duration' => true,
            'duration_error' => null,
        ], now()->addHour());

        // Kasir hanya menempelkan tautan YouTube TANPA mengisi judul atau artis
        $response = $this->actingAs($user)
            ->post(route('kasir.music.default.store'), [
                'youtube_url' => 'https://youtu.be/AUTOTITLE01',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('music_default_tracks', [
            'youtube_id' => 'AUTOTITLE01',
            'title' => 'Tulus - Manusia Kuat (Official)',
            'artist' => 'Tulus',
            'duration_seconds' => 205,
        ]);
    }

    public function test_kasir_can_inspect_link_endpoint(): void
    {
        $user = User::factory()->create();

        Cache::put('youtube_details_INSPECT0001', [
            'youtube_id' => 'INSPECT0001',
            'title' => 'Coldplay - Yellow',
            'artist' => 'Coldplay',
            'thumbnail_url' => 'https://example.com/coldplay.jpg',
            'duration_seconds' => 269,
            'duration_formatted' => '04:29',
            'is_valid_duration' => true,
            'duration_error' => null,
        ], now()->addHour());

        $response = $this->actingAs($user)
            ->getJson(route('kasir.music.inspect', ['url' => 'https://youtu.be/INSPECT0001']));

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('title', 'Coldplay - Yellow')
            ->assertJsonPath('artist', 'Coldplay')
            ->assertJsonPath('duration_formatted', '04:29')
            ->assertJsonPath('is_valid_duration', true);
    }

    public function test_kasir_can_batch_import_default_tracks(): void
    {
        $user = User::factory()->create();

        Cache::put('youtube_details_BATCH000001', [
            'youtube_id' => 'BATCH000001',
            'title' => 'Lagu Batch 1',
            'artist' => 'Artis 1',
            'thumbnail_url' => 'https://example.com/b1.jpg',
            'duration_seconds' => 180,
            'duration_formatted' => '03:00',
            'is_valid_duration' => true,
            'duration_error' => null,
        ], now()->addHour());

        Cache::put('youtube_details_BATCH000002', [
            'youtube_id' => 'BATCH000002',
            'title' => 'Lagu Batch 2',
            'artist' => 'Artis 2',
            'thumbnail_url' => 'https://example.com/b2.jpg',
            'duration_seconds' => 210,
            'duration_formatted' => '03:30',
            'is_valid_duration' => true,
            'duration_error' => null,
        ], now()->addHour());

        $response = $this->actingAs($user)
            ->post(route('kasir.music.default.store_batch'), [
                'youtube_urls' => "https://youtu.be/BATCH000001\nhttps://youtu.be/BATCH000002",
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('music_default_tracks', ['youtube_id' => 'BATCH000001']);
        $this->assertDatabaseHas('music_default_tracks', ['youtube_id' => 'BATCH000002']);
    }

    public function test_player_transition_loops_circularly_through_default_playlist(): void
    {
        $user = User::factory()->create();

        MusicDefaultTrack::query()->delete();
        $t1 = MusicDefaultTrack::factory()->create(['title' => 'Track 1', 'sort_order' => 1, 'is_active' => true]);
        $t2 = MusicDefaultTrack::factory()->create(['title' => 'Track 2', 'sort_order' => 2, 'is_active' => true]);
        $t3 = MusicDefaultTrack::factory()->create(['title' => 'Track 3', 'sort_order' => 3, 'is_active' => true]);

        // Selesai Track 1 -> Harusnya Track 2
        $res1 = $this->actingAs($user)->postJson(route('kasir.music.next'), [
            'last_default_track_id' => $t1->id,
        ]);
        $res1->assertOk()->assertJsonPath('id', $t2->id);

        // Selesai Track 2 -> Harusnya Track 3
        $res2 = $this->actingAs($user)->postJson(route('kasir.music.next'), [
            'last_default_track_id' => $t2->id,
        ]);
        $res2->assertOk()->assertJsonPath('id', $t3->id);

        // Selesai Track 3 -> Harusnya berputar kembali ke Track 1 secara circular!
        $res3 = $this->actingAs($user)->postJson(route('kasir.music.next'), [
            'last_default_track_id' => $t3->id,
        ]);
        $res3->assertOk()->assertJsonPath('id', $t1->id);
    }

    public function test_owner_code_123123_validates_with_unlimited_access(): void
    {
        $response = $this->postJson(route('music.validate_code'), [
            'code' => '123123',
        ]);

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('is_owner', true)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Owner'));
    }

    public function test_owner_code_allows_unlimited_consecutive_requests(): void
    {
        // Request lagu 1
        $res1 = $this->postJson(route('music.store'), [
            'code' => '123123',
            'song_title' => 'Owner Song 1',
            'artist' => 'Artist 1',
            'youtube_id' => 'OWNERTRACK1',
            'duration_seconds' => 180,
            'customer_name' => 'Owner Cafe',
        ]);
        $res1->assertCreated()
            ->assertJsonPath('is_owner', true)
            ->assertJsonPath('request.song_title', 'Owner Song 1');

        // Request lagu 2 tanpa batas (tidak diblokir / tidak ada limit 1 transaksi)
        $res2 = $this->postJson(route('music.store'), [
            'code' => '123123',
            'song_title' => 'Owner Song 2',
            'artist' => 'Artist 2',
            'youtube_id' => 'OWNERTRACK2',
            'duration_seconds' => 200,
            'customer_name' => 'Owner Cafe',
        ]);
        $res2->assertCreated()
            ->assertJsonPath('is_owner', true)
            ->assertJsonPath('request.song_title', 'Owner Song 2');

        // Request lagu 3
        $res3 = $this->postJson(route('music.store'), [
            'code' => '123123',
            'song_title' => 'Owner Song 3',
            'artist' => 'Artist 3',
            'youtube_id' => 'OWNERTRACK3',
            'duration_seconds' => 220,
        ]);
        $res3->assertCreated()
            ->assertJsonPath('is_owner', true)
            ->assertJsonPath('request.song_title', 'Owner Song 3');

        $this->assertDatabaseHas('music_requests', ['youtube_id' => 'OWNERTRACK1', 'order_id' => null]);
        $this->assertDatabaseHas('music_requests', ['youtube_id' => 'OWNERTRACK2', 'order_id' => null]);
        $this->assertDatabaseHas('music_requests', ['youtube_id' => 'OWNERTRACK3', 'order_id' => null]);
        $this->assertSame(3, MusicRequest::whereIn('youtube_id', ['OWNERTRACK1', 'OWNERTRACK2', 'OWNERTRACK3'])->count());
    }

    public function test_music_request_page_renders_owner_mode_when_code_123123_provided(): void
    {
        $response = $this->get(route('music.request', ['code' => '123123']));

        $response->assertOk()
            ->assertSee('123123')
            ->assertSee('AKSES OWNER: UNLIMITED REQUEST');
    }

    public function test_test_code_validates_as_testing_mode_and_not_owner(): void
    {
        // Pengujian huruf kecil "test"
        $responseLower = $this->postJson(route('music.validate_code'), [
            'code' => 'test',
        ]);

        $responseLower->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('is_owner', false)
            ->assertJsonPath('is_test', true)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Testing'));

        // Pengujian huruf besar "TEST"
        $responseUpper = $this->postJson(route('music.validate_code'), [
            'code' => 'TEST',
        ]);

        $responseUpper->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('is_owner', false)
            ->assertJsonPath('is_test', true);
    }

    public function test_test_code_allows_unlimited_consecutive_requests_with_customer_rules(): void
    {
        // Request 1 dengan kode "test"
        $res1 = $this->postJson(route('music.store'), [
            'code' => 'test',
            'song_title' => 'Testing Song 1',
            'artist' => 'Tester Artist',
            'youtube_id' => 'TESTTRACK01',
            'duration_seconds' => 180,
            'customer_name' => 'Tester Budi',
        ]);

        $res1->assertCreated()
            ->assertJsonPath('is_owner', false)
            ->assertJsonPath('is_test', true)
            ->assertJsonPath('request.song_title', 'Testing Song 1')
            ->assertJsonPath('request.customer_name', 'Tester Budi');

        // Request 2 berturut-turut tanpa batas / tidak terkunci
        $res2 = $this->postJson(route('music.store'), [
            'code' => 'test',
            'song_title' => 'Testing Song 2',
            'artist' => 'Tester Artist',
            'youtube_id' => 'TESTTRACK02',
            'duration_seconds' => 200,
        ]);

        $res2->assertCreated()
            ->assertJsonPath('is_owner', false)
            ->assertJsonPath('is_test', true)
            ->assertJsonPath('request.song_title', 'Testing Song 2')
            ->assertJsonPath('request.customer_name', 'Pelanggan (Testing)');

        // Request 3
        $res3 = $this->postJson(route('music.store'), [
            'code' => 'test',
            'song_title' => 'Testing Song 3',
            'youtube_id' => 'TESTTRACK03',
            'duration_seconds' => 210,
        ]);

        $res3->assertCreated()
            ->assertJsonPath('is_owner', false)
            ->assertJsonPath('is_test', true);

        $this->assertSame(3, MusicRequest::whereIn('youtube_id', ['TESTTRACK01', 'TESTTRACK02', 'TESTTRACK03'])->count());
    }

    public function test_test_code_enforces_duration_limits_like_normal_codes(): void
    {
        // Uji lagu > 7 menit (3600 detik) harus tetap ditolak sesuai aturan kafe
        $resTooLong = $this->postJson(route('music.store'), [
            'code' => 'test',
            'song_title' => '1 Hour Chill Coffee',
            'youtube_id' => 'TESTLONGSNG',
            'duration_seconds' => 3600,
        ]);

        $resTooLong->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'terlalu panjang'));

        // Uji lagu < 45 detik harus tetap ditolak
        $resTooShort = $this->postJson(route('music.store'), [
            'code' => 'test',
            'song_title' => 'Short Sound Meme',
            'youtube_id' => 'TESTSHORTSG',
            'duration_seconds' => 15,
        ]);

        $resTooShort->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'terlalu pendek'));
    }

    public function test_music_request_page_renders_test_mode_when_code_test_provided(): void
    {
        $response = $this->get(route('music.request', ['code' => 'test']));

        $response->assertOk()
            ->assertSee('TEST')
            ->assertSee('TESTING: MULTI-REQUEST')
            ->assertSee('isOwner: false', false)
            ->assertSee('isTest: true', false);
    }

    public function test_music_request_page_displays_priority_and_instant_play_notices(): void
    {
        $response = $this->get(route('music.request'));

        $response->assertOk()
            ->assertSee('Prioritas Utama & Putar Langsung', false)
            ->assertSee('di-pause', false)
            ->assertSee('Antrean Prioritas', false);
    }

    public function test_next_track_marks_request_as_skipped_with_notes_when_was_blocked(): void
    {
        $user = User::factory()->create();

        // 1. Buat request yang sedang berstatus 'playing'
        $blockedReq = MusicRequest::factory()->create([
            'song_title' => 'Blocked Song Copyright',
            'youtube_id' => 'BLOCKED0001',
            'status' => 'playing',
        ]);

        // Buat request berikutnya di antrean
        $nextQueue = MusicRequest::factory()->create([
            'song_title' => 'Healthy Song in Queue',
            'youtube_id' => 'HEALTHY0001',
            'status' => 'queued',
        ]);

        // Simulasikan YouTube player mendeteksi error 101/150 dan mengirim was_blocked: true ke nextTrack
        $response = $this->actingAs($user)->postJson(route('kasir.music.next'), [
            'finish_request_id' => $blockedReq->id,
            'was_blocked' => true,
            'blocked_reason' => 'Video diblokir oleh pemilik hak cipta / melarang pemutaran di luar situs YouTube.',
        ]);

        $response->assertOk()
            ->assertJsonPath('id', $nextQueue->id)
            ->assertJsonPath('type', 'customer_request');

        // Verifikasi lagu yang diblokir otomatis di-skip dan alasan tercatat rapi
        $blockedReq->refresh();
        $this->assertSame('skipped', $blockedReq->status);
        $this->assertSame('Video diblokir oleh pemilik hak cipta / melarang pemutaran di luar situs YouTube.', $blockedReq->notes);
        $this->assertNotNull($blockedReq->played_at);

        // Verifikasi lagu berikutnya di antrean otomatis mulai diputar
        $nextQueue->refresh();
        $this->assertSame('playing', $nextQueue->status);
    }

    public function test_next_track_resumes_paused_cashier_default_track_when_queue_finishes(): void
    {
        $user = User::factory()->create();

        // Siapkan lagu panjang kasir
        $longTrack = MusicDefaultTrack::factory()->create([
            'title' => '1 Hour Lofi Cafe Mix',
            'youtube_id' => 'RESUME00001',
            'duration_seconds' => 3600,
            'is_active' => true,
        ]);

        $request1 = MusicRequest::factory()->create([
            'song_title' => 'Customer Song',
            'status' => 'playing',
        ]);

        // Antrean customer sekarang kosong (request1 adalah lagu terakhir)
        MusicRequest::where('id', $request1->id)->update(['status' => 'playing']);

        // Request nextTrack dengan parameter resume untuk melanjutkan lagu kasir yang ter-pause di detik 425
        $response = $this->actingAs($user)->postJson(route('kasir.music.next'), [
            'finish_request_id' => $request1->id,
            'resume_default_track_id' => $longTrack->id,
            'resume_position' => 425,
        ]);

        $response->assertOk()
            ->assertJsonPath('type', 'default_track')
            ->assertJsonPath('id', $longTrack->id)
            ->assertJsonPath('youtube_id', 'RESUME00001')
            ->assertJsonPath('resume_position', 425)
            ->assertJsonPath('has_queue', false);
    }

    public function test_music_display_renders_successfully_with_ready_orders(): void
    {
        $readyOrder = Order::factory()->create([
            'prep_status' => 'ready',
            'customer_name' => 'Kak Sarah',
        ]);

        $response = $this->get(route('music.display'));

        $response->assertOk()
            ->assertViewIs('music.display')
            ->assertViewHas('playerState')
            ->assertViewHas('readyOrders')
            ->assertSee('Kak Sarah')
            ->assertSee($readyOrder->code);
    }

    public function test_music_status_includes_ready_orders_in_payload(): void
    {
        $readyOrder = Order::factory()->create([
            'prep_status' => 'ready',
            'customer_name' => 'Kak Dimas',
        ]);

        $response = $this->getJson(route('music.status'));

        $response->assertOk()
            ->assertJsonStructure([
                'now_playing',
                'queue',
                'queue_count',
                'ready_orders',
            ])
            ->assertJsonFragment([
                'id' => $readyOrder->id,
                'customer_name' => 'Kak Dimas',
                'prep_status' => 'ready',
            ]);
    }

    public function test_kasir_can_claim_master_host_and_retrieve_playback_state(): void
    {
        $user = User::factory()->create();

        // Siapkan playback state yang tersimpan sebelumnya
        Cache::put('soundstation_playback_state', [
            'current_time' => 85.5,
            'duration' => 240,
            'is_playing' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('kasir.music.master.claim'), [
            'client_id' => 'tab_device_a_123',
            'device_id' => 'device_tablet_pos',
            'device_name' => 'Tablet Kasir',
            'page_title' => 'Sound Station',
            'priority' => 100,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'granted')
            ->assertJsonPath('master.client_id', 'tab_device_a_123')
            ->assertJsonPath('master.device_name', 'Tablet Kasir')
            ->assertJsonPath('playback_state.current_time', 85.5);
    }

    public function test_kasir_claim_rejected_if_another_host_active_with_higher_priority_unless_forced(): void
    {
        $user = User::factory()->create();

        // Host aktif di Device A dengan priority 100 (dedicated page)
        Cache::put('soundstation_master_host', [
            'client_id' => 'tab_device_a_123',
            'device_name' => 'PC Kasir Utama',
            'page_title' => 'Sound Station',
            'priority' => 100,
            'claimed_at' => now()->timestamp,
            'updated_at' => now()->timestamp,
        ], 30);

        // Device B coba klaim tanpa force dengan priority lebih rendah (10)
        $response = $this->actingAs($user)->postJson(route('kasir.music.master.claim'), [
            'client_id' => 'tab_device_b_456',
            'device_name' => 'Tablet POS',
            'priority' => 10,
            'force' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('current_master.client_id', 'tab_device_a_123');

        // Device B klaim dengan force = true (ambil alih)
        $responseForce = $this->actingAs($user)->postJson(route('kasir.music.master.claim'), [
            'client_id' => 'tab_device_b_456',
            'device_name' => 'Tablet POS',
            'priority' => 10,
            'force' => true,
        ]);

        $responseForce->assertOk()
            ->assertJsonPath('status', 'granted')
            ->assertJsonPath('master.client_id', 'tab_device_b_456');
    }

    public function test_master_heartbeat_returns_preempted_when_another_client_took_over(): void
    {
        $user = User::factory()->create();

        // Device B mengambil alih master host
        Cache::put('soundstation_master_host', [
            'client_id' => 'tab_device_b_456',
            'device_name' => 'Tablet POS',
            'page_title' => 'Sound Station',
            'priority' => 100,
            'claimed_at' => now()->timestamp,
            'updated_at' => now()->timestamp,
        ], 30);

        // Device A (bekas master) mengirim heartbeat
        $response = $this->actingAs($user)->postJson(route('kasir.music.master.heartbeat'), [
            'client_id' => 'tab_device_a_123',
            'current_time' => 90.0,
            'duration' => 240,
            'is_playing' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'preempted')
            ->assertJsonPath('current_master.client_id', 'tab_device_b_456');
    }

    public function test_remote_device_can_send_command_and_master_receives_in_heartbeat(): void
    {
        $user = User::factory()->create();

        // Device B (Master) terdaftar
        Cache::put('soundstation_master_host', [
            'client_id' => 'tab_device_b_456',
            'device_name' => 'Tablet POS',
            'page_title' => 'Sound Station',
            'priority' => 100,
            'claimed_at' => now()->timestamp,
            'updated_at' => now()->timestamp,
        ], 30);

        // Device A (Remote) mengirim perintah SKIP via API
        $cmdResponse = $this->actingAs($user)->postJson(route('kasir.music.master.command'), [
            'command' => 'SKIP',
            'data' => [],
        ]);

        $cmdResponse->assertOk()
            ->assertJsonPath('status', 'queued');

        // Device B (Master) mengirim heartbeat, harus menerima perintah SKIP
        $heartbeatResponse = $this->actingAs($user)->postJson(route('kasir.music.master.heartbeat'), [
            'client_id' => 'tab_device_b_456',
            'current_time' => 50,
        ]);

        $heartbeatResponse->assertOk()
            ->assertJsonPath('status', 'ok');

        $commands = $heartbeatResponse->json('commands');
        $this->assertNotEmpty($commands);
        $this->assertEquals('SKIP', $commands[0]['command']);
    }

    public function test_kasir_can_release_master_host(): void
    {
        $user = User::factory()->create();

        Cache::put('soundstation_master_host', [
            'client_id' => 'tab_device_b_456',
            'device_name' => 'Tablet POS',
            'claimed_at' => now()->timestamp,
            'updated_at' => now()->timestamp,
        ], 30);

        $response = $this->actingAs($user)->postJson(route('kasir.music.master.release'), [
            'client_id' => 'tab_device_b_456',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'released');

        $this->assertNull(Cache::get('soundstation_master_host'));
    }

    public function test_sync_playback_is_ignored_when_called_by_non_master_client(): void
    {
        $user = User::factory()->create();

        // Host aktif adalah client_A di detik 95
        Cache::put('soundstation_master_host', [
            'client_id' => 'tab_client_A',
            'device_name' => 'PC Kasir',
            'updated_at' => now()->timestamp,
        ], 30);

        Cache::put('soundstation_playback_state', [
            'current_time' => 95.0,
            'duration' => 200,
            'is_playing' => true,
            'client_id' => 'tab_client_A',
        ]);

        // client_B (remote) mencoba kirim sync 0.0 (misal baru buka halaman)
        $response = $this->actingAs($user)->postJson(route('kasir.music.playback.sync'), [
            'client_id' => 'tab_client_B',
            'current_time' => 0.0,
            'duration' => 0,
            'is_playing' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ignored');

        // Playback state di cache tetap milik client_A di detik 95
        $state = Cache::get('soundstation_playback_state');
        $this->assertEquals(95.0, $state['current_time']);
        $this->assertTrue($state['is_playing']);
    }

    public function test_kasir_can_reorder_default_tracks(): void
    {
        $user = User::factory()->create();

        $track1 = MusicDefaultTrack::factory()->create(['sort_order' => 1]);
        $track2 = MusicDefaultTrack::factory()->create(['sort_order' => 2]);
        $track3 = MusicDefaultTrack::factory()->create(['sort_order' => 3]);

        $response = $this->actingAs($user)->postJson(route('kasir.music.default.reorder'), [
            'track_ids' => [$track3->id, $track1->id, $track2->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(1, $track3->fresh()->sort_order);
        $this->assertEquals(2, $track1->fresh()->sort_order);
        $this->assertEquals(3, $track2->fresh()->sort_order);
    }

    public function test_kasir_can_add_history_request_to_default_playlist(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $musicRequest = MusicRequest::create([
            'order_id' => $order->id,
            'customer_name' => 'Budi Meja 4',
            'song_title' => 'Sialan',
            'artist' => 'Juicy Luicy',
            'youtube_id' => 'vX123456789',
            'duration_seconds' => 210,
            'status' => 'played',
        ]);

        $response = $this->actingAs($user)->postJson(route('kasir.music.requests.add_to_default', $musicRequest));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_exists', false)
            ->assertJsonPath('track.title', 'Sialan')
            ->assertJsonPath('track.artist', 'Juicy Luicy')
            ->assertJsonPath('track.youtube_id', 'vX123456789');

        $this->assertDatabaseHas('music_default_tracks', [
            'title' => 'Sialan',
            'artist' => 'Juicy Luicy',
            'youtube_id' => 'vX123456789',
            'is_active' => true,
        ]);
    }

    public function test_kasir_adding_existing_history_track_reactivates_it_and_avoids_duplicate(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $existing = MusicDefaultTrack::create([
            'title' => 'Sialan',
            'artist' => 'Juicy Luicy',
            'youtube_id' => 'vX123456789',
            'duration_seconds' => 210,
            'sort_order' => 1,
            'is_active' => false,
        ]);

        $musicRequest = MusicRequest::create([
            'order_id' => $order->id,
            'customer_name' => 'Budi Meja 4',
            'song_title' => 'Sialan',
            'artist' => 'Juicy Luicy',
            'youtube_id' => 'vX123456789',
            'duration_seconds' => 210,
            'status' => 'played',
        ]);

        $response = $this->actingAs($user)->postJson(route('kasir.music.requests.add_to_default', $musicRequest));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_exists', true)
            ->assertJsonPath('track.id', $existing->id);

        $this->assertEquals(1, MusicDefaultTrack::where('youtube_id', 'vX123456789')->count());
        $this->assertTrue($existing->fresh()->is_active);
    }

    public function test_customer_can_submit_request_without_song_title_using_youtube_link(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        $response = $this->postJson(route('music.store'), [
            'code' => $order->music_code,
            'youtube_id' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'customer_name' => 'Meja 5 Budi',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('request.youtube_id', 'dQw4w9WgXcQ');

        $this->assertNotEmpty($response->json('request.song_title'));

        $this->assertDatabaseHas('music_requests', [
            'order_id' => $order->id,
            'youtube_id' => 'dQw4w9WgXcQ',
            'status' => 'queued',
        ]);
    }

    public function test_get_player_state_prioritizes_playing_customer_request_over_stale_default_track_cache(): void
    {
        // 1. Simulasikan playlist bawaan sebelumnya sedang berjalan di cache
        Cache::put('soundstation_playback_state', [
            'current_time' => 45,
            'duration' => 200,
            'is_playing' => true,
            'current_track' => [
                'id' => 99,
                'title' => 'Lofi Coffee Vibes',
                'song_title' => 'Lofi Coffee Vibes',
                'artist' => 'Chill Cafe',
                'youtube_id' => 'LOFI0001',
                'type' => 'default_track',
            ],
            'updated_at' => now()->timestamp * 1000,
            'client_id' => 'tab_cashier_1',
        ], now()->addMinutes(2));

        Cache::put('soundstation_current_track', [
            'id' => 99,
            'title' => 'Lofi Coffee Vibes',
            'song_title' => 'Lofi Coffee Vibes',
            'type' => 'default_track',
        ], now()->addHours(8));

        // 2. Ada request pelanggan yang sedang berstatus 'playing'
        $order = Order::factory()->create(['status' => 'paid']);
        $playingReq = MusicRequest::create([
            'order_id' => $order->id,
            'customer_name' => 'Meja 2 Rian',
            'song_title' => 'Komang',
            'artist' => 'Raim Laode',
            'youtube_id' => 'KOMANG12345',
            'duration_seconds' => 215,
            'status' => 'playing',
            'played_at' => now(),
        ]);

        // 3. Panggil API status (yang digunakan oleh Display TV)
        $response = $this->getJson(route('music.status'));

        $response->assertOk()
            ->assertJsonPath('now_playing.type', 'customer_request')
            ->assertJsonPath('now_playing.id', $playingReq->id)
            ->assertJsonPath('now_playing.song_title', 'Komang')
            ->assertJsonPath('now_playing.customer_name', 'Meja 2 Rian');

        // Pastikan status request pelanggan tetap 'playing', TIDAK terhapus/terubah menjadi 'played'
        $this->assertEquals('playing', $playingReq->fresh()->status);
    }

    public function test_music_tts_endpoint_returns_audio_stream(): void
    {
        Http::fake([
            'translate.google.com/*' => Http::response('FAKE_AUDIO_MP3_STREAM_CONTENT_BYTES_1234567890_1234567890_1234567890_1234567890_1234567890_1234567890_1234567890', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $response = $this->get(route('music.tts', ['text' => 'Pesanan Kak Budi, siap diambil di kasir.']));

        $response->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg');
    }

    public function test_cashier_can_access_announcer_settings_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('kasir.announcer.settings'));

        $response->assertOk()
            ->assertSee('Pengaturan Suara Announcer')
            ->assertSee('Mbak Google')
            ->assertSee('Tempo Santai')
            ->assertSee('Tempo Gesit');
    }

    public function test_cashier_can_save_announcer_settings(): void
    {
        $user = User::factory()->create();

        $payload = [
            'voice_model' => 'ms_ardi',
            'template_type' => 'airport',
            'custom_template' => '',
            'chime_style' => 'airport',
            'rate' => 0.95,
            'pitch' => 1.05,
            'duck_volume' => 15,
        ];

        $response = $this->actingAs($user)
            ->post(route('kasir.announcer.save'), $payload);

        $response->assertRedirect(route('kasir.announcer.settings'))
            ->assertSessionHas('success');

        $saved = Cache::get('soundstation_voice_settings');
        $this->assertNotNull($saved);
        $this->assertEquals('ms_ardi', $saved['voice_model']);
        $this->assertEquals('airport', $saved['template_type']);
        $this->assertEquals('airport', $saved['chime_style']);
    }

    public function test_announcer_settings_persist_across_cache_clear_and_supports_zero_duck_volume(): void
    {
        $user = User::factory()->create();

        $payload = [
            'voice_model' => 'english_cafe',
            'template_type' => 'english',
            'custom_template' => '',
            'chime_style' => 'bell',
            'rate' => 1.1,
            'pitch' => 0.95,
            'duck_volume' => 0,
            'adzan_mode_enabled' => true,
            'adzan_target_volume' => 2,
            'adzan_duration_minutes' => 7,
            'auto_pause_midnight' => true,
            'closing_time' => '23:30:00',
            'reopen_time' => '07:00',
        ];

        $response = $this->actingAs($user)
            ->postJson(route('kasir.announcer.save'), $payload);

        $response->assertOk()
            ->assertJsonPath('settings.duck_volume', 0)
            ->assertJsonPath('settings.closing_time', '23:30')
            ->assertJsonPath('settings.voice_model', 'english_cafe');

        // Simulasi php artisan cache:clear (menghapus Cache)
        Cache::flush();
        $this->assertNull(Cache::get('soundstation_voice_settings'));

        // getActiveAnnouncerSettings harus memulihkan settingan dari persistent file storage
        $activeSettings = KasirMusicController::getActiveAnnouncerSettings();
        $this->assertEquals('english_cafe', $activeSettings['voice_model']);
        $this->assertSame(0, $activeSettings['duck_volume']);
        $this->assertEquals('23:30', $activeSettings['closing_time']);
        $this->assertEquals('07:00', $activeSettings['reopen_time']);
        $this->assertSame(2, $activeSettings['adzan_target_volume']);
        $this->assertSame(7, $activeSettings['adzan_duration_minutes']);

        // Bersihkan file storage testing dan cache agar terisolasi dari test lain
        $testFile = KasirMusicController::getAnnouncerSettingsStoragePath();
        if (file_exists($testFile)) {
            @unlink($testFile);
        }
        Cache::flush();
    }

    public function test_cashier_can_fetch_announcer_settings_json(): void
    {
        $user = User::factory()->create();

        Cache::put('soundstation_voice_settings', [
            'voice_model' => 'ms_gadis',
            'template_type' => 'formal',
            'custom_template' => '',
            'chime_style' => 'bell',
            'rate' => 1.0,
            'pitch' => 1.0,
            'duck_volume' => 10,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('kasir.announcer.json'));

        $response->assertOk()
            ->assertJsonPath('settings.voice_model', 'ms_gadis')
            ->assertJsonPath('settings.template_type', 'formal')
            ->assertJsonPath('settings.chime_style', 'bell');
    }

    public function test_status_endpoint_returns_combined_queue_with_request_and_bawaan_badges(): void
    {
        MusicRequest::query()->delete();
        MusicDefaultTrack::query()->delete();

        // 1. Buat track bawaan kafe
        $defaultTrack1 = MusicDefaultTrack::create([
            'title' => 'Morning Coffee Jazz',
            'artist' => 'Coffee Lounge',
            'youtube_id' => 'MCJAZZ123',
            'duration_seconds' => 180,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $defaultTrack2 = MusicDefaultTrack::create([
            'title' => 'Evening Chill Lo-Fi',
            'artist' => 'Lofi Beats',
            'youtube_id' => 'ECLOFI456',
            'duration_seconds' => 210,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // 2. Buat request dari pelanggan yang berstatus 'queued'
        $order = Order::factory()->create(['status' => 'paid']);
        $req = MusicRequest::create([
            'order_id' => $order->id,
            'customer_name' => 'Meja 8 Doni',
            'song_title' => 'Sialan',
            'artist' => 'Juicy Luicy',
            'youtube_id' => 'SIALAN789',
            'duration_seconds' => 240,
            'status' => 'queued',
        ]);

        // 3. Panggil API status
        $response = $this->getJson(route('music.status'));

        $response->assertOk();

        $data = $response->json();

        // Verifikasi queue_count hanya menghitung request pelanggan (untuk menjaga aturan fade-out)
        $this->assertEquals(1, $data['queue_count']);
        $this->assertEquals(1, $data['request_queue_count']);
        $this->assertEquals(2, $data['default_tracks_count']);
        $this->assertEquals(2, $data['total_queue_count']);

        // Item pertama di antrean HARUS merupakan request pelanggan (prioritas utama)
        $firstItem = $data['queue'][0];
        $this->assertEquals('request', $firstItem['type']);
        $this->assertEquals('Request', $firstItem['badge']);
        $this->assertEquals('Sialan', $firstItem['title']);
        $this->assertEquals('Meja 8 Doni', $firstItem['customer_name']);
        $this->assertTrue($firstItem['is_request']);

        // Item kedua dan ketiga adalah lagu bawaan kafe
        $secondItem = $data['queue'][1];
        $this->assertEquals('default', $secondItem['type']);
        $this->assertEquals('Bawaan', $secondItem['badge']);
        $this->assertFalse($secondItem['is_request']);
    }

    public function test_release_master_host_sets_playback_to_paused(): void
    {
        $user = User::factory()->create();

        Cache::put('soundstation_master_host', [
            'client_id' => 'tab_cashier_closing',
            'device_name' => 'PC Kasir',
            'updated_at' => now()->timestamp,
        ], 30);

        Cache::put('soundstation_playback_state', [
            'current_time' => 120.0,
            'duration' => 240,
            'is_playing' => true,
            'client_id' => 'tab_cashier_closing',
        ]);

        $response = $this->actingAs($user)->postJson(route('kasir.music.master.release'), [
            'client_id' => 'tab_cashier_closing',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'released');

        $this->assertNull(Cache::get('soundstation_master_host'));
        $playback = Cache::get('soundstation_playback_state');
        $this->assertNotNull($playback);
        $this->assertFalse($playback['is_playing']);
    }

    public function test_status_endpoint_returns_closing_settings_and_safeguards_stale_master(): void
    {
        // 1. Simulasikan master host mati (> 45 detik yang lalu)
        Cache::put('soundstation_master_host', [
            'client_id' => 'tab_stale_cashier',
            'updated_at' => now()->subSeconds(50)->timestamp,
        ], 60);

        Cache::put('soundstation_playback_state', [
            'current_time' => 50.0,
            'duration' => 200,
            'is_playing' => true,
        ]);

        Cache::put('soundstation_voice_settings', [
            'auto_pause_midnight' => true,
            'closing_time' => '00:00',
            'reopen_time' => '06:00',
        ]);

        $response = $this->getJson(route('music.status'));

        $response->assertOk()
            ->assertJsonPath('playback.is_playing', false)
            ->assertJsonPath('closing_settings.auto_pause_enabled', true)
            ->assertJsonPath('closing_settings.closing_time', '00:00')
            ->assertJsonPath('closing_settings.reopen_time', '06:00');
    }

    public function test_sync_playback_and_remote_command_persist_volume(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. Klaim master host terlebih dahulu
        $claim = $this->postJson(route('kasir.music.master.claim'), [
            'client_id' => 'master_tab_vol_test',
            'device_id' => 'dev_123',
            'device_name' => 'PC Kasir',
            'priority' => 100,
        ]);
        $claim->assertOk()->assertJsonPath('status', 'granted');

        // 2. Sync playback dengan volume
        $sync = $this->postJson(route('kasir.music.playback.sync'), [
            'client_id' => 'master_tab_vol_test',
            'current_time' => 12.5,
            'duration' => 200,
            'is_playing' => true,
            'volume' => 35,
        ]);
        $sync->assertOk()->assertJsonPath('status', 'ok');

        $this->assertSame(35, Cache::get('soundstation_playback_volume'));

        // 3. Status endpoint mengembalikan volume yang dipersist
        $status = $this->getJson(route('kasir.music.master.status'));
        $status->assertOk()
            ->assertJsonPath('has_master', true)
            ->assertJsonPath('volume', 35);

        // 4. Remote command SET_VOLUME memperbarui volume di cache
        $remote = $this->postJson(route('kasir.music.master.command'), [
            'command' => 'SET_VOLUME',
            'data' => ['volume' => 15],
        ]);
        $remote->assertOk()->assertJsonPath('status', 'queued');

        $this->assertSame(15, Cache::get('soundstation_playback_volume'));

        // 5. Heartbeat master mengembalikan volume terbaru
        $heartbeat = $this->postJson(route('kasir.music.master.heartbeat'), [
            'client_id' => 'master_tab_vol_test',
            'volume' => 20,
        ]);
        $heartbeat->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('volume', 20);

        $this->assertSame(20, Cache::get('soundstation_playback_volume'));
    }

    public function test_remote_command_persists_duck_and_adzan_volume_to_persistent_storage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. Remote command SET_DUCK_VOLUME memperbarui duck_volume ke cache dan storage
        $remoteDuck = $this->postJson(route('kasir.music.master.command'), [
            'command' => 'SET_DUCK_VOLUME',
            'data' => ['duck_volume' => 5],
        ]);
        $remoteDuck->assertOk()->assertJsonPath('status', 'queued');

        $activeSettings = KasirMusicController::getActiveAnnouncerSettings();
        $this->assertSame(5, $activeSettings['duck_volume']);

        // 2. Remote command SET_ADZAN_VOLUME memperbarui adzan_target_volume ke cache dan storage
        $remoteAdzan = $this->postJson(route('kasir.music.master.command'), [
            'command' => 'SET_ADZAN_VOLUME',
            'data' => ['target_volume' => 8],
        ]);
        $remoteAdzan->assertOk()->assertJsonPath('status', 'queued');

        $activeSettings = KasirMusicController::getActiveAnnouncerSettings();
        $this->assertSame(8, $activeSettings['adzan_target_volume']);

        // 3. Simulasi server restart / cache:clear: Cache dihapus total
        Cache::flush();
        $this->assertNull(Cache::get('soundstation_voice_settings'));

        // Harus tetap pulih dari persistent file storage
        $restoredSettings = KasirMusicController::getActiveAnnouncerSettings();
        $this->assertSame(5, $restoredSettings['duck_volume']);
        $this->assertSame(8, $restoredSettings['adzan_target_volume']);

        // Cleanup
        $testFile = KasirMusicController::getAnnouncerSettingsStoragePath();
        if (file_exists($testFile)) {
            @unlink($testFile);
        }
        Cache::flush();
    }

    public function test_customer_and_test_code_cannot_request_live_stream_video(): void
    {
        Cache::flush();
        Http::fake([
            'https://www.youtube.com/watch?v=LIVE_STRM_1' => Http::response(
                '<html><head><meta itemprop="isLiveBroadcast" content="True"></head><body><script>var ytInitialPlayerResponse = {"videoDetails":{"isLive":true,"isLiveContent":true,"title":"24/7 Lo-Fi Chill Radio Live Stream"}};</script></body></html>',
                200
            ),
        ]);

        // 1. Uji dengan kode struk transaksi biasa
        $order = Order::factory()->create(['status' => 'paid']);
        $resCustomer = $this->postJson(route('music.store'), [
            'code' => $order->music_code,
            'song_title' => '24/7 Lo-Fi Live',
            'youtube_id' => 'LIVE_STRM_1',
            'duration_seconds' => 0,
        ]);

        $resCustomer->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'siaran langsung') || str_contains($m, 'live stream'));

        // Struk transaksi tidak boleh hangus/terkunci jika request gagal karena live stream
        $this->assertNull($order->fresh()->music_request_used_at);

        // 2. Uji dengan kode testing "test"
        $resTest = $this->postJson(route('music.store'), [
            'code' => 'test',
            'song_title' => '24/7 Lo-Fi Live',
            'youtube_id' => 'LIVE_STRM_1',
            'duration_seconds' => 0,
        ]);

        $resTest->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'siaran langsung') || str_contains($m, 'live stream'));
    }

    public function test_customer_and_test_code_cannot_request_nsfw_or_age_restricted_video(): void
    {
        Cache::flush();
        Http::fake([
            'https://www.youtube.com/watch?v=NSFW_VID_01' => Http::response(
                '<html><head><meta property="og:restrictions:age" content="18+"></head><body><script>var ytInitialPlayerResponse = {"playerMicroformatRenderer":{"familySafe":false},"playabilityStatus":{"status":"LOGIN_REQUIRED","reason":"Sign in to confirm your age. This video may be inappropriate for some users."}};</script></body></html>',
                200
            ),
        ]);

        // 1. Uji video dengan penanda YouTube age-restricted via struk transaksi
        $order = Order::factory()->create(['status' => 'paid']);
        $resNsfwCustomer = $this->postJson(route('music.store'), [
            'code' => $order->music_code,
            'youtube_id' => 'NSFW_VID_01',
            'duration_seconds' => 180,
        ]);

        $resNsfwCustomer->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains(strtolower($m), 'dewasa') || str_contains(strtolower($m), 'nsfw'));

        // 2. Uji video dengan penanda YouTube age-restricted via kode "test"
        $resNsfwTest = $this->postJson(route('music.store'), [
            'code' => 'test',
            'youtube_id' => 'NSFW_VID_01',
            'duration_seconds' => 180,
        ]);

        $resNsfwTest->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains(strtolower($m), 'dewasa') || str_contains(strtolower($m), 'nsfw'));

        // 3. Uji penolakan berdasarkan kata kunci eksplisit NSFW pada judul
        $resKeyword = $this->postJson(route('music.store'), [
            'code' => 'test',
            'song_title' => 'Video Musik 18+ Hentai Uncensored',
            'youtube_id' => 'CLEAN_YT_01',
            'duration_seconds' => 180,
        ]);

        $resKeyword->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains(strtolower($m), 'dewasa') || str_contains(strtolower($m), 'nsfw'));
    }

    public function test_search_endpoint_flags_live_stream_and_nsfw_content(): void
    {
        Cache::flush();
        Http::fake([
            'https://www.youtube.com/watch?v=LIVE_SRCH_1' => Http::response(
                '<html><head><meta itemprop="isLiveBroadcast" content="True"></head><body><script>var ytInitialPlayerResponse = {"videoDetails":{"isLive":true,"title":"Live Stream Station"}};</script></body></html>',
                200
            ),
            'https://www.youtube.com/watch?v=NSFW_SRCH_1' => Http::response(
                '<html><head></head><body><script>var ytInitialPlayerResponse = {"playerMicroformatRenderer":{"familySafe":false}};</script></body></html>',
                200
            ),
        ]);

        // 1. Pencarian URL live stream
        $searchLive = $this->getJson(route('music.search', ['q' => 'https://www.youtube.com/watch?v=LIVE_SRCH_1']));
        $searchLive->assertOk()
            ->assertJsonPath('results.0.is_live', true)
            ->assertJsonPath('results.0.is_valid', false)
            ->assertJsonPath('results.0.error_message', fn ($m) => str_contains($m, 'siaran langsung'));

        // 2. Pencarian URL NSFW
        $searchNsfw = $this->getJson(route('music.search', ['q' => 'https://www.youtube.com/watch?v=NSFW_SRCH_1']));
        $searchNsfw->assertOk()
            ->assertJsonPath('results.0.is_nsfw', true)
            ->assertJsonPath('results.0.is_valid', false)
            ->assertJsonPath('results.0.error_message', fn ($m) => str_contains(strtolower($m), 'dewasa') || str_contains(strtolower($m), 'nsfw'));

        // 3. Pencarian query teks bebas dengan kata kunci NSFW
        $searchQueryNsfw = $this->getJson(route('music.search', ['q' => 'hentai opening ost']));
        $searchQueryNsfw->assertOk()
            ->assertJsonPath('results.0.is_nsfw', true)
            ->assertJsonPath('results.0.is_valid', false);
    }

    public function test_music_service_detects_static_visual_and_dynamic_video_correctly(): void
    {
        $service = app(MusicService::class);

        // Kasus 1: Channel Topic YouTube (100% Art Track statis)
        $this->assertTrue($service->isStaticVisualTrack('The Scientist', 'Coldplay - Topic'));

        // Kasus 2: Official Audio / Cover Art (Statis)
        $this->assertTrue($service->isStaticVisualTrack('Bruno Mars - Die With A Smile (Official Audio)', 'Bruno Mars'));
        $this->assertTrue($service->isStaticVisualTrack('Joji - Glimpse of Us (Visualizer)', '88rising'));
        $this->assertTrue($service->isStaticVisualTrack('1 A.M Study Session [1 Jam Lo-Fi Chill Cafe Beats]', 'Lofi Girl'));

        // Kasus 3: Official Music Video / MV (Video Dinamis)
        $this->assertFalse($service->isStaticVisualTrack('Bruno Mars - Die With A Smile (Official Music Video)', 'Bruno Mars'));
        $this->assertFalse($service->isStaticVisualTrack('NewJeans - Super Shy (Official MV)', 'HYBE LABELS'));
        $this->assertFalse($service->isStaticVisualTrack('Adele - Easy On Me (Live at NRJ)', 'AdeleVEVO'));
        $this->assertFalse($service->isStaticVisualTrack('Coldplay - The Scientist (Official 4K Video)', 'Coldplay'));
    }

    public function test_music_status_payload_includes_is_static_visual_field(): void
    {
        MusicDefaultTrack::factory()->create([
            'title' => 'Cafe Lo-Fi Beats (Official Audio)',
            'artist' => 'Chillhop Music',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson(route('music.status'));

        $response->assertOk()
            ->assertJsonPath('now_playing.is_static_visual', true);
    }
}
