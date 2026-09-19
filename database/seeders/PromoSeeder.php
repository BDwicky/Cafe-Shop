<?php

namespace Database\Seeders;

use App\Models\Promo;
use Illuminate\Database\Seeder;

class PromoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promos = [
            [
                'code' => 'DISKON10',
                'name' => 'Diskon 10% Semua Menu',
                'description' => 'Potongan 10% untuk semua menu tanpa syarat minimal belanja',
                'type' => 'percentage',
                'discount_value' => 10,
                'max_discount' => null,
                'min_order' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'KOPIHEMAT',
                'name' => 'Kopi Hemat 20%',
                'description' => 'Diskon 20% Min. belanja Rp 35.000, Maks. diskon Rp 15.000',
                'type' => 'percentage',
                'discount_value' => 20,
                'max_discount' => 15000,
                'min_order' => 35000,
                'is_active' => true,
            ],
            [
                'code' => 'CASH5K',
                'name' => 'Voucher Potongan Rp 5.000',
                'description' => 'Potongan langsung Rp 5.000 dengan minimal belanja Rp 25.000',
                'type' => 'fixed',
                'discount_value' => 5000,
                'max_discount' => null,
                'min_order' => 25000,
                'is_active' => true,
            ],
            [
                'code' => 'SENJA30',
                'name' => 'Spesial Kopi Senja 30%',
                'description' => 'Diskon 30% Min. belanja Rp 50.000, Maks. diskon Rp 25.000',
                'type' => 'percentage',
                'discount_value' => 30,
                'max_discount' => 25000,
                'min_order' => 50000,
                'is_active' => true,
            ],
        ];

        foreach ($promos as $data) {
            Promo::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
