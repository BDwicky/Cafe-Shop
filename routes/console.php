<?php

use App\Models\KasirAuthorizedDevice;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('kasir:generate-secret {--revoke-all : Cabut otorisasi semua perangkat yang ada}', function () {
    $newSecret = 'pos-sec-'.Str::random(32);
    $revokeAll = $this->option('revoke-all');

    cache()->forever('kasir_dynamic_device_secret', $newSecret);
    config(['cafe.kasir_device_secret' => $newSecret]);

    $envPath = app()->environmentFilePath();
    if (file_exists($envPath) && is_writable($envPath)) {
        $content = file_get_contents($envPath);
        if (preg_match('/^KASIR_DEVICE_SECRET=.*$/m', $content)) {
            $content = preg_replace('/^KASIR_DEVICE_SECRET=.*$/m', 'KASIR_DEVICE_SECRET='.$newSecret, $content);
        } else {
            $content .= PHP_EOL.'KASIR_DEVICE_SECRET='.$newSecret.PHP_EOL;
        }
        file_put_contents($envPath, $content);
    }

    if ($revokeAll) {
        KasirAuthorizedDevice::query()->update([
            'is_revoked' => true,
            'revoked_at' => now(),
        ]);
        $this->warn('Seluruh perangkat terdaftar telah dicabut izinnya.');
    }

    $this->info("KASIR_DEVICE_SECRET baru berhasil digenerate: {$newSecret}");
})->purpose('Generate security secret key baru untuk otorisasi perangkat kasir POS');

// ===================================================================
// JADWAL OTOMATIS REKAPITULASI PENJUALAN TELEGRAM (HARI - MINGGU - BULAN)
// ===================================================================
use Illuminate\Support\Facades\Schedule;

// 1. Rekapitulasi Harian (Perhari): Setiap malam pukul 22:00 WIB saat tutup kasir
Schedule::command('telegram:sales-recap --period=today')
    ->dailyAt('22:00')
    ->withoutOverlapping();

// 2. Rekapitulasi Mingguan (7 Hari Terakhir): Setiap hari Senin pukul 08:00 WIB
Schedule::command('telegram:sales-recap --period=7days')
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping();

// 3. Rekapitulasi Bulanan: Setiap tanggal 1 awal bulan pukul 08:00 WIB
Schedule::command('telegram:sales-recap --period=month')
    ->monthlyOn(1, '08:00')
    ->withoutOverlapping();
