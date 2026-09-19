<?php

namespace App\Models;

use Database\Factories\PromoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promo extends Model
{
    /** @use HasFactory<PromoFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'discount_value',
        'max_discount',
        'min_order',
        'start_date',
        'end_date',
        'usage_limit',
        'used_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'max_discount' => 'integer',
            'min_order' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Cek apakah promo valid untuk subtotal tertentu.
     *
     * @return array{valid: bool, reason: ?string}
     */
    public function canBeUsedFor(int $subtotal): array
    {
        if (! $this->is_active) {
            return ['valid' => false, 'reason' => 'Kode promo sedang tidak aktif.'];
        }

        if ($this->start_date && now()->lt($this->start_date)) {
            return ['valid' => false, 'reason' => 'Kode promo baru mulai berlaku pada '.$this->start_date->translatedFormat('d M Y H:i').'.'];
        }

        if ($this->end_date && now()->gt($this->end_date)) {
            return ['valid' => false, 'reason' => 'Kode promo sudah berakhir (kadaluarsa).'];
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return ['valid' => false, 'reason' => 'Kuota penggunaan kode promo sudah habis.'];
        }

        if ($subtotal < $this->min_order) {
            return [
                'valid' => false,
                'reason' => 'Minimal belanja Rp '.number_format($this->min_order, 0, ',', '.').' untuk menggunakan promo ini.',
            ];
        }

        return ['valid' => true, 'reason' => null];
    }

    /**
     * Hitung nilai nominal diskon dari subtotal.
     */
    public function calculateDiscount(int $subtotal): int
    {
        if ($subtotal <= 0) {
            return 0;
        }

        $check = $this->canBeUsedFor($subtotal);
        if (! $check['valid']) {
            return 0;
        }

        if ($this->type === 'percentage') {
            $calc = (int) round(($subtotal * $this->discount_value) / 100);
            if ($this->max_discount !== null && $this->max_discount > 0) {
                $calc = min($calc, $this->max_discount);
            }

            return max(0, min($calc, $subtotal));
        }

        // Fixed rupiah discount
        return max(0, min($this->discount_value, $subtotal));
    }
}
