<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Support\Money;
use Tests\TestCase;

class MenuModelTest extends TestCase
{
    public function test_menu_belongs_to_category_and_formats_price(): void
    {
        $cat = Category::factory()->create();
        $menu = Menu::factory()->create(['category_id' => $cat->id, 'price' => 28000]);

        $this->assertEquals($cat->name, $menu->category->name);
        $this->assertEquals('Rp 28.000', Money::rupiah(28000));
    }
}
