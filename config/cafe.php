<?php

// Single source of truth untuk brand & info cafe (placeholder — ganti sebelum produksi).
return [
    'name' => 'KopiKita',
    'tagline' => 'Kopi Seduh Presisi, Diseduh Untuk Kamu.',
    'address' => 'Jl. Contoh No. 123, Kota Kamu',
    'wa_number' => '6281234567890', // format internasional tanpa "+" — dipakai link wa.me
    'instagram' => '@kopikita',

    // Jam buka per hari, 1=Senin ... 7=Minggu, format [buka, tutup] "H:i"
    'hours' => [
        1 => ['08:00', '22:00'],
        2 => ['08:00', '22:00'],
        3 => ['08:00', '22:00'],
        4 => ['08:00', '22:00'],
        5 => ['08:00', '23:00'],
        6 => ['08:00', '23:00'],
        7 => ['09:00', '21:00'],
    ],

    // Info yang tercetak di struk di bawah QR code
    'wifi_ssid' => 'KopiKita WiFi',
    'wifi_password' => 'kopikita2026',

    'receipt_footer' => 'Terima kasih telah berkunjung — KopiKita',
];
