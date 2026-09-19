<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Order;
use App\Models\Promo;
use App\Models\User;
use App\Services\PromoService;
use Tests\TestCase;

class PromoCodeTest extends TestCase
{
    public function test_promo_model_percentage_discount_with_max_cap(): void
    {
        $promo = Promo::create([
            'code' => 'TEST20',
            'name' => 'Diskon 20% Maks 15rb',
            'type' => 'percentage',
            'discount_value' => 20,
            'max_discount' => 15000,
            'min_order' => 35000,
            'is_active' => true,
        ]);

        // Subtotal 100.000: 20% = 20.000, tapi kena batas max_discount 15.000
        $this->assertSame(15000, $promo->calculateDiscount(100000));

        // Subtotal 50.000: 20% = 10.000 (< 15.000)
        $this->assertSame(10000, $promo->calculateDiscount(50000));

        // Subtotal 30.000: kurang dari min_order (35.000)
        $this->assertSame(0, $promo->calculateDiscount(30000));
    }

    public function test_promo_model_fixed_discount(): void
    {
        $promo = Promo::create([
            'code' => 'TESTFIXED',
            'name' => 'Potongan 5rb',
            'type' => 'fixed',
            'discount_value' => 5000,
            'max_discount' => null,
            'min_order' => 25000,
            'is_active' => true,
        ]);

        // Subtotal 40.000: potongan 5.000
        $this->assertSame(5000, $promo->calculateDiscount(40000));

        // Subtotal 20.000: kurang dari min_order
        $this->assertSame(0, $promo->calculateDiscount(20000));
    }

    public function test_promo_validations_and_rejections(): void
    {
        $service = new PromoService;

        // 1. Promo tidak aktif
        $inactive = Promo::create([
            'code' => 'INACTIVE',
            'name' => 'Promo Nonaktif',
            'type' => 'fixed',
            'discount_value' => 5000,
            'is_active' => false,
        ]);
        $res = $service->validateAndCalculate('INACTIVE', 50000);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('tidak aktif', $res['message']);

        // 2. Promo kadaluarsa
        $expired = Promo::create([
            'code' => 'EXPIRED',
            'name' => 'Promo Kadaluarsa',
            'type' => 'fixed',
            'discount_value' => 5000,
            'end_date' => now()->subDay(),
            'is_active' => true,
        ]);
        $res = $service->validateAndCalculate('EXPIRED', 50000);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('kadaluarsa', $res['message']);

        // 3. Promo kuota habis
        $exhausted = Promo::create([
            'code' => 'HABIS',
            'name' => 'Promo Habis',
            'type' => 'fixed',
            'discount_value' => 5000,
            'usage_limit' => 5,
            'used_count' => 5,
            'is_active' => true,
        ]);
        $res = $service->validateAndCalculate('HABIS', 50000);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('Kuota', $res['message']);

        // 4. Promo tidak ditemukan
        $res = $service->validateAndCalculate('TIDAKADA', 50000);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('tidak ditemukan', $res['message']);
    }

    public function test_kasir_can_check_promo_endpoint(): void
    {
        $user = User::factory()->create();

        Promo::create([
            'code' => 'SENJA15',
            'name' => 'Promo Sore 15%',
            'type' => 'percentage',
            'discount_value' => 15,
            'max_discount' => 10000,
            'min_order' => 30000,
            'is_active' => true,
        ]);

        // Request valid
        $response = $this->actingAs($user)->postJson('/kasir/promo/check', [
            'code' => 'senja15', // lowercase should auto-uppercase
            'subtotal' => 40000,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 'SENJA15')
            ->assertJsonPath('discount', 6000); // 15% dari 40.000 = 6.000

        // Request invalid (subtotal kurang dari min_order)
        $responseFail = $this->actingAs($user)->postJson('/kasir/promo/check', [
            'code' => 'SENJA15',
            'subtotal' => 20000,
        ]);

        $responseFail->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('discount', 0);
    }

    public function test_cashier_checkout_with_promo_code_records_order_and_increments_used_count(): void
    {
        $user = User::factory()->create();
        $menu = Menu::factory()->create(['price' => 25000]);

        $promo = Promo::create([
            'code' => 'ORDERPROMO',
            'name' => 'Promo Transaksi 20%',
            'type' => 'percentage',
            'discount_value' => 20,
            'max_discount' => 15000,
            'min_order' => 35000,
            'usage_limit' => 50,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/kasir/orders', [
            'items' => [['menu_id' => $menu->id, 'qty' => 2]], // Subtotal: 50.000
            'order_type' => 'take_away',
            'payment_method' => 'cash',
            'promo_code' => 'ORDERPROMO',
            'discount' => 10000, // 20% dari 50.000 = 10.000
            'paid_amount' => 50000,
            'customer_name' => 'Rian',
        ]);

        $response->assertCreated()
            ->assertJsonPath('total', 40000)
            ->assertJsonPath('paid_amount', 50000)
            ->assertJsonPath('change_amount', 10000);

        $order = Order::latest('id')->first();
        $this->assertSame(40000, $order->total);
        $this->assertSame(10000, $order->discount);
        $this->assertSame('ORDERPROMO', $order->promo_code);
        $this->assertSame($promo->id, $order->promo_id);

        // Pastikan kuota used_count bertambah 1
        $promo->refresh();
        $this->assertSame(1, $promo->used_count);
    }
}
