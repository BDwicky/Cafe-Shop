<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Promo;
use App\Models\User;
use Tests\TestCase;

class PromoManagementTest extends TestCase
{
    public function test_authenticated_kasir_can_view_promos_page(): void
    {
        $user = User::factory()->create();
        $promo = Promo::create([
            'code' => 'PRAM10',
            'name' => 'Promo Ramadhan 10%',
            'type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('kasir.promos.index'));

        $response->assertOk();
        $response->assertSee('PRAM10');
        $response->assertSee('Promo Ramadhan 10%');
        $response->assertSee('Kupon & Promo Diskon');
    }

    public function test_kasir_can_create_new_percentage_promo(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('kasir.promos.store'), [
            'code' => 'kopi50',
            'name' => 'Diskon 50% Akhir Pekan',
            'description' => 'Khusus pembelian kopi akhir pekan',
            'type' => 'percentage',
            'discount_value' => 50,
            'max_discount' => 25000,
            'min_order' => 50000,
            'usage_limit' => 100,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('kasir.promos.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('promos', [
            'code' => 'KOPI50',
            'name' => 'Diskon 50% Akhir Pekan',
            'type' => 'percentage',
            'discount_value' => 50,
            'max_discount' => 25000,
            'min_order' => 50000,
            'usage_limit' => 100,
            'is_active' => 1,
        ]);
    }

    public function test_kasir_can_create_new_fixed_discount_promo(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('kasir.promos.store'), [
            'code' => 'hemat10k',
            'name' => 'Potongan Langsung 10 Ribu',
            'type' => 'fixed',
            'discount_value' => 10000,
            'min_order' => 40000,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('kasir.promos.index'));

        $this->assertDatabaseHas('promos', [
            'code' => 'HEMAT10K',
            'type' => 'fixed',
            'discount_value' => 10000,
            'min_order' => 40000,
        ]);
    }

    public function test_kasir_can_toggle_promo_active_status(): void
    {
        $user = User::factory()->create();
        $promo = Promo::create([
            'code' => 'TOGGLEME',
            'name' => 'Toggle Test',
            'type' => 'fixed',
            'discount_value' => 5000,
            'is_active' => true,
        ]);

        // Toggle via standard HTTP redirect
        $response = $this->actingAs($user)->patch(route('kasir.promos.toggle', $promo));
        $response->assertRedirect();
        $this->assertFalse((bool) $promo->fresh()->is_active);

        // Toggle back via JSON
        $jsonResponse = $this->actingAs($user)->patchJson(route('kasir.promos.toggle', $promo));
        $jsonResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_active', true);
        $this->assertTrue((bool) $promo->fresh()->is_active);
    }

    public function test_kasir_can_delete_promo(): void
    {
        $user = User::factory()->create();
        $promo = Promo::create([
            'code' => 'DELETEME',
            'name' => 'Delete Test',
            'type' => 'fixed',
            'discount_value' => 5000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('kasir.promos.destroy', $promo));
        $response->assertRedirect(route('kasir.promos.index'));

        $this->assertDatabaseMissing('promos', ['code' => 'DELETEME']);
    }

    public function test_kasir_can_save_menu_composition_and_nutrition(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $menu = Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Artisan Matcha Latte',
            'price' => 35000,
        ]);

        $this->actingAs($user)->put(route('kasir.menu.update', $menu), [
            'category_id' => $category->id,
            'name' => 'Artisan Matcha Latte',
            'price' => 35000,
            'description' => 'Matcha premium Jepang dengan susu oat.',
            'ingredients' => "Ceremonial Matcha 4g\nOat Milk Barista 180ml\nBrown Sugar 10ml\nIce Cube",
            'flavor_notes' => 'Earthy, Sweet Cream, Nutty',
            'nutrition' => [
                'calories' => '160 kkal',
                'caffeine' => '35 mg',
                'sugar' => '8 g',
                'fat' => '4 g',
                'allergens' => 'Oat (Gluten-free)',
                'serving' => '350 ml',
            ],
        ])->assertRedirect(route('kasir.menu.index'));

        $freshMenu = $menu->fresh();
        $this->assertIsArray($freshMenu->ingredients);
        $this->assertContains('Ceremonial Matcha 4g', $freshMenu->ingredients);
        $this->assertContains('Oat Milk Barista 180ml', $freshMenu->ingredients);
        $this->assertSame('Earthy, Sweet Cream, Nutty', $freshMenu->flavor_notes);
        $this->assertSame('160 kkal', $freshMenu->nutrition['calories'] ?? null);
        $this->assertSame('35 mg', $freshMenu->nutrition['caffeine'] ?? null);
    }
}
