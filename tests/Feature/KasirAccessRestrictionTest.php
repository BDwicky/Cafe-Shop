<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\KasirLoginController;
use App\Models\KasirAuthorizedDevice;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class KasirAccessRestrictionTest extends TestCase
{
    public function test_allowed_ip_can_access_kasir_login(): void
    {
        Config::set('cafe.kasir_ip_restriction_enabled', true);
        Config::set('cafe.kasir_allowed_ips', ['127.0.0.1', '192.168.1.*']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/kasir/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Terminal');
    }

    public function test_allowed_wildcard_ip_can_access_kasir_login(): void
    {
        Config::set('cafe.kasir_ip_restriction_enabled', true);
        Config::set('cafe.kasir_allowed_ips', ['192.168.1.*']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.55'])
            ->get('/kasir/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Terminal');
    }

    public function test_unauthorized_ip_is_blocked_with_403(): void
    {
        Config::set('cafe.kasir_ip_restriction_enabled', true);
        Config::set('cafe.kasir_allowed_ips', ['192.168.1.*']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.195'])
            ->get('/kasir/login');

        $response->assertStatus(403);
        $response->assertSee('Akses Terminal Ditolak');
        $response->assertSee('203.0.113.195');
    }

    public function test_unauthorized_ip_with_json_request_receives_403_json(): void
    {
        Config::set('cafe.kasir_ip_restriction_enabled', true);
        Config::set('cafe.kasir_allowed_ips', ['192.168.1.*']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.195'])
            ->getJson('/kasir/login');

        $response->assertStatus(403);
        $response->assertJsonPath('client_ip', '203.0.113.195');
    }

    public function test_device_with_legacy_hmac_token_can_access_even_from_unauthorized_ip(): void
    {
        Config::set('cafe.kasir_ip_restriction_enabled', true);
        Config::set('cafe.kasir_allowed_ips', ['192.168.1.*']);
        $secret = 'test-secret-device-key';
        Config::set('cafe.kasir_device_secret', $secret);

        $validToken = hash_hmac('sha256', 'kopikita-authorized-pos-device', $secret);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.195'])
            ->withCookie('kasir_device_token', $validToken)
            ->get('/kasir/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Terminal');
    }

    public function test_authorize_device_route_sets_cookie_and_registers_device_in_database(): void
    {
        $secret = 'test-secret-device-key';
        Config::set('cafe.kasir_device_secret', $secret);

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '10.0.0.5',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X) AppleWebKit/605.1.15 Chrome/110.0.0.0 Safari/604.1',
        ])->get('/kasir/authorize-device?key='.$secret);

        $response->assertRedirect('/kasir/login');
        $response->assertSessionHas('status', 'Perangkat ini berhasil diotorisasi sebagai terminal kasir resmi!');
        $response->assertCookieNotExpired('kasir_device_token');

        $this->assertDatabaseHas('kasir_authorized_devices', [
            'platform' => 'iPadOS',
            'browser' => 'Chrome',
            'device_type' => 'tablet',
            'ip_address' => '10.0.0.5',
            'is_revoked' => false,
        ]);
    }

    public function test_authorize_device_with_invalid_key_fails(): void
    {
        $secret = 'test-secret-device-key';
        Config::set('cafe.kasir_device_secret', $secret);

        $response = $this->get('/kasir/authorize-device?key=wrong-key');

        $response->assertRedirect('/kasir/login');
        $response->assertSessionHas('error');
        $response->assertCookieMissing('kasir_device_token');
    }

    public function test_registered_active_device_can_access_kasir_from_unauthorized_ip(): void
    {
        Config::set('cafe.kasir_ip_restriction_enabled', true);
        Config::set('cafe.kasir_allowed_ips', ['192.168.1.*']);

        $plainToken = Str::random(64);
        KasirAuthorizedDevice::create([
            'device_name' => 'Tablet Kasir Barista',
            'device_token_hash' => hash('sha256', $plainToken),
            'device_type' => 'tablet',
            'platform' => 'Android',
            'browser' => 'Chrome',
            'ip_address' => '192.168.1.10',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.195'])
            ->withCookie('kasir_device_token', $plainToken)
            ->get('/kasir/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Terminal');
    }

    public function test_owner_can_revoke_remote_device_and_revoked_device_is_blocked_with_403(): void
    {
        Config::set('cafe.kasir_ip_restriction_enabled', true);
        Config::set('cafe.kasir_allowed_ips', ['192.168.1.*']);

        $user = User::factory()->create();

        // 1. Buat 2 perangkat terdaftar
        $tokenDeviceA = Str::random(64);
        $deviceA = KasirAuthorizedDevice::create([
            'device_name' => 'Tablet Kasir Kasir 1',
            'device_token_hash' => hash('sha256', $tokenDeviceA),
            'device_type' => 'tablet',
            'platform' => 'Android',
            'browser' => 'Chrome',
            'ip_address' => '192.168.1.10',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        $tokenDeviceB = Str::random(64);
        $deviceB = KasirAuthorizedDevice::create([
            'device_name' => 'Tablet Kasir Barista',
            'device_token_hash' => hash('sha256', $tokenDeviceB),
            'device_type' => 'tablet',
            'platform' => 'iOS',
            'browser' => 'Safari',
            'ip_address' => '192.168.1.11',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        // 2. Owner mencabut izin Device A secara remote
        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '192.168.1.2'])
            ->post("/kasir/devices/{$deviceA->id}/revoke");

        $response->assertRedirect('/kasir/device-setup');
        $response->assertSessionHas('status');

        $this->assertTrue($deviceA->fresh()->is_revoked);
        $this->assertFalse($deviceB->fresh()->is_revoked);

        // 3. Device A mencoba membuka kasir dari luar jaringan kafe -> 403 Ditolak
        $responseDeviceA = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.195'])
            ->withCookie('kasir_device_token', $tokenDeviceA)
            ->get('/kasir/login');

        $responseDeviceA->assertStatus(403);
        $responseDeviceA->assertSee('Otorisasi Dicabut');

        // 4. Device B tetap dapat mengakses dengan normal tanpa terganggu
        $responseDeviceB = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.195'])
            ->withCookie('kasir_device_token', $tokenDeviceB)
            ->get('/kasir/login');

        $responseDeviceB->assertStatus(200);
        $responseDeviceB->assertSee('Masuk ke Terminal');
    }

    public function test_owner_can_restore_revoked_device(): void
    {
        $user = User::factory()->create();

        $plainToken = Str::random(64);
        $device = KasirAuthorizedDevice::create([
            'device_name' => 'Tablet Dicabut',
            'device_token_hash' => hash('sha256', $plainToken),
            'device_type' => 'tablet',
            'platform' => 'Android',
            'browser' => 'Chrome',
            'ip_address' => '192.168.1.10',
            'is_revoked' => true,
            'revoked_at' => now(),
            'last_active_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post("/kasir/devices/{$device->id}/restore");

        $response->assertRedirect('/kasir/device-setup');
        $response->assertSessionHas('status');

        $this->assertFalse($device->fresh()->is_revoked);
    }

    public function test_owner_can_rename_remote_device(): void
    {
        $user = User::factory()->create();

        $device = KasirAuthorizedDevice::create([
            'device_name' => 'Nama Lama',
            'device_token_hash' => hash('sha256', Str::random(64)),
            'device_type' => 'tablet',
            'platform' => 'Android',
            'browser' => 'Chrome',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->patch("/kasir/devices/{$device->id}/rename", [
                'device_name' => 'Tablet Kasir Kasir Utama',
            ]);

        $response->assertRedirect('/kasir/device-setup');
        $this->assertEquals('Tablet Kasir Kasir Utama', $device->fresh()->device_name);
    }

    public function test_owner_can_delete_remote_device(): void
    {
        $user = User::factory()->create();

        $device = KasirAuthorizedDevice::create([
            'device_name' => 'Perangkat Usang',
            'device_token_hash' => hash('sha256', Str::random(64)),
            'device_type' => 'tablet',
            'platform' => 'Android',
            'browser' => 'Chrome',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->delete("/kasir/devices/{$device->id}");

        $response->assertRedirect('/kasir/device-setup');
        $this->assertDatabaseMissing('kasir_authorized_devices', ['id' => $device->id]);
    }

    public function test_feature_disabled_allows_all_ips(): void
    {
        Config::set('cafe.kasir_ip_restriction_enabled', false);
        Config::set('cafe.kasir_allowed_ips', ['192.168.1.*']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.195'])
            ->get('/kasir/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Terminal');
    }

    public function test_authenticated_user_can_view_device_setup_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/kasir/device-setup');

        $response->assertStatus(200);
        $response->assertSee('Otorisasi Perangkat & Jaringan', false);
        $response->assertSee('Daftar Perangkat Kasir Terdaftar', false);
        $response->assertSee('data:image/png;base64', false);
    }

    public function test_unauthenticated_user_is_redirected_from_device_setup(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/kasir/device-setup');

        $response->assertRedirect('/kasir/login');
    }

    public function test_revoke_device_forgets_cookie_and_redirects(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/kasir/device-revoke');

        $response->assertRedirect('/kasir/device-setup');
        $response->assertSessionHas('status', 'Otorisasi perangkat ini telah berhasil dicabut.');
        $response->assertCookieExpired('kasir_device_token');
    }

    public function test_owner_can_regenerate_device_secret(): void
    {
        $user = User::factory()->create();
        $oldSecret = KasirLoginController::getDeviceSecret();

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/kasir/device-secret/regenerate');

        $response->assertRedirect('/kasir/device-setup');
        $response->assertSessionHas('status');

        $newSecret = KasirLoginController::getDeviceSecret();
        $this->assertNotEquals($oldSecret, $newSecret);
        $this->assertStringStartsWith('pos-sec-', $newSecret);
    }

    public function test_owner_can_regenerate_device_secret_with_revoke_all_exempting_current_device(): void
    {
        $user = User::factory()->create();

        $tokenDeviceA = Str::random(64);
        $deviceA = KasirAuthorizedDevice::create([
            'device_name' => 'Tablet Eksekutor (Owner)',
            'device_token_hash' => hash('sha256', $tokenDeviceA),
            'device_type' => 'tablet',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        $tokenDeviceB = Str::random(64);
        $deviceB = KasirAuthorizedDevice::create([
            'device_name' => 'Tablet Kasir Lain',
            'device_token_hash' => hash('sha256', $tokenDeviceB),
            'device_type' => 'tablet',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withCookie('kasir_device_token', $tokenDeviceA)
            ->post('/kasir/device-secret/regenerate', [
                'revoke_all_devices' => '1',
            ]);

        $response->assertRedirect('/kasir/device-setup');
        $this->assertFalse($deviceA->fresh()->is_revoked);
        $this->assertTrue($deviceB->fresh()->is_revoked);
    }

    public function test_artisan_command_generates_new_secret(): void
    {
        $this->artisan('kasir:generate-secret')
            ->expectsOutputToContain('KASIR_DEVICE_SECRET baru berhasil digenerate')
            ->assertExitCode(0);

        $secret = KasirLoginController::getDeviceSecret();
        $this->assertStringStartsWith('pos-sec-', $secret);
    }

    public function test_revoked_device_is_blocked_even_on_allowed_ip(): void
    {
        Config::set('cafe.kasir_ip_restriction_enabled', true);
        Config::set('cafe.kasir_allowed_ips', ['127.0.0.1', '192.168.1.*']);

        $token = Str::random(64);
        KasirAuthorizedDevice::create([
            'device_name' => 'Tablet Diblokir',
            'device_token_hash' => hash('sha256', $token),
            'device_type' => 'tablet',
            'is_revoked' => true,
            'revoked_at' => now(),
            'last_active_at' => now(),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withCookie('kasir_device_token', $token)
            ->get('/kasir/login');

        $response->assertStatus(403);
        $response->assertSee('Otorisasi Dicabut');
    }

    public function test_revoked_device_cannot_reregister_via_qr_authorization(): void
    {
        $secret = 'test-secret-device-key';
        Config::set('cafe.kasir_device_secret', $secret);

        $token = Str::random(64);
        $device = KasirAuthorizedDevice::create([
            'device_name' => 'HP Karyawan',
            'device_token_hash' => hash('sha256', $token),
            'device_type' => 'mobile',
            'is_revoked' => true,
            'revoked_at' => now(),
            'last_active_at' => now(),
        ]);

        $initialCount = KasirAuthorizedDevice::count();

        $response = $this->withCookie('kasir_device_token', $token)
            ->get('/kasir/authorize-device?key='.$secret);

        $response->assertRedirect('/kasir/login');
        $response->assertSessionHas('error');
        $this->assertEquals($initialCount, KasirAuthorizedDevice::count());
        $this->assertTrue($device->fresh()->is_revoked);
    }

    public function test_active_device_rescanning_qr_does_not_create_duplicate_record(): void
    {
        $secret = 'test-secret-device-key';
        Config::set('cafe.kasir_device_secret', $secret);

        $token = Str::random(64);
        $device = KasirAuthorizedDevice::create([
            'device_name' => 'Tablet Barista',
            'device_token_hash' => hash('sha256', $token),
            'device_type' => 'tablet',
            'is_revoked' => false,
            'last_active_at' => now()->subDay(),
        ]);

        $initialCount = KasirAuthorizedDevice::count();

        $response = $this->withCookie('kasir_device_token', $token)
            ->get('/kasir/authorize-device?key='.$secret);

        $response->assertRedirect('/kasir/login');
        $response->assertSessionHas('status');
        $this->assertEquals($initialCount, KasirAuthorizedDevice::count());
    }

    public function test_owner_can_delete_remote_device_via_ajax_receiving_json(): void
    {
        $user = User::factory()->create();

        $device = KasirAuthorizedDevice::create([
            'device_name' => 'Tablet Usang AJAX',
            'device_token_hash' => hash('sha256', Str::random(64)),
            'device_type' => 'tablet',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->deleteJson("/kasir/devices/{$device->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('kasir_authorized_devices', ['id' => $device->id]);
    }

    public function test_owner_can_revoke_and_restore_device_via_ajax_receiving_json(): void
    {
        $user = User::factory()->create();

        $device = KasirAuthorizedDevice::create([
            'device_name' => 'Tablet AJAX Test',
            'device_token_hash' => hash('sha256', Str::random(64)),
            'device_type' => 'tablet',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        // 1. Revoke via AJAX
        $revokeResponse = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson("/kasir/devices/{$device->id}/revoke");

        $revokeResponse->assertStatus(200);
        $revokeResponse->assertJson(['success' => true]);
        $this->assertTrue($device->fresh()->is_revoked);

        // 2. Restore via AJAX
        $restoreResponse = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson("/kasir/devices/{$device->id}/restore");

        $restoreResponse->assertStatus(200);
        $restoreResponse->assertJson(['success' => true]);
        $this->assertFalse($device->fresh()->is_revoked);
    }

    public function test_owner_can_generate_one_time_enrollment_token_via_api(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/kasir/device-enrollment/create');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ok',
            'message',
            'token',
            'qr_code_uri',
            'authorize_url',
            'expires_at',
            'ttl_minutes',
        ]);

        $token = $response->json('token');
        $this->assertTrue(Cache::has("kasir_enrollment_token_{$token}"));
    }

    public function test_device_can_be_authorized_using_one_time_token_and_token_is_consumed(): void
    {
        // 1. Owner generates enrollment token
        $token = Str::random(40);
        Cache::put("kasir_enrollment_token_{$token}", [
            'created_at' => now()->timestamp,
        ], now()->addMinutes(15));

        $this->assertTrue(Cache::has("kasir_enrollment_token_{$token}"));

        // 2. Device scans QR and visits authorize endpoint with token
        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.88'])
            ->get("/kasir/authorize-device?token={$token}");

        $response->assertRedirect('/kasir/login');
        $response->assertCookie('kasir_device_token');
        $response->assertSessionHas('status', 'Perangkat ini berhasil diotorisasi sebagai terminal kasir resmi!');

        // 3. Verify token is consumed atomically (Cache::pull)
        $this->assertFalse(Cache::has("kasir_enrollment_token_{$token}"));

        // 4. Second device/browser attempts to use the same token -> Rejected!
        $secondResponse = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
            ->get("/kasir/authorize-device?token={$token}");

        $secondResponse->assertRedirect('/kasir/login');
        $secondResponse->assertSessionHas('error', 'Kode QR otorisasi ini sudah pernah digunakan atau telah kadaluarsa (berlaku 15 menit). Minta Owner untuk membuat kode QR baru.');
    }

    public function test_expired_or_invalid_one_time_token_is_rejected(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.88'])
            ->get('/kasir/authorize-device?token=non-existent-token');

        $response->assertRedirect('/kasir/login');
        $response->assertSessionHas('error', 'Kode QR otorisasi ini sudah pernah digunakan atau telah kadaluarsa (berlaku 15 menit). Minta Owner untuk membuat kode QR baru.');
    }

    public function test_owner_can_fetch_device_fleet_data_in_realtime(): void
    {
        $user = User::factory()->create();

        $deviceA = KasirAuthorizedDevice::create([
            'device_token_hash' => hash('sha256', 'token-a'),
            'device_name' => 'Tablet Kasir Kasir 1',
            'device_type' => 'tablet',
            'platform' => 'iPadOS',
            'browser' => 'Safari',
            'ip_address' => '192.168.1.10',
            'is_revoked' => false,
            'last_used_at' => now(),
        ]);

        $deviceB = KasirAuthorizedDevice::create([
            'device_token_hash' => hash('sha256', 'token-b'),
            'device_name' => 'HP Waiter 2',
            'device_type' => 'mobile',
            'platform' => 'Android',
            'browser' => 'Chrome',
            'ip_address' => '192.168.1.11',
            'is_revoked' => true,
            'revoked_at' => now(),
            'last_used_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->getJson('/kasir/device-fleet/data');

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
            'kpi' => [
                'total' => 2,
                'active' => 1,
                'revoked' => 1,
            ],
        ]);

        $response->assertJsonStructure([
            'ok',
            'devices' => [
                '*' => [
                    'id',
                    'device_name',
                    'device_type',
                    'platform',
                    'browser',
                    'ip_address',
                    'is_revoked',
                    'is_current',
                    'last_active_at',
                    'created_at_formatted',
                    'revoke_url',
                    'restore_url',
                    'delete_url',
                    'rename_url',
                ],
            ],
            'kpi' => ['total', 'active', 'revoked'],
            'enrollment' => ['token', 'qr_code_uri', 'authorize_url'],
        ]);
    }
}
