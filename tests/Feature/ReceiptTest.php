<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    public function test_receipt_shows_code_items_and_totals(): void
    {
        $order = Order::factory()->has(OrderItem::factory()->count(2), 'items')->create();

        $this->actingAs(User::factory()->create())
            ->get("/kasir/orders/{$order->id}/receipt")
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee($order->items[0]->menu_name);
    }

    public function test_receipt_contains_qr_code_pointing_to_landing(): void
    {
        $order = Order::factory()->has(OrderItem::factory()->count(1), 'items')->create();

        $r = $this->actingAs(User::factory()->create())
            ->get("/kasir/orders/{$order->id}/receipt");

        $r->assertOk()->assertSee('data:image/png;base64'); // QR inline Data-URI
        $this->assertStringContainsString(config('app.url'), $r->getContent()); // QR menunjuk landing
    }

    public function test_receipt_shows_wifi_info(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/kasir/orders/{$order->id}/receipt")
            ->assertOk()
            ->assertSee(config('cafe.wifi_ssid'))
            ->assertSee(config('cafe.wifi_password'));
    }

    public function test_void_changes_status(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        $this->actingAs(User::factory()->create())
            ->post("/kasir/orders/{$order->id}/void")
            ->assertRedirect();

        $this->assertSame('voided', $order->fresh()->status);
    }

    public function test_voided_order_cannot_be_voided_again(): void
    {
        $order = Order::factory()->create(['status' => 'voided']);

        $this->actingAs(User::factory()->create())
            ->post("/kasir/orders/{$order->id}/void")
            ->assertStatus(422);
    }

    public function test_history_shows_orders_with_void_marker(): void
    {
        Order::factory()->create(['code' => 'KKI-OK-0001', 'status' => 'paid']);
        Order::factory()->create(['code' => 'KKI-VOID-0002', 'status' => 'voided']);

        $this->actingAs(User::factory()->create())
            ->get('/kasir/orders')
            ->assertOk()
            ->assertSee('KKI-OK-0001')
            ->assertSee('KKI-VOID-0002')
            ->assertSee('VOID');
    }
}
