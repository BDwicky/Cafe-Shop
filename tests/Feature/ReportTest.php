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
        $owner = User::factory()->create(['name' => 'Owner Dwicky', 'role' => 'owner']);
        $cashier = User::factory()->create(['name' => 'Barista Andre', 'role' => 'kasir']);
        $order = Order::factory()->create(['user_id' => $cashier->id, 'total' => 75000, 'status' => 'paid', 'created_at' => now()]);
        OrderItem::create([
            'order_id' => $order->id,
            'menu_id' => null,
            'menu_name' => 'Caramel Macchiato',
            'price' => 32000,
            'qty' => 2,
            'line_total' => 64000,
        ]);

        $this->actingAs($owner)
            ->get('/kasir/laporan/receipt')
            ->assertOk()
            ->assertSee('*** LAPORAN KASIR ***')
            ->assertSee('Caramel Macchiato')
            ->assertSee('75.000')
            ->assertSee('Barista Andre');
    }

    public function test_guest_cannot_access_report_receipt(): void
    {
        $this->get('/kasir/laporan/receipt')
            ->assertRedirect('/kasir/login');
    }

    public function test_owner_can_see_cashier_performance_breakdown(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'name' => 'Pak Bos Owner']);
        $cashierA = User::factory()->create(['role' => 'kasir', 'name' => 'Budi Kasir Pagi']);
        $cashierB = User::factory()->create(['role' => 'kasir', 'name' => 'Siti Kasir Malam']);

        Order::factory()->create([
            'user_id' => $cashierA->id,
            'total' => 150000,
            'status' => 'paid',
            'created_at' => now(),
        ]);
        Order::factory()->create([
            'user_id' => $cashierA->id,
            'total' => 50000,
            'status' => 'paid',
            'created_at' => now(),
        ]);
        Order::factory()->create([
            'user_id' => $cashierB->id,
            'total' => 70000,
            'status' => 'paid',
            'created_at' => now(),
        ]);
        Order::factory()->create([
            'user_id' => $cashierB->id,
            'total' => 45000,
            'status' => 'voided',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($owner)->get('/kasir/laporan');

        $response->assertOk()
            ->assertSee('Performa Kasir')
            ->assertSee('Budi Kasir Pagi')
            ->assertSee('200.000')
            ->assertSee('Siti Kasir Malam')
            ->assertSee('70.000')
            ->assertSee('1 batal');
    }

    public function test_report_filter_form_has_seamless_filter_handler(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $response = $this->actingAs($owner)->get('/kasir/laporan');

        $response->assertOk()
            ->assertSee('@submit.prevent="submitDateFilter()"', false)
            ->assertSee('submitDateFilter()', false)
            ->assertSee('swapKasirPage(url.href, true)', false);
    }
}
