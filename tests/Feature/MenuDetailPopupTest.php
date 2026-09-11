<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use Tests\TestCase;

class MenuDetailPopupTest extends TestCase
{
    public function test_menu_page_renders_interactive_cards_with_ingredients_and_nutrition_modal(): void
    {
        $category = Category::factory()->create([
            'name' => 'Kopi',
            'slug' => 'kopi',
        ]);

        $menu = Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Caffe Latte',
            'slug' => 'caffe-latte',
            'price' => 28000,
            'description' => 'Espresso dengan steamed milk lembut.',
            'is_available' => true,
        ]);

        $response = $this->get(route('menu.public'));

        $response->assertStatus(200);
        $response->assertSee('Caffe Latte');
        $response->assertSee('Komposisi & Bahan Baku Utama', false);
        $response->assertSee('Informasi Kandungan & Karakteristik', false);
        $response->assertSee('Bahan & Gizi', false);
        $response->assertSee('openDetail');
    }

    public function test_menu_model_returns_curated_ingredients_and_nutrition(): void
    {
        $category = Category::factory()->create([
            'name' => 'Kopi',
            'slug' => 'kopi',
        ]);

        $latte = Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Caffe Latte',
            'slug' => 'caffe-latte',
        ]);

        $this->assertNotEmpty($latte->detailed_ingredients);
        $this->assertStringContainsString('Steamed Milk', json_encode($latte->detailed_ingredients));
        $this->assertArrayHasKey('calories', $latte->detailed_nutrition);
        $this->assertArrayHasKey('caffeine', $latte->detailed_nutrition);
        $this->assertNotEmpty($latte->detailed_flavor_notes);
        $this->assertNotEmpty($latte->detailed_barista_notes);
    }

    public function test_menu_model_falls_back_to_smart_category_defaults(): void
    {
        $category = Category::factory()->create([
            'name' => 'Kopi Khusus',
            'slug' => 'kopi-khusus',
        ]);

        $customMenu = Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Kopi Eksperimen X',
            'slug' => 'kopi-eksperimen-x',
            'ingredients' => null,
            'nutrition' => null,
            'flavor_notes' => null,
        ]);

        $this->assertIsArray($customMenu->detailed_ingredients);
        $this->assertNotEmpty($customMenu->detailed_ingredients);
        $this->assertIsArray($customMenu->detailed_nutrition);
        $this->assertArrayHasKey('calories', $customMenu->detailed_nutrition);
    }

    public function test_menu_model_uses_explicit_ingredients_and_nutrition_when_set(): void
    {
        $category = Category::factory()->create(['name' => 'Pastry', 'slug' => 'pastry']);

        $customIngredients = ['Tepung Organik', 'Madagaskar Vanilla Extract'];
        $customNutrition = ['calories' => '250 kkal', 'caffeine' => '0 mg', 'sugar' => '15 g', 'allergens' => 'Gluten'];

        $pastry = Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Pastry Khusus',
            'slug' => 'pastry-khusus',
            'ingredients' => $customIngredients,
            'nutrition' => $customNutrition,
            'flavor_notes' => 'Crisp, Sweet Vanilla',
        ]);

        $this->assertSame($customIngredients, $pastry->detailed_ingredients);
        $this->assertSame($customNutrition, $pastry->detailed_nutrition);
        $this->assertSame('Crisp, Sweet Vanilla', $pastry->detailed_flavor_notes);
    }
}
