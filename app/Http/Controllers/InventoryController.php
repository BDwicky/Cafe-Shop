<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Menu;
use App\Models\MenuRecipe;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Halaman master bahan baku & stok real-time.
     */
    public function index(Request $request, InventoryService $service): View
    {
        $search = trim((string) $request->query('search', ''));
        $category = $request->query('category', 'all');
        $status = $request->query('status', 'all');

        $query = Ingredient::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($category !== 'all' && $category !== '') {
            $query->where('category', $category);
        }

        if ($status === 'low_stock') {
            $query->whereColumn('current_stock', '<=', 'minimum_stock')
                ->where('current_stock', '>', 0);
        } elseif ($status === 'out_of_stock') {
            $query->where('current_stock', '<=', 0);
        } elseif ($status === 'safe') {
            $query->whereColumn('current_stock', '>', 'minimum_stock');
        }

        $ingredients = $query->orderBy('name')->paginate(20)->withQueryString();
        $stats = $service->getInventoryStats();
        $allCategories = Ingredient::CATEGORIES;

        return view('kasir.inventory.index', compact('ingredients', 'stats', 'search', 'category', 'status', 'allCategories'));
    }

    /**
     * Tambah bahan baku baru.
     */
    public function store(Request $request, InventoryService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:ingredients,code'],
            'unit' => ['required', 'string', 'in:gr,ml,pcs'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(Ingredient::CATEGORIES))],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'cost_per_unit' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $initialStock = (float) $validated['current_stock'];
        $validated['current_stock'] = 0; // Mulai dari 0 lalu sesuaikan dengan mutasi jika ada stok awal

        $ingredient = Ingredient::create($validated);

        if ($initialStock > 0) {
            $service->recordAdjustment(
                $ingredient,
                $initialStock,
                'Stok awal bahan baku baru',
                $request->user()
            );
        }

        return redirect()->route('kasir.inventory.index')
            ->with('success', "Bahan baku \"{$ingredient->name}\" berhasil ditambahkan.");
    }

    /**
     * Update data master bahan baku.
     */
    public function update(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:ingredients,code,'.$ingredient->id],
            'unit' => ['required', 'string', 'in:gr,ml,pcs'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(Ingredient::CATEGORIES))],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'cost_per_unit' => ['required', 'integer', 'min:0'],
        ]);

        $ingredient->update($validated);

        return redirect()->route('kasir.inventory.index')
            ->with('success', "Data bahan baku \"{$ingredient->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus bahan baku jika tidak terikat ke resep aktif.
     */
    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        $recipesCount = $ingredient->recipes()->count();
        if ($recipesCount > 0) {
            return back()->with('error', "Bahan \"{$ingredient->name}\" tidak dapat dihapus karena digunakan pada {$recipesCount} resep menu.");
        }

        $name = $ingredient->name;
        $ingredient->delete();

        return redirect()->route('kasir.inventory.index')
            ->with('success', "Bahan baku \"{$name}\" berhasil dihapus.");
    }

    /**
     * Restock / Kulakan bahan baku.
     */
    public function restock(Request $request, InventoryService $service): RedirectResponse
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'cost_per_unit' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
            'record_as_expense' => ['nullable', 'boolean'],
        ]);

        $ingredient = Ingredient::findOrFail($validated['ingredient_id']);
        $quantity = (float) $validated['quantity'];
        $costPerUnit = ! empty($validated['cost_per_unit']) ? (int) $validated['cost_per_unit'] : null;
        $recordExpense = $request->boolean('record_as_expense', true);

        $service->recordRestock(
            $ingredient,
            $quantity,
            $costPerUnit,
            $request->user(),
            $validated['notes'] ?? null,
            $recordExpense
        );

        return back()->with('success', "Berhasil restock {$quantity} {$ingredient->unit} untuk \"{$ingredient->name}\".");
    }

    /**
     * Catat bahan rusak, tumpah, atau kedaluwarsa (Waste/Spill).
     */
    public function waste(Request $request, InventoryService $service): RedirectResponse
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['required', 'string', 'max:255'],
        ]);

        $ingredient = Ingredient::findOrFail($validated['ingredient_id']);
        $quantity = (float) $validated['quantity'];

        if ($quantity > (float) $ingredient->current_stock) {
            return back()->with('error', "Jumlah terbuang ({$quantity} {$ingredient->unit}) melebihi stok yang ada ({$ingredient->formatted_stock}).");
        }

        $service->recordWaste(
            $ingredient,
            $quantity,
            $validated['notes'],
            $request->user()
        );

        return back()->with('success', "Berhasil mencatat {$quantity} {$ingredient->unit} \"{$ingredient->name}\" sebagai waste.");
    }

    /**
     * Penyesuaian stok fisik (Stock Opname).
     */
    public function adjustment(Request $request, InventoryService $service): RedirectResponse
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'actual_stock' => ['required', 'numeric', 'min:0'],
            'notes' => ['required', 'string', 'max:255'],
        ]);

        $ingredient = Ingredient::findOrFail($validated['ingredient_id']);
        $actualStock = (float) $validated['actual_stock'];

        $service->recordAdjustment(
            $ingredient,
            $actualStock,
            $validated['notes'],
            $request->user()
        );

        return back()->with('success', "Stok \"{$ingredient->name}\" berhasil disesuaikan menjadi {$actualStock} {$ingredient->unit}.");
    }

    /**
     * Kartu riwayat mutasi stok (Ledger/Audit trail).
     */
    public function history(Request $request): View
    {
        $ingredientId = $request->query('ingredient_id');
        $type = $request->query('type', 'all');
        $date = $request->query('date');

        $query = InventoryMovement::with(['ingredient', 'user']);

        if ($ingredientId && $ingredientId !== 'all') {
            $query->where('ingredient_id', $ingredientId);
        }

        if ($type !== 'all' && $type !== '') {
            $query->where('type', $type);
        }

        if ($date) {
            $dateCarbon = Carbon::createFromFormat('Y-m-d', $date);
            $query->whereBetween('created_at', [$dateCarbon->copy()->startOfDay(), $dateCarbon->copy()->endOfDay()]);
        }

        $movements = $query->orderByDesc('id')->paginate(25)->withQueryString();
        $ingredients = Ingredient::orderBy('name')->get();

        return view('kasir.inventory.history', compact('movements', 'ingredients', 'ingredientId', 'type', 'date'));
    }

    /**
     * Halaman manajemen Resep Menu (Bill of Materials / BOM).
     */
    public function recipes(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id', 'all');

        $query = Menu::with(['category', 'recipes.ingredient'])->orderBy('sort_order');

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($categoryId !== 'all' && $categoryId !== '') {
            $query->where('category_id', $categoryId);
        }

        $menus = $query->paginate(15)->withQueryString();
        $ingredients = Ingredient::orderBy('name')->get();
        $categories = Category::orderBy('sort_order')->get();

        return view('kasir.inventory.recipes', compact('menus', 'ingredients', 'categories', 'search', 'categoryId'));
    }

    /**
     * Simpan / Perbarui resep BOM untuk menu tertentu.
     */
    public function updateRecipe(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'recipes' => ['nullable', 'array'],
            'recipes.*.ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'recipes.*.amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $recipes = $validated['recipes'] ?? [];

        // Hapus resep lama
        $menu->recipes()->delete();

        // Tambah resep baru
        foreach ($recipes as $item) {
            MenuRecipe::create([
                'menu_id' => $menu->id,
                'ingredient_id' => $item['ingredient_id'],
                'amount' => (float) $item['amount'],
            ]);
        }

        return back()->with('success', "Resep BOM untuk menu \"{$menu->name}\" berhasil disimpan.");
    }
}
