<?php

namespace Tests\Feature;

use App\Models\MusicBannedTrack;
use App\Models\MusicRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MusicBanListTest extends TestCase
{
    public function test_kasir_can_store_banned_track_manually(): void
    {
        $kasir = User::factory()->create();

        $response = $this->actingAs($kasir)->postJson(route('kasir.music.ban.store'), [
            'youtube_id' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title' => 'Never Gonna Give You Up',
            'artist' => 'Rick Astley',
            'reason' => 'Terlalu sering diputar / dilarang',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('music_banned_tracks', [
            'youtube_id' => 'dQw4w9WgXcQ',
            'title' => 'Never Gonna Give You Up',
            'artist' => 'Rick Astley',
            'reason' => 'Terlalu sering diputar / dilarang',
            'is_active' => true,
        ]);
    }

    public function test_customer_cannot_request_banned_track_by_youtube_id(): void
    {
        Http::fake([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ*' => Http::response('<html><title>Never Gonna Give You Up - YouTube</title><body>"approxDurationMs":"213000"</body></html>', 200),
            'https://www.youtube.com/oembed*' => Http::response([
                'title' => 'Never Gonna Give You Up',
                'author_name' => 'Rick Astley',
                'thumbnail_url' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            ], 200),
        ]);

        MusicBannedTrack::factory()->create([
            'youtube_id' => 'dQw4w9WgXcQ',
            'title' => 'Never Gonna Give You Up',
            'reason' => 'Dilarang kasir kafe',
            'is_active' => true,
        ]);

        $order = Order::factory()->create(['status' => 'paid']);

        $response = $this->postJson(route('music.store'), [
            'code' => $order->music_code,
            'youtube_id' => 'dQw4w9WgXcQ',
            'song_title' => 'Never Gonna Give You Up',
            'artist' => 'Rick Astley',
            'duration_seconds' => 213,
        ]);

        $response->assertStatus(422);

        $this->assertStringContainsString('Blacklist', $response->json('message'));
        $this->assertStringContainsString('Dilarang kasir kafe', $response->json('message'));
    }

    public function test_customer_cannot_request_banned_track_by_title_keyword(): void
    {
        Http::fake([
            'https://www.youtube.com/watch?v=x7z9y8x1234*' => Http::response('<html><title>Musik DJ Remix Dugem Horeg - YouTube</title><body>"approxDurationMs":"210000"</body></html>', 200),
            'https://www.youtube.com/oembed*' => Http::response([
                'title' => 'Musik DJ Remix Dugem Horeg',
                'author_name' => 'DJ Horeg',
                'thumbnail_url' => 'https://img.youtube.com/vi/x7z9y8x1234/hqdefault.jpg',
            ], 200),
        ]);

        MusicBannedTrack::factory()->create([
            'youtube_id' => null,
            'title' => 'Dugem Horeg',
            'reason' => 'Terlalu bising dan tidak cocok untuk kafe santai',
            'is_active' => true,
        ]);

        $order = Order::factory()->create(['status' => 'paid']);

        $response = $this->postJson(route('music.store'), [
            'code' => $order->music_code,
            'youtube_id' => 'x7z9y8x1234',
            'song_title' => 'Musik DJ Remix Dugem Horeg Paling Santuy',
            'artist' => 'DJ Horeg',
            'duration_seconds' => 210,
        ]);

        $response->assertStatus(422);

        $this->assertStringContainsString('Blacklist', $response->json('message'));
        $this->assertStringContainsString('Terlalu bising', $response->json('message'));
    }

    public function test_inactive_banned_track_is_ignored_and_allowed(): void
    {
        Http::fake([
            'https://www.youtube.com/watch?v=allow123456*' => Http::response('<html><title>Chill Coffee Song - YouTube</title><body>"approxDurationMs":"180000"</body></html>', 200),
            'https://www.youtube.com/oembed*' => Http::response([
                'title' => 'Chill Coffee Song',
                'author_name' => 'Barista Vibes',
                'thumbnail_url' => 'https://img.youtube.com/vi/allow123456/hqdefault.jpg',
            ], 200),
        ]);

        // Lagu di ban tapi status is_active = false (larangan dicabut sementara)
        MusicBannedTrack::factory()->create([
            'youtube_id' => 'allow123456',
            'title' => 'Chill Coffee Song',
            'is_active' => false,
        ]);

        $order = Order::factory()->create(['status' => 'paid']);

        $response = $this->postJson(route('music.store'), [
            'code' => $order->music_code,
            'youtube_id' => 'allow123456',
            'song_title' => 'Chill Coffee Song',
            'artist' => 'Barista Vibes',
            'duration_seconds' => 180,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('music_requests', [
            'youtube_id' => 'allow123456',
            'status' => 'queued',
        ]);
    }

    public function test_kasir_can_toggle_banned_track_status(): void
    {
        $kasir = User::factory()->create();
        $banned = MusicBannedTrack::factory()->create(['is_active' => true]);

        $response = $this->actingAs($kasir)->patchJson(route('kasir.music.ban.toggle', $banned));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('banned_track.is_active', false);

        $this->assertDatabaseHas('music_banned_tracks', [
            'id' => $banned->id,
            'is_active' => false,
        ]);
    }

    public function test_kasir_can_delete_banned_track(): void
    {
        $kasir = User::factory()->create();
        $banned = MusicBannedTrack::factory()->create();

        $response = $this->actingAs($kasir)->deleteJson(route('kasir.music.ban.destroy', $banned));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('music_banned_tracks', [
            'id' => $banned->id,
        ]);
    }

    public function test_kasir_can_auto_ban_and_reject_request_from_queue(): void
    {
        $kasir = User::factory()->create();
        $request = MusicRequest::factory()->create([
            'song_title' => 'Lagu Kasar',
            'artist' => 'Artis A',
            'youtube_id' => 'banned99999',
            'status' => 'queued',
        ]);

        $response = $this->actingAs($kasir)->postJson(route('kasir.music.requests.ban', $request), [
            'reason' => 'Lirik vulgar dilarang kasir',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('music_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'notes' => 'Lirik vulgar dilarang kasir',
        ]);

        $this->assertDatabaseHas('music_banned_tracks', [
            'youtube_id' => 'banned99999',
            'title' => 'Lagu Kasar',
            'reason' => 'Lirik vulgar dilarang kasir',
            'is_active' => true,
        ]);
    }

    public function test_kasir_can_quick_ban_current_track(): void
    {
        $kasir = User::factory()->create();

        $response = $this->actingAs($kasir)->postJson(route('kasir.music.ban.current'), [
            'youtube_id' => 'quickban123',
            'title' => 'Lagu Mengganggu',
            'artist' => 'Artis Berisik',
            'reason' => 'Dilarang oleh kasir saat diputar',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('music_banned_tracks', [
            'youtube_id' => 'quickban123',
            'title' => 'Lagu Mengganggu',
            'reason' => 'Dilarang oleh kasir saat diputar',
            'is_active' => true,
        ]);
    }
}
