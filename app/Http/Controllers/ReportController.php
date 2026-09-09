<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->filled('from')
            ? \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $request->from)->startOfDay()
            : now()->startOfDay();
        $to = $request->filled('to')
            ? \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $request->to)->endOfDay()
            : now()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $base = Order::query()
            ->where('status', 'paid')
            ->whereBetween('created_at', [$from, $to]);

        $totals = (clone $base)->selectRaw('COUNT(*) trx, COALESCE(SUM(total),0) omzet, COALESCE(AVG(total),0) avg_basket')->first();

        $itemsSold = (clone $base)
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->selectRaw('COALESCE(SUM(order_items.qty),0) q')
            ->value('q');

        $byMethod = (clone $base)
            ->selectRaw('payment_method, SUM(total) t, COUNT(*) c')
            ->groupBy('payment_method')
            ->get();

        $perDay = (clone $base)
            ->selectRaw('DATE(created_at) d, SUM(total) t, COUNT(*) c')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $best = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('order_items.menu_name, SUM(order_items.qty) qty, SUM(order_items.line_total) omzet')
            ->groupBy('order_items.menu_name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        return view('kasir.laporan', compact('from', 'to', 'totals', 'itemsSold', 'byMethod', 'perDay', 'best'));
    }
}
