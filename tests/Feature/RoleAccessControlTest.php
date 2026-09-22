<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    public function test_kasir_can_access_shared_operational_pages(): void
    {
        $kasir = User::factory()->kasir()->create();

        $this->actingAs($kasir)->get('/kasir')->assertOk();
        $this->actingAs($kasir)->get('/kasir/kitchen')->assertOk();
        $this->actingAs($kasir)->get('/kasir/orders')->assertOk();
        $this->actingAs($kasir)->get('/kasir/inventory')->assertOk();
        $this->actingAs($kasir)->get('/kasir/expenses')->assertOk();
    }

    public function test_kasir_cannot_access_owner_pages_and_is_redirected_with_alert(): void
    {
        $kasir = User::factory()->kasir()->create();

        $ownerOnlyPages = [
            '/kasir/laporan',
            '/kasir/menu',
            '/kasir/promos',
            '/kasir/inventory/recipes',
            '/kasir/device-setup',
        ];

        foreach ($ownerOnlyPages as $url) {
            $response = $this->actingAs($kasir)->get($url);

            $response->assertRedirect('/kasir');
            $response->assertSessionHas('alert', 'Akses ditolak: Halaman ini hanya dapat diakses oleh Owner.');
        }
    }

    public function test_kasir_json_request_to_owner_route_returns_403_forbidden(): void
    {
        $kasir = User::factory()->kasir()->create();

        $response = $this->actingAs($kasir)->getJson('/kasir/device-fleet/data');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Akses ditolak: Fitur ini hanya dapat diakses oleh Owner.',
        ]);
    }

    public function test_owner_can_access_all_pages(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/kasir')->assertOk();
        $this->actingAs($owner)->get('/kasir/kitchen')->assertOk();
        $this->actingAs($owner)->get('/kasir/orders')->assertOk();
        $this->actingAs($owner)->get('/kasir/inventory')->assertOk();
        $this->actingAs($owner)->get('/kasir/expenses')->assertOk();
        $this->actingAs($owner)->get('/kasir/laporan')->assertOk();
        $this->actingAs($owner)->get('/kasir/menu')->assertOk();
        $this->actingAs($owner)->get('/kasir/promos')->assertOk();
        $this->actingAs($owner)->get('/kasir/inventory/recipes')->assertOk();
        $this->actingAs($owner)->get('/kasir/device-setup')->assertOk();
    }

    public function test_sidebar_displays_kasir_view_correctly(): void
    {
        $kasir = User::factory()->kasir()->create(['name' => 'Siti Kasir']);

        $response = $this->actingAs($kasir)->get('/kasir');

        $response->assertOk();
        $response->assertSee('☕ Kasir');
        $response->assertDontSee('👑 Owner');
        $response->assertDontSee('Katalog & Promo');
        $response->assertDontSee('href="'.route('kasir.menu.index').'"', false);
        $response->assertDontSee('href="'.route('kasir.promos.index').'"', false);
        $response->assertDontSee('href="'.route('kasir.inventory.recipes').'"', false);
        $response->assertDontSee('href="'.route('kasir.laporan').'"', false);
        $response->assertDontSee('href="'.route('kasir.device-setup').'"', false);
    }

    public function test_sidebar_displays_owner_view_correctly(): void
    {
        $owner = User::factory()->owner()->create(['name' => 'Budi Owner']);

        $response = $this->actingAs($owner)->get('/kasir');

        $response->assertOk();
        $response->assertSee('👑 Owner');
        $response->assertDontSee('☕ Kasir');
        $response->assertSee('Katalog & Promo', false);
        $response->assertSee('href="'.route('kasir.menu.index').'"', false);
        $response->assertSee('href="'.route('kasir.promos.index').'"', false);
        $response->assertSee('href="'.route('kasir.inventory.recipes').'"', false);
        $response->assertSee('href="'.route('kasir.laporan').'"', false);
        $response->assertSee('href="'.route('kasir.device-setup').'"', false);
    }

    public function test_owner_login_redirects_to_laporan(): void
    {
        $owner = User::factory()->owner()->create([
            'email' => 'owner@kopikita.test',
            'password' => bcrypt('123123'),
        ]);

        $res = $this->post('/kasir/login', [
            'email' => 'owner',
            'password' => '123123',
        ]);

        $res->assertRedirect(route('kasir.laporan'));
    }

    public function test_kasir_login_redirects_to_terminal(): void
    {
        $kasir = User::factory()->kasir()->create([
            'email' => 'kasir@kopikita.test',
            'password' => bcrypt('kopikita123'),
        ]);

        $res = $this->post('/kasir/login', [
            'email' => 'kasir',
            'password' => 'kopikita123',
        ]);

        $res->assertRedirect('/kasir');
    }

    public function test_kasir_cannot_mutate_master_ingredients(): void
    {
        $kasir = User::factory()->kasir()->create();

        // Create ingredient as kasir
        $resStore = $this->actingAs($kasir)->post('/kasir/inventory', [
            'name' => 'Biji Kopi Arabika Baru',
            'unit' => 'gr',
            'category' => 'coffee_beans',
            'current_stock' => 1000,
            'minimum_stock' => 200,
            'cost_per_unit' => 300,
        ]);
        $resStore->assertRedirect('/kasir');
        $resStore->assertSessionHas('alert', 'Akses ditolak: Halaman ini hanya dapat diakses oleh Owner.');
    }

    public function test_kasir_cannot_delete_expense(): void
    {
        $kasir = User::factory()->kasir()->create();
        $expense = Expense::create([
            'user_id' => $kasir->id,
            'title' => 'Beli Es Batu Darurat',
            'category' => 'operational',
            'amount' => 15000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $res = $this->actingAs($kasir)->delete("/kasir/expenses/{$expense->id}");
        $res->assertRedirect('/kasir');
        $res->assertSessionHas('alert', 'Akses ditolak: Halaman ini hanya dapat diakses oleh Owner.');

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_inventory_and_expense_views_hide_owner_actions_from_kasir(): void
    {
        $kasir = User::factory()->kasir()->create();
        $expense = Expense::create([
            'user_id' => $kasir->id,
            'title' => 'Beli Es Batu',
            'category' => 'operational',
            'amount' => 10000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        // Kasir view in inventory
        $resInv = $this->actingAs($kasir)->get('/kasir/inventory');
        $resInv->assertOk();
        $resInv->assertDontSee('+ Bahan Baru ›');
        $resInv->assertDontSee('title="Edit Master Data"', false);
        $resInv->assertDontSee('title="Hapus Bahan"', false);

        // Kasir view in expenses
        $resExp = $this->actingAs($kasir)->get('/kasir/expenses');
        $resExp->assertOk();
        $resExp->assertDontSee('✕ Hapus');
    }

    public function test_owner_inject_artisan_command_works(): void
    {
        $this->artisan('owner:inject --email=testowner@kopikita.test --password=123123 --name="Owner Test"')
            ->assertSuccessful();

        $owner = User::where('email', 'testowner@kopikita.test')->first();
        $this->assertNotNull($owner);
        $this->assertSame('owner', $owner->role);
        $this->assertTrue(Hash::check('123123', $owner->password));
    }
}
