<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    /**
     * Daftar seluruh pengeluaran toko / kasir.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $category = $request->query('category', 'all');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Expense::with('user');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('expense_number', 'like', "%{$search}%")
                    ->orWhere('supplier', 'like', "%{$search}%");
            });
        }

        if ($category !== 'all' && $category !== '') {
            $query->where('category', $category);
        }

        if ($dateFrom) {
            $query->whereDate('expense_date', '>=', Carbon::parse($dateFrom));
        }
        if ($dateTo) {
            $query->whereDate('expense_date', '<=', Carbon::parse($dateTo));
        }

        $expenses = (clone $query)->orderByDesc('expense_date')->orderByDesc('id')->paginate(20)->withQueryString();

        // Ringkasan statistik
        $stats = [
            'total_amount' => (int) (clone $query)->sum('amount'),
            'restock_amount' => (int) (clone $query)->where('category', 'restock')->sum('amount'),
            'operational_amount' => (int) (clone $query)->where('category', '!=', 'restock')->sum('amount'),
            'count' => (clone $query)->count(),
        ];

        $categories = Expense::CATEGORIES;

        return view('kasir.expenses.index', compact('expenses', 'stats', 'categories', 'search', 'category', 'dateFrom', 'dateTo'));
    }

    /**
     * Catat pengeluaran baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(Expense::CATEGORIES))],
            'amount' => ['required', 'integer', 'min:100'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:cash,transfer,qris'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['user_id'] = $request->user()->id;

        $expense = Expense::create($validated);

        return redirect()->route('kasir.expenses.index')
            ->with('success', "Pengeluaran #{$expense->expense_number} sebesar Rp ".number_format($expense->amount, 0, ',', '.').' berhasil dicatat.');
    }

    /**
     * Hapus catatan pengeluaran.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $number = $expense->expense_number;
        $expense->delete();

        return redirect()->route('kasir.expenses.index')
            ->with('success', "Catatan pengeluaran #{$number} berhasil dihapus.");
    }
}
