<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\KitchenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KitchenController extends Controller
{
    public function __construct(
        protected KitchenService $kitchenService
    ) {}

    /**
     * Tampilan Layar KDS (Kitchen Display System) untuk Barista & Kitchen.
     */
    public function index(Request $request): View
    {
        $station = $request->query('station', 'all');
        $activeData = $this->kitchenService->getActiveOrdersWithCounts($station);
        $orders = $activeData['orders'];
        $counts = $activeData['counts'];

        return view('kasir.kitchen', compact('orders', 'station', 'counts'));
    }

    /**
     * API JSON Realtime untuk KDS Monitor (polling berkala).
     */
    public function orders(Request $request): JsonResponse
    {
        $station = $request->query('station', 'all');
        $activeData = $this->kitchenService->getActiveOrdersWithCounts($station);
        $orders = $activeData['orders'];
        $counts = $activeData['counts'];

        $data = $orders->map(function (Order $o) use ($station) {
            $items = $o->items->map(function ($item) {
                $slug = $item->menu?->category?->slug ?? '';
                $isDrink = in_array($slug, KitchenService::DRINK_CATEGORIES, true);
                $isFood = in_array($slug, KitchenService::FOOD_CATEGORIES, true);

                return [
                    'id' => $item->id,
                    'name' => $item->menu_name,
                    'qty' => $item->qty,
                    'is_drink' => $isDrink,
                    'is_food' => $isFood,
                    'category' => $item->menu?->category?->name ?? '',
                ];
            });

            if ($station === 'barista') {
                $items = $items->where('is_drink', true)->values();
            } elseif ($station === 'kitchen') {
                $items = $items->where('is_food', true)->values();
            }

            return [
                'id' => $o->id,
                'code' => $o->code,
                'music_code' => $o->music_code,
                'customer_name' => $o->customer_name ?: 'Pelanggan',
                'order_type' => $o->order_type,
                'note' => $o->note,
                'prep_status' => $o->prep_status,
                'created_at_time' => $o->created_at->format('H:i'),
                'elapsed_minutes' => (int) $o->created_at->diffInMinutes(now()),
                'items' => $items,
            ];
        });

        return response()->json([
            'orders' => $data,
            'counts' => $counts,
        ]);
    }

    /**
     * Perbarui status pesanan (preparing, ready, completed).
     */
    public function updateStatus(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,preparing,ready,completed'],
        ]);

        $updated = $this->kitchenService->updatePrepStatus($order, $request->status);

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'message' => "Status pesanan {$order->code} berhasil diubah menjadi {$request->status}.",
                'order' => $updated,
            ]);
        }

        return back()->with('success', "Status pesanan {$order->code} diperbarui.");
    }

    /**
     * Panggil ulang (re-announce) pesanan yang siap via sound system kasir.
     */
    public function recall(Order $order): JsonResponse|RedirectResponse
    {
        $this->kitchenService->recall($order);

        if (request()->wantsJson() || request()->ajax() || request()->expectsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'message' => "Pesanan {$order->code} akan dipanggil ulang via pengeras suara.",
            ]);
        }

        return back()->with('success', "Pesanan {$order->code} dijadwalkan untuk dipanggil ulang.");
    }

    /**
     * Cetak Tiket Dapur / Barista thermal.
     */
    public function ticket(Order $order, Request $request): View
    {
        $station = $request->query('station', 'all');
        $order->load('items.menu.category');

        return view('kasir.kitchen-ticket', compact('order', 'station'));
    }
}
