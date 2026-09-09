<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Tests\TestCase;

class ReportTest extends TestCase
{
    public function test_report_excludes_voided_orders(): void
    {
        Order::factory()->create(['total' => 50000, 'status' => 'paid', 'created_at' => now()]);
        Order::factory()->create(['total' => 30000, 'status' => 'paid', 'created_at' => now()]);
        Order::factory()->create(['total' => 99000, 'status' => 'voided', 'created_at' => now()]);

        $r = $this->actingAs(User::factory()->create())->get('/kasir/laporan');

        $r->assertOk()
            ->assertSee('2')      // 2 transaksi
            ->assertSee('80.000') // omzet 80.000
            ->assertDontSee('99.000');
    }

    public function test_report_shows_best_sellers(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);
        OrderItem::create([
            'order_id' => $order->id,
            'menu_id' => null,
            'menu_name' => 'Americano',
            'price' => 22000,
            'qty' => 3,
            'line_total' => 66000,
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/kasir/laporan')
            ->assertOk()
            ->assertSee('Americano');
    }

    public function test_report_supports_date_range(): void
    {
        Order::factory()->create(['total' => 50000, 'status' => 'paid', 'created_at' => now()->subDays(3)]);

        $today = now()->format('Y-m-d');
        $threeDaysAgo = now()->subDays(3)->format('Y-m-d');

        $this->actingAs(User::factory()->create())
            ->get("/kasir/laporan?from={$threeDaysAgo}&to={$threeDaysAgo}")
            ->assertOk()
            ->assertSee('50.000');

        $this->actingAs(User::factory()->create())
            ->get("/kasir/laporan?from={$today}&to={$today}")
            ->assertOk()
            ->assertSee('0');
    }

    public function test_report_receipt_view_renders_thermal_format(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['total' => 75000, 'status' => 'paid', 'created_at' => now()]);
        OrderItem::create([
            'order_id' => $order->id,
            'menu_id' => null,
            'menu_name' => 'Caramel Macchiato',
            'price' => 32000,
            'qty' => 2,
            'line_total' => 64000,
        ]);

        $this->actingAs($user)
            ->get('/kasir/laporan/receipt')
            ->assertOk()
            ->assertSee('*** LAPORAN KASIR ***')
            ->assertSee('Caramel Macchiato')
            ->assertSee('75.000');
    }

    public function test_guest_cannot_access_report_receipt(): void
    {
        $this->get('/kasir/laporan/receipt')
            ->assertRedirect('/kasir/login');
    }
}
