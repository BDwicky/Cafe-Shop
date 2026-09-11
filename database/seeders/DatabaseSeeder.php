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
        User::firstOrCreate(
            ['email' => 'kasir@kopikita.test'],
            [
                'name' => 'Kasir KopiKita',
                'password' => bcrypt('kopikita123'),
            ]
        );

        $categoriesData = [
            ['Kopi', 'kopi'],
            ['Non-Kopi', 'non-kopi'],
            ['Cocktail', 'cocktail'],
            ['Mocktail', 'mocktail'],
            ['Herbal & Artisan Tea', 'tea-herbal'],
            ['Snack', 'snack'],
            ['Pastry', 'pastry'],
        ];

        $cats = collect($categoriesData)->mapWithKeys(function ($c, $i) {
            $cat = Category::updateOrCreate(
                ['slug' => $c[1]],
                ['name' => $c[0], 'sort_order' => $i]
            );

            return [$c[1] => $cat];
        });

        $menus = [
            // Kopi (Coffee)
            ['kopi', 'Espresso', 18000, 'Ekstraksi kopi murni dengan aroma pekat dan crema tebal keemasan.', 'menus/espresso.jpg'],
            ['kopi', 'Americano', 22000, 'Espresso ganda berpadu dengan air panas untuk cita rasa klasik yang bersih.', 'menus/americano.jpg'],
            ['kopi', 'Caffe Latte', 28000, 'Espresso kaya rasa dipadu dengan steamed milk lembut dan microfoam halus.', 'menus/caffe-latte.jpg'],
            ['kopi', 'Kopi Susu Gula Aren', 25000, 'Perpaduan espresso khas Nusantara, susu segar, dan manis gurihnya gula aren alami.', 'menus/kopi-susu-gula-aren.jpg'],
            ['kopi', 'Caramel Macchiato', 32000, 'Espresso berlapis susu vanilla lembut dengan siraman saus karamel legit di atasnya.', 'menus/caramel-macchiato.jpg'],
            ['kopi', 'V60 Gayo Wine', 35000, 'Manual brew biji kopi arabika Gayo proses wine dengan notes fruity dan acidity seimbang.', 'menus/v60-gayo-wine.jpg'],
            ['kopi', 'Cold Brew Black', 28000, 'Kopi seduh dingin selama 16 jam, menghasilkan rasa halus, rendah asam, dan menyegarkan.', 'menus/cold-brew-black.jpg'],
            ['kopi', 'Affogato al Caffe', 30000, 'Satu scoop es krim vanilla premium disiram satu shot espresso panas yang pekat.', 'menus/affogato.jpg'],

            // Non-Kopi (Non-Coffee)
            ['non-kopi', 'Matcha Latte', 30000, 'Bubuk matcha Uji Jepang premium dipadukan dengan susu segar yang creamy.', 'menus/matcha-latte.jpg'],
            ['non-kopi', 'Chocolate', 28000, 'Kakao murni berkualitas tinggi dengan susu hangat yang kaya dan memanjakan lidah.', 'menus/chocolate.jpg'],
            ['non-kopi', 'Red Velvet Latte', 30000, 'Kombinasi rasa red velvet manis lembut dengan sentuhan vanila dan susu segar.', 'menus/red-velvet-latte.jpg'],
            ['non-kopi', 'Lychee Yakult', 26000, 'Kesegaran leci manis dipadu dengan probiotik Yakult dingin yang asam manis menyegarkan.', 'menus/lychee-yakult.jpg'],

            // Cocktail
            ['cocktail', 'Espresso Martini', 55000, 'Perpaduan klasik espresso pekat, coffee liqueur, dan sentuhan premium dengan busa lembut.', 'menus/espresso-martini.jpg'],
            ['cocktail', 'Irish Coffee Supreme', 58000, 'Kopi hitam hangat diracik dengan whiskey Irlandia, demerara sugar, dan whipped cream kental.', 'menus/irish-coffee.jpg'],
            ['cocktail', 'Coffee Negroni', 58000, 'Twist aroma kopi pada koktail legendaris: gin, sweet vermouth, Campari dan infusi cold brew.', 'menus/coffee-negroni.jpg'],
            ['cocktail', 'Old Fashioned Cold Brew', 60000, 'Bourbon klasik berpadu dengan konsentrat cold brew, aromatic bitters, dan twist kulit jeruk.', 'menus/old-fashioned-cold-brew.jpg'],

            // Mocktail
            ['mocktail', 'Sunset Berry Breeze', 32000, 'Perpaduan puree stroberi segar, jeruk nipis, soda tonik dingin, dan daun mint wangi.', 'menus/sunset-berry-breeze.jpg'],
            ['mocktail', 'Blue Ocean Sparkle', 32000, 'Minuman segar bernuansa laut biru dengan sirup citrus, sparkling soda, dan irisan lemon.', 'menus/blue-ocean-sparkle.jpg'],
            ['mocktail', 'Citrus Cold Brew Tonic', 35000, 'Inovasi segar cold brew arabika berpadu dengan tonic water bersoda dan perasan sunkist.', 'menus/citrus-cold-brew-tonic.jpg'],
            ['mocktail', 'Virgin Mojito Mint', 30000, 'Kesegaran perasan jeruk nipis asli, gula tebu, remasan daun mint segar, dan soda.', 'menus/virgin-mojito-mint.jpg'],

            // Herbal & Artisan Tea
            ['tea-herbal', 'Chamomile Blossom', 28000, 'Seduhan bunga chamomile utuh yang menenangkan, beraroma manis lembut mirip apel madu.', 'menus/chamomile-blossom.jpg'],
            ['tea-herbal', 'Earl Grey Lavender', 28000, 'Teh hitam klasik dengan minyak bergamot Italia dan sentuhan bunga lavender menenangkan.', 'menus/earl-grey-lavender.jpg'],
            ['tea-herbal', 'Lemongrass Ginger Infusion', 26000, 'Seduhan serai segar dan jahe merah hangat dengan aroma pandan serta madu murni.', 'menus/lemongrass-ginger-infusion.jpg'],
            ['tea-herbal', 'Peppermint Fresh Tea', 26000, 'Daun peppermint pilihan yang menyegarkan tenggorokan dan meredakan kepenatan.', 'menus/peppermint-fresh-tea.jpg'],
            ['tea-herbal', 'Artisan Jasmine Green Tea', 26000, 'Pucuk teh hijau berkualitas tinggi yang diinfus berulang kali dengan bunga melati asli.', 'menus/jasmine-green-tea.jpg'],

            // Snack
            ['snack', 'Pisang Goreng Keju', 22000, 'Pisang raja goreng krispi dengan limpahan keju parut cheddar dan susu kental manis.', 'menus/pisang-goreng-keju.jpg'],
            ['snack', 'French Fries', 20000, 'Kentang goreng renyah bumbu sea salt, disajikan bersama saus sambal dan mayones.', 'menus/french-fries.jpg'],
            ['snack', 'Crispy Chicken Tenders', 30000, 'Fillet dada ayam goreng tepung gurih krispi dengan saus honey mustard spesial.', 'menus/crispy-chicken-tenders.jpg'],

            // Pastry
            ['pastry', 'Butter Croissant', 24000, 'Croissant khas Prancis dengan lapisan pastry renyah dan butter aromatik berkualitas.', 'menus/butter-croissant.jpg'],
            ['pastry', 'Pain au Chocolat', 26000, 'Pastry berlapis dengan isian dark chocolate couverture yang meleleh saat dipanggang.', 'menus/pain-au-chocolat.jpg'],
            ['pastry', 'Cinnamon Roll', 26000, 'Roti gulung kayu manis lembut dengan glaze cream cheese vanilla manis gurih.', 'menus/cinnamon-roll.jpg'],
        ];

        $catalog = Menu::curatedCatalog();

        foreach ($menus as $i => [$slug, $name, $price, $desc, $image]) {
            $itemSlug = Str::slug($name);
            $curated = $catalog[$itemSlug] ?? null;

            Menu::updateOrCreate(
                ['slug' => $itemSlug],
                [
                    'category_id' => $cats[$slug]->id,
                    'name' => $name,
                    'description' => $desc,
                    'price' => $price,
                    'image' => $image,
                    'is_available' => true,
                    'sort_order' => $i,
                    'ingredients' => $curated['ingredients'] ?? null,
                    'nutrition' => $curated['nutrition'] ?? null,
                    'flavor_notes' => $curated['flavor_notes'] ?? null,
                ]
            );
        }

        $this->call(MusicDefaultSeeder::class);
    }
}
