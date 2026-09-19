<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrayerTimeService
{
    /**
     * Koordinat Geografis Wilayah Surabaya & Sidoarjo, Jawa Timur
     */
    public const LATITUDE = -7.2575; // Surabaya / Sidoarjo

    public const LONGITUDE = 112.7521;

    public const TIMEZONE = 7; // WIB (UTC+7)

    public const IHTIYAT_MINUTES = 2; // Pengaman waktu hisab standar Kemenag RI (+2 menit)

    /**
     * Dapatkan jadwal sholat untuk hari ini atau tanggal tertentu (WIB).
     *
     * @return array{
     *     city: string,
     *     date: string,
     *     date_formatted: string,
     *     schedule: array{
     *         subuh: string,
     *         terbit: string,
     *         dzuhur: string,
     *         ashar: string,
     *         maghrib: string,
     *         isya: string
     *     },
     *     next_prayer: array{
     *         name: string,
     *         time: string,
     *         minutes_left: int
     *     }|null,
     *     active_prayer: array{
     *         name: string,
     *         time: string,
     *         duration_minutes: int,
     *         minutes_remaining: int
     *     }|null
     * }
     */
    public static function getSchedule(?Carbon $date = null, int $adzanDurationMinutes = 5): array
    {
        $date = $date ? $date->copy()->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');
        $dateKey = $date->format('Y-m-d');
        $cacheKey = "prayer_times_sby_sid_{$dateKey}";

        $schedule = Cache::remember($cacheKey, 86400, function () use ($date) {
            return self::fetchOrCalculateSchedule($date);
        });

        $now = Carbon::now('Asia/Jakarta');
        $nextPrayer = self::determineNextPrayer($schedule, $now);
        $activePrayer = self::determineActivePrayer($schedule, $now, $adzanDurationMinutes);

        return [
            'city' => 'Surabaya & Sidoarjo',
            'region' => 'Jawa Timur (WIB)',
            'date' => $dateKey,
            'date_formatted' => $date->translatedFormat('l, d F Y'),
            'schedule' => $schedule,
            'next_prayer' => $nextPrayer,
            'active_prayer' => $activePrayer,
        ];
    }

    /**
     * Ambil dari API Kemenag / Bimas Islam atau kalkulasi hisab astronomis jika offline.
     */
    protected static function fetchOrCalculateSchedule(Carbon $date): array
    {
        // 1. Coba ambil dari API Kemenag / MyQuran untuk Kota Surabaya (ID: 1638)
        try {
            $year = $date->format('Y');
            $month = $date->format('m');
            $day = $date->format('d');
            $response = Http::timeout(3)
                ->get("https://api.myquran.com/v2/sholat/jadwal/1638/{$year}/{$month}/{$day}");

            if ($response->successful()) {
                $data = $response->json('data.jadwal');
                if (is_array($data) && ! empty($data['subuh']) && ! empty($data['maghrib'])) {
                    return [
                        'subuh' => $data['subuh'],
                        'terbit' => $data['terbit'] ?? '05:30',
                        'dzuhur' => $data['dzuhur'],
                        'ashar' => $data['ashar'],
                        'maghrib' => $data['maghrib'],
                        'isya' => $data['isya'],
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Log fallback dan lanjutkan ke hisab lokal tanpa henti
            Log::info('[PrayerTimeService] External API unreachable, using local astronomical calculation: '.$e->getMessage());
        }

        // 2. Fallback hisab astronomis presisi standar Kemenag RI (100% offline & akurat)
        return self::calculateAstronomicalSchedule($date);
    }

    /**
     * Hisab astronomis waktu sholat dengan parameter Kementerian Agama Republik Indonesia:
     * - Subuh: Sudut depresi matahari 20°
     * - Dzuhur: Zawal (Transit matahari) + 2 menit ihtiyat
     * - Ashar: Bayangan benda 1x (Madzhab Syafi'i) + panjang bayangan saat zawal
     * - Maghrib: Terbenam piringan atas matahari (-0.833°) + 2 menit ihtiyat
     * - Isya: Sudut depresi matahari 18°
     */
    public static function calculateAstronomicalSchedule(Carbon $date): array
    {
        $dayOfYear = (int) $date->format('z') + 1; // 1 - 366
        $lat = deg2rad(self::LATITUDE);
        $lon = self::LONGITUDE;
        $tz = self::TIMEZONE;

        // Fraksi tahun (gamma) dalam radian
        $gamma = 2 * M_PI / 365 * ($dayOfYear - 1 + (12 - 12) / 24);

        // Persamaan waktu (Equation of Time dalam menit)
        $eqtime = 229.18 * (0.000075
            + 0.001868 * cos($gamma) - 0.032077 * sin($gamma)
            - 0.014615 * cos(2 * $gamma) - 0.040849 * sin(2 * $gamma));

        // Deklinasi matahari (radian)
        $decl = 0.006918
            - 0.399912 * cos($gamma) + 0.070257 * sin($gamma)
            - 0.006758 * cos(2 * $gamma) + 0.000907 * sin(2 * $gamma)
            - 0.002697 * cos(3 * $gamma) + 0.00148 * sin(3 * $gamma);

        // Waktu transit matahari (Dzuhur hakiki dalam jam desimal)
        $transit = 12 + ($tz * 15 - $lon) / 15 - $eqtime / 60;

        // Fungsi sudut jam (Hour Angle)
        $hourAngle = function (float $angleDegree) use ($lat, $decl): ?float {
            $altitude = deg2rad(-$angleDegree);
            $cosH = (sin($altitude) - sin($lat) * sin($decl)) / (cos($lat) * cos($decl));
            if ($cosH < -1 || $cosH > 1) {
                return null;
            }

            return rad2deg(acos($cosH));
        };

        // 1. Subuh (Kemenag RI: 20°)
        $hFajr = $hourAngle(20.0);
        $subuhHours = $transit - ($hFajr / 15) + (self::IHTIYAT_MINUTES / 60);

        // 2. Terbit (Matahari terbit: 0.833°)
        $hSunrise = $hourAngle(0.833);
        $terbitHours = $transit - ($hSunrise / 15) - (self::IHTIYAT_MINUTES / 60);

        // 3. Dzuhur (Transit + 2 menit ihtiyat)
        $dzuhurHours = $transit + (self::IHTIYAT_MINUTES / 60);

        // 4. Ashar (Syafi'i: bayangan = 1 + bayangan saat zawal)
        $noonAltitude = M_PI / 2 - abs($lat - $decl);
        $noonShadow = 1 / tan($noonAltitude);
        $asrAltitude = atan(1 / (1 + $noonShadow));
        $cosHAsr = (sin($asrAltitude) - sin($lat) * sin($decl)) / (cos($lat) * cos($decl));
        $hAsr = ($cosHAsr >= -1 && $cosHAsr <= 1) ? rad2deg(acos($cosHAsr)) : 0;
        $asharHours = $transit + ($hAsr / 15) + (self::IHTIYAT_MINUTES / 60);

        // 5. Maghrib (Terbenam: 0.833° + 2 menit ihtiyat)
        $maghribHours = $transit + ($hSunrise / 15) + (self::IHTIYAT_MINUTES / 60);

        // 6. Isya (Kemenag RI: 18° + 2 menit ihtiyat)
        $hIsha = $hourAngle(18.0);
        $isyaHours = $transit + ($hIsha / 15) + (self::IHTIYAT_MINUTES / 60);

        return [
            'subuh' => self::decimalHoursToTimeString($subuhHours),
            'terbit' => self::decimalHoursToTimeString($terbitHours),
            'dzuhur' => self::decimalHoursToTimeString($dzuhurHours),
            'ashar' => self::decimalHoursToTimeString($asharHours),
            'maghrib' => self::decimalHoursToTimeString($maghribHours),
            'isya' => self::decimalHoursToTimeString($isyaHours),
        ];
    }

    /**
     * Konversi jam desimal ke format "HH:MM"
     */
    protected static function decimalHoursToTimeString(float $hours): string
    {
        $hours = fmod($hours + 24, 24);
        $h = (int) floor($hours);
        $m = (int) round(($hours - $h) * 60);
        if ($m >= 60) {
            $h = ($h + 1) % 24;
            $m = 0;
        }

        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * Tentukan waktu sholat berikutnya beserta sisa menit.
     */
    protected static function determineNextPrayer(array $schedule, Carbon $now): ?array
    {
        $todayStr = $now->format('Y-m-d');
        $prayerNames = [
            'subuh' => 'Subuh',
            'dzuhur' => 'Dzuhur',
            'ashar' => 'Ashar',
            'maghrib' => 'Maghrib',
            'isya' => 'Isya',
        ];

        foreach ($prayerNames as $key => $name) {
            if (empty($schedule[$key])) {
                continue;
            }
            $prayerTime = Carbon::createFromFormat('Y-m-d H:i', "{$todayStr} {$schedule[$key]}", 'Asia/Jakarta');
            if ($prayerTime->greaterThan($now)) {
                return [
                    'key' => $key,
                    'name' => $name,
                    'time' => $schedule[$key],
                    'minutes_left' => $now->diffInMinutes($prayerTime, false),
                ];
            }
        }

        // Jika semua sholat hari ini sudah lewat, maka sholat berikutnya adalah Subuh besok
        return [
            'key' => 'subuh',
            'name' => 'Subuh (Besok)',
            'time' => $schedule['subuh'],
            'minutes_left' => $now->diffInMinutes($now->copy()->addDay()->startOfDay()->setTimeFromTimeString($schedule['subuh']), false),
        ];
    }

    /**
     * Cek apakah saat ini sedang dalam rentang waktu adzan/sholat aktif.
     */
    protected static function determineActivePrayer(array $schedule, Carbon $now, int $durationMinutes): ?array
    {
        $todayStr = $now->format('Y-m-d');
        $prayerNames = [
            'subuh' => 'Subuh',
            'dzuhur' => ($now->isFriday() ? "Jum'at" : 'Dzuhur'),
            'ashar' => 'Ashar',
            'maghrib' => 'Maghrib',
            'isya' => 'Isya',
        ];

        foreach ($prayerNames as $key => $name) {
            if (empty($schedule[$key])) {
                continue;
            }
            $prayerStart = Carbon::createFromFormat('Y-m-d H:i', "{$todayStr} {$schedule[$key]}", 'Asia/Jakarta');
            $prayerEnd = $prayerStart->copy()->addMinutes($durationMinutes);

            if ($now->greaterThanOrEqualTo($prayerStart) && $now->lessThanOrEqualTo($prayerEnd)) {
                return [
                    'key' => $key,
                    'name' => $name,
                    'time' => $schedule[$key],
                    'duration_minutes' => $durationMinutes,
                    'minutes_remaining' => max(0, $now->diffInMinutes($prayerEnd, false)),
                ];
            }
        }

        return null;
    }
}
