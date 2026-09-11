<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'restock' => 'Belanja Bahan Baku',
        'operational' => 'Operasional (Listrik/Air/Gas)',
        'packaging' => 'Kemasan & Plastik',
        'maintenance' => 'Servis & Perawatan Alat',
        'other' => 'Pengeluaran Lainnya',
    ];

    protected $fillable = [
        'expense_number',
        'user_id',
        'category',
        'title',
        'amount',
        'expense_date',
        'payment_method',
        'supplier',
        'notes',
        'receipt_image',
    ];

    protected $casts = [
        'amount' => 'integer',
        'expense_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            if (empty($expense->expense_number)) {
                $expense->expense_number = static::generateExpenseNumber();
            }
        });
    }

    public static function generateExpenseNumber(): string
    {
        $prefix = 'EXP-'.now()->format('ymd');
        $latest = static::where('expense_number', 'like', $prefix.'%')->latest('id')->first();
        $seq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->expense_number, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return sprintf('%s-%04d', $prefix, $seq);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCategoryNameAttribute(): string
    {
        return match ($this->category) {
            'restock' => 'Belanja Bahan Baku',
            'operational' => 'Operasional (Listrik/Air/Gas)',
            'packaging' => 'Kemasan & Plastik',
            'maintenance' => 'Servis & Perawatan Alat',
            'other' => 'Pengeluaran Lainnya',
            default => ucfirst($this->category),
        };
    }
}
