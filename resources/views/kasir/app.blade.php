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
    <style>
        @keyframes kasirPageFadeIn {
            0% { opacity: 0.7; transform: translateY(4px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .kasir-page-enter {
            animation: kasirPageFadeIn 0.16s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="bg-[#F7F3EC] text-[#2A211A] antialiased h-full overflow-hidden font-sans selection:bg-[#D9973E] selection:text-[#1F1812]"
      x-data="kasirAppShell()"
      x-init="initShell()">

    <!-- TOP NAVIGATION PROGRESS BAR -->
    <div id="kasir-top-progress" class="fixed top-0 left-0 h-[3px] z-[9999] pointer-events-none transition-all duration-200 ease-out opacity-0"
         style="width: 0%; background: linear-gradient(90deg, #D9973E, #F59E0B); box-shadow: 0 0 10px rgba(217, 151, 62, 0.8), 0 0 4px rgba(245, 158, 11, 0.6);">
    </div>

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
                            <div class="w-9 h-9 rounded-xl bg-[#261D16] border border-[#3A2D22] flex items-center justify-center p-1.5 shrink-0 group-hover:border-[#D9973E] group-hover:scale-105 transition-all shadow-xs">
                                <img src="{{ asset('images/logo-mark.svg') }}" alt="{{ config('cafe.name') }}" class="h-full w-full object-contain">
                            </div>
                            <div class="truncate min-w-0">
                                <div class="font-bold tracking-tight text-sm text-[#FAF7F2] group-hover:text-[#D9973E] transition truncate leading-tight">{{ config('cafe.name') }}</div>
                                <div class="font-mono text-[9px] uppercase tracking-[0.22em] text-[#D9973E] font-semibold mt-0.5">Tablet POS System</div>
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
                            <span class="font-mono text-[10px] uppercase tracking-wider text-[#A89A85]" x-text="currentDate">{{ now()->setTimezone('Asia/Jakarta')->translatedFormat('d M Y') }}</span>
                        </div>
                        <span class="font-mono text-xs text-[#FAF7F2] font-bold tracking-wider" x-text="currentTime"></span>
                    </div>
                </div>

                <!-- Navigasi Menu Kasir (Auto-close overlay saat link diklik) -->
                <nav class="p-3 space-y-1" @click="if ($event.target.closest('a')) { terminalSidebarOpen = false; mobileNavOpen = false; }">
                    <!-- 1. OPERASIONAL -->
                    <div class="px-2 pt-2 pb-1.5 flex items-center gap-2">
                        <span class="font-mono text-[9px] uppercase tracking-[0.25em] text-[#8A7B66] font-semibold">Operasional</span>
                        <span class="flex-1 h-px bg-[#32261C]"></span>
                    </div>

                    <!-- Terminal Kasir (POS) -->
                    <a href="{{ route('kasir.terminal') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.terminal') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span>Terminal (POS)</span>
                    </a>

                    <!-- Layar Dapur (KDS) -->
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

                    <!-- Riwayat Pesanan -->
                    <a href="{{ route('kasir.orders.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.orders.*') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span>Riwayat Pesanan</span>
                    </a>

                    <!-- 2. KATALOG & PROMOSI -->
                    <div class="px-2 pt-3 pb-1.5 flex items-center gap-2">
                        <span class="font-mono text-[9px] uppercase tracking-[0.25em] text-[#8A7B66] font-semibold">Katalog & Promo</span>
                        <span class="flex-1 h-px bg-[#32261C]"></span>
                    </div>

                    <!-- Kelola Menu -->
                    <a href="{{ route('kasir.menu.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.menu.*') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span>Kelola Menu</span>
                    </a>

                    <!-- Kupon Diskon -->
                    <a href="{{ route('kasir.promos.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.promos.*') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <span>Kupon Diskon</span>
                    </a>

                    <!-- 3. INVENTARIS & BAHAN BAKU -->
                    <div class="px-2 pt-3 pb-1.5 flex items-center gap-2">
                        <span class="font-mono text-[9px] uppercase tracking-[0.25em] text-[#8A7B66] font-semibold">Inventaris & Resep</span>
                        <span class="flex-1 h-px bg-[#32261C]"></span>
                    </div>

                    <!-- Stok Bahan Baku -->
                    <a href="{{ route('kasir.inventory.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.inventory.index') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Stok Bahan Baku</span>
                    </a>

                    <!-- Resep Menu (BOM) -->
                    <a href="{{ route('kasir.inventory.recipes') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.inventory.recipes') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Resep BOM Menu</span>
                    </a>

                    <!-- Mutasi Stok / Ledger -->
                    <a href="{{ route('kasir.inventory.history') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.inventory.history') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Kartu Stok Mutasi</span>
                    </a>

                    <!-- 4. KEUANGAN & LAPORAN -->
                    <div class="px-2 pt-3 pb-1.5 flex items-center gap-2">
                        <span class="font-mono text-[9px] uppercase tracking-[0.25em] text-[#8A7B66] font-semibold">Keuangan & Laporan</span>
                        <span class="flex-1 h-px bg-[#32261C]"></span>
                    </div>

                    <!-- Laporan Penjualan -->
                    <a href="{{ route('kasir.laporan') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.laporan') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Laporan Kasir</span>
                    </a>

                    <!-- Pengeluaran Toko -->
                    <a href="{{ route('kasir.expenses.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.expenses.*') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Pengeluaran Toko</span>
                    </a>

                    <!-- 5. SUASANA & AUDIO -->
                    <div class="px-2 pt-3 pb-1.5 flex items-center gap-2">
                        <span class="font-mono text-[9px] uppercase tracking-[0.25em] text-[#8A7B66] font-semibold">Suasana & Audio</span>
                        <span class="flex-1 h-px bg-[#32261C]"></span>
                    </div>

                    <!-- Sound Station (Musik Kafe) -->
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

                    <!-- Pengaturan Suara Announcer -->
                    <a href="{{ route('kasir.announcer.settings') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase transition-all {{ request()->routeIs('kasir.announcer.*') ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md shadow-[#D9973E]/20' : 'text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#261D16]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/>
                        </svg>
                        <span>Suara Announcer</span>
                    </a>

                    <!-- 6. TAUTAN PUBLIK -->
                    <div class="pt-3">
                        <a href="{{ route('landing') }}" target="_blank"
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-mono tracking-wider uppercase bg-[#261D16]/80 hover:bg-[#32261C] border border-[#3A2D22] hover:border-[#D9973E]/60 text-[#FAF7F2] hover:text-[#D9973E] transition-all shadow-2xs group">
                            <span class="flex items-center gap-2.5 font-bold">
                                <svg class="w-4 h-4 text-[#D9973E] group-hover:scale-110 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                <span>Halaman Publik</span>
                            </span>
                            <span class="text-xs text-[#D9973E] font-mono group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform">↗</span>
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
                currentDate: '',
                kdsCount: {{ \App\Models\Order::prepActive()->count() }},
                musicQueueCount: {{ \App\Models\MusicRequest::queued()->count() }},

                initShell() {
                    const updateClock = () => {
                        const now = new Date();
                        this.currentTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/:/g, '.');
                        const day = String(now.getDate()).padStart(2, '0');
                        const months = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
                        const month = months[now.getMonth()];
                        const year = now.getFullYear();
                        this.currentDate = `${day} ${month} ${year}`;
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

                    // Event untuk menutup sidebar drawer seketika
                    window.addEventListener('kasir:close-sidebar', () => {
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
                        if (e.altKey && (e.key === 'k' || e.key === 'K')) {
                            e.preventDefault();
                            if (typeof swapKasirPage === 'function') {
                                swapKasirPage('{{ route('kasir.kitchen.index') }}', true);
                            } else {
                                window.location.href = '{{ route('kasir.kitchen.index') }}';
                            }
                        }
                        if (e.altKey && (e.key === 't' || e.key === 'T')) {
                            e.preventDefault();
                            if (typeof swapKasirPage === 'function') {
                                swapKasirPage('{{ route('kasir.terminal') }}', true);
                            } else {
                                window.location.href = '{{ route('kasir.terminal') }}';
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
                                if (e.data) {
                                    if (e.data.type === 'STATE_UPDATE' && e.data.state && typeof e.data.state.queueCount !== 'undefined') {
                                        this.musicQueueCount = Number(e.data.state.queueCount);
                                    }
                                    if (e.data.type === 'KDS_COUNT_UPDATE' && typeof e.data.count !== 'undefined') {
                                        this.kdsCount = Number(e.data.count);
                                    }
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

        // IN-MEMORY PAGE CACHE & PROGRESS BAR UNTUK NAVIGASI INSTAN
        const kasirPageCache = new Map();
        const MAX_CACHE_SIZE = 15;
        const CACHE_TTL_MS = 60000; // 60 detik
        let activeNavAbortController = null;
        const prefetchAbortControllers = new Map();

        const kasirProgressBar = {
            el: null,
            timer: null,
            init() {
                this.el = document.getElementById('kasir-top-progress');
            },
            start() {
                if (!this.el) this.init();
                if (!this.el) return;
                clearInterval(this.timer);
                this.el.style.opacity = '1';
                this.el.style.width = '25%';
                let progress = 25;
                this.timer = setInterval(() => {
                    if (progress < 80) {
                        progress += (80 - progress) * 0.15;
                        if (this.el) this.el.style.width = Math.round(progress) + '%';
                    }
                }, 120);
            },
            done() {
                if (!this.el) this.init();
                if (!this.el) return;
                clearInterval(this.timer);
                this.el.style.width = '100%';
                setTimeout(() => {
                    if (this.el) {
                        this.el.style.opacity = '0';
                        setTimeout(() => {
                            if (this.el) this.el.style.width = '0%';
                        }, 200);
                    }
                }, 150);
            }
        };

        // Cache Invalidation Helper
        window.clearKasirPageCache = function(pattern) {
            if (!pattern) {
                kasirPageCache.clear();
                return;
            }
            for (const key of kasirPageCache.keys()) {
                if (key.includes(pattern)) {
                    kasirPageCache.delete(key);
                }
            }
        };

        // Otomatis bersihkan cache halaman saat ada form submit / mutasi data
        window.addEventListener('submit', () => window.clearKasirPageCache());
        window.addEventListener('kasir:invalidate-cache', () => window.clearKasirPageCache());

        // Intercept non-GET fetch untuk otomatis invalidasi cache
        const _origFetch = window.fetch;
        window.fetch = function(...args) {
            const [, config] = args;
            const method = (config && config.method) ? String(config.method).toUpperCase() : 'GET';
            if (method !== 'GET' && method !== 'HEAD') {
                window.clearKasirPageCache();
            }
            return _origFetch.apply(this, args);
        };

        // Predictive Preloading saat kursor/sentuhan mendekati menu
        function prefetchKasirPage(url) {
            if (!url || typeof url !== 'string') return;
            try {
                const parsed = new URL(url, window.location.origin);
                if (parsed.origin !== window.location.origin) return;
                if (!parsed.pathname.startsWith('/kasir')) return;
                if (parsed.pathname.includes('/receipt') || parsed.pathname.includes('/mini') || parsed.pathname.includes('/ticket') || parsed.pathname.includes('/logout')) return;

                const cleanUrl = parsed.href;
                const cached = kasirPageCache.get(cleanUrl);
                if (cached && (Date.now() - cached.timestamp < CACHE_TTL_MS)) {
                    return; // Sudah ada di cache segar
                }

                if (prefetchAbortControllers.has(cleanUrl)) {
                    return; // Sedang di-fetch
                }

                const controller = new AbortController();
                prefetchAbortControllers.set(cleanUrl, controller);

                _origFetch(cleanUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                    priority: 'low'
                })
                .then(res => {
                    if (res.ok) return res.text();
                    throw new Error('Prefetch error');
                })
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newMain = doc.querySelector('main');
                    if (newMain) {
                        if (kasirPageCache.size >= MAX_CACHE_SIZE) {
                            const oldestKey = kasirPageCache.keys().next().value;
                            kasirPageCache.delete(oldestKey);
                        }
                        kasirPageCache.set(cleanUrl, {
                            html: newMain.innerHTML,
                            title: doc.title || '',
                            timestamp: Date.now()
                        });
                    }
                })
                .catch(() => {})
                .finally(() => {
                    prefetchAbortControllers.delete(cleanUrl);
                });
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Update teks tombol musik mobile saat status berubah
            window.addEventListener('soundstation:state', function(e) {
                const el = document.getElementById('mobile-music-status');
                if (el) el.textContent = e.detail.isPlaying ? '⏸ Putar' : '▶ Jeda';
            });

            // Predictive Prefetching pada hover & touch
            document.addEventListener('pointerenter', function(e) {
                const link = e.target.closest('a');
                if (link) {
                    const href = link.getAttribute('href');
                    if (href) prefetchKasirPage(href);
                }
            }, { passive: true });

            document.addEventListener('touchstart', function(e) {
                const link = e.target.closest('a');
                if (link) {
                    const href = link.getAttribute('href');
                    if (href) prefetchKasirPage(href);
                }
            }, { passive: true });

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

            // Intercept submit form kasir agar create/update/delete data berlangsung mulus tanpa reload halaman (musik tidak terputus)
            document.addEventListener('submit', async function(e) {
                const form = e.target;
                if (!form || !form.action) return;
                if (form.dataset.noPjax !== undefined || form.target === '_blank') return;

                // Jika form memiliki data-confirm, biarkan modal-dialog.blade.php yang menanganinya lebih dulu
                if (form.getAttribute('data-confirm')) return;

                const formAction = form.getAttribute('action') || window.location.href;
                try {
                    const url = new URL(formAction, window.location.origin);
                    if (url.origin !== window.location.origin) return;
                    if (!url.pathname.startsWith('/kasir')) return;
                    if (url.pathname.includes('/logout') || url.pathname.includes('/receipt') || url.pathname.includes('/ticket') || url.pathname.includes('/mini')) return;

                    // Form GET (pencarian/filter): alihkan ke swapKasirPage
                    if ((form.getAttribute('method') || 'GET').toUpperCase() === 'GET') {
                        e.preventDefault();
                        const formData = new FormData(form);
                        const params = new URLSearchParams(formData);
                        const targetUrl = url.pathname + (params.toString() ? '?' + params.toString() : '');
                        swapKasirPage(targetUrl, true);
                        return;
                    }

                    // Form POST / PUT / PATCH / DELETE:
                    e.preventDefault();
                    await window.submitKasirFormSeamless(form);
                } catch (err) {}
            });

            window.addEventListener('popstate', function() {
                swapKasirPage(window.location.href, false);
            });
        });

        function applyKasirContent(contentHtml, docTitle, targetUrl, pushState = true) {
            const mainEl = document.querySelector('main');
            if (!mainEl) return;

            // Beritahu halaman sebelumnya untuk cleanup (timers, listeners, dll)
            window.dispatchEvent(new CustomEvent('kasir:page-leave', {
                detail: { from: window.location.pathname, to: targetUrl }
            }));

            if (docTitle) {
                document.title = docTitle;
            }

            if (pushState && targetUrl) {
                window.history.pushState({}, '', targetUrl);
            }

            // Beritahukan shell bahwa rute kasir telah berpindah
            const newPath = targetUrl ? new URL(targetUrl, window.location.origin).pathname : window.location.pathname;
            window.dispatchEvent(new CustomEvent('kasir:route-changed', {
                detail: { url: targetUrl, pathname: newPath }
            }));

            highlightActiveNav(targetUrl || window.location.href);

            // Bersihkan Alpine trees lama
            if (window.Alpine && window.Alpine.destroyTree) {
                window.Alpine.destroyTree(mainEl);
            }

            // Masukkan konten baru dengan kelas animasi halus
            mainEl.classList.remove('kasir-page-enter');
            void mainEl.offsetWidth; // Force reflow agar animasi re-trigger
            mainEl.innerHTML = contentHtml;
            mainEl.style.opacity = '1';
            mainEl.style.pointerEvents = 'auto';
            mainEl.classList.add('kasir-page-enter');

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

            // Scroll konten kembali ke atas
            const scrollable = mainEl.querySelector('.overflow-y-auto') || mainEl;
            if (scrollable) scrollable.scrollTop = 0;
        }

        async function swapKasirPage(url, pushState = true) {
            // 1. Tutup sidebar drawer seketika jika terbuka
            window.dispatchEvent(new CustomEvent('kasir:close-sidebar'));

            const mainEl = document.querySelector('main');
            if (!mainEl) {
                window.location.href = url;
                return;
            }

            // 2. Berikan visual feedback instan di sidebar bahwa menu ini sudah dipilih (0ms latency)
            highlightActiveNav(url);

            // 3. Batalkan fetch navigasi sebelumnya jika pengguna mengklik link lain secara cepat
            if (activeNavAbortController) {
                activeNavAbortController.abort();
            }
            activeNavAbortController = new AbortController();

            // 4. Cek ketersediaan di cache in-memory
            const cleanUrl = new URL(url, window.location.origin).href;
            const cached = kasirPageCache.get(cleanUrl);
            const isCached = cached && (Date.now() - cached.timestamp < CACHE_TTL_MS);

            if (!isCached) {
                kasirProgressBar.start();
                mainEl.style.transition = 'opacity 0.12s ease';
                mainEl.style.opacity = '0.75';
            }

            // Kunci background polling agar seluruh kapasitas Apache difokuskan ke halaman ini
            window._isNavigatingKasirPage = true;

            try {
                let contentHtml = '';
                let docTitle = '';

                if (isCached) {
                    contentHtml = cached.html;
                    docTitle = cached.title;
                } else {
                    const res = await _origFetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        signal: activeNavAbortController.signal
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

                    contentHtml = newMain.innerHTML;
                    docTitle = doc.title || '';

                    // Simpan ke cache
                    if (kasirPageCache.size >= MAX_CACHE_SIZE) {
                        const oldestKey = kasirPageCache.keys().next().value;
                        kasirPageCache.delete(oldestKey);
                    }
                    kasirPageCache.set(cleanUrl, {
                        html: contentHtml,
                        title: docTitle,
                        timestamp: Date.now()
                    });
                }

                applyKasirContent(contentHtml, docTitle, url, pushState);
                kasirProgressBar.done();

            } catch (err) {
                if (err.name === 'AbortError') {
                    return; // Request dibatalkan karena navigasi baru
                }
                console.warn('Seamless swap failed, standard reload:', err);
                window.location.href = url;
            } finally {
                window._isNavigatingKasirPage = false;
            }
        }

        window.submitKasirFormSeamless = async function(form) {
            const mainEl = document.querySelector('main');
            if (!mainEl) {
                form.submit();
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            kasirProgressBar.start();
            mainEl.style.transition = 'opacity 0.15s ease';
            mainEl.style.opacity = '0.7';

            try {
                const formData = new FormData(form);
                const action = form.getAttribute('action') || window.location.href;
                const method = (form.getAttribute('method') || 'POST').toUpperCase();

                const res = await _origFetch(action, {
                    method: method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const html = await res.text();

                // Cek apakah response berupa JSON (misal API endpoint)
                let isJson = false;
                let jsonData = null;
                try {
                    jsonData = JSON.parse(html);
                    isJson = true;
                } catch (e) {}

                if (isJson && jsonData) {
                    kasirProgressBar.done();
                    mainEl.style.opacity = '1';
                    if (submitBtn) submitBtn.disabled = false;
                    if (window.customToast && jsonData.message) {
                        window.customToast({
                            message: jsonData.message,
                            type: jsonData.success !== false ? 'success' : 'error'
                        });
                    }
                    kasirPageCache.clear();
                    swapKasirPage(window.location.href, false);
                    return;
                }

                // Jika respons adalah halaman HTML (redirect hasil simpan/hapus/validasi)
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newMain = doc.querySelector('main');

                if (newMain) {
                    kasirPageCache.clear();
                    const targetUrl = res.url || action;
                    applyKasirContent(newMain.innerHTML, doc.title || '', targetUrl, true);
                    kasirProgressBar.done();
                    return;
                }

                window.location.href = res.url || action;
            } catch (err) {
                console.warn('Seamless form submission failed, falling back to standard submit:', err);
                form.submit();
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        };

        function highlightActiveNav(currentUrl) {
            const path = new URL(currentUrl, window.location.origin).pathname;
            const navLinks = Array.from(document.querySelectorAll('aside nav a[href]'));

            // Temukan link yang paling spesifik (path terpanjang yang cocok) agar submenu tidak mengaktifkan induk
            let bestMatch = null;
            let bestMatchLength = -1;

            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (!linkHref) return;
                const linkPath = new URL(linkHref, window.location.origin).pathname;

                let matches = false;
                if (linkPath === '/kasir' && (path === '/kasir' || path === '/kasir/terminal')) {
                    matches = true;
                } else if (linkPath !== '/kasir' && (path === linkPath || path.startsWith(linkPath + '/'))) {
                    matches = true;
                }

                if (matches && linkPath.length > bestMatchLength) {
                    bestMatch = link;
                    bestMatchLength = linkPath.length;
                }
            });

            navLinks.forEach(link => {
                if (link === bestMatch) {
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
