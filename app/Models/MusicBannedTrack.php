<?php

namespace App\Models;

use Database\Factories\MusicBannedTrackFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusicBannedTrack extends Model
{
    /** @use HasFactory<MusicBannedTrackFactory> */
    use HasFactory;

    protected $fillable = [
        'youtube_id',
        'title',
        'artist',
        'reason',
        'banned_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
