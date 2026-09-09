<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'Kasir') — {{ config('cafe.name') }} POS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F7F3EC] text-[#2A211A] antialiased h-full overflow-hidden font-sans selection:bg-[#D9973E] selection:text-[#1F1812]"
      x-data="{ mobileNavOpen: false, currentTime: '' }"
      x-init="
        const updateClock = () => {
            const now = new Date();
            currentTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        };
        updateClock();
        setInterval(updateClock, 1000);
      ">

    <div class="flex flex-col md:flex-row h-full w-full overflow-hidden">

        <!-- MOBILE TOPBAR (khusus smartphone kecil) -->
        <header class="md:hidden bg-[#1F1812] text-[#F7F3EC] border-b border-[#3A3026] px-4 py-2.5 flex items-center justify-between shrink-0 z-30">
            <a href="{{ route('kasir.terminal') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo-light.svg') }}" alt="{{ config('cafe.name') }}" class="h-6 w-auto">
                <span class="font-mono text-[11px] tracking-[0.2em] uppercase text-[#D9973E]">POS</span>
            </a>
            <div class="flex items-center gap-3">
                <span class="font-mono text-xs text-[#A89A85]" x-text="currentTime"></span>
                <button @click="mobileNavOpen = !mobileNavOpen"
                        class="p-1.5 text-[#A89A85] hover:text-[#F7F3EC] focus:outline-none"
                        aria-label="Toggle Navigation">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileNavOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileNavOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </header>

        <!-- SIDEBAR NAVBAR KIRI (OPTIMAL UNTUK LAYOUT TABLET & POS) -->
        <aside :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
               class="fixed inset-y-0 left-0 z-40 md:static md:translate-x-0 w-60 lg:w-64 bg-[#1F1812] text-[#F7F3EC] border-r border-[#3A3026] flex flex-col justify-between shrink-0 transition-transform duration-200 ease-in-out h-full select-none shadow-2xl md:shadow-none">

            <!-- Bagian Atas: Brand & Info Jam Tablet -->
            <div>
                <div class="p-5 border-b border-[#3A3026]">
                    <a href="{{ route('kasir.terminal') }}" class="flex items-center gap-3">
                        <img src="{{ asset('images/logo-light.svg') }}" alt="{{ config('cafe.name') }}" class="h-7 w-auto">
                        <div>
                            <div class="font-medium tracking-tight text-sm text-[#F7F3EC]">{{ config('cafe.name') }}</div>
                            <div class="font-mono text-[9px] uppercase tracking-[0.25em] text-[#D9973E]">TABLET POS SYSTEM</div>
                        </div>
                    </a>

                    <!-- Digital Clock Live Widget untuk Tablet POS -->
                    <div class="mt-4 bg-[#2A211A] border border-[#3A3026] px-3 py-2 flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-[#5F7F42] animate-pulse"></span>
                            <span class="font-mono text-[10px] uppercase tracking-wider text-[#A89A85]">{{ now()->translatedFormat('d M Y') }}</span>
                        </div>
                        <span class="font-mono text-sm text-[#F7F3EC] font-semibold" x-text="currentTime"></span>
                    </div>
                </div>

                <!-- Navigasi Menu Kasir (Grid / List Model Navbar Tablet) -->
                <nav class="p-3 space-y-1.5">
                    <div class="px-3 pt-2 pb-1 font-mono text-[9px] uppercase tracking-[0.25em] text-[#A89A85]">NAVIGASI UTAMA</div>

                    <!-- 1. Terminal Kasir -->
                    <a href="{{ route('kasir.terminal') }}"
                       class="flex items-center gap-3 px-3.5 py-3 rounded-none border text-xs font-mono tracking-wider uppercase transition-colors {{ request()->routeIs('kasir.terminal') ? 'bg-[#D9973E] text-[#1F1812] border-[#D9973E] font-bold shadow-md' : 'text-[#F7F3EC] border-transparent hover:bg-[#2A211A] hover:border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span>Terminal (POS)</span>
                    </a>

                    <!-- 2. Riwayat Transaksi -->
                    <a href="{{ route('kasir.orders.index') }}"
                       class="flex items-center gap-3 px-3.5 py-3 rounded-none border text-xs font-mono tracking-wider uppercase transition-colors {{ request()->routeIs('kasir.orders.*') ? 'bg-[#D9973E] text-[#1F1812] border-[#D9973E] font-bold shadow-md' : 'text-[#A89A85] border-transparent hover:bg-[#2A211A] hover:border-[#3A3026] hover:text-[#F7F3EC]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span>Riwayat Pesanan</span>
                    </a>

                    <!-- 3. Kelola Menu -->
                    <a href="{{ route('kasir.menu.index') }}"
                       class="flex items-center gap-3 px-3.5 py-3 rounded-none border text-xs font-mono tracking-wider uppercase transition-colors {{ request()->routeIs('kasir.menu.*') ? 'bg-[#D9973E] text-[#1F1812] border-[#D9973E] font-bold shadow-md' : 'text-[#A89A85] border-transparent hover:bg-[#2A211A] hover:border-[#3A3026] hover:text-[#F7F3EC]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span>Kelola Menu</span>
                    </a>

                    <!-- 4. Laporan Penjualan -->
                    <a href="{{ route('kasir.laporan') }}"
                       class="flex items-center gap-3 px-3.5 py-3 rounded-none border text-xs font-mono tracking-wider uppercase transition-colors {{ request()->routeIs('kasir.laporan') ? 'bg-[#D9973E] text-[#1F1812] border-[#D9973E] font-bold shadow-md' : 'text-[#A89A85] border-transparent hover:bg-[#2A211A] hover:border-[#3A3026] hover:text-[#F7F3EC]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Laporan Kasir</span>
                    </a>

                    <div class="pt-3 pb-1 border-t border-[#3A3026]/70 mt-3">
                        <a href="{{ route('landing') }}" target="_blank"
                           class="flex items-center justify-between px-3.5 py-2.5 text-[11px] font-mono tracking-wider uppercase text-[#A89A85] hover:text-[#D9973E] hover:bg-[#2A211A] transition-colors">
                            <span class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                Halaman Publik
                            </span>
                            <span class="text-xs">↗</span>
                        </a>
                    </div>
                </nav>
            </div>

            <!-- Bagian Bawah: Info Kasir & Tombol Logout -->
            <div class="p-4 border-t border-[#3A3026] bg-[#19130E]">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-full bg-[#D9973E] text-[#1F1812] flex items-center justify-center font-bold text-sm shrink-0 shadow">
                        {{ strtoupper(substr(auth()->user()->name ?? 'K', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-medium text-[#F7F3EC] truncate">{{ auth()->user()->name ?? 'Kasir' }}</div>
                        <div class="font-mono text-[10px] text-[#5F7F42] flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42]"></span>
                            Shift Aktif
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('kasir.logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-[#2A211A] hover:bg-[#C4553D] text-[#A89A85] hover:text-white text-xs font-mono uppercase tracking-wider transition-colors border border-[#3A3026]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <!-- Overlay backdrop untuk mobile drawer -->
        <div x-show="mobileNavOpen"
             @click="mobileNavOpen = false"
             class="fixed inset-0 bg-black/60 z-30 md:hidden backdrop-blur-sm"
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>

        <!-- MAIN AREA / KONTEN UTAMA -->
        <main class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-[#F7F3EC]">
            @if (session('status'))
                <div class="shrink-0 m-4 mb-0 border border-[#5F7F42]/30 bg-[#5F7F42]/10 text-[#2A211A] px-4 py-3 text-sm flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#5F7F42]"></span>
                        <span>{{ session('status') }}</span>
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-[#8A7B66] hover:text-[#2A211A]">&times;</button>
                </div>
            @endif

            <div class="flex-1 min-h-0 overflow-y-auto">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
