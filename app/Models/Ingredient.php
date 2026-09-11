<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'coffee' => 'Biji Kopi (Coffee)',
        'tea' => 'Teh & Daun (Tea)',
        'dairy' => 'Susu & Creamer (Dairy)',
        'syrup' => 'Sirup & Pemanis (Syrup)',
        'powder' => 'Bubuk & Perasa (Powder)',
        'topping' => 'Topping & Pelengkap',
        'food' => 'Bahan Makanan / Pastry',
        'packaging' => 'Kemasan & Cup (Packaging)',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'name',
        'code',
        'sku',
        'category',
        'unit',
        'current_stock',
        'minimum_stock',
        'cost_per_unit',
        'notes',
    ];

    protected $casts = [
        'current_stock' => 'float',
        'minimum_stock' => 'float',
        'cost_per_unit' => 'integer',
    ];

    public function recipes(): HasMany
    {
        return $this->hasMany(MenuRecipe::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest();
    }

    /**
     * Apakah stok bahan ini sudah habis (<= 0).
     */
    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    /**
     * Apakah stok bahan menipis (<= minimum_stock tapi > 0).
     */
    public function isLowStock(): bool
    {
        return $this->current_stock > 0 && $this->current_stock <= $this->minimum_stock;
    }

    /**
     * Label status stok: 'out', 'low', 'safe'
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'out';
        }
        if ($this->isLowStock()) {
            return 'low';
        }

        return 'safe';
    }

    /**
     * Format unit tampilan (e.g., 500 gr, 1.2 liter / ml, 10 pcs).
     */
    public function getFormattedStockAttribute(): string
    {
        $val = rtrim(rtrim(number_format($this->current_stock, 2, ',', '.'), '0'), ',');

        return $val.' '.$this->unit;
    }

    public function getCategoryNameAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }
}
