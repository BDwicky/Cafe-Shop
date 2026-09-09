<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Kasir KopiKita',
            'email' => 'kasir@kopikita.test',
            'password' => bcrypt('kopikita123'),
        ]);

        $cats = collect([
            ['Kopi', 'kopi'],
            ['Non-Kopi', 'non-kopi'],
            ['Snack', 'snack'],
            ['Pastry', 'pastry'],
        ])->mapWithKeys(function ($c, $i) {
            $cat = Category::create(['name' => $c[0], 'slug' => $c[1], 'sort_order' => $i]);

            return [$c[1] => $cat];
        });

        $menus = [
            ['kopi', 'Espresso', 18000],
            ['kopi', 'Americano', 22000],
            ['kopi', 'Caffe Latte', 28000],
            ['kopi', 'Kopi Susu Gula Aren', 25000],
            ['kopi', 'V60 Gayo Wine', 35000],
            ['non-kopi', 'Matcha Latte', 30000],
            ['non-kopi', 'Chocolate', 28000],
            ['non-kopi', 'Lychee Yakult', 26000],
            ['snack', 'Pisang Goreng Keju', 22000],
            ['snack', 'French Fries', 20000],
            ['pastry', 'Butter Croissant', 24000],
            ['pastry', 'Pain au Chocolat', 26000],
        ];

        foreach ($menus as $i => [$slug, $name, $price]) {
            Menu::create([
                'category_id' => $cats[$slug]->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => null,
                'price' => $price,
                'sort_order' => $i,
            ]);
        }
    }
}
