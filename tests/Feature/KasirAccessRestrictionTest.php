<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\KasirLoginController;
use App\Models\KasirAuthorizedDevice;
use App\Models\User;
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
}
