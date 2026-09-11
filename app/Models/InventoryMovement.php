<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'user_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'cost_per_unit',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
        'stock_before' => 'float',
        'stock_after' => 'float',
        'cost_per_unit' => 'integer',
        'total_cost' => 'integer',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            'purchase' => 'Belanja / Restock Masuk',
            'sale' => 'Terjual (Penjualan POS)',
            'void_return' => 'Retur Batal (Void Order)',
            'waste' => 'Bahan Basi / Tumpah (Waste)',
            'adjustment' => 'Penyesuaian Stok Opname',
            default => ucfirst($this->type),
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            'purchase' => 'bg-green-100 text-green-800 border-green-200',
            'sale' => 'bg-blue-100 text-blue-800 border-blue-200',
            'void_return' => 'bg-purple-100 text-purple-800 border-purple-200',
            'waste' => 'bg-red-100 text-red-800 border-red-200',
            'adjustment' => 'bg-amber-100 text-amber-800 border-amber-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
    }
}
