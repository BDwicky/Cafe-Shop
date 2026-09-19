<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ config('cafe.name') }} — {{ config('cafe.tagline') }}">
    <title>@yield('title', config('cafe.name')) — {{ config('cafe.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F7F3EC] text-[#2A211A] antialiased" x-data="{ mobileNavOpen: false }">

    <!-- Topbar band espresso (Modern, High Contrast) -->
    <header class="sticky top-0 z-40 bg-[#1F1812] text-[#F7F3EC] border-b border-[#3A3026] shadow-md">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                <div class="w-9 h-9 rounded-xl bg-[#2A211A] border border-[#3A3026] flex items-center justify-center p-1.5 shrink-0 group-hover:border-[#D9973E] transition-all shadow-xs">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="{{ config('cafe.name') }}" class="h-full w-full object-contain">
                </div>
                <div class="min-w-0">
                    <span class="font-bold tracking-tight text-base sm:text-lg text-[#FAF7F2] group-hover:text-[#D9973E] transition">{{ config('cafe.name') }}</span>
                    <span class="block font-mono text-[9px] uppercase tracking-[0.25em] text-[#D9973E] font-semibold">Coffee & Space</span>
                </div>
            </a>

            <!-- Desktop Navigation Links (High Contrast, Bold, Elegant) -->
            <nav class="hidden md:flex items-center gap-7 font-mono text-xs uppercase tracking-[0.18em]">
                <a href="{{ route('menu.public') }}"
                   class="transition py-1 border-b-2 {{ request()->routeIs('menu.public') ? 'text-[#D9973E] border-[#D9973E] font-bold' : 'text-[#FAF7F2] border-transparent hover:text-[#D9973E]' }}">
                    Daftar Menu
                </a>
                <a href="{{ route('landing') }}#lokasi"
                   class="text-[#FAF7F2] border-b-2 border-transparent hover:text-[#D9973E] transition py-1">
                    Lokasi & Jam
                </a>
                <a href="{{ route('music.request') }}"
                   class="text-[#FAF7F2] border-b-2 border-transparent hover:text-[#D9973E] transition py-1 inline-flex items-center gap-1.5">
                    <span class="text-[#D9973E]">♫</span>
                    <span>Request Musik</span>
                </a>
                <a href="https://wa.me/{{ config('cafe.wa_number') }}" target="_blank" rel="noopener"
                   class="px-3.5 py-1.5 rounded-xl bg-[#2A211A] hover:bg-[#D9973E] text-[#FAF7F2] hover:text-[#1F1812] border border-[#3A3026] hover:border-[#D9973E] transition shadow-xs font-bold">
                    Kontak WA ›
                </a>
            </nav>

            <!-- Mobile Hamburger Button -->
            <div class="flex items-center gap-3 md:hidden">
                <a href="{{ route('menu.public') }}"
                   class="px-3 py-1 text-xs font-mono font-bold rounded-lg bg-[#D9973E] text-[#1F1812] shadow-xs">
                    Menu
                </a>
                <button type="button"
                        @click="mobileNavOpen = !mobileNavOpen"
                        class="p-2 rounded-xl bg-[#2A211A] border border-[#3A3026] text-[#FAF7F2] hover:text-[#D9973E] hover:border-[#D9973E] transition focus:outline-none"
                        aria-label="Toggle Navigation">
                    <svg x-show="!mobileNavOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileNavOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- MOBILE SIDEBAR DRAWER (Redesain: Jelas, Berwarna Kontras Tinggi, Animasi Halus) -->
    <div x-show="mobileNavOpen"
         x-cloak
         class="fixed inset-0 z-50 md:hidden"
         style="display: none;">

        <!-- Backdrop Blur -->
        <div x-show="mobileNavOpen"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileNavOpen = false"
             class="fixed inset-0 bg-[#0F0A07]/80 backdrop-blur-sm"></div>

        <!-- Slide-out Sidebar Panel -->
        <div x-show="mobileNavOpen"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed inset-y-0 right-0 w-4/5 max-w-sm bg-[#1C1611] text-[#FAF7F2] border-l border-[#32261C] p-6 shadow-2xl flex flex-col justify-between overflow-y-auto">

            <div>
                <!-- Sidebar Top Header -->
                <div class="flex items-center justify-between pb-5 border-b border-[#32261C]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-[#261D16] border border-[#3A2D22] flex items-center justify-center p-1.5 shadow-xs">
                            <img src="{{ asset('images/logo-mark.svg') }}" alt="{{ config('cafe.name') }}" class="h-full w-full object-contain">
                        </div>
                        <div>
                            <div class="font-bold text-sm text-[#FAF7F2]">{{ config('cafe.name') }}</div>
                            <div class="font-mono text-[9px] uppercase tracking-widest text-[#D9973E]">Navigasi Publik</div>
                        </div>
                    </div>
                    <button type="button"
                            @click="mobileNavOpen = false"
                            class="w-8 h-8 rounded-xl bg-[#261D16] border border-[#3A2D22] text-[#A89A85] hover:text-white flex items-center justify-center transition">
                        ✕
                    </button>
                </div>

                <!-- Navigation Links List -->
                <nav class="mt-6 space-y-2 font-mono text-xs uppercase tracking-wider">
                    <a href="{{ route('landing') }}"
                       @click="mobileNavOpen = false"
                       class="flex items-center justify-between px-4 py-3 rounded-xl bg-[#261D16] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] transition">
                        <span class="flex items-center gap-3 font-semibold">
                            <span>🏠</span>
                            <span>Beranda Kafe</span>
                        </span>
                        <span>›</span>
                    </a>

                    <a href="{{ route('menu.public') }}"
                       @click="mobileNavOpen = false"
                       class="flex items-center justify-between px-4 py-3 rounded-xl {{ request()->routeIs('menu.public') ? 'bg-[#D9973E] text-[#1F1812] font-bold border-[#D9973E]' : 'bg-[#261D16] hover:bg-[#32261C] border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E]' }} border transition">
                        <span class="flex items-center gap-3 font-semibold">
                            <span>☕</span>
                            <span>Daftar Menu & Komposisi</span>
                        </span>
                        <span>›</span>
                    </a>

                    <a href="{{ route('landing') }}#lokasi"
                       @click="mobileNavOpen = false"
                       class="flex items-center justify-between px-4 py-3 rounded-xl bg-[#261D16] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] transition">
                        <span class="flex items-center gap-3 font-semibold">
                            <span>📍</span>
                            <span>Lokasi & Jam Buka</span>
                        </span>
                        <span>›</span>
                    </a>

                    <a href="{{ route('music.request') }}"
                       @click="mobileNavOpen = false"
                       class="flex items-center justify-between px-4 py-3 rounded-xl bg-[#261D16] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] transition">
                        <span class="flex items-center gap-3 font-semibold">
                            <span>♫</span>
                            <span>Request Musik Kafe</span>
                        </span>
                        <span class="text-[10px] text-[#D9973E] font-bold">LIVE ›</span>
                    </a>

                    <a href="https://wa.me/{{ config('cafe.wa_number') }}" target="_blank" rel="noopener"
                       class="flex items-center justify-between px-4 py-3 rounded-xl bg-[#261D16] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#5F7F42] transition">
                        <span class="flex items-center gap-3 font-semibold">
                            <span>💬</span>
                            <span>Hubungi via WhatsApp</span>
                        </span>
                        <span class="text-xs">↗</span>
                    </a>
                </nav>
            </div>

            <!-- Sidebar Bottom: Info Jam & Staff Login -->
            <div class="pt-6 border-t border-[#32261C] space-y-3">
                <div class="text-[11px] text-[#D2C8BA] leading-relaxed">
                    <div class="font-mono text-[10px] uppercase tracking-wider text-[#D9973E] font-semibold">Buka Setiap Hari:</div>
                    <div class="mt-0.5 font-mono text-xs text-[#FAF7F2]">08:00 – 23:00 WIB</div>
                </div>

                <a href="{{ route('kasir.login') }}"
                   class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-[#140E0A] hover:bg-[#261D16] border border-[#3A2D22] text-[#D9973E] hover:text-[#FAF7F2] font-mono text-xs uppercase tracking-wider font-bold transition">
                    <span>🔐</span>
                    <span>Portal Staff Kasir</span>
                </a>
            </div>

        </div>
    </div>

    <main>
        @yield('content')
    </main>

    <!-- Footer band espresso (High Contrast) -->
    <footer class="bg-[#1F1812] text-[#F7F3EC] border-t border-[#3A3026] mt-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <img src="{{ asset('images/logo-light.svg') }}" alt="{{ config('cafe.name') }}" class="h-8 w-auto">
                <p class="mt-3 text-sm text-[#D2C8BA] leading-relaxed">{{ config('cafe.tagline') }}</p>
            </div>
            <div>
                <div class="font-mono text-[11px] uppercase tracking-[0.2em] text-[#D9973E] font-semibold">Jam Buka</div>
                <ul class="mt-3 space-y-1.5 text-sm text-[#D2C8BA]">
                    @foreach (\App\Support\OpeningHours::all() as $day => $h)
                        <li class="flex justify-between gap-6 border-b border-[#3A3026] pb-1.5">
                            <span class="text-[#FAF7F2] font-medium">{{ $day }}</span>
                            <span class="font-mono text-[#D9973E] font-semibold">{{ $h ? $h[0] . '–' . $h[1] : 'Tutup' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="font-mono text-[11px] uppercase tracking-[0.2em] text-[#D9973E] font-semibold">Kontak & Lokasi</div>
                <p class="mt-3 text-sm text-[#D2C8BA] leading-relaxed">{{ config('cafe.address') }}</p>
                <div class="mt-4 flex flex-col gap-2 font-mono text-xs uppercase tracking-[0.15em]">
                    <a href="https://wa.me/{{ config('cafe.wa_number') }}" target="_blank" rel="noopener" class="text-[#FAF7F2] hover:text-[#D9973E] transition inline-flex items-center gap-1.5 font-bold">
                        <span>💬</span>
                        <span>WhatsApp Resmi ›</span>
                    </a>
                    <a href="https://instagram.com/{{ ltrim(config('cafe.instagram'), '@') }}" target="_blank" rel="noopener" class="text-[#FAF7F2] hover:text-[#D9973E] transition inline-flex items-center gap-1.5 font-bold">
                        <span>📸</span>
                        <span>Instagram @kopikita ›</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="border-t border-[#3A3026]">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 font-mono text-xs uppercase tracking-[0.2em] text-[#A89A85] flex flex-wrap justify-between gap-2">
                <span>© {{ date('Y') }} {{ config('cafe.name') }}</span>
                <a href="{{ route('kasir.login') }}" class="text-[#D9973E] hover:text-white font-bold transition">Staff Login ›</a>
            </div>
        </div>
    </footer>

    <!-- CUSTOM CONFIRMATION & ALERT MODAL DIALOG -->
    @include('components.modal-dialog')
</body>
</html>
