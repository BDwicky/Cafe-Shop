<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Kasir Dibatasi — {{ config('cafe.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F7F3EC] text-[#2A211A] min-h-screen flex items-center justify-center p-4 sm:p-6 antialiased selection:bg-[#B5762A] selection:text-white">

    <div class="w-full max-w-lg bg-white border border-[#E4DCCC] rounded-3xl p-6 sm:p-9 shadow-[0_10px_35px_rgba(42,33,26,0.08)] relative overflow-hidden"
         x-data="{ showAuthorizeModal: false, secretKey: '' }">

        <!-- Ambient decorative coffee watermark -->
        <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full border border-[#D9973E]/10 pointer-events-none"></div>

        <!-- Header Brand & Lock Icon -->
        <div class="flex items-center justify-between pb-6 border-b border-[#E8E1D5]">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-xl bg-[#1F1812] border border-[#3A2D22] flex items-center justify-center p-2 shadow-xs group-hover:scale-105 transition-transform">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="{{ config('cafe.name') }}" class="h-full w-full object-contain">
                </div>
                <div>
                    <div class="font-bold tracking-tight text-base text-[#1F1812] leading-tight">{{ config('cafe.name') }}</div>
                    <div class="font-mono text-[9px] uppercase tracking-[0.25em] text-[#B5762A] font-semibold mt-0.5">Sistem Keamanan POS</div>
                </div>
            </a>
            <div class="w-10 h-10 rounded-full bg-rose-50 border border-rose-200 text-rose-700 flex items-center justify-center font-mono text-sm font-bold shadow-2xs">
                🔒
            </div>
        </div>

        <!-- Main Restriction Message -->
        <div class="pt-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-800 font-mono text-[10px] font-bold uppercase tracking-wider mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-pulse"></span>
                <span>Akses Khusus Jaringan Kasir</span>
            </div>

            <h1 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-[#1F1812] leading-tight">
                Akses Terminal Ditolak
            </h1>

            <p class="mt-2 text-xs sm:text-sm text-[#6B5A4B] font-serif italic leading-relaxed">
                Halaman terminal kasir ini dikunci dan hanya dapat dibuka melalui <b>jaringan Wi-Fi resmi kedai kafe</b> atau <b>perangkat tablet kasir yang telah diotorisasi</b>.
            </p>

            @if (session('error'))
                <div class="mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
                    <span class="font-bold">⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
        </div>

        <!-- Client Network Info Box -->
        <div class="mt-6 p-4 rounded-2xl bg-[#FAF7F2] border border-[#E8E1D5] space-y-2.5">
            <div class="flex items-center justify-between text-xs">
                <span class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] font-bold">IP Anda Saat Ini:</span>
                <span class="font-mono font-bold text-[#1F1812] bg-white px-2.5 py-1 rounded-lg border border-[#E8E1D5] shadow-2xs">
                    {{ $clientIp ?? request()->ip() }}
                </span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] font-bold">Status Otorisasi:</span>
                <span class="font-mono text-[11px] font-semibold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                    ✕ Di Luar Jaringan Kasir
                </span>
            </div>
        </div>

        <!-- Instructions for Staff / Owner -->
        <div class="mt-5 p-3.5 rounded-xl bg-amber-50/70 border border-amber-200/80 text-[11px] text-amber-900 leading-relaxed font-sans">
            <p class="font-bold font-mono uppercase tracking-wide text-amber-800 text-[10px] mb-1">
                Petunjuk Bagi Staf & Owner:
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-amber-950">
                <li>Pastikan perangkat terhubung ke <b>Wi-Fi Resmi Kafe ({{ config('cafe.wifi_ssid', 'WiFi Kafe') }})</b>.</li>
                <li>Jika ini adalah perangkat tablet kasir resmi baru, minta Owner untuk melakukan otorisasi perangkat sekali.</li>
            </ul>
        </div>

        <!-- Actions -->
        <div class="mt-6 pt-5 border-t border-[#E8E1D5] flex flex-col sm:flex-row items-center gap-3">
            <a href="{{ route('landing') }}"
               class="w-full sm:flex-1 text-center py-2.5 px-4 bg-[#1F1812] hover:bg-[#B5762A] text-white rounded-xl font-mono text-xs uppercase tracking-wider font-bold transition-all shadow-xs active:scale-95">
                ← Kembali ke Beranda
            </a>
            <button type="button"
                    @click="showAuthorizeModal = true"
                    class="w-full sm:w-auto text-center py-2.5 px-4 bg-white hover:bg-stone-100 text-[#5C4D3C] border border-[#D5CCC0] rounded-xl font-mono text-xs uppercase tracking-wider font-semibold transition-all shadow-2xs active:scale-95 cursor-pointer">
                🔑 Otorisasi Perangkat
            </button>
        </div>

        <!-- Modal Otorisasi Kunci Perangkat (Untuk Owner Mendaftarkan Tablet Baru) -->
        <div x-show="showAuthorizeModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs"
             style="display: none;">
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-6 max-w-sm w-full shadow-2xl relative"
                 @click.away="showAuthorizeModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-[#E8E1D5]">
                    <span class="font-mono text-xs font-bold uppercase tracking-wider text-[#1F1812]">Daftarkan Perangkat Ini</span>
                    <button type="button" @click="showAuthorizeModal = false" class="text-[#8A7B66] hover:text-[#1F1812] text-sm font-mono cursor-pointer">✕</button>
                </div>
                <p class="mt-3 text-xs text-[#6B5A4B] font-serif leading-relaxed">
                    Masukkan Kunci Rahasia Perangkat (KASIR_DEVICE_SECRET) yang dimiliki Owner untuk mendaftarkan tablet ini:
                </p>
                <form method="GET" action="{{ route('kasir.authorize-device') }}" class="mt-3.5 space-y-3">
                    <input type="password" name="key" x-model="secretKey" required placeholder="Kunci Rahasia Perangkat"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#B5762A] focus:outline-none rounded-xl px-3 py-2 text-xs font-mono">
                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" @click="showAuthorizeModal = false" class="px-3 py-1.5 text-xs font-mono text-[#8A7B66] hover:text-[#1F1812] cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-1.5 bg-[#B5762A] hover:bg-[#1F1812] text-white rounded-lg font-mono text-xs font-bold tracking-wider transition-colors cursor-pointer">Otorisasi & Masuk</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</body>
</html>
