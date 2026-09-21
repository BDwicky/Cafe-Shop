<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
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

    public function test_device_with_valid_token_can_access_even_from_unauthorized_ip(): void
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

    public function test_authorize_device_route_sets_valid_cookie_and_redirects(): void
    {
        $secret = 'test-secret-device-key';
        Config::set('cafe.kasir_device_secret', $secret);

        $response = $this->get('/kasir/authorize-device?key='.$secret);

        $response->assertRedirect('/kasir/login');
        $response->assertSessionHas('status', 'Perangkat ini berhasil diotorisasi sebagai terminal kasir resmi!');

        $expectedToken = hash_hmac('sha256', 'kopikita-authorized-pos-device', $secret);
        $response->assertCookie('kasir_device_token', $expectedToken);
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
}
