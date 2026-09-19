<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Promo;

class PromoService
{
    /**
     * Validasi kode promo dan hitung nominal diskon untuk subtotal tertentu.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     discount: int,
     *     promo?: Promo,
     *     code?: string,
     *     name?: string,
     *     type?: string,
     *     discount_value?: int,
     *     max_discount?: ?int,
     *     min_order?: int
     * }
     */
    public function validateAndCalculate(string $code, int $subtotal): array
    {
        $cleanCode = strtoupper(trim($code));
        if ($cleanCode === '') {
            return [
                'success' => false,
                'message' => 'Kode promo tidak boleh kosong.',
                'discount' => 0,
            ];
        }

        $promo = Promo::where('code', $cleanCode)->first();
        if (! $promo) {
            return [
                'success' => false,
                'message' => 'Kode promo "'.$cleanCode.'" tidak ditemukan.',
                'discount' => 0,
            ];
        }

        $check = $promo->canBeUsedFor($subtotal);
        if (! $check['valid']) {
            return [
                'success' => false,
                'message' => $check['reason'] ?? 'Kode promo tidak dapat digunakan.',
                'discount' => 0,
                'promo' => $promo,
                'code' => $promo->code,
                'name' => $promo->name,
                'min_order' => $promo->min_order,
            ];
        }

        $discount = $promo->calculateDiscount($subtotal);

        return [
            'success' => true,
            'message' => 'Kode promo '.$promo->code.' berhasil diterapkan.',
            'discount' => $discount,
            'promo' => $promo,
            'code' => $promo->code,
            'name' => $promo->name,
            'type' => $promo->type,
            'discount_value' => $promo->discount_value,
            'max_discount' => $promo->max_discount,
            'min_order' => $promo->min_order,
        ];
    }

    /**
     * Terapkan promo ke order dan naikkan hitungan kuota pemakaian.
     */
    public function recordOrderPromo(Order $order, ?string $promoCode): void
    {
        if (! $promoCode || trim($promoCode) === '') {
            return;
        }

        $cleanCode = strtoupper(trim($promoCode));
        $promo = Promo::where('code', $cleanCode)->first();

        if ($promo) {
            $promo->increment('used_count');
            $order->update([
                'promo_id' => $promo->id,
                'promo_code' => $promo->code,
            ]);
        } else {
            $order->update([
                'promo_code' => $cleanCode,
            ]);
        }
    }
}
