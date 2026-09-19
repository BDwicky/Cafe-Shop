<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Promo;
use App\Services\InventoryService;
use App\Services\KitchenService;
use App\Services\OrderService;
use App\Services\PromoService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class KasirController extends Controller
{
    public function index(Request $request)
    {
        $menus = Menu::with('category')->orderBy('sort_order')->get();
        $categories = Category::withCount('menus')->orderBy('sort_order')->get();

        // Data polos untuk Alpine (hindari @json dengan ekspresi kompleks di Blade)
        $menuData = $menus->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'price' => $m->price,
            'available' => (bool) $m->is_available,
            'category_id' => $m->category_id,
            'category_name' => $m->category->name ?? '',
            'category_slug' => $m->category->slug ?? '',
            'category_order' => $m->category->sort_order ?? 999,
            'is_drink' => in_array($m->category?->slug, KitchenService::DRINK_CATEGORIES, true),
            'image' => $m->image ? asset('storage/'.$m->image) : null,
            'description' => $m->description,
        ])->values()->all();

        $activePromos = Promo::active()->get(['id', 'code', 'name', 'type', 'discount_value', 'max_discount', 'min_order']);

        return view('kasir.terminal', compact('menus', 'menuData', 'categories', 'activePromos'));
    }

    public function checkPromo(Request $request, PromoService $promoService)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'subtotal' => ['required', 'integer', 'min:0'],
        ]);

        $result = $promoService->validateAndCalculate($validated['code'], (int) $validated['subtotal']);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'discount' => 0,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'code' => $result['code'],
            'name' => $result['name'],
            'type' => $result['type'],
            'discount_value' => $result['discount_value'],
            'max_discount' => $result['max_discount'],
            'min_order' => $result['min_order'],
            'discount' => $result['discount'],
        ]);
    }

    public function store(Request $request, OrderService $svc, InventoryService $inventoryService, PromoService $promoService)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.note' => ['nullable', 'string', 'max:150'],
            'order_type' => ['required', 'in:dine_in,take_away'],
            'payment_method' => ['required', 'in:cash,qris,debit'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'paid_amount' => ['required', 'integer', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $menus = Menu::whereIn('id', collect($data['items'])->pluck('menu_id'))->get()->keyBy('id');

        foreach ($data['items'] as $i) {
            if (! isset($menus[$i['menu_id']])) {
                return response()->json(['message' => 'Menu tidak ditemukan.'], 422);
            }
            if (! $menus[$i['menu_id']]->is_available) {
                return response()->json(['message' => 'Menu "'.$menus[$i['menu_id']]->name.'" sedang tidak tersedia (stok kosong).'], 422);
            }
            if (! $menus[$i['menu_id']]->areIngredientsInStock((int) $i['qty'])) {
                return response()->json(['message' => 'Stok bahan baku untuk "'.$menus[$i['menu_id']]->name.'" tidak mencukupi.'], 422);
            }
        }

        $lines = collect($data['items'])->map(fn ($i) => [
            'menu' => $menus[$i['menu_id']],
            'price' => (int) $menus[$i['menu_id']]->price,
            'qty' => (int) $i['qty'],
            'note' => isset($i['note']) && trim((string) $i['note']) !== '' ? trim((string) $i['note']) : null,
        ]);

        $subtotal = (int) $lines->sum(fn ($l) => $l['price'] * $l['qty']);
        $promoCode = ! empty($data['promo_code']) ? strtoupper(trim((string) $data['promo_code'])) : null;
        $promoModel = null;
        $discount = (int) ($data['discount'] ?? 0);

        if ($promoCode) {
            $promoCheck = $promoService->validateAndCalculate($promoCode, $subtotal);
            if (! $promoCheck['success']) {
                return response()->json(['message' => $promoCheck['message']], 422);
            }
            $discount = $promoCheck['discount'];
            $promoModel = $promoCheck['promo'] ?? null;
        }

        try {
            $calc = $svc->calculate($lines, $discount, (int) $data['paid_amount']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $order = DB::transaction(function () use ($data, $lines, $calc, $request, $inventoryService, $promoModel, $promoCode, $discount) {
            $order = Order::create([
                'code' => 'TMP',
                'user_id' => $request->user()->id,
                'order_type' => $data['order_type'],
                'customer_name' => $data['customer_name'] ?? null,
                'payment_method' => $data['payment_method'],
                'subtotal' => $calc['subtotal'],
                'discount' => $discount,
                'promo_id' => $promoModel?->id,
                'promo_code' => $promoCode,
                'total' => $calc['total'],
                'paid_amount' => (int) $data['paid_amount'],
                'change_amount' => $calc['change_amount'],
                'status' => 'paid',
                'note' => $data['note'] ?? null,
            ]);

            foreach ($lines as $l) {
                $displayName = $l['menu']->name;
                if ($l['note']) {
                    $displayName .= ' ('.$l['note'].')';
                }

                $order->items()->create([
                    'menu_id' => $l['menu']->id,
                    'menu_name' => $displayName,
                    'price' => $l['price'],
                    'qty' => $l['qty'],
                    'line_total' => $l['price'] * $l['qty'],
                ]);
            }

            $order->update(['code' => sprintf('KKI-%s-%04d', now()->format('ymd'), $order->id)]);

            if ($promoModel) {
                $promoModel->increment('used_count');
            }

            // Potong stok bahan baku secara otomatis sesuai resep BOM
            $inventoryService->deductForOrder($order);

            return $order;
        });

        return response()->json([
            'order_id' => $order->id,
            'code' => $order->code,
            'music_code' => $order->music_code,
            'customer_name' => $order->customer_name,
            'order_type' => $order->order_type,
            'payment_method' => $order->payment_method,
            'total' => $order->total,
            'paid_amount' => $order->paid_amount,
            'change_amount' => $order->change_amount,
            'receipt_url' => route('kasir.receipt', $order, false),
            'kds_count' => Order::prepActive()->count(),
        ], 201);
    }

    public function orders(Request $request)
    {
        $date = $request->date
            ? Carbon::createFromFormat('Y-m-d', $request->date)->startOfDay()
            : now()->startOfDay();

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');
        $orderType = $request->query('order_type', 'all');

        $baseQuery = Order::whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);

        // Ringkasan metrik statistik untuk tanggal terpilih
        $stats = [
            'total_orders' => (clone $baseQuery)->count(),
            'paid_orders' => (clone $baseQuery)->where('status', 'paid')->count(),
            'net_omzet' => (int) (clone $baseQuery)->where('status', 'paid')->sum('total'),
            'void_count' => (clone $baseQuery)->where('status', 'voided')->count(),
        ];
        $stats['avg_basket'] = $stats['paid_orders'] > 0
            ? round($stats['net_omzet'] / $stats['paid_orders'])
            : 0;

        $ordersQuery = (clone $baseQuery)->with(['items', 'user', 'promo']);

        if ($search !== '') {
            $ordersQuery->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all' && in_array($status, ['paid', 'voided'])) {
            $ordersQuery->where('status', $status);
        }

        if ($orderType !== 'all' && in_array($orderType, ['dine_in', 'take_away'])) {
            $ordersQuery->where('order_type', $orderType);
        }

        $orders = $ordersQuery
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('kasir.orders', compact('orders', 'date', 'stats', 'search', 'status', 'orderType'));
    }

    public function receipt(Order $order)
    {
        $order->load('items');

        return view('kasir.receipt', compact('order'));
    }

    public function void(Order $order, Request $request, InventoryService $inventoryService)
    {
        abort_unless($order->status === 'paid', 422);

        DB::transaction(function () use ($order, $request, $inventoryService) {
            $order->update(['status' => 'voided']);
            $inventoryService->restoreForOrder($order, $request->user());
        });

        return back();
    }
}
