<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    public function test_cashier_creates_order_with_item_snapshots(): void
    {
        $user = User::factory()->create();
        $menu = Menu::factory()->create(['price' => 25000]);

        $r = $this->actingAs($user)->postJson('/kasir/orders', [
            'items' => [['menu_id' => $menu->id, 'qty' => 2]],
            'order_type' => 'take_away',
            'payment_method' => 'cash',
            'discount' => 5000,
            'paid_amount' => 50000,
            'customer_name' => 'Budi',
        ]);

        $r->assertCreated()
            ->assertJsonPath('code', fn ($c) => str_starts_with($c, 'KKI-'))
            ->assertJsonPath('music_code', fn ($mc) => str_starts_with($mc, 'MK-'))
            ->assertJsonPath('total', 45000)
            ->assertJsonPath('paid_amount', 50000)
            ->assertJsonPath('change_amount', 5000)
            ->assertJsonPath('receipt_url', fn ($url) => str_contains($url, '/kasir/orders/'));

        $order = Order::first();
        $this->assertSame(45000, $order->total);
        $this->assertSame(5000, $order->discount);
        $this->assertSame(5000, $order->change_amount);
        $this->assertSame('Budi', $order->customer_name);
        $this->assertSame($menu->name, $order->items[0]->menu_name); // snapshot
        $this->assertSame(25000, (int) $order->items[0]->price);
    }

    public function test_guest_cannot_create_order(): void
    {
        $this->postJson('/kasir/orders', ['items' => []])->assertUnauthorized();
    }

    public function test_cannot_order_unavailable_menu(): void
    {
        $user = User::factory()->create();
        $menu = Menu::factory()->create(['price' => 25000, 'is_available' => false]);

        $r = $this->actingAs($user)->postJson('/kasir/orders', [
            'items' => [['menu_id' => $menu->id, 'qty' => 1]],
            'order_type' => 'take_away',
            'payment_method' => 'cash',
            'paid_amount' => 25000,
        ]);

        $r->assertStatus(422)
            ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'stok kosong'));
    }
}
