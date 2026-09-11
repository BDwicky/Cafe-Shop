<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Menu;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Potong stok bahan baku secara otomatis saat order dibayar (status: paid).
     * Mencatat setiap mutasi stok bertipe 'sale'.
     */
    public function deductForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing('items.menu.recipes.ingredient');

            foreach ($order->items as $item) {
                $menu = $item->menu;
                if (! $menu) {
                    continue;
                }

                foreach ($menu->recipes as $recipe) {
                    $ingredient = $recipe->ingredient;
                    if (! $ingredient) {
                        continue;
                    }

                    $amountToDeduct = (float) ($recipe->amount * $item->qty);
                    $stockBefore = (float) $ingredient->current_stock;
                    $stockAfter = max(0, $stockBefore - $amountToDeduct);

                    $costPerUnit = (int) $ingredient->cost_per_unit;
                    $totalCost = (int) round($amountToDeduct * $costPerUnit);

                    // Update stok bahan
                    $ingredient->update(['current_stock' => $stockAfter]);

                    // Catat mutasi kartu stok
                    InventoryMovement::create([
                        'ingredient_id' => $ingredient->id,
                        'user_id' => $order->user_id,
                        'type' => 'sale',
                        'quantity' => -$amountToDeduct,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'cost_per_unit' => $costPerUnit,
                        'total_cost' => $totalCost,
                        'notes' => sprintf('Penjualan order #%s: %dx %s', $order->code, $item->qty, $item->menu_name),
                    ]);

                    // Jika stok bahan habis (0), periksa apakah menu terkait perlu dinonaktifkan
                    $this->updateMenusAvailabilityForIngredient($ingredient);
                }
            }
        });
    }

    /**
     * Kembalikan stok bahan baku saat order dibatalkan (void).
     * Mencatat setiap mutasi stok bertipe 'void_return'.
     */
    public function restoreForOrder(Order $order, ?User $user = null): void
    {
        DB::transaction(function () use ($order, $user) {
            $order->loadMissing('items.menu.recipes.ingredient');

            foreach ($order->items as $item) {
                $menu = $item->menu;
                if (! $menu) {
                    continue;
                }

                foreach ($menu->recipes as $recipe) {
                    $ingredient = $recipe->ingredient;
                    if (! $ingredient) {
                        continue;
                    }

                    $amountToRestore = (float) ($recipe->amount * $item->qty);
                    $stockBefore = (float) $ingredient->current_stock;
                    $stockAfter = $stockBefore + $amountToRestore;

                    $costPerUnit = (int) $ingredient->cost_per_unit;
                    $totalCost = (int) round($amountToRestore * $costPerUnit);

                    // Update stok bahan
                    $ingredient->update(['current_stock' => $stockAfter]);

                    // Catat mutasi kartu stok
                    InventoryMovement::create([
                        'ingredient_id' => $ingredient->id,
                        'user_id' => $user ? $user->id : $order->user_id,
                        'type' => 'void_return',
                        'quantity' => $amountToRestore,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'cost_per_unit' => $costPerUnit,
                        'total_cost' => $totalCost,
                        'notes' => sprintf('Pembatalan (Void) order #%s: %dx %s', $order->code, $item->qty, $item->menu_name),
                    ]);

                    // Cek ketersediaan menu kembali
                    $this->updateMenusAvailabilityForIngredient($ingredient);
                }
            }
        });
    }

    /**
     * Catat pembelian / restock bahan baku masuk ke gudang.
     * Dapat otomatis mencatat ke tabel Expenses.
     */
    public function recordRestock(
        Ingredient $ingredient,
        float $quantity,
        int $costPerUnit,
        ?User $user = null,
        ?string $supplier = null,
        ?string $notes = null,
        bool $recordExpense = true
    ): InventoryMovement {
        return DB::transaction(function () use ($ingredient, $quantity, $costPerUnit, $user, $supplier, $notes, $recordExpense) {
            $stockBefore = (float) $ingredient->current_stock;
            $stockAfter = $stockBefore + $quantity;
            $totalCost = (int) round($quantity * $costPerUnit);

            // Update stok dan harga modal per unit jika ada
            $ingredient->update([
                'current_stock' => $stockAfter,
                'cost_per_unit' => $costPerUnit > 0 ? $costPerUnit : $ingredient->cost_per_unit,
            ]);

            $expense = null;
            if ($recordExpense && $totalCost > 0) {
                $expense = Expense::create([
                    'user_id' => $user?->id,
                    'category' => 'restock',
                    'title' => sprintf('Restock %s (%s %s)', $ingredient->name, number_format($quantity, 2), $ingredient->unit),
                    'amount' => $totalCost,
                    'expense_date' => now()->toDateString(),
                    'payment_method' => 'cash',
                    'supplier' => $supplier,
                    'notes' => $notes ?: sprintf('Restock bahan baku %s', $ingredient->name),
                ]);
            }

            $movement = InventoryMovement::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => $user?->id,
                'type' => 'purchase',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => $expense ? Expense::class : null,
                'reference_id' => $expense?->id,
                'cost_per_unit' => $costPerUnit,
                'total_cost' => $totalCost,
                'notes' => $notes ?: ($supplier ? "Supplier: {$supplier}" : 'Restock bahan baku'),
            ]);

            $this->updateMenusAvailabilityForIngredient($ingredient);

            return $movement;
        });
    }

    /**
     * Catat bahan rusak / tumpah / basi (waste).
     */
    public function recordWaste(
        Ingredient $ingredient,
        float $quantity,
        string $reason,
        ?User $user = null
    ): InventoryMovement {
        return DB::transaction(function () use ($ingredient, $quantity, $reason, $user) {
            $stockBefore = (float) $ingredient->current_stock;
            $stockAfter = max(0, $stockBefore - $quantity);
            $costPerUnit = (int) $ingredient->cost_per_unit;
            $totalCost = (int) round($quantity * $costPerUnit);

            $ingredient->update(['current_stock' => $stockAfter]);

            $movement = InventoryMovement::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => $user?->id,
                'type' => 'waste',
                'quantity' => -$quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'cost_per_unit' => $costPerUnit,
                'total_cost' => $totalCost,
                'notes' => 'Bahan Terbuang (Waste): '.$reason,
            ]);

            $this->updateMenusAvailabilityForIngredient($ingredient);

            return $movement;
        });
    }

    /**
     * Penyesuaian stok opname manual.
     */
    public function recordAdjustment(
        Ingredient $ingredient,
        float $actualStock,
        ?string $reason = null,
        ?User $user = null
    ): InventoryMovement {
        return DB::transaction(function () use ($ingredient, $actualStock, $reason, $user) {
            $stockBefore = (float) $ingredient->current_stock;
            $difference = $actualStock - $stockBefore;
            $costPerUnit = (int) $ingredient->cost_per_unit;
            $totalCost = (int) round(abs($difference) * $costPerUnit);

            $ingredient->update(['current_stock' => $actualStock]);

            $movement = InventoryMovement::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => $user?->id,
                'type' => 'adjustment',
                'quantity' => $difference,
                'stock_before' => $stockBefore,
                'stock_after' => $actualStock,
                'cost_per_unit' => $costPerUnit,
                'total_cost' => $totalCost,
                'notes' => 'Penyesuaian Stok Opname: '.($reason ?: 'Penyelarasan stok fisik'),
            ]);

            $this->updateMenusAvailabilityForIngredient($ingredient);

            return $movement;
        });
    }

    /**
     * Hitung total HPP dari seluruh pesanan yang dibayar pada rentang waktu tertentu.
     */
    public function calculateTotalCogsForOrders(Collection $orders): int
    {
        if ($orders->isEmpty()) {
            return 0;
        }

        $orderIds = $orders->pluck('id')->all();

        return (int) InventoryMovement::where('reference_type', Order::class)
            ->whereIn('reference_id', $orderIds)
            ->where('type', 'sale')
            ->sum('total_cost');
    }

    /**
     * Sinkronkan status ketersediaan menu (is_available) berdasarkan bahan baku yang habis.
     */
    public function updateMenusAvailabilityForIngredient(Ingredient $ingredient): void
    {
        $menus = Menu::whereHas('recipes', function ($query) use ($ingredient) {
            $query->where('ingredient_id', $ingredient->id);
        })->with('recipes.ingredient')->get();

        foreach ($menus as $menu) {
            $canMake = $menu->areIngredientsInStock(1);
            if (! $canMake && $menu->is_available) {
                $menu->update(['is_available' => false]);
            }
        }
    }

    /**
     * Dapatkan ringkasan statistik stok untuk dashboard inventaris.
     */
    public function getInventoryStats(): array
    {
        $ingredients = Ingredient::all();

        $totalIngredients = $ingredients->count();
        $outOfStockCount = $ingredients->where('current_stock', '<=', 0)->count();
        $lowStockCount = $ingredients->filter(fn ($i) => $i->current_stock > 0 && $i->current_stock <= $i->minimum_stock)->count();
        $safeStockCount = $totalIngredients - $outOfStockCount - $lowStockCount;

        $totalAssetValue = (int) $ingredients->sum(fn ($i) => round($i->current_stock * $i->cost_per_unit));

        return [
            'total' => $totalIngredients,
            'total_ingredients' => $totalIngredients,
            'out_of_stock' => $outOfStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'low_stock' => $lowStockCount,
            'low_stock_count' => $lowStockCount,
            'safe_stock' => $safeStockCount,
            'total_asset_value' => $totalAssetValue,
            'total_inventory_value' => $totalAssetValue,
        ];
    }
}
