<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasirAuthorizedDevice extends Model
{
    use HasFactory;

    protected $table = 'kasir_authorized_devices';

    protected $fillable = [
        'device_name',
        'device_token_hash',
        'device_type',
        'platform',
        'browser',
        'ip_address',
        'user_agent',
        'is_revoked',
        'revoked_at',
        'last_active_at',
    ];

    protected $casts = [
        'is_revoked' => 'boolean',
        'revoked_at' => 'datetime',
        'last_active_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_revoked', false);
    }

    public function revoke(): bool
    {
        return $this->update([
            'is_revoked' => true,
            'revoked_at' => now(),
        ]);
    }

    public function restore(): bool
    {
        return $this->update([
            'is_revoked' => false,
            'revoked_at' => null,
            'last_active_at' => now(),
        ]);
    }

    public function touchActivity(?string $ip = null): void
    {
        $attributes = ['last_active_at' => now()];
        if ($ip && $ip !== $this->ip_address) {
            $attributes['ip_address'] = $ip;
        }

        $this->update($attributes);
    }
}
