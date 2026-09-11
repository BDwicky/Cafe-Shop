<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MusicRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_name',
        'song_title',
        'artist',
        'youtube_id',
        'thumbnail_url',
        'duration_seconds',
        'status',
        'played_at',
        'notes',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'played_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeQueued(Builder $query): Builder
    {
        return $query->where('status', 'queued')->orderBy('id', 'asc');
    }

    public function scopePlaying(Builder $query): Builder
    {
        return $query->where('status', 'playing');
    }

    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }

    public function isPlaying(): bool
    {
        return $this->status === 'playing';
    }
}
