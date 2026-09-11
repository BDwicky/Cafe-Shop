<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Menu;
use App\Models\MenuRecipe;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

class InventoryAndExpenseTest extends TestCase
{
    public function test_authenticated_cashier_can_view_inventory_list(): void
    {
        $user = User::factory()->create();
        Ingredient::factory()->create([
            'name' => 'Biji Kopi Arabica',
            'current_stock' => 500,
            'minimum_stock' => 100,
        ]);

        $response = $this->actingAs($user)->get('/kasir/inventory');

        $response->assertOk()
            ->assertSee('Stok & Inventaris Bahan Baku', false)
            ->assertSee('Biji Kopi Arabica')
            ->assertSee('Resep BOM Menu');
    }

    public function test_cashier_can_add_ingredient_and_records_initial_adjustment(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/kasir/inventory', [
            'name' => 'Fresh Milk Diamond',
            'code' => 'ING-MILK-01',
            'unit' => 'ml',
            'category' => 'dairy',
            'current_stock' => 1000,
            'minimum_stock' => 200,
            'cost_per_unit' => 25, // Rp 25/ml = Rp 25.000/liter
        ]);

        $response->assertRedirect('/kasir/inventory');

        $ingredient = Ingredient::where('name', 'Fresh Milk Diamond')->first();
        $this->assertNotNull($ingredient);
        $this->assertEquals(1000, $ingredient->current_stock);
        $this->assertEquals(25, $ingredient->cost_per_unit);

        // Mutasi awal harus tercatat
        $movement = InventoryMovement::where('ingredient_id', $ingredient->id)->first();
        $this->assertNotNull($movement);
        $this->assertSame('adjustment', $movement->type);
        $this->assertEquals(1000, $movement->quantity);
    }

    public function test_cashier_can_restock_ingredient_and_creates_expense(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'name' => 'Sirup Karamel',
            'current_stock' => 100,
            'unit' => 'ml',
            'cost_per_unit' => 150,
        ]);

        $response = $this->actingAs($user)->post('/kasir/inventory/restock', [
            'ingredient_id' => $ingredient->id,
            'quantity' => 500,
            'cost_per_unit' => 150,
            'notes' => 'Beli di Toko Bahan',
            'record_as_expense' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $ingredient->refresh();

        $this->assertEquals(600, $ingredient->current_stock);

        // Mutasi 'purchase' tercatat
        $this->assertDatabaseHas('inventory_movements', [
            'ingredient_id' => $ingredient->id,
            'type' => 'purchase',
            'quantity' => 500,
        ]);

        // Pengeluaran tercatat otomatis (500 ml * Rp 150 = Rp 75.000)
        $this->assertDatabaseHas('expenses', [
            'category' => 'restock',
            'amount' => 75000,
        ]);
    }

    public function test_cashier_can_record_waste(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'current_stock' => 500,
            'unit' => 'ml',
            'cost_per_unit' => 30,
        ]);

        $response = $this->actingAs($user)->post('/kasir/inventory/waste', [
            'ingredient_id' => $ingredient->id,
            'quantity' => 50,
            'notes' => 'Susu tumpah saat foaming',
        ]);

        $response->assertSessionHasNoErrors();
        $ingredient->refresh();

        $this->assertEquals(450, $ingredient->current_stock);

        $this->assertDatabaseHas('inventory_movements', [
            'ingredient_id' => $ingredient->id,
            'type' => 'waste',
            'quantity' => -50,
        ]);
    }

    public function test_cashier_can_record_stock_opname_adjustment(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'current_stock' => 500,
            'unit' => 'gr',
        ]);

        $response = $this->actingAs($user)->post('/kasir/inventory/adjustment', [
            'ingredient_id' => $ingredient->id,
            'actual_stock' => 480,
            'notes' => 'Opname akhir shift sore',
        ]);

        $response->assertSessionHasNoErrors();
        $ingredient->refresh();

        $this->assertEquals(480, $ingredient->current_stock);

        $this->assertDatabaseHas('inventory_movements', [
            'ingredient_id' => $ingredient->id,
            'type' => 'adjustment',
            'quantity' => -20,
        ]);
    }

    public function test_menu_bom_recipe_configuration(): void
    {
        $user = User::factory()->create();
        $menu = Menu::factory()->create(['price' => 30000]);
        $beans = Ingredient::factory()->create(['cost_per_unit' => 200]); // 200/gr
        $milk = Ingredient::factory()->create(['cost_per_unit' => 25]);   // 25/ml

        $response = $this->actingAs($user)->put("/kasir/inventory/recipes/{$menu->id}", [
            'recipes' => [
                ['ingredient_id' => $beans->id, 'amount' => 18], // 18 * 200 = 3600
                ['ingredient_id' => $milk->id, 'amount' => 150], // 150 * 25 = 3750
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertCount(2, $menu->recipes);
        $this->assertEquals(7350, $menu->calculateHpp());
        // Margin: (30000 - 7350) / 30000 = 75.5%
        $this->assertEquals(75.5, $menu->margin_percent);
    }

    public function test_order_creation_automatically_deducts_ingredient_stock(): void
    {
        $user = User::factory()->create();
        $menu = Menu::factory()->create(['price' => 25000, 'is_available' => true]);
        $bean = Ingredient::factory()->create([
            'name' => 'Espresso Beans',
            'current_stock' => 100, // 100 gr
            'unit' => 'gr',
            'cost_per_unit' => 200,
        ]);

        MenuRecipe::create([
            'menu_id' => $menu->id,
            'ingredient_id' => $bean->id,
            'amount' => 18, // 18 gr per cup
        ]);

        // Kasir order 2 cups
        $response = $this->actingAs($user)->postJson('/kasir/orders', [
            'items' => [['menu_id' => $menu->id, 'qty' => 2]],
            'order_type' => 'dine_in',
            'payment_method' => 'cash',
            'paid_amount' => 50000,
            'customer_name' => 'Andi',
        ]);

        $response->assertCreated();

        $bean->refresh();
        // Stok awal 100 - (18 * 2) = 64
        $this->assertEquals(64, $bean->current_stock);

        // Mutasi 'sale' tercatat
        $this->assertDatabaseHas('inventory_movements', [
            'ingredient_id' => $bean->id,
            'type' => 'sale',
            'quantity' => -36,
            'total_cost' => 7200, // 36 * 200
        ]);
    }

    public function test_order_fails_if_ingredient_stock_insufficient(): void
    {
        $user = User::factory()->create();
        $menu = Menu::factory()->create(['price' => 25000, 'is_available' => true]);
        $bean = Ingredient::factory()->create([
            'name' => 'Rare Specialty Beans',
            'current_stock' => 20, // hanya 20 gr
            'unit' => 'gr',
        ]);

        MenuRecipe::create([
            'menu_id' => $menu->id,
            'ingredient_id' => $bean->id,
            'amount' => 18,
        ]);

        // Order 2 cups butuh 36 gr > 20 gr
        $response = $this->actingAs($user)->postJson('/kasir/orders', [
            'items' => [['menu_id' => $menu->id, 'qty' => 2]],
            'order_type' => 'dine_in',
            'payment_method' => 'cash',
            'paid_amount' => 50000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'tidak mencukupi'));
    }

    public function test_voiding_order_restores_ingredient_stock(): void
    {
        $user = User::factory()->create();
        $menu = Menu::factory()->create(['price' => 25000, 'is_available' => true]);
        $bean = Ingredient::factory()->create([
            'current_stock' => 100,
            'unit' => 'gr',
            'cost_per_unit' => 200,
        ]);

        MenuRecipe::create([
            'menu_id' => $menu->id,
            'ingredient_id' => $bean->id,
            'amount' => 20,
        ]);

        // Order 1 cup (potong 20 gr, sisa 80 gr)
        $r = $this->actingAs($user)->postJson('/kasir/orders', [
            'items' => [['menu_id' => $menu->id, 'qty' => 1]],
            'order_type' => 'dine_in',
            'payment_method' => 'cash',
            'paid_amount' => 25000,
        ]);
        $orderId = $r->json('order_id');
        $order = Order::findOrFail($orderId);

        $bean->refresh();
        $this->assertEquals(80, $bean->current_stock);

        // Void pesanan
        $voidResponse = $this->actingAs($user)->post("/kasir/orders/{$order->id}/void");
        $voidResponse->assertRedirect();

        $bean->refresh();
        // Stok dikembalikan ke 100 gr
        $this->assertEquals(100, $bean->current_stock);

        // Mutasi 'void_return' tercatat
        $this->assertDatabaseHas('inventory_movements', [
            'ingredient_id' => $bean->id,
            'type' => 'void_return',
            'quantity' => 20,
        ]);
    }

    public function test_cashier_can_create_and_delete_expense(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/kasir/expenses', [
            'title' => 'Beli Gas Elpiji 12kg',
            'category' => 'operational',
            'amount' => 210000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'supplier' => 'Agen Gas Berkah',
            'notes' => 'Nota kasir shift siang',
        ]);

        $response->assertRedirect('/kasir/expenses');

        $expense = Expense::where('title', 'Beli Gas Elpiji 12kg')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(210000, $expense->amount);

        // Delete expense
        $delResponse = $this->actingAs($user)->delete("/kasir/expenses/{$expense->id}");
        $delResponse->assertRedirect('/kasir/expenses');

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_financial_report_calculates_cogs_and_net_profit(): void
    {
        $user = User::factory()->create();
        $menu = Menu::factory()->create(['price' => 20000]);
        $bean = Ingredient::factory()->create(['current_stock' => 1000, 'cost_per_unit' => 200]);
        MenuRecipe::create(['menu_id' => $menu->id, 'ingredient_id' => $bean->id, 'amount' => 15]); // 15*200 = 3000 HPP

        // Order 1: 20.000 omzet, 3000 HPP
        $this->actingAs($user)->postJson('/kasir/orders', [
            'items' => [['menu_id' => $menu->id, 'qty' => 1]],
            'order_type' => 'dine_in',
            'payment_method' => 'cash',
            'paid_amount' => 20000,
        ]);

        // Expense: 5000 operasional
        Expense::factory()->create([
            'amount' => 5000,
            'category' => 'operational',
            'expense_date' => now()->toDateString(),
        ]);

        $reportResponse = $this->actingAs($user)->get('/kasir/laporan');
        $reportResponse->assertOk()
            ->assertSee('Analisis Keuangan & Laba Toko', false)
            ->assertSee('HPP Bahan Terjual')
            ->assertSee('Laba Kotor (Gross)')
            ->assertSee('Laba Bersih Toko');
    }
}
