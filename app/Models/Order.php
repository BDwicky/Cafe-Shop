<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'music_code', 'user_id', 'order_type', 'customer_name', 'payment_method',
        'subtotal', 'discount', 'promo_id', 'promo_code', 'total', 'paid_amount', 'change_amount', 'status', 'prep_status',
        'ready_at', 'announced_at', 'completed_at', 'music_request_used_at', 'note',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'discount' => 'integer',
        'total' => 'integer',
        'paid_amount' => 'integer',
        'change_amount' => 'integer',
        'ready_at' => 'datetime',
        'announced_at' => 'datetime',
        'completed_at' => 'datetime',
        'music_request_used_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->music_code)) {
                $order->music_code = static::generateUniqueMusicCode();
            }
            if (empty($order->prep_status)) {
                $order->prep_status = 'pending';
            }
        });
    }

    public static function generateUniqueMusicCode(): string
    {
        do {
            $code = 'MK-'.strtoupper(Str::random(6));
        } while (static::where('music_code', $code)->exists());

        return $code;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->cashier();
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }

    public function musicRequest(): HasOne
    {
        return $this->hasOne(MusicRequest::class);
    }

    public function canRequestMusic(): bool
    {
        return $this->status === 'paid' && $this->music_request_used_at === null;
    }

    public function scopePrepActive(Builder $query): Builder
    {
        return $query->where('status', 'paid')
            ->where('prep_status', '!=', 'completed')
            ->orderBy('id', 'asc');
    }

    public function scopePrepPending(Builder $query): Builder
    {
        return $query->where('prep_status', 'pending');
    }

    public function scopePrepPreparing(Builder $query): Builder
    {
        return $query->where('prep_status', 'preparing');
    }

    public function scopePrepReady(Builder $query): Builder
    {
        return $query->where('prep_status', 'ready');
    }

    public function scopeUnannouncedReady(Builder $query): Builder
    {
        return $query->where('prep_status', 'ready')
            ->whereNull('announced_at')
            ->orderBy('ready_at', 'asc');
    }

    public function markPreparing(): void
    {
        $this->update(['prep_status' => 'preparing']);
    }

    public function markReady(): void
    {
        $this->update([
            'prep_status' => 'ready',
            'ready_at' => now(),
            'announced_at' => null,
        ]);
    }

    public function markAnnounced(): void
    {
        $this->update(['announced_at' => now()]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'prep_status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
