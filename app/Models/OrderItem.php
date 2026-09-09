<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id', 'menu_id', 'menu_name', 'price', 'qty', 'line_total',
    ];

    protected $casts = [
        'price' => 'integer',
        'qty' => 'integer',
        'line_total' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
