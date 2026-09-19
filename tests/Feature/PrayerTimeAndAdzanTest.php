<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PrayerTimeService;
use Carbon\Carbon;
use Tests\TestCase;

class PrayerTimeAndAdzanTest extends TestCase
{
    public function test_prayer_time_service_calculates_surabaya_sidoarjo_schedule(): void
    {
        $date = Carbon::parse('2026-09-19 12:00:00', 'Asia/Jakarta');
        $result = PrayerTimeService::getSchedule($date, 5);

        $this->assertEquals('Surabaya & Sidoarjo', $result['city']);
        $this->assertArrayHasKey('schedule', $result);
        $this->assertArrayHasKey('subuh', $result['schedule']);
        $this->assertArrayHasKey('dzuhur', $result['schedule']);
        $this->assertArrayHasKey('ashar', $result['schedule']);
        $this->assertArrayHasKey('maghrib', $result['schedule']);
        $this->assertArrayHasKey('isya', $result['schedule']);

        // Pastikan format jam:menit
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $result['schedule']['subuh']);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $result['schedule']['maghrib']);
    }

    public function test_kasir_can_fetch_prayer_times_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('kasir.music.prayer-times'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'city',
                    'date',
                    'schedule' => ['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya'],
                ],
                'settings' => [
                    'enabled',
                    'target_volume',
                    'duration_minutes',
                ],
            ]);

        $this->assertTrue($response->json('settings.enabled'));
        $this->assertEquals(5, $response->json('settings.duration_minutes'));
    }

    public function test_kasir_can_save_adzan_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('kasir.announcer.save'), [
            'voice_model' => 'mbak_google',
            'template_type' => 'concise',
            'chime_style' => 'ding_dong',
            'rate' => 1.0,
            'pitch' => 1.05,
            'duck_volume' => 15,
            'adzan_mode_enabled' => true,
            'adzan_target_volume' => 10,
            'adzan_duration_minutes' => 5,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'settings' => [
                    'adzan_mode_enabled' => true,
                    'adzan_target_volume' => 10,
                    'adzan_duration_minutes' => 5,
                ],
            ]);
    }

    public function test_public_music_status_includes_prayer_times_and_adzan_settings(): void
    {
        $response = $this->getJson(route('music.status'));

        $response->assertOk()
            ->assertJsonStructure([
                'now_playing',
                'prayer_times' => [
                    'city',
                    'schedule' => ['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya'],
                ],
                'adzan_settings' => [
                    'enabled',
                    'target_volume',
                    'duration_minutes',
                ],
            ]);
    }
}
