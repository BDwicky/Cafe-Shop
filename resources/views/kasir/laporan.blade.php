@extends('kasir.app')

@section('title', 'Laporan Keuangan & Penjualan')

@section('content')
<div x-data="reportPage()" class="w-full p-4 sm:p-6 space-y-6">

    <!-- TAMPILAN DASHBOARD (LAYAR MONITOR / TABLET) -->
    <div class="screen-dashboard-container space-y-6">

        <!-- 1. HEADER HALAMAN -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-[#E4DCCC]">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                        Laporan Keuangan & Penjualan
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full bg-[#D9973E]/15 border border-[#D9973E]/30 text-[#B5762A] font-mono text-xs font-bold">
                        Rekonsiliasi Kasir
                    </span>
                </div>
                <p class="font-sans text-xs text-[#8A7B66] mt-1">
                    Rekapitulasi penjualan kasir, analisis omzet, laba kotor, biaya operasional, dan laba bersih toko.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 print:hidden">
                <!-- Periode Badge -->
                <div class="flex items-center gap-2 font-mono text-xs text-[#8A7B66] bg-white border border-[#E4DCCC] rounded-xl px-3.5 py-2 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-[#5F7F42] animate-pulse"></span>
                    <span>
                        @if ($from->format('Y-m-d') === $to->format('Y-m-d'))
                            {{ $from->translatedFormat('d M Y') }}
                        @else
                            {{ $from->translatedFormat('d M Y') }} — {{ $to->translatedFormat('d M Y') }}
                        @endif
                    </span>
                </div>

                <!-- Tombol Kirim Rekap ke Telegram (Hari, Minggu, Bulan) -->
                <div class="relative" x-data="{ openTele: false }" @click.outside="openTele = false">
                    <button type="button" @click="openTele = !openTele" :disabled="isSendingTelegram"
                            class="px-3.5 py-2 bg-sky-50 hover:bg-sky-100 text-sky-900 border border-sky-200 font-mono text-xs font-bold rounded-xl transition shadow-2xs flex items-center gap-2 active:scale-98 cursor-pointer disabled:opacity-50">
                        <svg class="w-4 h-4 text-sky-700 shrink-0" :class="{'animate-spin': isSendingTelegram}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span x-text="isSendingTelegram ? 'Mengirim...' : 'Kirim ke Telegram'">Kirim ke Telegram</span>
                        <svg class="w-3 h-3 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="openTele" x-cloak
                         class="absolute right-0 mt-1.5 w-72 bg-white border border-[#E4DCCC] rounded-2xl shadow-xl py-2 z-50 text-left divide-y divide-[#F0EBE1]">
                        <div class="px-3.5 py-1.5 text-[10px] font-mono font-bold uppercase tracking-wider text-[#8A7B66]">
                            Kirim Rekap Menu Otomatis:
                        </div>
                        <div class="py-1">
                            <!-- Rekap Hari Ini -->
                            <button type="button" @click="sendTelegramRecap('today'); openTele = false"
                                    class="w-full px-3.5 py-2.5 text-xs font-sans text-[#1F1812] hover:bg-sky-50 flex items-center gap-3 transition text-left cursor-pointer">
                                <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-[#1F1812]">Rekap Hari Ini</div>
                                    <div class="text-[10px] text-[#8A7B66]">Omzet & semua menu terjual hari ini</div>
                                </div>
                            </button>

                            <!-- Rekap 7 Hari Terakhir -->
                            <button type="button" @click="sendTelegramRecap('7days'); openTele = false"
                                    class="w-full px-3.5 py-2.5 text-xs font-sans text-[#1F1812] hover:bg-sky-50 flex items-center gap-3 transition text-left cursor-pointer">
                                <div class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-[#1F1812]">Rekap 7 Hari Terakhir</div>
                                    <div class="text-[10px] text-[#8A7B66]">Rangkuman omzet 1 minggu & ranking menu</div>
                                </div>
                            </button>

                            <!-- Rekap Bulan Ini -->
                            <button type="button" @click="sendTelegramRecap('month'); openTele = false"
                                    class="w-full px-3.5 py-2.5 text-xs font-sans text-[#1F1812] hover:bg-sky-50 flex items-center gap-3 transition text-left cursor-pointer">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-800 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-[#1F1812]">Rekap Bulan Ini</div>
                                    <div class="text-[10px] text-[#8A7B66]">Rekapitulasi penjualan bulan berjalan</div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tombol Cetak Struk Thermal 80mm -->
                <a href="{{ route('kasir.laporan.receipt', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}"
                   target="_blank"
                   title="Buka dan cetak format struk thermal 80mm"
                   class="px-3.5 py-2 bg-white hover:bg-[#FAF7F2] text-[#1F1812] border border-[#E4DCCC] hover:border-[#D9973E] font-mono text-xs font-bold rounded-xl transition shadow-2xs flex items-center gap-2 active:scale-98">
                    <svg class="w-4 h-4 text-[#B5762A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Cetak Struk (80mm)</span>
                </a>

                <!-- Buka POS -->
                <a href="{{ route('kasir.terminal') }}"
                   class="px-4 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all shadow-md active:scale-98 flex items-center gap-1.5">
                    <span>+ Terminal POS ›</span>
                </a>
            </div>
        </div>

        <!-- 2. FILTER PERIODE & PINTASAN CEPAT -->
        @php
            $todayStr = now()->format('Y-m-d');
            $yesterdayStr = now()->subDay()->format('Y-m-d');
            $from7DaysStr = now()->subDays(6)->format('Y-m-d');
            $startOfMonthStr = now()->startOfMonth()->format('Y-m-d');

            $currentFrom = $from->format('Y-m-d');
            $currentTo = $to->format('Y-m-d');

            $isToday = ($currentFrom === $todayStr && $currentTo === $todayStr);
            $isYesterday = ($currentFrom === $yesterdayStr && $currentTo === $yesterdayStr);
            $is7Days = ($currentFrom === $from7DaysStr && $currentTo === $todayStr);
            $isMonth = ($currentFrom === $startOfMonthStr && $currentTo === $todayStr);
        @endphp
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs print:hidden">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                
                <!-- Pintasan Periode Cepat -->
                <div class="flex items-center flex-wrap gap-2">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold mr-1">
                        PERIODE CEPAT:
                    </span>
                    <button type="button"
                            @click="setDateRange('today')"
                            class="px-3.5 py-1.5 rounded-xl font-mono text-xs font-bold transition border {{ $isToday ? 'bg-[#1F1812] text-[#F7F3EC] border-[#1F1812] shadow-xs' : 'bg-[#FAF7F2] text-[#8A7B66] border-[#E4DCCC] hover:text-[#1F1812] hover:border-[#B5762A]' }}">
                        Hari Ini
                    </button>
                    <button type="button"
                            @click="setDateRange('yesterday')"
                            class="px-3.5 py-1.5 rounded-xl font-mono text-xs font-bold transition border {{ $isYesterday ? 'bg-[#1F1812] text-[#F7F3EC] border-[#1F1812] shadow-xs' : 'bg-[#FAF7F2] text-[#8A7B66] border-[#E4DCCC] hover:text-[#1F1812] hover:border-[#B5762A]' }}">
                        Kemarin
                    </button>
                    <button type="button"
                            @click="setDateRange('7days')"
                            class="px-3.5 py-1.5 rounded-xl font-mono text-xs font-bold transition border {{ $is7Days ? 'bg-[#1F1812] text-[#F7F3EC] border-[#1F1812] shadow-xs' : 'bg-[#FAF7F2] text-[#8A7B66] border-[#E4DCCC] hover:text-[#1F1812] hover:border-[#B5762A]' }}">
                        7 Hari Terakhir
                    </button>
                    <button type="button"
                            @click="setDateRange('month')"
                            class="px-3.5 py-1.5 rounded-xl font-mono text-xs font-bold transition border {{ $isMonth ? 'bg-[#1F1812] text-[#F7F3EC] border-[#1F1812] shadow-xs' : 'bg-[#FAF7F2] text-[#8A7B66] border-[#E4DCCC] hover:text-[#1F1812] hover:border-[#B5762A]' }}">
                        Bulan Ini
                    </button>
                </div>

                <!-- Form Filter Tanggal Kustom -->
                <form id="report-filter-form" method="GET" action="{{ route('kasir.laporan') }}" @submit.prevent="submitDateFilter()" class="flex items-center flex-wrap gap-2.5">
                    <div class="flex items-center gap-1.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-2.5 py-1.5 focus-within:border-[#B5762A] focus-within:bg-white transition">
                        <span class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] font-bold">DARI:</span>
                        <input type="date"
                               id="report-from-date"
                               name="from"
                               value="{{ $from->format('Y-m-d') }}"
                               class="bg-transparent border-0 text-xs font-mono text-[#1F1812] focus:outline-none cursor-pointer">
                    </div>

                    <span class="text-[#8A7B66] font-mono text-xs">s/d</span>

                    <div class="flex items-center gap-1.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-2.5 py-1.5 focus-within:border-[#B5762A] focus-within:bg-white transition">
                        <span class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] font-bold">SAMPAI:</span>
                        <input type="date"
                               id="report-to-date"
                               name="to"
                               value="{{ $to->format('Y-m-d') }}"
                               class="bg-transparent border-0 text-xs font-mono text-[#1F1812] focus:outline-none cursor-pointer">
                    </div>

                    <button type="submit"
                            class="px-4 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-xs active:scale-95">
                        Tampilkan
                    </button>
                </form>

            </div>
        </div>

        <!-- 3. KARTU STATISTIK UTAMA PENJUALAN (HERO STAT CARDS) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            
            <!-- 1. Total Transaksi -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/60 flex flex-col justify-between">
                <div class="flex items-center justify-between text-[#8A7B66]">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">TOTAL TRANSAKSI</span>
                    <span class="w-8 h-8 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-sm shadow-2xs">🧾</span>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="font-mono text-3xl sm:text-4xl font-extrabold text-[#1F1812] tracking-tight">
                        {{ number_format($totals->trx, 0, ',', '.') }}
                    </span>
                    <span class="text-xs text-[#8A7B66] font-mono font-medium">transaksi</span>
                </div>
                <div class="mt-2 text-[11px] text-[#8A7B66] font-mono flex items-center gap-1.5 pt-2 border-t border-[#F2EDE4]">
                    <span class="h-2 w-2 rounded-full bg-[#5F7F42]"></span>
                    <span>Status Lunas (Paid)</span>
                </div>
            </div>

            <!-- 2. Omzet Penjualan (Highlight Card) -->
            <div class="bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] rounded-2xl p-4 sm:p-5 shadow-md relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-center justify-between text-[#A89A85]">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold text-[#D9973E]">OMZET BERSIH</span>
                    <span class="w-8 h-8 rounded-xl bg-[#2A211A] border border-[#3A3026] flex items-center justify-center text-sm shadow-2xs">💰</span>
                </div>
                <div class="mt-3">
                    <div class="font-mono text-2xl sm:text-3xl font-extrabold text-[#D9973E] tracking-tight">
                        Rp {{ number_format($totals->omzet, 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-2 text-[11px] text-[#A89A85] font-mono flex items-center justify-between pt-2 border-t border-[#3A3026]">
                    <span>Pendapatan Penjualan</span>
                    @if (!empty($totals->total_discount) && $totals->total_discount > 0)
                        <span class="text-[#D9973E] font-semibold">Diskon: Rp {{ number_format($totals->total_discount, 0, ',', '.') }}</span>
                    @endif
                </div>
            </div>

            <!-- 3. Rata-rata per Basket / Transaksi -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/60 flex flex-col justify-between">
                <div class="flex items-center justify-between text-[#8A7B66]">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">RATA-RATA / BASKET</span>
                    <span class="w-8 h-8 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-sm shadow-2xs">🏷️</span>
                </div>
                <div class="mt-3">
                    <div class="font-mono text-2xl sm:text-3xl font-bold text-[#1F1812] tracking-tight">
                        Rp {{ number_format(round($totals->avg_basket), 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-2 text-[11px] text-[#8A7B66] font-mono flex items-center gap-1.5 pt-2 border-t border-[#F2EDE4]">
                    <span>Rata-rata belanja per tamu</span>
                </div>
            </div>

            <!-- 4. Total Item Terjual -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/60 flex flex-col justify-between">
                <div class="flex items-center justify-between text-[#8A7B66]">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">ITEM TERJUAL</span>
                    <span class="w-8 h-8 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-sm shadow-2xs">☕</span>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="font-mono text-3xl sm:text-4xl font-extrabold text-[#1F1812] tracking-tight">
                        {{ number_format($itemsSold, 0, ',', '.') }}
                    </span>
                    <span class="text-xs text-[#8A7B66] font-mono font-medium">porsi</span>
                </div>
                <div class="mt-2 text-[11px] text-[#8A7B66] font-mono flex items-center gap-1.5 pt-2 border-t border-[#F2EDE4]">
                    <span>Total cup & makanan keluar</span>
                </div>
            </div>

        </div>

        <!-- 4. KARTU ANALISIS KEUANGAN & LABA BERSIH TOKO -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-[#E4DCCC] gap-3 mb-5">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg">📊</span>
                        <h2 class="font-serif font-bold text-lg text-[#1F1812]">Analisis Keuangan & Laba Toko</h2>
                    </div>
                    <p class="font-mono text-xs text-[#8A7B66] mt-0.5">
                        Perhitungan otomatis dari resep BOM bahan baku dan buku pengeluaran kasir.
                    </p>
                </div>
                <div class="flex items-center gap-2 print:hidden">
                    <a href="{{ route('kasir.expenses.index') }}"
                       class="px-3 py-1.5 bg-[#FAF7F2] hover:bg-[#1F1812] hover:text-white border border-[#E4DCCC] rounded-xl font-mono text-xs text-[#1F1812] transition shadow-2xs">
                        + Catat Pengeluaran
                    </a>
                    <a href="{{ route('kasir.inventory.index') }}"
                       class="px-3 py-1.5 bg-[#FAF7F2] hover:bg-[#1F1812] hover:text-white border border-[#E4DCCC] rounded-xl font-mono text-xs text-[#1F1812] transition shadow-2xs">
                        📦 Master Stok Bahan
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. HPP Bahan Baku Terpakai -->
                <div class="p-4 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl flex flex-col justify-between">
                    <div class="flex items-center justify-between text-[#8A7B66]">
                        <span class="font-mono text-[10px] uppercase tracking-wider font-bold">HPP Bahan Terjual</span>
                        <span class="text-sm">🌱</span>
                    </div>
                    <div class="mt-3 font-mono text-2xl font-bold text-[#1F1812]">
                        Rp {{ number_format($finance['cogs'], 0, ',', '.') }}
                    </div>
                    <div class="mt-2 text-[11px] font-mono text-[#8A7B66] pt-2 border-t border-[#E4DCCC]/60">
                        Modal resep bahan yang terpakai
                    </div>
                </div>

                <!-- 2. Laba Kotor (Gross Profit) -->
                <div class="p-4 bg-[#5F7F42]/10 border border-[#5F7F42]/30 rounded-xl flex flex-col justify-between">
                    <div class="flex items-center justify-between text-[#5F7F42]">
                        <span class="font-mono text-[10px] uppercase tracking-wider font-bold">Laba Kotor (Gross)</span>
                        <span class="px-2 py-0.5 rounded-full bg-[#5F7F42]/20 font-mono text-[10px] font-bold text-[#5F7F42]">
                            {{ $finance['gross_margin'] }}%
                        </span>
                    </div>
                    <div class="mt-3 font-mono text-2xl font-bold text-[#5F7F42]">
                        Rp {{ number_format($finance['gross_profit'], 0, ',', '.') }}
                    </div>
                    <div class="mt-2 text-[11px] font-mono text-[#5F7F42] pt-2 border-t border-[#5F7F42]/20">
                        Omzet dikurangi HPP bahan
                    </div>
                </div>

                <!-- 3. Total Pengeluaran Toko (Expenses) -->
                <div class="p-4 bg-[#C4553D]/10 border border-[#C4553D]/30 rounded-xl flex flex-col justify-between">
                    <div class="flex items-center justify-between text-[#C4553D]">
                        <span class="font-mono text-[10px] uppercase tracking-wider font-bold">Pengeluaran Toko</span>
                        <span class="text-sm">🧾</span>
                    </div>
                    <div class="mt-3 font-mono text-2xl font-bold text-[#C4553D]">
                        Rp {{ number_format($finance['total_expenses'], 0, ',', '.') }}
                    </div>
                    <div class="mt-2 text-[10px] font-mono text-[#8A7B66] pt-2 border-t border-[#C4553D]/20 truncate"
                         title="Restock: Rp {{ number_format($finance['restock_expenses'], 0, ',', '.') }} • Opr: Rp {{ number_format($finance['operational_expenses'], 0, ',', '.') }}">
                        Restock: Rp {{ number_format($finance['restock_expenses'], 0, ',', '.') }} • Opr: Rp {{ number_format($finance['operational_expenses'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- 4. Laba Bersih Toko (Net Profit) -->
                @php
                    $isNetPositive = $finance['net_profit'] >= 0;
                @endphp
                <div class="p-4 {{ $isNetPositive ? 'bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026]' : 'bg-red-50 text-red-900 border border-red-200' }} rounded-xl flex flex-col justify-between shadow-xs">
                    <div class="flex items-center justify-between {{ $isNetPositive ? 'text-[#D9973E]' : 'text-red-700' }}">
                        <span class="font-mono text-[10px] uppercase tracking-wider font-bold">Laba Bersih Toko</span>
                        <span class="px-2 py-0.5 rounded-full {{ $isNetPositive ? 'bg-[#D9973E]/20 text-[#D9973E]' : 'bg-red-200 text-red-800' }} font-mono text-[10px] font-bold">
                            {{ $finance['net_margin'] }}%
                        </span>
                    </div>
                    <div class="mt-3 font-mono text-2xl font-extrabold {{ $isNetPositive ? 'text-[#D9973E]' : 'text-red-700' }}">
                        Rp {{ number_format($finance['net_profit'], 0, ',', '.') }}
                    </div>
                    <div class="mt-2 text-[11px] font-mono {{ $isNetPositive ? 'text-[#A89A85]' : 'text-red-600' }} pt-2 border-t {{ $isNetPositive ? 'border-[#3A3026]' : 'border-red-200' }}">
                        Omzet dikurangi seluruh pengeluaran
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. DUA KOLOM KOMPARASI: METODE PEMBAYARAN & 10 MENU TERLARIS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Kolom Kiri: Distribusi Metode Pembayaran -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-[#E4DCCC] mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-sm">💳</span>
                            <h3 class="font-serif font-bold text-base text-[#1F1812]">Metode Pembayaran</h3>
                        </div>
                        <span class="font-mono text-xs text-[#8A7B66]">
                            Total: Rp {{ number_format($totals->omzet, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="space-y-3.5">
                        @php
                            $omzetTotal = (int) $totals->omzet ?: 1;
                        @endphp
                        @forelse ($byMethod as $m)
                            @php
                                $pct = round(($m->t / $omzetTotal) * 100, 1);
                                $badgeColor = match($m->payment_method) {
                                    'cash' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'qris' => 'bg-purple-50 text-purple-800 border-purple-200',
                                    'debit' => 'bg-blue-50 text-blue-800 border-blue-200',
                                    default => 'bg-[#FAF7F2] text-[#1F1812] border-[#E4DCCC]'
                                };
                                $barColor = match($m->payment_method) {
                                    'cash' => 'bg-emerald-500',
                                    'qris' => 'bg-purple-500',
                                    'debit' => 'bg-blue-500',
                                    default => 'bg-[#D9973E]'
                                };
                                $methodLabel = match($m->payment_method) {
                                    'cash' => 'Tunai (Cash)',
                                    'qris' => 'QRIS Dinamis',
                                    'debit' => 'Kartu Debit',
                                    default => strtoupper($m->payment_method)
                                };
                            @endphp
                            <div class="p-3 bg-[#FAF7F2] rounded-xl border border-[#E4DCCC]/70">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold uppercase border {{ $badgeColor }}">
                                            {{ $methodLabel }}
                                        </span>
                                        <span class="font-mono text-xs text-[#8A7B66]">
                                            {{ number_format($m->c, 0, ',', '.') }} transaksi
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-mono text-sm font-bold text-[#1F1812]">
                                            Rp {{ number_format($m->t, 0, ',', '.') }}
                                        </span>
                                        <span class="font-mono text-[11px] text-[#8A7B66] ml-1.5 font-semibold">
                                            ({{ $pct }}%)
                                        </span>
                                    </div>
                                </div>
                                <!-- Progress Bar Persentase -->
                                <div class="w-full h-2 bg-[#E4DCCC]/60 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-[#8A7B66] font-mono text-xs">
                                Belum ada transaksi tercatat pada periode ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: 10 Menu Terlaris (Top Performers) -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-[#E4DCCC] mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-sm">🏆</span>
                            <h3 class="font-serif font-bold text-base text-[#1F1812]">10 Menu Terlaris</h3>
                        </div>
                        <span class="font-mono text-xs text-[#8A7B66]">Peringkat Penjualan</span>
                    </div>

                    <div class="space-y-2.5">
                        @php
                            $maxQty = $best->max('qty') ?: 1;
                        @endphp
                        @forelse ($best as $idx => $b)
                            @php
                                $rankColor = match($idx) {
                                    0 => 'bg-[#D9973E] text-[#1F1812]', // Emas
                                    1 => 'bg-[#A89A85] text-white',      // Perak
                                    2 => 'bg-[#8A7B66] text-white',      // Perunggu
                                    default => 'bg-[#FAF7F2] text-[#8A7B66] border border-[#E4DCCC]'
                                };
                                $barPct = round(($b->qty / $maxQty) * 100);
                            @endphp
                            <div class="flex items-center gap-3 p-2.5 bg-[#FAF7F2] rounded-xl border border-[#E4DCCC]/60 hover:border-[#D9973E]/60 transition">
                                <!-- Rank Badge -->
                                <div class="w-6 h-6 rounded-lg font-mono text-xs font-bold flex items-center justify-center shrink-0 shadow-2xs {{ $rankColor }}">
                                    {{ $idx + 1 }}
                                </div>

                                <!-- Detail Menu & Progress -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-sans text-xs font-bold text-[#1F1812] truncate mr-2">
                                            {{ $b->menu_name }}
                                        </span>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="font-mono text-xs font-bold text-[#B5762A]">
                                                {{ $b->qty }} pcs
                                            </span>
                                            <span class="font-mono text-xs text-[#1F1812] font-semibold">
                                                Rp {{ number_format($b->omzet, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Progress Bar -->
                                    <div class="w-full h-1.5 bg-[#E4DCCC]/60 rounded-full overflow-hidden">
                                        <div class="h-full bg-[#D9973E] rounded-full transition-all duration-500"
                                             style="width: {{ $barPct }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-[#8A7B66] font-mono text-xs">
                                Belum ada hidangan terjual pada periode ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>

        <!-- 6. PERFORMA KASIR & STAF (CASHIER PERFORMANCE BREAKDOWN) -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-[#E4DCCC] gap-3 mb-5">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🧑‍💼</span>
                        <h2 class="font-serif font-bold text-lg text-[#1F1812]">Performa Kasir & Staf</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono text-[10px] font-bold">
                            {{ $cashierStats->count() }} Staf Bertransaksi
                        </span>
                    </div>
                    <p class="font-mono text-xs text-[#8A7B66] mt-0.5">
                        Transparansi transaksi, omzet per staf, nilai rata-rata tiket, serta catatan void/pembatalan kasir.
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs font-mono text-[#8A7B66]">
                    <span>Total Omzet: <strong class="text-[#1F1812]">Rp {{ number_format($totals->omzet, 0, ',', '.') }}</strong></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#E4DCCC] text-[11px] font-mono uppercase tracking-wider text-[#8A7B66]">
                            <th class="pb-3 font-bold">Kasir / Staf</th>
                            <th class="pb-3 font-bold text-center">Peran (Role)</th>
                            <th class="pb-3 font-bold text-center">Trx Lunas</th>
                            <th class="pb-3 font-bold text-right">Total Omzet</th>
                            <th class="pb-3 font-bold text-center">Kontribusi</th>
                            <th class="pb-3 font-bold text-right">Avg Basket</th>
                            <th class="pb-3 font-bold text-right">Total Diskon</th>
                            <th class="pb-3 font-bold text-center">Void (Batal)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F2EDE4] text-xs font-sans">
                        @forelse ($cashierStats as $c)
                            @php
                                $totalOmzetNumber = (int) $totals->omzet ?: 1;
                                $contribPct = round(($c->total_sales / $totalOmzetNumber) * 100, 1);
                                $isOwner = strtolower($c->cashier_role) === 'owner';
                            @endphp
                            <tr class="hover:bg-[#FAF7F2]/80 transition">
                                <!-- Kasir / Staf Name & Info -->
                                <td class="py-3.5 pr-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-mono font-bold text-sm shadow-2xs {{ $isOwner ? 'bg-[#1F1812] text-[#D9973E] border border-[#3A3026]' : 'bg-[#FAF7F2] text-[#1F1812] border border-[#E4DCCC]' }}">
                                            {{ strtoupper(substr($c->cashier_name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-[#1F1812] flex items-center gap-1.5">
                                                <span>{{ $c->cashier_name }}</span>
                                                @if ($isOwner)
                                                    <span class="px-1.5 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300 font-mono text-[9px] font-extrabold uppercase">
                                                        Owner
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] font-mono text-[#8A7B66]">
                                                {{ $c->cashier_email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold uppercase border {{ $isOwner ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-sky-50 text-sky-800 border-sky-200' }}">
                                        {{ $c->cashier_role }}
                                    </span>
                                </td>

                                <!-- Trx Selesai -->
                                <td class="py-3.5 px-3 text-center font-mono font-bold text-[#1F1812]">
                                    <span class="inline-block px-2 py-0.5 rounded-lg bg-[#FAF7F2] border border-[#E4DCCC]">
                                        {{ number_format($c->paid_count, 0, ',', '.') }} trx
                                    </span>
                                </td>

                                <!-- Total Omzet -->
                                <td class="py-3.5 px-3 text-right font-mono font-bold text-[#1F1812]">
                                    Rp {{ number_format($c->total_sales, 0, ',', '.') }}
                                </td>

                                <!-- Kontribusi Omzet & Bar -->
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-16 h-2 bg-[#E4DCCC]/60 rounded-full overflow-hidden hidden sm:block">
                                            <div class="h-full bg-[#5F7F42] rounded-full transition-all duration-500"
                                                 style="width: {{ min(100, $contribPct) }}%"></div>
                                        </div>
                                        <span class="font-mono text-xs font-bold text-[#5F7F42]">
                                            {{ $contribPct }}%
                                        </span>
                                    </div>
                                </td>

                                <!-- Avg Basket -->
                                <td class="py-3.5 px-3 text-right font-mono text-[#8A7B66]">
                                    Rp {{ number_format(round($c->avg_sale), 0, ',', '.') }}
                                </td>

                                <!-- Diskon Diberikan -->
                                <td class="py-3.5 px-3 text-right font-mono {{ $c->total_discount > 0 ? 'text-[#C4553D] font-bold' : 'text-[#8A7B66]' }}">
                                    @if ($c->total_discount > 0)
                                        -Rp {{ number_format($c->total_discount, 0, ',', '.') }}
                                    @else
                                        Rp 0
                                    @endif
                                </td>

                                <!-- Void Count -->
                                <td class="py-3.5 pl-3 text-center">
                                    @if ($c->void_count > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-mono text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                                            ⚠️ {{ $c->void_count }} batal
                                        </span>
                                    @else
                                        <span class="font-mono text-xs text-[#8A7B66]">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-[#8A7B66] font-mono text-xs">
                                    Tidak ada transaksi kasir pada periode yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 7. PERKEMBANGAN OMZET PER HARI (JIKA PERIODE > 1 HARI) -->
        @if ($perDay->count() > 1)
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-[#E4DCCC] mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm">📈</span>
                        <h3 class="font-serif font-bold text-base text-[#1F1812]">Perkembangan Omzet Harian</h3>
                    </div>
                    <span class="font-mono text-xs text-[#8A7B66]">{{ $perDay->count() }} Hari Terpilih</span>
                </div>

                @php
                    $maxDaily = $perDay->max('t') ?: 1;
                @endphp
                <div class="space-y-3">
                    @foreach ($perDay as $d)
                        @php
                            $dailyDate = \Illuminate\Support\Carbon::parse($d->d);
                            $barWidth = round(($d->t / $maxDaily) * 100);
                        @endphp
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-xs text-[#8A7B66] w-28 shrink-0 font-medium">
                                {{ $dailyDate->translatedFormat('D, d M Y') }}
                            </span>
                            <div class="flex-1 h-6 bg-[#FAF7F2] border border-[#E4DCCC] rounded-lg overflow-hidden p-0.5">
                                <div class="h-full bg-gradient-to-r from-[#D9973E] to-[#B5762A] rounded transition-all duration-500"
                                     style="width: {{ $barWidth }}%"></div>
                            </div>
                            <div class="flex items-center gap-2 w-44 justify-end shrink-0">
                                <span class="px-2 py-0.5 rounded bg-[#FAF7F2] border border-[#E4DCCC] font-mono text-[10px] text-[#8A7B66]">
                                    {{ $d->c }} trx
                                </span>
                                <span class="font-mono text-xs font-bold text-[#1F1812]">
                                    Rp {{ number_format($d->t, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div> <!-- end .screen-dashboard-container -->

    <!-- FORMAT STRUK THERMAL 80MM (TAMPIL HANYA SAAT PRINT LANGSUNG DARI HALAMAN INI) -->
    <div class="print-receipt-container hidden">
        <div class="receipt-inner">
            <div class="center">
                <div class="brand">{{ config('cafe.name') }}</div>
                <div class="meta">{{ config('cafe.address') }}</div>
                <div class="title">*** LAPORAN KASIR ***</div>
            </div>

            <div class="dashed meta">
                <table style="font-size: 8.5pt;">
                    <tr>
                        <td>Tgl Cetak</td>
                        <td class="r">{{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <td>Periode</td>
                        <td class="r">
                            @if ($from->format('Y-m-d') === $to->format('Y-m-d'))
                                {{ $from->format('d/m/Y') }}
                            @else
                                {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Kasir</td>
                        <td class="r">{{ auth()->user()->name ?? 'Kasir' }}</td>
                    </tr>
                    <tr>
                        <td>Status Shift</td>
                        <td class="r">TUTUP / REKONSILIASI</td>
                    </tr>
                </table>
            </div>

            <div class="dashed">
                <div class="section-title">Ringkasan Penjualan</div>
                <table>
                    <tr>
                        <td>Total Transaksi</td>
                        <td class="r"><b>{{ number_format($totals->trx, 0, ',', '.') }}</b> Trx</td>
                    </tr>
                    <tr>
                        <td>Total Item Terjual</td>
                        <td class="r"><b>{{ number_format($itemsSold, 0, ',', '.') }}</b> pcs</td>
                    </tr>
                    <tr>
                        <td>Rata-rata / Trx</td>
                        <td class="r">Rp {{ number_format(round($totals->avg_basket), 0, ',', '.') }}</td>
                    </tr>
                    <tr class="tot" style="border-top: 1px dashed #000; padding-top: 1.5mm;">
                        <td style="padding-top: 1.5mm;">TOTAL OMZET</td>
                        <td class="r" style="padding-top: 1.5mm;">Rp {{ number_format($totals->omzet, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <div class="dashed">
                <div class="section-title">Analisis Keuangan</div>
                <table>
                    <tr>
                        <td>HPP Modal Bahan</td>
                        <td class="r">Rp {{ number_format($finance['cogs'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Laba Kotor (Gross)</td>
                        <td class="r">Rp {{ number_format($finance['gross_profit'], 0, ',', '.') }} ({{ $finance['gross_margin'] }}%)</td>
                    </tr>
                    <tr>
                        <td>Pengeluaran Toko</td>
                        <td class="r">Rp {{ number_format($finance['total_expenses'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="tot" style="border-top: 1px dashed #000; padding-top: 1.5mm;">
                        <td style="padding-top: 1.5mm;">LABA BERSIH</td>
                        <td class="r" style="padding-top: 1.5mm;">Rp {{ number_format($finance['net_profit'], 0, ',', '.') }} ({{ $finance['net_margin'] }}%)</td>
                    </tr>
                </table>
            </div>

            <div class="dashed">
                <div class="section-title">Metode Pembayaran</div>
                <table>
                    @forelse ($byMethod as $m)
                        <tr>
                            <td style="text-transform: uppercase;">
                                {{ $m->payment_method === 'cash' ? 'Tunai (Cash)' : strtoupper($m->payment_method) }}
                                <span style="font-size: 7.5pt; color: #333;">({{ $m->c }} trx)</span>
                            </td>
                            <td class="r">Rp {{ number_format($m->t, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" style="font-style: italic; color: #555;">Tidak ada transaksi.</td></tr>
                    @endforelse
                </table>
            </div>

            <div class="dashed">
                <div class="section-title">10 Menu Terlaris</div>
                <table>
                    @forelse ($best as $idx => $b)
                        <tr>
                            <td style="padding-bottom: 1mm;">
                                {{ $idx + 1 }}. {{ $b->menu_name }}<br>
                                &nbsp;&nbsp;&nbsp;<span style="font-size: 7.5pt; color: #444;">{{ $b->qty }} pcs terjual</span>
                            </td>
                            <td class="r" style="vertical-align: top;">
                                Rp {{ number_format($b->omzet, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" style="font-style: italic; color: #555;">Tidak ada penjualan.</td></tr>
                    @endforelse
                </table>
            </div>

            @if ($perDay->count() > 1)
                <div class="dashed">
                    <div class="section-title">Rincian Per Hari</div>
                    <table>
                        @foreach ($perDay as $d)
                            <tr>
                                <td>
                                    {{ \Illuminate\Support\Str::of($d->d)->explode('-')->reverse()->implode('/') }}
                                    <span style="font-size: 7.5pt; color: #444;">({{ $d->c }} trx)</span>
                                </td>
                                <td class="r">Rp {{ number_format($d->t, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            @if (isset($cashierStats) && $cashierStats->isNotEmpty())
                <div class="dashed">
                    <div class="section-title">Performa Kasir & Staf</div>
                    <table>
                        @foreach ($cashierStats as $cs)
                            @php
                                $cTotalOmzet = (int) $totals->omzet ?: 1;
                                $cPct = round(($cs->total_sales / $cTotalOmzet) * 100, 1);
                            @endphp
                            <tr>
                                <td style="padding-bottom: 1mm;">
                                    <b>{{ $cs->cashier_name }}</b> ({{ strtoupper($cs->cashier_role) }})<br>
                                    &nbsp;&nbsp;&nbsp;<span style="font-size: 7.5pt; color: #444;">{{ $cs->paid_count }} trx @if($cs->void_count > 0) • {{ $cs->void_count }} void @endif • ({{ $cPct }}%)</span>
                                </td>
                                <td class="r" style="vertical-align: top;">
                                    Rp {{ number_format($cs->total_sales, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            <div class="dashed">
                <div class="signatures">
                    <div class="sig-box">
                        <div class="meta">Kasir Bertugas</div>
                        <div class="sig-line"></div>
                        <div class="meta" style="margin-top: 1mm;">{{ auth()->user()->name ?? 'Kasir' }}</div>
                    </div>
                    <div class="sig-box">
                        <div class="meta">Supervisor / Owner</div>
                        <div class="sig-line"></div>
                        <div class="meta" style="margin-top: 1mm;">( .................... )</div>
                    </div>
                </div>
            </div>

            <div class="center meta" style="margin-top: 3.5mm; border-top: 1px dashed #000; padding-top: 2.5mm;">
                *** TUTUP KASIR // REKONSILIASI ***<br>
                Dicetak oleh Sistem Kasir {{ config('cafe.name') }}<br>
                Simpan struk ini sebagai bukti rekonsiliasi kas.
            </div>
        </div>
    </div>
</div>

<script>
    function reportPage() {
        return {
            isSendingTelegram: false,

            sendTelegramRecap(period) {
                if (this.isSendingTelegram) return;
                this.isSendingTelegram = true;

                fetch('{{ route('kasir.laporan.send-telegram') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ period: period })
                })
                .then(async (res) => {
                    const data = await res.json();
                    if (res.ok && data.success) {
                        if (window.customToast) {
                            window.customToast({
                                message: data.message || 'Rekap berhasil dikirim ke Telegram!',
                                type: 'success'
                            });
                        } else {
                            alert(data.message || 'Rekap berhasil dikirim ke Telegram!');
                        }
                    } else {
                        throw new Error(data.message || 'Gagal mengirim rekap ke Telegram');
                    }
                })
                .catch((err) => {
                    if (window.customToast) {
                        window.customToast({
                            message: err.message || 'Gagal mengirim rekap ke Telegram.',
                            type: 'error'
                        });
                    } else {
                        alert(err.message || 'Gagal mengirim rekap ke Telegram.');
                    }
                })
                .finally(() => {
                    this.isSendingTelegram = false;
                });
            },

            setDateRange(preset) {
                const fromInput = document.getElementById('report-from-date');
                const toInput = document.getElementById('report-to-date');
                if (!fromInput || !toInput) return;

                const today = new Date();
                const formatDate = (d) => {
                    const y = d.getFullYear();
                    const m = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${y}-${m}-${day}`;
                };

                if (preset === 'today') {
                    const tStr = formatDate(today);
                    fromInput.value = tStr;
                    toInput.value = tStr;
                } else if (preset === 'yesterday') {
                    const yest = new Date(today);
                    yest.setDate(yest.getDate() - 1);
                    const yStr = formatDate(yest);
                    fromInput.value = yStr;
                    toInput.value = yStr;
                } else if (preset === '7days') {
                    const past7 = new Date(today);
                    past7.setDate(past7.getDate() - 6);
                    fromInput.value = formatDate(past7);
                    toInput.value = formatDate(today);
                } else if (preset === 'month') {
                    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    fromInput.value = formatDate(firstDay);
                    toInput.value = formatDate(today);
                }

                this.submitDateFilter();
            },

            submitDateFilter() {
                const fromInput = document.getElementById('report-from-date');
                const toInput = document.getElementById('report-to-date');
                const fromVal = fromInput ? fromInput.value : '';
                const toVal = toInput ? toInput.value : '';

                const url = new URL('{{ route('kasir.laporan') }}', window.location.origin);
                if (fromVal) url.searchParams.set('from', fromVal);
                if (toVal) url.searchParams.set('to', toVal);

                if (typeof swapKasirPage === 'function') {
                    swapKasirPage(url.href, true);
                } else {
                    const form = document.getElementById('report-filter-form');
                    if (form) form.submit();
                    else window.location.href = url.href;
                }
            }
        };
    }
</script>

<style>
@media print {
    @page {
        size: 80mm auto;
        margin: 0;
    }
    aside, header, nav, .print\:hidden, form {
        display: none !important;
    }
    body, html, main {
        background: #fff !important;
        overflow: visible !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .screen-dashboard-container {
        display: none !important;
    }
    .print-receipt-container {
        display: block !important;
        width: 72mm !important;
        margin: 0 auto !important;
        padding: 2mm 1mm !important;
        background: #fff !important;
        color: #000 !important;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        font-size: 9pt !important;
        line-height: 1.4 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .print-receipt-container .center { text-align: center; }
    .print-receipt-container .brand { font-size: 13pt; font-weight: bold; letter-spacing: 0.12em; text-transform: uppercase; }
    .print-receipt-container .title { font-size: 10pt; font-weight: bold; letter-spacing: 0.08em; text-transform: uppercase; margin-top: 1.5mm; }
    .print-receipt-container .meta { font-size: 8.5pt; color: #111; }
    .print-receipt-container .dashed { border-top: 1px dashed #000; margin: 2.5mm 0; padding-top: 2mm; }
    .print-receipt-container table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    .print-receipt-container td { padding: 0.8mm 0; vertical-align: top; }
    .print-receipt-container td.r { text-align: right; white-space: nowrap; }
    .print-receipt-container .section-title { font-weight: bold; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 1mm; }
    .print-receipt-container .tot { font-size: 10.5pt; font-weight: bold; }
    .print-receipt-container .signatures { display: flex; justify-content: space-between; text-align: center; margin-top: 4mm; padding-top: 2mm; }
    .print-receipt-container .sig-box { width: 45%; }
    .print-receipt-container .sig-line { border-bottom: 1px solid #000; margin-top: 11mm; }
}
</style>
@endsection
