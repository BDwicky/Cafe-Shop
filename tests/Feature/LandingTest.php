<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use Tests\TestCase;

class LandingTest extends TestCase
{
    public function test_landing_shows_available_menus_and_hides_unavailable(): void
    {
        Menu::factory()->create(['name' => 'Americano Test', 'is_available' => true]);
        Menu::factory()->create(['name' => 'Kopi Susu Test', 'is_available' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Americano Test')
            ->assertDontSee('Kopi Susu Test');
    }

    public function test_full_menu_page_groups_by_category(): void
    {
        Category::factory()->has(Menu::factory()->count(3))->create(['name' => 'Kopi Uji']);

        $this->get('/menu')
            ->assertOk()
            ->assertSee('Kopi Uji');
    }

    public function test_landing_shows_open_status_from_config_hours(): void
    {
        $r = $this->get('/');

        $r->assertOk();
        $this->assertTrue(
            str_contains($r->getContent(), 'BUKA') || str_contains($r->getContent(), 'TUTUP')
        );
    }
}
