<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    public function test_kasir_can_create_menu_with_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $cat = Category::factory()->create();
        $img = UploadedFile::fake()->image('latte.jpg');

        $this->actingAs($user)->post('/kasir/menu', [
            'category_id' => $cat->id,
            'name' => 'Cappuccino',
            'price' => 27000,
            'description' => null,
            'image' => $img,
            'is_available' => 1,
        ])->assertRedirect('/kasir/menu');

        $menu = Menu::where('name', 'Cappuccino')->first();
        $this->assertNotNull($menu);
        $this->assertSame('cappuccino', $menu->slug);
        Storage::disk('public')->assertExists($menu->image);
    }

    public function test_kasir_can_update_menu(): void
    {
        $menu = Menu::factory()->create(['price' => 25000]);

        $this->actingAs(User::factory()->create())
            ->put("/kasir/menu/{$menu->id}", [
                'category_id' => $menu->category_id,
                'name' => $menu->name,
                'price' => 30000,
                'description' => $menu->description,
            ])
            ->assertRedirect('/kasir/menu');

        $this->assertSame(30000, $menu->fresh()->price);
    }

    public function test_kasir_can_update_menu_nutrition_and_ingredients(): void
    {
        $menu = Menu::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/kasir/menu/{$menu->id}", [
                'category_id' => $menu->category_id,
                'name' => $menu->name,
                'price' => 28000,
                'ingredients' => "Arabica Beans 18g\nSteamed Milk 120ml",
                'flavor_notes' => 'Hazelnut, Cocoa',
                'nutrition' => [
                    'calories' => '120 kkal',
                    'caffeine' => '85 mg',
                    'sugar' => '6 g',
                    'fat' => '4 g',
                    'serving' => 'Hot (180ml)',
                    'allergens' => 'Susu Sapi (Laktosa)',
                ],
            ])
            ->assertRedirect('/kasir/menu');

        $updated = $menu->fresh();
        $this->assertSame(28000, $updated->price);
        $this->assertSame(['Arabica Beans 18g', 'Steamed Milk 120ml'], $updated->ingredients);
        $this->assertSame('Hazelnut, Cocoa', $updated->flavor_notes);
        $this->assertSame('120 kkal', $updated->nutrition['calories'] ?? null);
        $this->assertSame('85 mg', $updated->nutrition['caffeine'] ?? null);
    }

    public function test_kasir_can_toggle_availability(): void
    {
        $menu = Menu::factory()->create(['is_available' => true]);

        $this->actingAs(User::factory()->create())
            ->patch("/kasir/menu/{$menu->id}/toggle")
            ->assertRedirect('/kasir/menu');

        $this->assertFalse($menu->fresh()->is_available);
    }

    public function test_kasir_can_toggle_availability_via_json(): void
    {
        $menu = Menu::factory()->create(['is_available' => false]);

        $response = $this->actingAs(User::factory()->create())
            ->patchJson("/kasir/menu/{$menu->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_available', true);

        $this->assertTrue($menu->fresh()->is_available);
    }

    public function test_kasir_can_delete_menu(): void
    {
        $menu = Menu::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/kasir/menu/{$menu->id}")
            ->assertRedirect('/kasir/menu');

        $this->assertNull(Menu::find($menu->id));
    }

    public function test_kasir_can_create_category(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/kasir/categories', ['name' => 'Dessert'])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['slug' => 'dessert']);
    }

    public function test_cannot_delete_category_with_menus(): void
    {
        $cat = Category::factory()->has(Menu::factory()->count(2))->create();

        $this->actingAs(User::factory()->create())
            ->delete("/kasir/categories/{$cat->id}")
            ->assertStatus(422);

        $this->assertNotNull(Category::find($cat->id));
    }
}
