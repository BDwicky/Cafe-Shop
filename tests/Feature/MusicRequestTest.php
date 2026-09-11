<?php

namespace Tests\Feature;

use App\Models\MusicDefaultTrack;
use App\Models\MusicRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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
}
