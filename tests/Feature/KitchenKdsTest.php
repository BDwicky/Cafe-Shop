<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Tests\TestCase;

class KitchenKdsTest extends TestCase
{
    public function test_new_order_defaults_to_prep_pending(): void
    {
        $order = Order::factory()->create();

        $this->assertSame('pending', $order->prep_status);
        $this->assertNull($order->ready_at);
        $this->assertNull($order->announced_at);
        $this->assertNull($order->completed_at);
    }

    public function test_kitchen_can_view_active_orders_filtered_by_station(): void
    {
        $user = User::factory()->create();

        $drinkCat = Category::create(['name' => 'Kopi', 'slug' => 'kopi', 'sort_order' => 0]);
        $foodCat = Category::create(['name' => 'Snack', 'slug' => 'snack', 'sort_order' => 1]);

        $drinkMenu = Menu::create([
            'category_id' => $drinkCat->id,
            'name' => 'Latte',
            'slug' => 'latte',
            'price' => 20000,
            'is_available' => true,
        ]);

        $foodMenu = Menu::create([
            'category_id' => $foodCat->id,
            'name' => 'Croissant',
            'slug' => 'croissant',
            'price' => 25000,
            'is_available' => true,
        ]);

        // Order 1: Minuman saja
        $orderDrink = Order::factory()->create(['status' => 'paid', 'customer_name' => 'Pelanggan Minum']);
        OrderItem::create([
            'order_id' => $orderDrink->id,
            'menu_id' => $drinkMenu->id,
            'menu_name' => $drinkMenu->name,
            'price' => 20000,
            'qty' => 1,
            'line_total' => 20000,
        ]);

        // Order 2: Makanan saja
        $orderFood = Order::factory()->create(['status' => 'paid', 'customer_name' => 'Pelanggan Makan']);
        OrderItem::create([
            'order_id' => $orderFood->id,
            'menu_id' => $foodMenu->id,
            'menu_name' => $foodMenu->name,
            'price' => 25000,
            'qty' => 1,
            'line_total' => 25000,
        ]);

        // Filter Barista: hanya order minuman
        $resBarista = $this->actingAs($user)->getJson(route('kasir.kitchen.orders', ['station' => 'barista']));
        $resBarista->assertOk()
            ->assertJsonFragment(['customer_name' => 'Pelanggan Minum'])
            ->assertJsonMissing(['customer_name' => 'Pelanggan Makan']);

        // Filter Kitchen: hanya order makanan
        $resKitchen = $this->actingAs($user)->getJson(route('kasir.kitchen.orders', ['station' => 'kitchen']));
        $resKitchen->assertOk()
            ->assertJsonFragment(['customer_name' => 'Pelanggan Makan'])
            ->assertJsonMissing(['customer_name' => 'Pelanggan Minum']);

        // Filter All: kedua order muncul
        $resAll = $this->actingAs($user)->getJson(route('kasir.kitchen.orders', ['station' => 'all']));
        $resAll->assertOk()
            ->assertJsonFragment(['customer_name' => 'Pelanggan Minum'])
            ->assertJsonFragment(['customer_name' => 'Pelanggan Makan']);
    }

    public function test_kitchen_can_mark_order_as_preparing_and_ready(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['status' => 'paid', 'prep_status' => 'pending']);

        // 1. Mulai buat (preparing)
        $this->actingAs($user)
            ->postJson(route('kasir.kitchen.status', $order), ['status' => 'preparing'])
            ->assertOk();

        $this->assertSame('preparing', $order->fresh()->prep_status);

        // 2. Pesanan Siap (ready)
        $this->actingAs($user)
            ->postJson(route('kasir.kitchen.status', $order), ['status' => 'ready'])
            ->assertOk();

        $this->assertSame('ready', $order->fresh()->prep_status);
        $this->assertNotNull($order->fresh()->ready_at);
        $this->assertNull($order->fresh()->announced_at);
    }

    public function test_sound_station_can_fetch_and_mark_unannounced_ready_orders(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'status' => 'paid',
            'prep_status' => 'ready',
            'ready_at' => now(),
            'announced_at' => null,
            'customer_name' => 'Ahmad Kasir',
        ]);

        // Sound Station mendeteksi pesanan ready
        $res = $this->actingAs($user)->getJson(route('kasir.music.announcements.pending'));
        $res->assertOk()
            ->assertJsonPath('orders.0.code', $order->code)
            ->assertJsonPath('orders.0.customer_name', 'Ahmad Kasir');

        // Sound Station menandai sudah diumumkan
        $this->actingAs($user)
            ->postJson(route('kasir.music.announcements.mark', $order))
            ->assertOk();

        $this->assertNotNull($order->fresh()->announced_at);

        // Tidak lagi muncul di pending announcements
        $resAfter = $this->actingAs($user)->getJson(route('kasir.music.announcements.pending'));
        $resAfter->assertOk()
            ->assertJsonCount(0, 'orders');
    }

    public function test_cashier_can_mark_order_as_completed_upon_pickup(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'status' => 'paid',
            'prep_status' => 'ready',
        ]);

        $this->actingAs($user)
            ->postJson(route('kasir.kitchen.status', $order), ['status' => 'completed'])
            ->assertOk();

        $this->assertSame('completed', $order->fresh()->prep_status);
        $this->assertNotNull($order->fresh()->completed_at);
    }

    public function test_kitchen_can_recall_order_for_reannouncement(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'status' => 'paid',
            'prep_status' => 'ready',
            'announced_at' => now(),
        ]);

        // Panggil ulang
        $this->actingAs($user)
            ->postJson(route('kasir.kitchen.recall', $order))
            ->assertOk();

        $this->assertNull($order->fresh()->announced_at);

        // Sekarang muncul kembali di pending announcements
        $res = $this->actingAs($user)->getJson(route('kasir.music.announcements.pending'));
        $res->assertOk()
            ->assertJsonPath('orders.0.code', $order->code);
    }

    public function test_kitchen_ticket_renders_without_errors(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $this->actingAs($user)
            ->get(route('kasir.kitchen.ticket', $order))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('TIKET');
    }
}
