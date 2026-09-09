<?php

namespace App\Services;

use Illuminate\Support\Collection;

class OrderService
{
    /**
     * Hitung subtotal/total/kembalian dari baris order.
     *
     * @param  Collection<int, array{price:int, qty:int}>  $lines
     * @return array{subtotal:int, total:int, change_amount:int}
     *
     * @throws \InvalidArgumentException
     */
    public function calculate(Collection $lines, int $discount, int $paid): array
    {
        $subtotal = (int) $lines->sum(fn ($l) => $l['price'] * $l['qty']);

        if ($discount < 0 || $discount > $subtotal) {
            throw new \InvalidArgumentException('Diskon melebihi subtotal.');
        }

        $total = $subtotal - $discount;

        if ($paid < $total) {
            throw new \InvalidArgumentException('Pembayaran kurang dari total.');
        }

        return [
            'subtotal' => $subtotal,
            'total' => $total,
            'change_amount' => $paid - $total,
        ];
    }
}
