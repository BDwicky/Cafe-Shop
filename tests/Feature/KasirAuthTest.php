<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class KasirAuthTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/kasir')->assertRedirect('/kasir/login');
    }

    public function test_kasir_can_login(): void
    {
        $u = User::factory()->create(['password' => bcrypt('kopikita123')]);

        $r = $this->post('/kasir/login', ['email' => $u->email, 'password' => 'kopikita123']);

        $r->assertRedirect('/kasir');
        $this->assertAuthenticatedAs($u);
    }

    public function test_wrong_password_rejected(): void
    {
        $u = User::factory()->create(['password' => bcrypt('kopikita123')]);

        $this->from('/kasir/login')
            ->post('/kasir/login', ['email' => $u->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
