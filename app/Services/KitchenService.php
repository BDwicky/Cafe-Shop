<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class KitchenService
{
    /**
     * Slug kategori minuman (Barista Station).
     *
     * @var array<int, string>
     */
    public const DRINK_CATEGORIES = [
        'kopi',
        'non-kopi',
        'cocktail',
        'mocktail',
        'tea-herbal',
    ];

    /**
     * Slug kategori makanan (Kitchen Station).
     *
     * @var array<int, string>
     */
    public const FOOD_CATEGORIES = [
        'snack',
        'pastry',
    ];

    /**
     * Ambil pesanan aktif beserta jumlah counter untuk setiap station dalam 1 kueri tunggal.
     *
     * @return array{orders: Collection<int, Order>, counts: array{all: int, barista: int, kitchen: int}}
     */
    public function getActiveOrdersWithCounts(?string $station = null): array
    {
        $allOrders = Order::prepActive()
            ->with(['items.menu.category'])
            ->get();

        $baristaOrders = $allOrders->filter(function (Order $order) {
            return $order->items->contains(function ($item) {
                $catSlug = $item->menu?->category?->slug ?? '';

                return in_array($catSlug, self::DRINK_CATEGORIES, true);
            });
        })->values();

        $kitchenOrders = $allOrders->filter(function (Order $order) {
            return $order->items->contains(function ($item) {
                $catSlug = $item->menu?->category?->slug ?? '';

                return in_array($catSlug, self::FOOD_CATEGORIES, true);
            });
        })->values();

        $stationOrders = match ($station) {
            'barista' => $baristaOrders,
            'kitchen' => $kitchenOrders,
            default => $allOrders,
        };

        return [
            'orders' => $stationOrders,
            'counts' => [
                'all' => $allOrders->count(),
                'barista' => $baristaOrders->count(),
                'kitchen' => $kitchenOrders->count(),
            ],
        ];
    }

    /**
     * Ambil pesanan aktif yang sedang diproses di dapur / bar.
     *
     * @return Collection<int, Order>
     */
    public function getActiveOrders(?string $station = null): Collection
    {
        return $this->getActiveOrdersWithCounts($station)['orders'];
    }

    /**
     * Perbarui status persiapan pesanan.
     *
     * @throws InvalidArgumentException
     */
    public function updatePrepStatus(Order $order, string $newStatus): Order
    {
        $validStatuses = ['pending', 'preparing', 'ready', 'completed'];

        if (! in_array($newStatus, $validStatuses, true)) {
            throw new InvalidArgumentException("Status persiapan '{$newStatus}' tidak valid.");
        }

        if ($newStatus === 'preparing') {
            $order->markPreparing();
        } elseif ($newStatus === 'ready') {
            $order->markReady();
        } elseif ($newStatus === 'completed') {
            $order->markCompleted();
        } else {
            $order->update(['prep_status' => 'pending']);
        }

        return $order->fresh();
    }

    /**
     * Panggil ulang (recall) pesanan yang sudah siap tapi belum diambil.
     */
    public function recall(Order $order): void
    {
        $order->update([
            'prep_status' => 'ready',
            'announced_at' => null,
        ]);
    }

    /**
     * Ambil antrean pesanan yang berstatus 'ready' dan belum diumumkan via suara.
     *
     * @return Collection<int, Order>
     */
    public function getPendingAnnouncements(): Collection
    {
        return Order::unannouncedReady()->get();
    }

    /**
     * Tandai pesanan telah dibacakan oleh voice announcer.
     */
    public function markAnnounced(Order $order): void
    {
        $order->markAnnounced();
    }
}
