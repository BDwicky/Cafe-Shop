<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.telegram.bot_token', '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11');
        Config::set('services.telegram.owner_chat_id', '987654321');
        Config::set('services.telegram.enabled', true);
    }

    public function test_telegram_service_configuration_detection(): void
    {
        $service = app(TelegramService::class);
        $this->assertTrue($service->isConfigured());

        Config::set('services.telegram.bot_token', '');
        $this->assertFalse($service->isConfigured());

        Config::set('services.telegram.bot_token', 'valid-token');
        Config::set('services.telegram.enabled', false);
        $this->assertFalse($service->isConfigured());
    }

    public function test_transaction_checkout_triggers_telegram_notification(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $user = User::factory()->create();
        $menu = Menu::factory()->create(['name' => 'Signature Espresso Blend', 'price' => 28000]);

        $response = $this->actingAs($user)->postJson('/kasir/orders', [
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 2, 'note' => 'Extra Shot'],
            ],
            'order_type' => 'dine_in',
            'payment_method' => 'qris',
            'discount' => 0,
            'paid_amount' => 56000,
            'customer_name' => 'Pak Hendra',
        ]);

        $response->assertCreated();
        $orderCode = $response->json('code');

        Http::assertSent(function ($request) use ($orderCode) {
            $data = $request->data();

            return str_contains($request->url(), 'sendMessage') &&
                $data['chat_id'] === '987654321' &&
                str_contains($data['text'], $orderCode) &&
                str_contains($data['text'], 'Signature Espresso Blend') &&
                str_contains($data['text'], 'Pak Hendra') &&
                str_contains($data['text'], 'QRIS');
        });
    }

    public function test_transaction_checkout_still_succeeds_even_if_telegram_fails(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Service Unavailable'], 503),
        ]);

        $user = User::factory()->create();
        $menu = Menu::factory()->create(['price' => 20000]);

        $response = $this->actingAs($user)->postJson('/kasir/orders', [
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
            'order_type' => 'take_away',
            'payment_method' => 'cash',
            'discount' => 0,
            'paid_amount' => 20000,
            'customer_name' => 'Rina',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('orders', ['customer_name' => 'Rina']);
    }

    public function test_telegram_sales_recap_summarizes_all_menus_sold(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $user = User::factory()->create();

        // 1. Buat transaksi dalam 7 hari terakhir
        $order1 = Order::factory()->create([
            'status' => 'paid',
            'total' => 60000,
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'menu_name' => 'Caramel Macchiato Ice',
            'price' => 30000,
            'qty' => 2,
            'line_total' => 60000,
        ]);

        $order2 = Order::factory()->create([
            'status' => 'paid',
            'total' => 45000,
            'user_id' => $user->id,
            'created_at' => now()->subDays(1),
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'menu_name' => 'Almond Croissant',
            'price' => 25000,
            'qty' => 1,
            'line_total' => 25000,
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'menu_name' => 'Americano Hot',
            'price' => 20000,
            'qty' => 1,
            'line_total' => 20000,
        ]);

        $service = app(TelegramService::class);
        $result = $service->sendSalesRecap('7days');

        $this->assertTrue($result['success']);
        $this->assertSame(105000, $result['total_omzet']);
        $this->assertSame(2, $result['total_trx']);
        $this->assertSame(4, $result['total_items']);

        Http::assertSent(function ($request) {
            $text = $request->data()['text'];

            return str_contains($text, 'REKAP PENJUALAN 7 HARI TERAKHIR') &&
                str_contains($text, 'Caramel Macchiato Ice') &&
                str_contains($text, 'Almond Croissant') &&
                str_contains($text, 'Americano Hot') &&
                str_contains($text, 'Rp 105.000');
        });
    }

    public function test_telegram_sales_recap_monthly_period(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $service = app(TelegramService::class);
        $result = $service->sendSalesRecap('month');

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            return str_contains($request->data()['text'], 'BULAN INI');
        });
    }

    public function test_console_command_telegram_sales_recap_runs_successfully(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->artisan('telegram:sales-recap', ['--period' => '7days'])
            ->expectsOutputToContain('Menyiapkan rekapitulasi penjualan menu (7days)...')
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            return str_contains($request->data()['text'], '7 HARI TERAKHIR');
        });
    }

    public function test_daily_sales_recap_runs_successfully(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->artisan('telegram:sales-recap', ['--period' => 'today'])
            ->expectsOutputToContain('Menyiapkan rekapitulasi penjualan menu (today)...')
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            return str_contains($request->data()['text'], 'HARI INI');
        });
    }

    public function test_telegram_webhook_processes_incoming_commands(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $response = $this->postJson('/telegram/webhook', [
            'update_id' => 123456,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 987654321,
                    'first_name' => 'Owner',
                ],
                'chat' => [
                    'id' => 987654321,
                    'type' => 'private',
                ],
                'text' => '/hari',
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        Http::assertSent(function ($request) {
            return str_contains($request->data()['text'], 'HARI INI');
        });
    }

    public function test_laporan_send_telegram_endpoint_responds_successfully(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/kasir/laporan/send-telegram', [
                'period' => 'today',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
    }
}
