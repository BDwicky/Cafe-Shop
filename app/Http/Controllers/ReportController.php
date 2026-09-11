<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->queryReport($request);

        return view('kasir.laporan', $data);
    }

    public function receipt(Request $request)
    {
        $data = $this->queryReport($request);

        return view('kasir.laporan-receipt', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function queryReport(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::createFromFormat('Y-m-d', $request->from)->startOfDay()
            : now()->startOfDay();
        $to = $request->filled('to')
            ? Carbon::createFromFormat('Y-m-d', $request->to)->endOfDay()
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

        // 1. Total HPP Bahan Baku Terpakai (COGS) dari kartu mutasi stok
        $saleCogs = (int) InventoryMovement::where('type', 'sale')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_cost');
        $voidCogs = (int) InventoryMovement::where('type', 'void_return')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_cost');
        $cogs = max(0, $saleCogs - $voidCogs);

        $omzet = (int) $totals->omzet;
        $grossProfit = $omzet - $cogs;
        $grossMargin = $omzet > 0 ? round(($grossProfit / $omzet) * 100, 1) : 0;

        // 2. Pengeluaran Toko (Expenses)
        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();
        $totalExpenses = (int) Expense::whereBetween('expense_date', [$fromStr, $toStr])->sum('amount');
        $restockExpenses = (int) Expense::where('category', 'restock')->whereBetween('expense_date', [$fromStr, $toStr])->sum('amount');
        $operationalExpenses = (int) Expense::where('category', '!=', 'restock')->whereBetween('expense_date', [$fromStr, $toStr])->sum('amount');

        // 3. Laba Bersih Toko (Net Profit = Omzet - Total Pengeluaran)
        $netProfit = $omzet - $totalExpenses;
        $netMargin = $omzet > 0 ? round(($netProfit / $omzet) * 100, 1) : 0;

        $finance = [
            'omzet' => $omzet,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_margin' => $grossMargin,
            'total_expenses' => $totalExpenses,
            'restock_expenses' => $restockExpenses,
            'operational_expenses' => $operationalExpenses,
            'net_profit' => $netProfit,
            'net_margin' => $netMargin,
        ];

        return compact('from', 'to', 'totals', 'itemsSold', 'byMethod', 'perDay', 'best', 'finance');
    }
}
