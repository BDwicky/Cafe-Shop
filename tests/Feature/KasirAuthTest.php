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
        $u = User::factory()->kasir()->create(['password' => bcrypt('kopikita123')]);

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

    public function test_owner_can_login_with_owner_alias(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'owner@kopikita.test'],
            ['name' => 'Owner KopiKita', 'password' => bcrypt('123123'), 'role' => 'owner']
        );

        $res = $this->post('/kasir/login', [
            'email' => 'owner',
            'password' => '123123',
        ]);

        $res->assertRedirect(route('kasir.laporan'));
        $this->assertAuthenticatedAs($owner);
    }

    public function test_kasir_can_login_with_kasir_alias(): void
    {
        $kasir = User::firstOrCreate(
            ['email' => 'kasir@kopikita.test'],
            ['name' => 'Kasir KopiKita', 'password' => bcrypt('kopikita123')]
        );

        $res = $this->post('/kasir/login', [
            'email' => 'kasir',
            'password' => 'kopikita123',
        ]);

        $res->assertRedirect('/kasir');
        $this->assertAuthenticatedAs($kasir);
    }
}
