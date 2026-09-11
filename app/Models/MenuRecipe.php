<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuRecipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'ingredient_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Hitung biaya modal HPP bahan ini per porsi menu.
     */
    public function getCostAttribute(): int
    {
        if (! $this->ingredient) {
            return 0;
        }

        return (int) round($this->amount * (float) $this->ingredient->cost_per_unit);
    }
}
