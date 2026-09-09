<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasirController extends Controller
{
    public function index(Request $request)
    {
        $menus = Menu::with('category')->orderBy('sort_order')->get();

        return view('kasir.terminal', compact('menus'));
    }

    public function store(Request $request, OrderService $svc)
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

        // Guard: menu harus ada (bisa saja dihapus di antara waktu)
        foreach ($data['items'] as $i) {
            if (! isset($menus[$i['menu_id']])) {
                return response()->json(['message' => 'Menu tidak ditemukan.'], 422);
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

        $order = DB::transaction(function () use ($data, $lines, $calc, $request) {
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

            return $order;
        });

        return response()->json([
            'code' => $order->code,
            'receipt_url' => route('kasir.receipt', $order),
        ], 201);
    }
}
