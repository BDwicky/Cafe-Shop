<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Services\InventoryService;
use App\Services\OrderService;
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
            'image' => $m->image ? asset('storage/'.$m->image) : null,
            'description' => $m->description,
        ])->values()->all();

        return view('kasir.terminal', compact('menus', 'menuData', 'categories'));
    }

    public function store(Request $request, OrderService $svc, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'order_type' => ['required', 'in:dine_in,take_away'],
            'payment_method' => ['required', 'in:cash,qris,debit'],
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
        ]);

        try {
            $calc = $svc->calculate($lines, (int) ($data['discount'] ?? 0), (int) $data['paid_amount']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $order = DB::transaction(function () use ($data, $lines, $calc, $request, $inventoryService) {
            $order = Order::create([
                'code' => 'TMP',
                'user_id' => $request->user()->id,
                'order_type' => $data['order_type'],
                'customer_name' => $data['customer_name'] ?? null,
                'payment_method' => $data['payment_method'],
                'subtotal' => $calc['subtotal'],
                'discount' => (int) ($data['discount'] ?? 0),
                'total' => $calc['total'],
                'paid_amount' => (int) $data['paid_amount'],
                'change_amount' => $calc['change_amount'],
                'status' => 'paid',
                'note' => $data['note'] ?? null,
            ]);

            foreach ($lines as $l) {
                $order->items()->create([
                    'menu_id' => $l['menu']->id,
                    'menu_name' => $l['menu']->name,
                    'price' => $l['price'],
                    'qty' => $l['qty'],
                    'line_total' => $l['price'] * $l['qty'],
                ]);
            }

            $order->update(['code' => sprintf('KKI-%s-%04d', now()->format('ymd'), $order->id)]);

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

        $ordersQuery = (clone $baseQuery)->with(['items', 'user']);

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
