<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#1F1812">
    <title>@yield('title', 'Kasir') — {{ config('cafe.name') }} POS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F7F3EC] text-[#2A211A] antialiased h-full overflow-hidden font-sans selection:bg-[#D9973E] selection:text-[#1F1812]"
      x-data="kasirAppShell()"
      x-init="initShell()">

    <div class="flex flex-col md:flex-row h-full w-full overflow-hidden">

        <!-- MOBILE TOPBAR (khusus smartphone kecil) -->
        <header class="md:hidden bg-[#1F1812] text-[#F7F3EC] border-b border-[#3A3026] px-4 py-2 flex items-center justify-between shrink-0 z-30">
            <a href="{{ route('kasir.terminal') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo-light.svg') }}" alt="{{ config('cafe.name') }}" class="h-6 w-auto">
                <span class="font-mono text-[11px] tracking-[0.2em] uppercase text-[#D9973E]">POS</span>
            </a>
            <div class="flex items-center gap-2.5">
                <!-- Mobile Music Play/Pause Quick Button -->
                <button type="button"
                        onclick="if (window.SoundStation) window.SoundStation.togglePlayPause()"
                        title="Putar / Jeda Musik"
                        class="px-2 py-1 bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] rounded text-[10px] font-mono text-[#D9973E] flex items-center gap-1.5 transition">
                    <span>♫</span>
                    <span id="mobile-music-status">Musik</span>
                </button>
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

        <!-- SIDEBAR NAVBAR KIRI (REAKTIF: OVERLAY DRAWER DI TERMINAL POS, STATIK DI HALAMAN LAIN) -->
        <aside :class="{
                   'fixed inset-y-0 left-0 z-50 w-64 sm:w-72 shadow-2xl': isTerminalPage,
                   'translate-x-0': isTerminalPage ? terminalSidebarOpen : mobileNavOpen,
                   '-translate-x-full': isTerminalPage ? !terminalSidebarOpen : (!mobileNavOpen && true),
                   'fixed inset-y-0 left-0 z-40 md:static md:translate-x-0 w-64 lg:w-68 shadow-2xl md:shadow-none': !isTerminalPage
               }"
               class="bg-[#1C1611] text-[#FAF7F2] border-r border-[#32261C] flex flex-col justify-between shrink-0 transition-transform duration-300 ease-in-out h-full select-none"
               x-cloak>

            <!-- Bagian Atas: Brand, Info Jam Tablet & Navigasi (Scrollable) -->
            <div class="flex-1 min-h-0 overflow-y-auto scrollbar-thin scrollbar-thumb-[#32261C] scrollbar-track-transparent">
                <!-- Header Brand & Clock -->
                <div class="p-4 border-b border-[#32261C]">
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('kasir.terminal') }}" class="flex items-center gap-2.5 min-w-0 group">
                            <div class="w-8 h-8 rounded-xl bg-[#261D16] border border-[#3A2D22] flex items-center justify-center p-1.5 shrink-0 group-hover:border-[#D9973E] transition shadow-xs">
                                <img src="{{ asset('images/logo-light.svg') }}" alt="{{ config('cafe.name') }}" class="h-5 w-auto">
                            </div>
                            <div class="truncate">
                                <div class="font-medium tracking-tight text-sm text-[#FAF7F2] group-hover:text-[#D9973E] transition truncate">{{ config('cafe.name') }}</div>
                                <div class="font-mono text-[9px] uppercase tracking-[0.22em] text-[#D9973E] font-semibold">Tablet POS System</div>
                            </div>
                        </a>
                        <!-- Tombol Tutup (muncul di Terminal POS overlay atau mobile drawer) -->
                        <button type="button"
                                x-show="isTerminalPage || mobileNavOpen"
                                @click="isTerminalPage ? (terminalSidebarOpen = false) : (mobileNavOpen = false)"
                                class="w-7 h-7 rounded-lg bg-[#261D16] border border-[#3A2D22] text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#32261C] flex items-center justify-center transition shrink-0 cursor-pointer active:scale-95"
                                title="Tutup Navigasi (Esc)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Digital Clock Live Widget untuk Tablet POS -->
                    <div class="mt-3.5 bg-[#261D16]/90 border border-[#3A2D22] rounded-xl px-3 py-2 flex items-center justify-between shadow-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-[#5F7F42] animate-pulse"></span>
                            <span class="font-mono text-[10px] uppercase tracking-wider text-[#A89A85]">{{ now()->translatedFormat('d M Y') }}</span>
                        </div>
                        <span class="font-mono text-xs text-[#FAF7F2] font-bold tracking-wider" x-text="currentTime"></span>
                    </div>
                </div>

                <!-- Navigasi Menu Kasir -->
                <nav class="p-3 space-y-1">
                    <div class="px-2 pt-2 pb-1.5 flex items-center gap-2">
                        <span class="font-mono text-[9px] uppercase tracking-[0.25em] text-[#8A7B66] font-semibold">Navigasi Utama</span>
                        <span class="flex-1 h-px bg-[#32261C]"></span>
                    </div>

                    <!-- 1. Terminal Kasir -->
                    <a href="{{ route('kasir.terminal') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.terminal') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span>Terminal (POS)</span>
                    </a>

                    <!-- 2. Riwayat Transaksi -->
                    <a href="{{ route('kasir.orders.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.orders.*') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span>Riwayat Pesanan</span>
                    </a>

                    <!-- 3. Kelola Menu -->
                    <a href="{{ route('kasir.menu.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.menu.*') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span>Kelola Menu</span>
                    </a>

                    <!-- 4. Laporan Penjualan -->
                    <a href="{{ route('kasir.laporan') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.laporan') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Laporan Kasir</span>
                    </a>

                    <!-- 5. Kitchen Display System (KDS) -->
                    <a href="{{ route('kasir.kitchen.index') }}"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.kitchen.*') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Layar Dapur (KDS)</span>
                        </div>
                        <template x-if="kdsCount > 0">
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-[#5F7F42] text-white rounded-full animate-pulse shadow-sm"
                                  x-text="kdsCount"></span>
                        </template>
                    </a>

                    <!-- 6. Sound Station (Musik Kafe) -->
                    <div class="rounded-xl overflow-hidden border border-transparent hover:border-[#3A2D22] transition-colors {{ request()->routeIs('kasir.music.index') ? 'bg-[#261D16] border-[#3A2D22]' : '' }}">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('kasir.music.index') }}"
                               class="flex-1 flex items-center justify-between px-3 py-2.5 text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.music.index') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20 rounded-xl' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16] rounded-l-xl' }}">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                    </svg>
                                    <span>Sound Station</span>
                                </div>
                                <template x-if="musicQueueCount > 0">
                                    <span class="px-2 py-0.5 text-[10px] font-bold bg-[#D9973E] text-[#1F1812] rounded-full animate-pulse shadow-sm"
                                          x-text="musicQueueCount"></span>
                                </template>
                            </a>
                            <!-- Pop-up Mini Player Button -->
                            <button type="button"
                                    onclick="window.open('{{ route('kasir.music.mini') }}', 'SoundStationMini', 'width=380,height=520,resizable=yes')"
                                    title="Buka pemutar mini terpisah (Anti-Mati)"
                                    class="p-2.5 text-[#A89A85] hover:text-[#D9973E] hover:bg-[#261D16] rounded-r-xl transition shrink-0 cursor-pointer"
                                    :class="request()->routeIs('kasir.music.index') ? 'text-[#FAF7F2]' : ''">
                                <span class="text-xs">⧉</span>
                            </button>
                        </div>
                    </div>

                    <!-- 7. Pengaturan Suara Announcer -->
                    <a href="{{ route('kasir.announcer.settings') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.announcer.*') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/>
                        </svg>
                        <span>Suara Announcer</span>
                    </a>

                    <!-- INVENTARIS & KEUANGAN TOKO -->
                    <div class="px-2 pt-3 pb-1.5 flex items-center gap-2">
                        <span class="font-mono text-[9px] uppercase tracking-[0.25em] text-[#8A7B66] font-semibold">Inventaris & Biaya</span>
                        <span class="flex-1 h-px bg-[#32261C]"></span>
                    </div>

                    <!-- 8. Stok Bahan Baku -->
                    <a href="{{ route('kasir.inventory.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.inventory.index') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Stok Bahan Baku</span>
                    </a>

                    <!-- 9. Resep Menu (BOM) -->
                    <a href="{{ route('kasir.inventory.recipes') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.inventory.recipes') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Resep BOM Menu</span>
                    </a>

                    <!-- 10. Mutasi Stok / Ledger -->
                    <a href="{{ route('kasir.inventory.history') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.inventory.history') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Kartu Stok Mutasi</span>
                    </a>

                    <!-- 11. Pengeluaran Toko -->
                    <a href="{{ route('kasir.expenses.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.expenses.*') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Pengeluaran Toko</span>
                    </a>

                    <!-- External Public Link -->
                    <div class="pt-2">
                        <a href="{{ route('landing') }}" target="_blank"
                           class="flex items-center justify-between px-3 py-2 rounded-xl text-[11px] font-mono tracking-wider uppercase text-[#8A7B66] hover:text-[#D9973E] hover:bg-[#261D16] transition-colors">
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

            <!-- PERSISTENT NAVBAR MUSIC WIDGET (PEMUTAR MUSIK ANTI-MATI) -->
            @include('components.navbar-music-widget')

            <!-- Bagian Bawah: Info Kasir & Tombol Logout -->
            <div class="p-3.5 border-t border-[#32261C] bg-[#140E0A] shrink-0">
                <div class="flex items-center gap-3 mb-2.5">
                    <div class="w-8 h-8 rounded-xl bg-[#D9973E] text-[#1F1812] flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                        {{ strtoupper(substr(auth()->user()->name ?? 'K', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-semibold text-[#FAF7F2] truncate">{{ auth()->user()->name ?? 'Kasir' }}</div>
                        <div class="font-mono text-[10px] text-[#5F7F42] flex items-center gap-1.5 mt-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42] animate-pulse"></span>
                            Shift Aktif
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('kasir.logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-[#261D16] hover:bg-[#C4553D] hover:text-white text-[#A89A85] text-xs font-mono uppercase tracking-wider transition-all border border-[#3A2D22] hover:border-[#C4553D] shadow-2xs cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <!-- Backdrop overlay untuk drawer (di terminal POS atau mobile) -->
        <div x-show="isTerminalPage ? terminalSidebarOpen : mobileNavOpen"
             x-cloak
             @click="isTerminalPage ? (terminalSidebarOpen = false) : (mobileNavOpen = false)"
             :class="isTerminalPage ? 'z-40' : 'z-30 md:hidden'"
             class="fixed inset-0 bg-black/60 backdrop-blur-xs"
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

    <!-- CUSTOM CONFIRMATION & ALERT MODAL DIALOG -->
    @include('components.modal-dialog')

    <!-- SEAMLESS POS PAGE SWAPPER (MUSIK TIDAK MATI SAAT BERPINDAH MENU/TAB POS) -->
    <script>
        function kasirAppShell() {
            const checkIsTerminal = (pathname) => {
                const p = pathname || window.location.pathname;
                return (p === '/kasir' || p === '/kasir/' || p === '/kasir/terminal');
            };

            return {
                mobileNavOpen: false,
                terminalSidebarOpen: false,
                isTerminalPage: checkIsTerminal(),
                currentTime: '',
                kdsCount: {{ \App\Models\Order::prepActive()->count() }},
                musicQueueCount: {{ \App\Models\MusicRequest::queued()->count() }},

                initShell() {
                    const updateClock = () => {
                        const now = new Date();
                        this.currentTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    };
                    updateClock();
                    setInterval(updateClock, 1000);

                    // Sinkronisasi saat URL berpindah via seamless page swapper
                    window.addEventListener('kasir:route-changed', (e) => {
                        const path = e.detail && e.detail.pathname ? e.detail.pathname : window.location.pathname;
                        this.isTerminalPage = checkIsTerminal(path);
                        this.terminalSidebarOpen = false;
                        this.mobileNavOpen = false;
                    });

                    // Listen ke custom event toggle-terminal-sidebar
                    window.addEventListener('toggle-terminal-sidebar', () => {
                        this.terminalSidebarOpen = !this.terminalSidebarOpen;
                    });

                    // Keyboard shortcuts: Esc untuk tutup sidebar kasir, Alt+M untuk toggle
                    window.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            if (this.terminalSidebarOpen) this.terminalSidebarOpen = false;
                            if (this.mobileNavOpen) this.mobileNavOpen = false;
                        }
                        if (e.altKey && (e.key === 'm' || e.key === 'M')) {
                            e.preventDefault();
                            if (this.isTerminalPage) {
                                this.terminalSidebarOpen = !this.terminalSidebarOpen;
                            } else {
                                this.mobileNavOpen = !this.mobileNavOpen;
                            }
                        }
                    });

                    // Polling realtime counts untuk sidebar badge (hanya saat tab aktif & tidak sedang navigasi)
                    setInterval(() => {
                        if (!document.hidden && !window._isNavigatingKasirPage) {
                            this.fetchCounts();
                        }
                    }, 15000);

                    // Listen ke event SoundStation sync / KDS update untuk update instan
                    window.addEventListener('soundstation:sync', (e) => {
                        if (e.detail && typeof e.detail.queueCount !== 'undefined') {
                            this.musicQueueCount = Number(e.detail.queueCount);
                        }
                    });

                    window.addEventListener('kds:count', (e) => {
                        if (e.detail && typeof e.detail.count !== 'undefined') {
                            this.kdsCount = Number(e.detail.count);
                        }
                    });

                    if (typeof BroadcastChannel !== 'undefined') {
                        try {
                            const ch = new BroadcastChannel('cafe_soundstation_sync');
                            ch.addEventListener('message', (e) => {
                                if (e.data && e.data.type === 'STATE_UPDATE' && e.data.state && typeof e.data.state.queueCount !== 'undefined') {
                                    this.musicQueueCount = Number(e.data.state.queueCount);
                                }
                            });
                        } catch(e) {}
                    }
                },

                isFetchingCounts: false,
                async fetchCounts() {
                    if (this.isFetchingCounts) return;
                    this.isFetchingCounts = true;
                    try {
                        const res = await fetch('{{ route('kasir.music.sidebar_counts') }}', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (typeof data.kds_count !== 'undefined') this.kdsCount = Number(data.kds_count);
                        if (typeof data.music_queue_count !== 'undefined') this.musicQueueCount = Number(data.music_queue_count);
                    } catch (err) {}
                    finally {
                        this.isFetchingCounts = false;
                    }
                }
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Update teks tombol musik mobile saat status berubah
            window.addEventListener('soundstation:state', function(e) {
                const el = document.getElementById('mobile-music-status');
                if (el) el.textContent = e.detail.isPlaying ? '⏸ Putar' : '▶ Jeda';
            });

            // Intercept klik navigasi link kasir agar perpindahan menu berlangsung instan & musik tetap menyala
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a');
                if (!link) return;

                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                if (link.target === '_blank' || link.hasAttribute('download') || link.dataset.noPjax !== undefined) return;

                try {
                    const url = new URL(href, window.location.origin);
                    if (url.origin !== window.location.origin) return;
                    if (!url.pathname.startsWith('/kasir')) return;
                    if (url.pathname.includes('/receipt') || url.pathname.includes('/mini') || url.pathname.includes('/ticket')) return;

                    e.preventDefault();
                    swapKasirPage(url.href, true);
                } catch (err) {}
            });

            window.addEventListener('popstate', function() {
                swapKasirPage(window.location.href, false);
            });
        });

        async function swapKasirPage(url, pushState = true) {
            const mainEl = document.querySelector('main');
            if (!mainEl) {
                window.location.href = url;
                return;
            }

            // Kunci background polling agar seluruh kapasitas Apache difokuskan ke halaman ini
            window._isNavigatingKasirPage = true;

            try {
                mainEl.style.transition = 'opacity 0.12s ease';
                mainEl.style.opacity = '0.5';
                mainEl.style.pointerEvents = 'none';

                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!res.ok) {
                    window.location.href = url;
                    return;
                }

                const html = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newMain = doc.querySelector('main');
                if (!newMain) {
                    window.location.href = url;
                    return;
                }

                if (doc.title) {
                    document.title = doc.title;
                }

                if (pushState) {
                    window.history.pushState({}, '', url);
                }

                // Beritahukan shell bahwa rute kasir telah berpindah
                const newPath = new URL(url, window.location.origin).pathname;
                window.dispatchEvent(new CustomEvent('kasir:route-changed', {
                    detail: { url, pathname: newPath }
                }));

                // Bersihkan Alpine trees lama jika ada
                if (window.Alpine && window.Alpine.destroyTree) {
                    window.Alpine.destroyTree(mainEl);
                }

                mainEl.innerHTML = newMain.innerHTML;
                mainEl.style.opacity = '1';
                mainEl.style.pointerEvents = 'auto';

                // Jalankan kembali tag script yang baru dimasukkan
                const scripts = mainEl.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });

                // Inisialisasi ulang Alpine pada elemen konten baru
                if (window.Alpine && window.Alpine.initTree) {
                    window.Alpine.initTree(mainEl);
                }

                // Perbarui highlight link aktif di sidebar
                highlightActiveNav(url);

                // Scroll konten kembali ke atas
                const scrollable = mainEl.querySelector('.overflow-y-auto') || mainEl;
                if (scrollable) scrollable.scrollTop = 0;

            } catch (err) {
                console.warn('Seamless swap failed, standard reload:', err);
                window.location.href = url;
            } finally {
                window._isNavigatingKasirPage = false;
            }
        }

        function highlightActiveNav(currentUrl) {
            const path = new URL(currentUrl, window.location.origin).pathname;
            const navLinks = document.querySelectorAll('aside nav a[href]');
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (!linkHref) return;
                const linkPath = new URL(linkHref, window.location.origin).pathname;

                let isActive = false;
                if (linkPath === '/kasir' && (path === '/kasir' || path === '/kasir/terminal')) {
                    isActive = true;
                } else if (linkPath !== '/kasir' && path.startsWith(linkPath)) {
                    isActive = true;
                }

                if (isActive) {
                    link.classList.add('bg-[#D9973E]', 'text-[#1F1812]', 'font-bold', 'shadow-md', 'shadow-[#D9973E]/20');
                    link.classList.remove('text-[#A89A85]', 'hover:text-[#FAF7F2]', 'hover:bg-[#261D16]');
                } else {
                    link.classList.remove('bg-[#D9973E]', 'text-[#1F1812]', 'font-bold', 'shadow-md', 'shadow-[#D9973E]/20');
                    link.classList.add('text-[#A89A85]', 'hover:text-[#FAF7F2]', 'hover:bg-[#261D16]');
                }
            });
        }
    </script>
</body>
</html>
