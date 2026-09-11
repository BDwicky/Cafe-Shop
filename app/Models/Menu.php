<?php

namespace App\Models;

use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price', 'image', 'is_available', 'sort_order',
        'ingredients', 'nutrition', 'flavor_notes',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'price' => 'integer',
        'ingredients' => 'array',
        'nutrition' => 'array',
    ];

    protected $appends = [
        'detailed_ingredients',
        'detailed_nutrition',
        'detailed_flavor_notes',
        'detailed_barista_notes',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(MenuRecipe::class);
    }

    /**
     * Hitung HPP (Harga Pokok Penjualan / Total Modal Bahan Baku) per porsi menu.
     */
    public function calculateHpp(): int
    {
        $recipes = $this->relationLoaded('recipes') ? $this->recipes : $this->recipes()->with('ingredient')->get();

        return (int) $recipes->sum(function (MenuRecipe $recipe) {
            return $recipe->cost;
        });
    }

    /**
     * Margin Laba Kotor (%) = ((Harga Jual - HPP) / Harga Jual) * 100
     */
    public function getMarginPercentAttribute(): float
    {
        if ($this->price <= 0) {
            return 0.0;
        }

        $hpp = $this->calculateHpp();
        $profit = $this->price - $hpp;

        return round(($profit / $this->price) * 100, 1);
    }

    /**
     * Cek apakah seluruh bahan baku resep menu ini tersedia dengan stok yang cukup.
     */
    public function areIngredientsInStock(int $qty = 1): bool
    {
        $recipes = $this->relationLoaded('recipes') ? $this->recipes : $this->recipes()->with('ingredient')->get();

        if ($recipes->isEmpty()) {
            return true;
        }

        foreach ($recipes as $recipe) {
            if (! $recipe->ingredient || ($recipe->ingredient->current_stock < ($recipe->amount * $qty))) {
                return false;
            }
        }

        return true;
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function getDetailedIngredientsAttribute(): array
    {
        if (! empty($this->ingredients) && is_array($this->ingredients)) {
            return $this->ingredients;
        }

        $key = Str::slug($this->name);
        $catalog = static::curatedCatalog();

        return $catalog[$key]['ingredients'] ?? $this->defaultIngredients();
    }

    public function getDetailedNutritionAttribute(): array
    {
        if (! empty($this->nutrition) && is_array($this->nutrition)) {
            return $this->nutrition;
        }

        $key = Str::slug($this->name);
        $catalog = static::curatedCatalog();

        return $catalog[$key]['nutrition'] ?? $this->defaultNutrition();
    }

    public function getDetailedFlavorNotesAttribute(): string
    {
        if (! empty($this->flavor_notes)) {
            return $this->flavor_notes;
        }

        $key = Str::slug($this->name);
        $catalog = static::curatedCatalog();

        return $catalog[$key]['flavor_notes'] ?? $this->defaultFlavorNotes();
    }

    public function getDetailedBaristaNotesAttribute(): string
    {
        $key = Str::slug($this->name);
        $catalog = static::curatedCatalog();

        return $catalog[$key]['barista_notes'] ?? $this->defaultBaristaNotes();
    }

    public function defaultIngredients(): array
    {
        $cat = strtolower($this->category->slug ?? $this->category->name ?? '');
        if (str_contains($cat, 'kopi') || str_contains($cat, 'coffee')) {
            return [
                'Biji Kopi Arabika Pilihan Berkualitas Tinggi',
                'Air mineral filtrasi terukur suhu 92–94°C',
                'Susu sapi segar / pemanis alami sesuai racikan',
            ];
        }
        if (str_contains($cat, 'tea') || str_contains($cat, 'teh') || str_contains($cat, 'herbal')) {
            return [
                'Pucuk daun teh / rempah herbal kering pilihan murni',
                'Air mineral seduh suhu terukur',
                'Pemanis madu alami atau gula tebu (opsional)',
            ];
        }
        if (str_contains($cat, 'pastry') || str_contains($cat, 'snack') || str_contains($cat, 'makanan')) {
            return [
                'Bahan pangan berkualitas pilihan dapur',
                'Mentega & bumbu rempah aromatik',
                'Dipanggang / digoreng fresh setiap hari',
            ];
        }

        return [
            'Bahan baku berkualitas pilihan racikan kafe',
            'Sari buah / konsentrat alami murni',
            'Air mineral terfiltrasi & es kristal higienis',
        ];
    }

    public function defaultNutrition(): array
    {
        $cat = strtolower($this->category->slug ?? $this->category->name ?? '');
        if (str_contains($cat, 'kopi')) {
            return [
                'calories' => '110 kkal',
                'caffeine' => '85 mg',
                'sugar' => '6 g',
                'fat' => '3 g',
                'allergens' => 'Dapat mengandung produk susu',
                'serving' => 'Hot / Iced',
            ];
        }
        if (str_contains($cat, 'tea') || str_contains($cat, 'teh') || str_contains($cat, 'herbal')) {
            return [
                'calories' => '15 kkal',
                'caffeine' => '20 mg',
                'sugar' => '2 g',
                'fat' => '0 g',
                'allergens' => 'Bebas Alergen',
                'serving' => 'Hot / Iced',
            ];
        }
        if (str_contains($cat, 'pastry') || str_contains($cat, 'snack')) {
            return [
                'calories' => '280 kkal',
                'caffeine' => '0 mg',
                'sugar' => '12 g',
                'fat' => '12 g',
                'allergens' => 'Dapat mengandung gluten atau produk susu',
                'serving' => 'Freshly Prepared',
            ];
        }

        return [
            'calories' => '120 kkal',
            'caffeine' => '0 mg',
            'sugar' => '14 g',
            'fat' => '1 g',
            'allergens' => 'Bebas Alergen',
            'serving' => 'Dingin / Segar',
        ];
    }

    public function defaultFlavorNotes(): string
    {
        return 'Harmonis, Segar, Racikan Spesial Kafe';
    }

    public function defaultBaristaNotes(): string
    {
        return 'Dibuat fresh per pesanan oleh tim barista dan dapur dengan standar kebersihan tinggi.';
    }

    public static function curatedCatalog(): array
    {
        return [
            'espresso' => [
                'ingredients' => [
                    '100% Biji Kopi Arabika Single-Origin Gayo (Double Shot 18g)',
                    'Air mineral filtrasi murni bersuhu 93°C',
                ],
                'nutrition' => [
                    'calories' => '5 kkal',
                    'caffeine' => '120 mg',
                    'sugar' => '0 g',
                    'fat' => '0.1 g',
                    'allergens' => 'Bebas Alergen (Dairy-Free & Vegan)',
                    'serving' => 'Hot (30ml Single / 60ml Double)',
                ],
                'flavor_notes' => 'Dark Chocolate, Toasted Almond, Crema Tebal, Clean Finish',
                'barista_notes' => 'Ekstraksi presisi dengan tekanan 9 bar untuk mendapatkan crema keemasan yang padat.',
            ],
            'americano' => [
                'ingredients' => [
                    'Espresso ganda Arabika pilihan (36ml)',
                    'Air mineral panas bersuhu stabil (180ml)',
                ],
                'nutrition' => [
                    'calories' => '8 kkal',
                    'caffeine' => '130 mg',
                    'sugar' => '0 g',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen (100% Kopi Murni)',
                    'serving' => 'Hot atau Iced (Dingin dengan Es Kristal)',
                ],
                'flavor_notes' => 'Classic Bold, Cocoa Nibs, Crisp Citrus Acidity',
                'barista_notes' => 'Pilihan klasik rendah kalori tanpa gula, menjaga keaslian rasa biji kopi.',
            ],
            'caffe-latte' => [
                'ingredients' => [
                    'Espresso Arabika Single-Origin (30ml)',
                    'Susu sapi segar pasteurisasi (180ml Steamed Milk)',
                    'Lapisan microfoam halus lembut (0.5cm)',
                ],
                'nutrition' => [
                    'calories' => '140 kkal',
                    'caffeine' => '75 mg',
                    'sugar' => '9 g (Gula alami laktosa)',
                    'fat' => '5 g',
                    'allergens' => 'Mengandung Susu Sapi (Laktosa)',
                    'serving' => 'Hot (dengan Latte Art) / Iced',
                ],
                'flavor_notes' => 'Creamy, Milky Harmony, Velvety, Subtle Caramel Sweetness',
                'barista_notes' => 'Dapat diganti dengan Susu Oat / Almond untuk opsi bebas susu hewani.',
            ],
            'kopi-susu-gula-aren' => [
                'ingredients' => [
                    'House Blend Espresso (Robusta Temanggung & Arabika Gayo)',
                    'Fresh milk sapi segar pasteurisasi murni',
                    'Sirup gula aren organik asli Lebak Banten (tanpa pengawet)',
                    'Krimer nabati lembut gurih',
                ],
                'nutrition' => [
                    'calories' => '195 kkal',
                    'caffeine' => '95 mg',
                    'sugar' => '18 g (Gula aren murni)',
                    'fat' => '6 g',
                    'allergens' => 'Mengandung Susu Sapi (Laktosa)',
                    'serving' => 'Iced (Paling favorit disajikan dingin)',
                ],
                'flavor_notes' => 'Karamel Aren Legit, Gurih Lembut, Kopi Mantap',
                'barista_notes' => 'Tersedia pilihan tingkat manis: Normal Sweet (100%) atau Less Sugar (50%).',
            ],
            'caramel-macchiato' => [
                'ingredients' => [
                    'Double shot espresso Arabika segar',
                    'Susu sapi segar dengan ekstrak vanila Madagaskar',
                    'Siraman saus karamel mentega legit (Butter Caramel Drizzle)',
                ],
                'nutrition' => [
                    'calories' => '230 kkal',
                    'caffeine' => '110 mg',
                    'sugar' => '24 g',
                    'fat' => '7 g',
                    'allergens' => 'Mengandung Susu Sapi (Laktosa) & Mentega',
                    'serving' => 'Hot atau Iced berlapis estetis',
                ],
                'flavor_notes' => 'Sweet Butter Caramel, Vanilla Bean, Bold Espresso Contrast',
                'barista_notes' => 'Nikmati langsung tanpa diaduk agar merasakan transisi rasa manis lembut ke pekatnya espresso.',
            ],
            'v60-gayo-wine' => [
                'ingredients' => [
                    '15g Biji Kopi Arabika Aceh Gayo (Proses Anaerobik Natural Wine)',
                    '225ml Air seduh mineral bersuhu 91°C (Rasio 1:15)',
                    'Kertas filter V60 Jepang (Rinsed)',
                ],
                'nutrition' => [
                    'calories' => '4 kkal',
                    'caffeine' => '105 mg',
                    'sugar' => '0 g (Sensasi manis alami dari fermentasi buah)',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen (Single Origin Organik)',
                    'serving' => 'Hot Pour-over dalam Server Kaca',
                ],
                'flavor_notes' => 'Fermented Red Grapes, Dried Plum, Winey Floral, Sweet Aftertaste',
                'barista_notes' => 'Disajikan bersama kartu informasi cupping notes dan profil ketinggian kebun kopi.',
            ],
            'cold-brew-black' => [
                'ingredients' => [
                    'Biji kopi Arabika Flores Bajawa giling kasar',
                    'Air dingin steril terfiltrasi murni',
                    'Maserasi dingin ekstraksi perlahan selama 16 jam',
                ],
                'nutrition' => [
                    'calories' => '5 kkal',
                    'caffeine' => '140 mg',
                    'sugar' => '0 g',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen (Ramah Lambung)',
                    'serving' => 'Iced dengan Kubus Es Kristal',
                ],
                'flavor_notes' => 'Smooth Dark Cocoa, Molasses, Very Low Acidity, Clean Finish',
                'barista_notes' => 'Tingkat keasaman 67% lebih rendah dibanding seduhan panas biasa.',
            ],
            'affogato-al-caffe' => [
                'ingredients' => [
                    '1 Scoop es krim gelato vanilla bean Madagaskar',
                    '1 Shot espresso Arabika pekat bersuhu panas (30ml)',
                    'Taburan bubuk kakao dark murni di atasnya',
                ],
                'nutrition' => [
                    'calories' => '185 kkal',
                    'caffeine' => '65 mg',
                    'sugar' => '16 g',
                    'fat' => '9 g',
                    'allergens' => 'Mengandung Susu Sapi & Telur (Basis Gelato)',
                    'serving' => 'Disajikan dalam Gelas Slanted Kaca',
                ],
                'flavor_notes' => 'Perpaduan Dingin Manis & Pahit Panas, Creamy Vanilla',
                'barista_notes' => 'Tuang espresso panas perlahan ke atas es krim dan nikmati selagi kontras suhu terasa.',
            ],
            'matcha-latte' => [
                'ingredients' => [
                    'Bubuk Matcha Ceremonial Grade asli Uji, Kyoto Jepang',
                    'Susu sapi segar pasteurisasi hangat/dingin',
                    'Sirup tebu cair murni',
                ],
                'nutrition' => [
                    'calories' => '160 kkal',
                    'caffeine' => '35 mg (Kaya Antioksidan EGCG & L-Theanine)',
                    'sugar' => '12 g',
                    'fat' => '5 g',
                    'allergens' => 'Mengandung Susu Sapi (Bisa diganti Oat Milk)',
                    'serving' => 'Hot atau Iced',
                ],
                'flavor_notes' => 'Umami Alami, Vegetal Earthy, Creamy Sweetness, Non-Bitter',
                'barista_notes' => 'Memberikan efek rileks fokus tanpa hentakan kafein yang berlebihan.',
            ],
            'chocolate' => [
                'ingredients' => [
                    'Kakao murni couverture dark chocolate 70% Bali',
                    'Susu sapi segar pasteurisasi',
                    'Sentuhan ekstrak madu alami & vanila',
                ],
                'nutrition' => [
                    'calories' => '210 kkal',
                    'caffeine' => '10 mg',
                    'sugar' => '19 g',
                    'fat' => '8 g',
                    'allergens' => 'Mengandung Susu Sapi',
                    'serving' => 'Hot (dengan Latte Art) atau Iced',
                ],
                'flavor_notes' => 'Rich Decadent Cocoa, Warm Comforting, Silky Smooth',
                'barista_notes' => 'Bisa ditambahkan taburan marshmallow bakar atas permintaan.',
            ],
            'red-velvet-latte' => [
                'ingredients' => [
                    'Ekstrak beetroot alami & cocoa butter putih Belgia',
                    'Fresh milk sapi steamed lembut',
                    'Ekstrak vanila bean lembut',
                ],
                'nutrition' => [
                    'calories' => '190 kkal',
                    'caffeine' => '0 mg (Bebas Kafein)',
                    'sugar' => '21 g',
                    'fat' => '6 g',
                    'allergens' => 'Mengandung Susu Sapi',
                    'serving' => 'Hot atau Iced',
                ],
                'flavor_notes' => 'Buttery Red Velvet Cake, Vanilla Cream, Manis Lembut',
                'barista_notes' => 'Pilihan favorit ramah anak dan bagi yang menghindari kafein di malam hari.',
            ],
            'lychee-yakult' => [
                'ingredients' => [
                    '2 Botol Yakult Original probiotik fermentasi',
                    'Puree buah leci premium & buah leci utuh segar',
                    'Air soda dingin & es batu kristal',
                ],
                'nutrition' => [
                    'calories' => '130 kkal',
                    'caffeine' => '0 mg',
                    'sugar' => '22 g',
                    'fat' => '0 g',
                    'allergens' => 'Mengandung Susu Fermentasi (Laktosa Ringan)',
                    'serving' => 'Iced dengan Buah Leci Garnish',
                ],
                'flavor_notes' => 'Asam Segar Probiotik, Manis Buah Leci, Semriwing Bersoda',
                'barista_notes' => 'Kaya miliaran bakteri baik Shirota strain yang baik untuk pencernaan.',
            ],
            'espresso-martini' => [
                'ingredients' => [
                    'Fresh espresso Arabika pekat (45ml)',
                    'Coffee liqueur Kahlua premium',
                    'Vodka pilihan (Distilled wheat)',
                    'Sedikit sirup gula tebu murni',
                ],
                'nutrition' => [
                    'calories' => '170 kkal',
                    'caffeine' => '60 mg',
                    'alcohol' => '~15% ABV',
                    'sugar' => '11 g',
                    'allergens' => 'Mengandung Alkohol (Dewasa 21+)',
                    'serving' => 'Shaken Ice dalam Gelas Coupe Kaca',
                ],
                'flavor_notes' => 'Velvety Foam, Rich Coffee Liqueur, Warming Spirits, Bitter-Sweet',
                'barista_notes' => 'Dikocok kuat bersama es batu untuk menciptakan lapisan bua busa sutra yang mewah di atasnya.',
            ],
            'irish-coffee-supreme' => [
                'ingredients' => [
                    'Kopi hitam seduh segar berkualitas',
                    'Irish Whiskey Jameson asli',
                    'Gula demerara cokelat alami',
                    'Heavy whipped cream dingin di lapisan atas',
                ],
                'nutrition' => [
                    'calories' => '210 kkal',
                    'caffeine' => '80 mg',
                    'alcohol' => '~12% ABV',
                    'sugar' => '14 g',
                    'allergens' => 'Mengandung Alkohol & Produk Susu/Krim',
                    'serving' => 'Hot dalam Gelas Irish Coffee Bertangkai',
                ],
                'flavor_notes' => 'Warm Boozy Coffee, Toffee Sugar, Chilled Silky Cream',
                'barista_notes' => 'Minumlah langsung menembus lapisan krim dingin tanpa diaduk untuk perpaduan rasa autentik.',
            ],
            'coffee-negroni' => [
                'ingredients' => [
                    'London Dry Gin premium',
                    'Campari Italian Bitters',
                    'Sweet Vermouth Rosso',
                    'Konsentrat Cold Brew Kopi pekat',
                    'Kulit jeruk segar (Orange peel oils)',
                ],
                'nutrition' => [
                    'calories' => '165 kkal',
                    'caffeine' => '25 mg',
                    'alcohol' => '~20% ABV',
                    'sugar' => '8 g',
                    'allergens' => 'Mengandung Alkohol (21+ Only)',
                    'serving' => 'Stirred on Large Ice Rock',
                ],
                'flavor_notes' => 'Herbal Bittersweet, Botanical Citrus, Deep Roasted Coffee Twist',
                'barista_notes' => 'Kombinasi klasik Negroni dengan sentuhan aroma seduhan dingin kopi kafe.',
            ],
            'old-fashioned-cold-brew' => [
                'ingredients' => [
                    'Bourbon Whiskey Amerika pilihan',
                    'Konsentrat Cold Brew Arabika',
                    'Angostura Aromatic Bitters',
                    'Gula tebu & sentuhan kulit jeruk Sunkist',
                ],
                'nutrition' => [
                    'calories' => '155 kkal',
                    'caffeine' => '30 mg',
                    'alcohol' => '~18% ABV',
                    'sugar' => '6 g',
                    'allergens' => 'Mengandung Alkohol (21+ Only)',
                    'serving' => 'Rocks Glass dengan Satu Balok Es Bening',
                ],
                'flavor_notes' => 'Oak Wood Vanilla, Roasted Coffee Undertones, Spiced Citrus Bitters',
                'barista_notes' => 'Pilihan minuman malam bernuansa santai dengan aroma bourbon berkelas.',
            ],
            'sunset-berry-breeze' => [
                'ingredients' => [
                    'Puree buah stroberi asli pilihan',
                    'Perasan sari jeruk nipis segar',
                    'Air soda tonik berkarbonasi dingin',
                    'Daun mint organik segar & sirup delima alami',
                ],
                'nutrition' => [
                    'calories' => '110 kkal',
                    'caffeine' => '0 mg',
                    'sugar' => '18 g',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen (Non-Alcoholic, 100% Halal)',
                    'serving' => 'Iced dengan Gradasi Warna Sunset',
                ],
                'flavor_notes' => 'Sparkling Berry, Tart Lime Zest, Aromatic Mint, Refreshing Fizz',
                'barista_notes' => 'Kaya vitamin C alami dari sari buah asli tanpa pewarna buatan.',
            ],
            'blue-ocean-sparkle' => [
                'ingredients' => [
                    'Sirup citrus Curacao biru non-alkohol',
                    'Air perasan buah lemon segar',
                    'Sparkling soda water berkarbonasi',
                    'Biji selasih & irisan lemon kering',
                ],
                'nutrition' => [
                    'calories' => '95 kkal',
                    'caffeine' => '0 mg',
                    'sugar' => '16 g',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen (Non-Alcoholic)',
                    'serving' => 'Iced dalam Gelas Tinggi Bertingkat',
                ],
                'flavor_notes' => 'Citrus Burst, Crisp Sparkling, Tropical Sweetness, Cooling',
                'barista_notes' => 'Nuansa biru laut menyegarkan yang sangat fotogenik dan melepas dahaga.',
            ],
            'citrus-cold-brew-tonic' => [
                'ingredients' => [
                    'Cold brew Arabika Flores konsentrasi tinggi',
                    'Premium tonic water bersoda',
                    'Perasan air jeruk manis Sunkist asli',
                    'Garnish potongan jeruk dehidrasi & rosemary',
                ],
                'nutrition' => [
                    'calories' => '60 kkal',
                    'caffeine' => '90 mg',
                    'sugar' => '10 g',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen (Non-Alcoholic)',
                    'serving' => 'Iced Sparkling Coffee Tonic',
                ],
                'flavor_notes' => 'Bittersweet Tonic, Bright Orange Citrus, Crisp Coffee Kick',
                'barista_notes' => 'Perpaduan modern kopi dingin bersoda yang memberikan kesegaran instan.',
            ],
            'virgin-mojito-mint' => [
                'ingredients' => [
                    'Daun mint segar ditumbuk lembut (Muddled fresh mint)',
                    'Air perasan jeruk nipis murni',
                    'Gula tebu kristal alami',
                    'Air soda dingin berkarbonasi & es serut kristal',
                ],
                'nutrition' => [
                    'calories' => '85 kkal',
                    'caffeine' => '0 mg',
                    'sugar' => '15 g',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen (Vegan & 100% Herbal Alami)',
                    'serving' => 'Iced dengan Tangkai Mint Segar',
                ],
                'flavor_notes' => 'Super Fresh Minty, Tart Lime Acidity, Bubbly Fizz, Clean Palate',
                'barista_notes' => 'Pilihan penyegar mulut (*palate cleanser*) terbaik setelah menyantap hidangan.',
            ],
            'chamomile-blossom' => [
                'ingredients' => [
                    '100% Kuntum bunga chamomile utuh pilihan (Whole Dried Flowers)',
                    'Air panas terfiltrasi bersuhu 95°C',
                    'Seduhan teh herbal murni tanpa tambahan perisa',
                ],
                'nutrition' => [
                    'calories' => '2 kkal',
                    'caffeine' => '0 mg (100% Caffeine-Free)',
                    'sugar' => '0 g',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen (Organik Alami)',
                    'serving' => 'Hot dalam Teko Kaca (Glass Teapot)',
                ],
                'flavor_notes' => 'Floral Delikat, Sweet Apple Honey Aroma, Relaksasi Menenangkan',
                'barista_notes' => 'Kandungan antioksidan apigenin alami membantu menenangkan saraf dan meningkatkan kualitas tidur.',
            ],
            'earl-grey-lavender' => [
                'ingredients' => [
                    'Pucuk daun teh hitam Ceylon murni',
                    'Minyak esensial kulit bergamot alami Calabria Italia',
                    'Kuntum bunga lavender kering Prancis',
                ],
                'nutrition' => [
                    'calories' => '2 kkal',
                    'caffeine' => '45 mg',
                    'sugar' => '0 g',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen',
                    'serving' => 'Hot Tea (Dapat dipadukan susu hangat)',
                ],
                'flavor_notes' => 'Aromatic Bergamot Citrus, Soothing Lavender Floral, Bold Black Tea',
                'barista_notes' => 'Teh beraroma bangsawan klasik yang menyegarkan pikiran di sela waktu kerja.',
            ],
            'lemongrass-ginger-infusion' => [
                'ingredients' => [
                    'Batang serai wangi segar potong',
                    'Rimpang jahe merah segar digeprek',
                    'Daun pandan wangi alami',
                    'Madu hutan liar murni',
                ],
                'nutrition' => [
                    'calories' => '35 kkal',
                    'caffeine' => '0 mg (Bebas Kafein)',
                    'sugar' => '7 g (Madu hutan alami)',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen',
                    'serving' => 'Hot Warming Tea',
                ],
                'flavor_notes' => 'Spicy Warm Ginger, Pandan Fragrance, Citrusy Lemongrass, Honey Sweet',
                'barista_notes' => 'Seduhan rempah tradisional yang menghangatkan tubuh dan meningkatkan daya tahan imun.',
            ],
            'peppermint-fresh-tea' => [
                'ingredients' => [
                    '100% Daun peppermint kering pilihan kualitas premium',
                    'Air seduhan murni suhu 95°C',
                ],
                'nutrition' => [
                    'calories' => '2 kkal',
                    'caffeine' => '0 mg (Bebas Kafein)',
                    'sugar' => '0 g',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen',
                    'serving' => 'Hot atau Iced',
                ],
                'flavor_notes' => 'Cooling Menthol, Fresh Crisp Herb, Throat Soothing',
                'barista_notes' => 'Sangat bermanfaat melegakan pernapasan dan meredakan rasa begah pada perut.',
            ],
            'artisan-jasmine-green-tea' => [
                'ingredients' => [
                    'Pucuk daun teh hijau pilihan (First Flush Green Tea)',
                    'Kuntum bunga melati asli (Scents of Natural Jasmine Flowers)',
                ],
                'nutrition' => [
                    'calories' => '2 kkal',
                    'caffeine' => '25 mg',
                    'sugar' => '0 g',
                    'fat' => '0 g',
                    'allergens' => 'Bebas Alergen',
                    'serving' => 'Hot dalam Cangkir Keramik',
                ],
                'flavor_notes' => 'Semerbak Bunga Melati Asli, Halus Lembut, Low Astringency',
                'barista_notes' => 'Diinfus berulang kali secara tradisional dengan bunga melati segar sebelum dikeringkan.',
            ],
            'pisang-goreng-keju' => [
                'ingredients' => [
                    'Pisang raja manis matang pohon pilihan',
                    'Adonan tepung crispy rahasia berbumbu',
                    'Limpahan parutan keju cheddar gurih',
                    'Susu kental manis vanilla murni',
                ],
                'nutrition' => [
                    'calories' => '320 kkal',
                    'caffeine' => '0 mg',
                    'sugar' => '22 g',
                    'fat' => '12 g',
                    'allergens' => 'Mengandung Gluten (Terigu) & Produk Susu (Keju)',
                    'serving' => 'Freshly Fried Crispy',
                ],
                'flavor_notes' => 'Kulit Luar Renyah Krispi, Pisang Lembut Lumer, Gurih Asin Keju',
                'barista_notes' => 'Camilan lokal terfavorit sebagai teman ngopi hitam di pagi atau sore hari.',
            ],
            'french-fries' => [
                'ingredients' => [
                    'Kentang shoestring impor kualitas premium',
                    'Minyak nabati pilihan (Deep fried presisi)',
                    'Taburan garam laut alami (Natural Sea Salt)',
                    'Saus cabai & mayones pelengkap',
                ],
                'nutrition' => [
                    'calories' => '260 kkal',
                    'caffeine' => '0 mg',
                    'sugar' => '1 g',
                    'fat' => '14 g',
                    'allergens' => 'Diproses di fasilitas yang menangani gandum',
                    'serving' => 'Hot & Crispy Basket',
                ],
                'flavor_notes' => 'Golden Crispy Exterior, Fluffy Potato Interior, Savory Salted',
                'barista_notes' => 'Digoreng baru saat pesanan diterima agar kerenyahan tetap maksimal.',
            ],
            'crispy-chicken-tenders' => [
                'ingredients' => [
                    '150g Fillet dada ayam segar tanpa tulang',
                    'Marinasi rempah bawang putih & paprika',
                    'Tepung bumbu crispy renyah keemasan',
                    'Saus honey mustard spesial kafe',
                ],
                'nutrition' => [
                    'calories' => '380 kkal',
                    'protein' => '28 g (Tinggi Protein)',
                    'sugar' => '5 g',
                    'fat' => '16 g',
                    'allergens' => 'Mengandung Gluten & Telur',
                    'serving' => 'Sajian Hangat dengan Saus Cocolan',
                ],
                'flavor_notes' => 'Juicy Tender Chicken, Super Crunchy Crust, Sweet Savory Dip',
                'barista_notes' => 'Sumber protein tinggi yang lezat dan mengenyangkan untuk camilan santai.',
            ],
            'butter-croissant' => [
                'ingredients' => [
                    '100% French Butter AOP mentega impor berkualitas tinggi',
                    'Tepung gandum giling halus protein tinggi',
                    'Ragi alami & garam laut murni',
                    'Kilap olesan kuning telur (Golden egg wash)',
                ],
                'nutrition' => [
                    'calories' => '290 kkal',
                    'caffeine' => '0 mg',
                    'sugar' => '4 g',
                    'fat' => '16 g (Pure Dairy Butter)',
                    'allergens' => 'Mengandung Gluten (Gandum) & Produk Susu (Butter)',
                    'serving' => 'Freshly Baked & Warm Re-heated',
                ],
                'flavor_notes' => 'Flaky Delicate Layers, Aromatic French Butter, Honeycomb Crumb',
                'barista_notes' => 'Dihangatkan sesaat sebelum disajikan agar remahannya garing dan aromanya merebak.',
            ],
            'pain-au-chocolat' => [
                'ingredients' => [
                    'Adonan pastry croissant laminasi French butter',
                    'Dua batang isian cokelat couverture dark 55% Belgia',
                    'Glaze gula kilap keemasan',
                ],
                'nutrition' => [
                    'calories' => '340 kkal',
                    'caffeine' => '10 mg',
                    'sugar' => '16 g',
                    'fat' => '19 g',
                    'allergens' => 'Mengandung Gluten, Susu & Kedelai (Lecithin)',
                    'serving' => 'Warm Pastry',
                ],
                'flavor_notes' => 'Flaky Buttery Crust, Rich Melty Chocolate Center, Sweet Balance',
                'barista_notes' => 'Pastry khas kafe Prancis dengan lelehan cokelat hangat di setiap gigitan.',
            ],
            'cinnamon-roll' => [
                'ingredients' => [
                    'Roti brioche mentega lembut mengembang',
                    'Isian rempah kayu manis Korintje aromatik & brown sugar',
                    'Siraman topping cream cheese vanilla frosting gurih manis',
                ],
                'nutrition' => [
                    'calories' => '360 kkal',
                    'caffeine' => '0 mg',
                    'sugar' => '28 g',
                    'fat' => '15 g',
                    'allergens' => 'Mengandung Gluten, Susu, Telur & Kayu Manis',
                    'serving' => 'Warm Bun dengan Lumeran Cream Cheese',
                ],
                'flavor_notes' => 'Aromatic Warm Cinnamon, Pillowy Soft Dough, Tangy Sweet Glaze',
                'barista_notes' => 'Paling pas dinikmati bersama secangkir Americano atau V60 hangat.',
            ],
        ];
    }
}
