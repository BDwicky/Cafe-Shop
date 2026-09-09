<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class OpeningHours
{
    /** Apakah cafe sedang buka sekarang (timezone app = Asia/Jakarta). */
    public static function isOpen(?Carbon $at = null): bool
    {
        $now = $at ?? now();
        $h = config("cafe.hours.{$now->dayOfWeekIso}");

        if (! $h) {
            return false;
        }

        $time = $now->format('H:i');

        return $time >= $h[0] && $time < $h[1];
    }

    /** Label jam buka hari ini, mis. "08:00–22:00". */
    public static function todayLabel(): string
    {
        $h = config('cafe.hours.' . now()->dayOfWeekIso);

        return $h ? "{$h[0]}–{$h[1]}" : 'Tutup';
    }

    /** Status strip: "BUKA SEKARANG — 08:00–22:00" atau "TUTUP — buka besok 08:00". */
    public static function statusLine(): string
    {
        if (self::isOpen()) {
            return 'BUKA SEKARANG — ' . self::todayLabel();
        }

        // Cari jam buka berikutnya (besok, atau hari-hari berikutnya)
        for ($i = 1; $i <= 7; $i++) {
            $next = now()->addDays($i);
            $h = config("cafe.hours.{$next->dayOfWeekIso}");
            if ($h) {
                $day = $i === 1 ? 'buka besok' : 'buka ' . self::dayName($next->dayOfWeekIso);

                return strtoupper("TUTUP — {$day} {$h[0]}");
            }
        }

        return 'TUTUP';
    }

    public static function dayName(int $iso): string
    {
        return match ($iso) {
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
        };
    }

    /** Semua jam buka untuk ditampilkan di footer/lokasi. */
    public static function all(): array
    {
        return collect(range(1, 7))
            ->mapWithKeys(fn ($d) => [self::dayName($d) => config("cafe.hours.{$d}")])
            ->all();
    }
}
