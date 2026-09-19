@extends('kasir.app')

@section('title', 'Riwayat Transaksi')

@section('content')
<div x-data="ordersPage()" class="w-full p-4 sm:p-6 space-y-6">

    <!-- 1. HEADER HALAMAN -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-[#E4DCCC]">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                    Riwayat Transaksi
                </h1>
                <span class="px-2.5 py-0.5 rounded-full bg-[#D9973E]/15 border border-[#D9973E]/30 text-[#B5762A] font-mono text-xs font-bold">
                    {{ $orders->total() }} Pesanan
                </span>
            </div>
            <p class="font-sans text-xs text-[#8A7B66] mt-1">
                Pantau seluruh catatan transaksi kasir, rincian hidangan, kode promo, dan status persiapan dapur.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden sm:flex items-center gap-2 font-mono text-xs text-[#8A7B66] bg-white border border-[#E4DCCC] rounded-xl px-3 py-2 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-[#5F7F42] animate-pulse"></span>
                <span>{{ $date->translatedFormat('l, d M Y') }}</span>
            </div>
            <a href="{{ route('kasir.terminal') }}"
               class="px-4 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all shadow-md active:scale-98 flex items-center gap-2">
                <span>+ Buka Terminal POS ›</span>
            </a>
        </div>
    </div>

    <!-- 2. KARTU STATISTIK RINGKASAN HARI TERPILIH -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        <!-- Total Pesanan -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/60 flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#8A7B66]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">TOTAL PESANAN</span>
                <span class="w-8 h-8 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-sm">📋</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-extrabold text-[#1F1812] tracking-tight">
                    {{ number_format($stats['total_orders'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono font-medium">transaksi</span>
            </div>
            <div class="mt-2 text-[11px] text-[#8A7B66] font-mono flex items-center gap-1.5 pt-2 border-t border-[#F2EDE4]">
                <span class="h-2 w-2 rounded-full bg-[#5F7F42]"></span>
                <span class="font-semibold text-[#1F1812]">{{ $stats['paid_orders'] }} Berhasil</span>
                @if ($stats['void_count'] > 0)
                    <span class="text-[#C4553D] font-semibold">• {{ $stats['void_count'] }} Void</span>
                @endif
            </div>
        </div>

        <!-- Omzet Bersih (Highlight Card) -->
        <div class="bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] rounded-2xl p-4 sm:p-5 shadow-md relative overflow-hidden flex flex-col justify-between">
            <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-[#D9973E]/15 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center justify-between text-[#A89A85]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold text-[#D9973E]">OMZET BERSIH (PAID)</span>
                <span class="w-8 h-8 rounded-xl bg-[#2A2016] border border-[#D9973E]/30 flex items-center justify-center text-sm text-[#D9973E]">💰</span>
            </div>
            <div class="mt-3 font-mono text-2xl sm:text-3xl font-extrabold text-[#D9973E] truncate tracking-tight">
                Rp {{ number_format($stats['net_omzet'], 0, ',', '.') }}
            </div>
            <div class="mt-2 text-[11px] text-[#A89A85] font-mono pt-2 border-t border-[#3A3026]">
                {{ $date->translatedFormat('d F Y') }}
            </div>
        </div>

        <!-- Rata-rata Basket (Average Ticket) -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/60 flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#8A7B66]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">RATA-RATA BASKET</span>
                <span class="w-8 h-8 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-sm">☕</span>
            </div>
            <div class="mt-3 font-mono text-2xl sm:text-3xl font-extrabold text-[#1F1812] truncate tracking-tight">
                Rp {{ number_format($stats['avg_basket'], 0, ',', '.') }}
            </div>
            <div class="mt-2 text-[11px] text-[#8A7B66] font-mono pt-2 border-t border-[#F2EDE4]">
                Nilai rata-rata per struk sukses
            </div>
        </div>

        <!-- Transaksi Void -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/60 flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#8A7B66]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">DIBATALKAN (VOID)</span>
                <span class="w-8 h-8 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-sm text-[#C4553D]">🚫</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-extrabold tracking-tight {{ $stats['void_count'] > 0 ? 'text-[#C4553D]' : 'text-[#8A7B66]' }}">
                    {{ number_format($stats['void_count'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono font-medium">transaksi</span>
            </div>
            <div class="mt-2 text-[11px] text-[#8A7B66] font-mono pt-2 border-t border-[#F2EDE4]">
                {{ $stats['void_count'] > 0 ? 'Dibatalkan melalui otorisasi kasir' : 'Nihil pembatalan hari ini' }}
            </div>
        </div>
    </div>

    <!-- 3. TOOLBAR FILTER & PENCARIAN TERPADU -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
        <form method="GET" action="{{ route('kasir.orders.index') }}" class="space-y-4">
            <!-- Baris 1: Date Picker & Kolom Search -->
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
                <!-- Pilihan Tanggal & Shortcut Cepat -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs uppercase tracking-wider text-[#7A6A58] font-bold shrink-0">
                        Tanggal:
                    </span>
                    <input type="date" name="date" value="{{ $date->format('Y-m-d') }}"
                           class="bg-[#FAF7F2] border border-[#D5CCC0] px-3.5 py-2 text-xs text-[#1F1812] rounded-xl focus:outline-none focus:border-[#D9973E] font-mono font-semibold transition">

                    <!-- Shortcut Tanggal -->
                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('kasir.orders.index', ['date' => now()->format('Y-m-d'), 'status' => $status, 'order_type' => $orderType]) }}"
                           class="px-3 py-1.5 rounded-xl border text-xs font-mono transition {{ $date->isToday() ? 'bg-[#1F1812] text-white border-[#1F1812] font-bold shadow-xs' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#5C4D3C] hover:border-[#1F1812]' }}">
                            Hari Ini
                        </a>
                        <a href="{{ route('kasir.orders.index', ['date' => now()->subDay()->format('Y-m-d'), 'status' => $status, 'order_type' => $orderType]) }}"
                           class="px-3 py-1.5 rounded-xl border text-xs font-mono transition {{ $date->isYesterday() ? 'bg-[#1F1812] text-white border-[#1F1812] font-bold shadow-xs' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#5C4D3C] hover:border-[#1F1812]' }}">
                            Kemarin
                        </a>
                    </div>
                </div>

                <!-- Input Pencarian -->
                <div class="flex-1 relative">
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Cari kode transaksi (KKI-...), nama pelanggan, meja..."
                           class="w-full pl-9 pr-3.5 py-2 bg-[#FAF7F2] border border-[#D5CCC0] rounded-xl text-xs text-[#1F1812] placeholder-[#8A7B66] focus:outline-none focus:border-[#D9973E] font-mono transition">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#8A7B66] text-xs">
                        🔍
                    </span>
                </div>

                <!-- Tombol Submit Filter -->
                <button type="submit"
                        class="px-5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-xs cursor-pointer shrink-0 active:scale-98">
                    Terapkan
                </button>
            </div>

            <!-- Baris 2: Segmented Pills Filter (Status & Tipe Order) -->
            <div class="pt-3 border-t border-[#EAE2D5] flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Status Filter -->
                    <div class="flex items-center gap-1.5 font-mono">
                        <span class="text-[#7A6A58] text-[10px] uppercase tracking-wider font-bold">Status:</span>
                        <div class="inline-flex p-1 bg-[#FAF7F2] border border-[#D5CCC0] rounded-xl gap-1">
                            <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => 'all', 'order_type' => $orderType]) }}"
                               class="px-2.5 py-1 rounded-lg text-xs font-mono transition {{ $status === 'all' ? 'bg-[#1F1812] text-white font-bold shadow-xs' : 'text-[#5C4D3C] hover:text-[#1F1812]' }}">
                                Semua
                            </a>
                            <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => 'paid', 'order_type' => $orderType]) }}"
                               class="px-2.5 py-1 rounded-lg text-xs font-mono transition {{ $status === 'paid' ? 'bg-[#5F7F42] text-white font-bold shadow-xs' : 'text-[#5F7F42] hover:bg-[#5F7F42]/10' }}">
                                ✓ Paid
                            </a>
                            <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => 'voided', 'order_type' => $orderType]) }}"
                               class="px-2.5 py-1 rounded-lg text-xs font-mono transition {{ $status === 'voided' ? 'bg-[#C4553D] text-white font-bold shadow-xs' : 'text-[#C4553D] hover:bg-[#C4553D]/10' }}">
                                ✕ Void
                            </a>
                        </div>
                    </div>

                    <span class="text-[#D5CCC0] hidden sm:inline">|</span>

                    <!-- Tipe Order Filter -->
                    <div class="flex items-center gap-1.5 font-mono">
                        <span class="text-[#7A6A58] text-[10px] uppercase tracking-wider font-bold">Tipe:</span>
                        <div class="inline-flex p-1 bg-[#FAF7F2] border border-[#D5CCC0] rounded-xl gap-1">
                            <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => $status, 'order_type' => 'all']) }}"
                               class="px-2.5 py-1 rounded-lg text-xs font-mono transition {{ $orderType === 'all' ? 'bg-[#1F1812] text-white font-bold shadow-xs' : 'text-[#5C4D3C] hover:text-[#1F1812]' }}">
                                Semua
                            </a>
                            <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => $status, 'order_type' => 'dine_in']) }}"
                               class="px-2.5 py-1 rounded-lg text-xs font-mono transition flex items-center gap-1 {{ $orderType === 'dine_in' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-xs' : 'text-[#5C4D3C] hover:text-[#1F1812]' }}">
                                <span>🍽️</span>
                                <span>Dine In</span>
                            </a>
                            <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => $status, 'order_type' => 'take_away']) }}"
                               class="px-2.5 py-1 rounded-lg text-xs font-mono transition flex items-center gap-1 {{ $orderType === 'take_away' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-xs' : 'text-[#5C4D3C] hover:text-[#1F1812]' }}">
                                <span>🛍️</span>
                                <span>Take Away</span>
                            </a>
                        </div>
                    </div>
                </div>

                @if ($search || $status !== 'all' || $orderType !== 'all')
                    <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d')]) }}"
                       class="text-[#C4553D] font-mono hover:underline text-xs flex items-center gap-1 cursor-pointer">
                        <span>✕ Reset Filter</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- 4. TABEL RIWAYAT TRANSAKSI -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="bg-[#FAF7F2] border-b border-[#E4DCCC] font-mono text-[10.5px] uppercase tracking-[0.18em] text-[#7A6A58] select-none">
                        <th class="py-3.5 px-4 sm:px-5">Waktu</th>
                        <th class="py-3.5 px-4 sm:px-5">Transaksi & Pelanggan</th>
                        <th class="py-3.5 px-4 sm:px-5">Rincian Menu</th>
                        <th class="py-3.5 px-4 sm:px-5">Tipe & Diskon</th>
                        <th class="py-3.5 px-4 sm:px-5 text-right">Total Akhir</th>
                        <th class="py-3.5 px-4 sm:px-5 text-center">Status</th>
                        <th class="py-3.5 px-4 sm:px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EAE2D5]">
                    @forelse ($orders as $o)
                        @php
                            $orderPayload = [
                                'id' => $o->id,
                                'code' => $o->code,
                                'customer_name' => $o->customer_name ?: 'Pelanggan Walk-In',
                                'order_type' => $o->order_type,
                                'payment_method' => $o->payment_method,
                                'created_at_fmt' => $o->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i'),
                                'created_at_time' => $o->created_at->timezone('Asia/Jakarta')->format('H:i'),
                                'cashier_name' => $o->cashier?->name ?? 'Kasir',
                                'subtotal' => $o->subtotal,
                                'discount' => $o->discount,
                                'promo_code' => $o->promo_code,
                                'total' => $o->total,
                                'paid_amount' => $o->paid_amount,
                                'change_amount' => $o->change_amount,
                                'status' => $o->status,
                                'prep_status' => $o->prep_status,
                                'music_code' => $o->music_code,
                                'note' => $o->note,
                                'receipt_url' => route('kasir.receipt', $o),
                                'items' => $o->items->map(fn($it) => [
                                    'name' => $it->menu_name,
                                    'qty' => $it->qty,
                                    'price' => $it->price,
                                    'line_total' => $it->line_total,
                                ])->values()->all(),
                            ];
                        @endphp
                        <tr class="hover:bg-[#FAF7F2]/90 transition-colors group cursor-pointer {{ $o->status === 'voided' ? 'opacity-65 bg-red-50/25' : '' }}"
                            @click="openDetail(@js($orderPayload))">
                            
                            <!-- Waktu & Selisih -->
                            <td class="py-4 px-4 sm:px-5 whitespace-nowrap font-mono text-xs">
                                <div class="font-bold text-[#1F1812] text-sm group-hover:text-[#B5762A] transition-colors">
                                    {{ $o->created_at->timezone('Asia/Jakarta')->format('H:i') }}
                                </div>
                                <div class="text-[10px] text-[#8A7B66] mt-0.5">
                                    {{ $o->created_at->diffForHumans() }}
                                </div>
                            </td>

                            <!-- Kode Transaksi & Pelanggan -->
                            <td class="py-4 px-4 sm:px-5 min-w-[180px]">
                                <div class="font-mono text-xs font-bold text-[#1F1812] group-hover:text-[#D9973E] transition-colors flex items-center gap-1.5">
                                    <span>{{ $o->code }}</span>
                                </div>
                                <div class="text-xs text-[#2A211A] font-medium mt-0.5 truncate max-w-[200px]">
                                    {{ $o->customer_name ?: 'Pelanggan Walk-In' }}
                                </div>
                                <div class="text-[10.5px] font-mono text-[#8A7B66] mt-0.5">
                                    Kasir: {{ $o->cashier?->name ?? '-' }}
                                </div>
                            </td>

                            <!-- Rincian Menu Dipesan (Chips Cantik) -->
                            <td class="py-4 px-4 sm:px-5 max-w-xs">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($o->items->take(2) as $item)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#FAF7F2] border border-[#E5DDD0] text-xs text-[#1F1812] rounded-lg shadow-2xs">
                                            <b class="text-[#D9973E] font-mono font-bold">{{ $item->qty }}x</b>
                                            <span class="truncate max-w-[130px]" :title="'{{ $item->menu_name }}'">{{ $item->menu_name }}</span>
                                        </span>
                                    @endforeach
                                    @if ($o->items->count() > 2)
                                        <span class="inline-flex items-center px-2 py-1 bg-[#EAE2D5] text-[10px] font-mono font-bold text-[#5C4D3C] rounded-lg">
                                            +{{ $o->items->count() - 2 }} menu lagi
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Tipe & Pembayaran + Info Promo -->
                            <td class="py-4 px-4 sm:px-5 whitespace-nowrap text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-medium text-[#1F1812]">
                                        {{ $o->order_type === 'dine_in' ? '🍽️ Dine In' : '🛍️ Take Away' }}
                                    </span>
                                    <span class="text-[#D5CCC0]">•</span>
                                    <span class="font-mono text-[11px] uppercase tracking-wider font-semibold text-[#8A7B66]">
                                        {{ $o->payment_method }}
                                    </span>
                                </div>
                                @if ($o->discount > 0)
                                    <div class="mt-1 flex items-center gap-1">
                                        <span class="inline-flex items-center gap-1 font-mono text-[10px] px-1.5 py-0.2 rounded bg-[#5F7F42]/15 text-[#5F7F42] font-semibold border border-[#5F7F42]/30">
                                            <span>🏷️</span>
                                            <span>{{ $o->promo_code ? $o->promo_code : 'Diskon' }}</span>
                                        </span>
                                    </div>
                                @endif
                            </td>

                            <!-- Total Nominal -->
                            <td class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">
                                <div class="font-mono text-sm sm:text-base font-bold {{ $o->status === 'voided' ? 'line-through text-[#C4553D]' : 'text-[#1F1812]' }}">
                                    Rp {{ number_format($o->total, 0, ',', '.') }}
                                </div>
                                @if ($o->discount > 0)
                                    <div class="text-[10.5px] font-mono text-[#5F7F42] font-semibold mt-0.5">
                                        Hemat {{ number_format($o->discount, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>

                            <!-- Status Transaksi & Dapur -->
                            <td class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">
                                <div class="inline-flex flex-col items-center gap-1">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 font-mono text-[10px] uppercase tracking-wider rounded-full font-bold {{ $o->status === 'paid' ? 'bg-[#5F7F42]/15 text-[#5F7F42] border border-[#5F7F42]/30' : 'bg-red-100 text-[#C4553D] border border-red-300' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $o->status === 'paid' ? 'bg-[#5F7F42]' : 'bg-[#C4553D]' }}"></span>
                                        <span>{{ $o->status === 'paid' ? 'Paid' : 'VOID' }}</span>
                                    </span>

                                    @if ($o->status === 'paid' && $o->prep_status)
                                        <span class="font-mono text-[9.5px] uppercase tracking-wider mt-0.5">
                                            @if ($o->prep_status === 'ready')
                                                <span class="text-[#5F7F42] font-bold">● Siap Diambil</span>
                                            @elseif ($o->prep_status === 'preparing')
                                                <span class="text-[#D9973E] font-bold">● Dapur/Barista</span>
                                            @elseif ($o->prep_status === 'completed')
                                                <span class="text-gray-500 font-semibold">● Selesai</span>
                                            @else
                                                <span class="text-blue-500 font-semibold">● Antrean Dapur</span>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Aksi Cepat -->
                            <td class="py-4 px-4 sm:px-5 text-right whitespace-nowrap" @click.stop>
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Tombol Rincian Modal -->
                                    <button type="button"
                                            @click="openDetail(@js($orderPayload))"
                                            title="Lihat Rincian Lengkap"
                                            class="px-2.5 py-1.5 bg-[#FAF7F2] hover:bg-[#1F1812] text-[#1F1812] hover:text-[#F7F3EC] border border-[#D5CCC0] hover:border-[#1F1812] font-mono text-[11px] uppercase tracking-wider font-semibold transition rounded-lg cursor-pointer">
                                        Rincian
                                    </button>

                                    <!-- Tombol Struk -->
                                    <a href="{{ route('kasir.receipt', $o) }}"
                                       target="_blank"
                                       title="Buka Struk Thermal"
                                       class="px-2.5 py-1.5 bg-[#FAF7F2] hover:bg-[#D9973E] text-[#1F1812] border border-[#D5CCC0] hover:border-[#D9973E] font-mono text-[11px] uppercase tracking-wider font-semibold transition rounded-lg flex items-center gap-1 cursor-pointer">
                                        <span>🖨️</span>
                                        <span>Struk</span>
                                    </a>

                                    <!-- Tombol Void -->
                                    @if ($o->status === 'paid')
                                        <form method="POST" action="{{ route('kasir.orders.void', $o) }}" class="inline"
                                              data-confirm="Apakah Anda yakin ingin membatalkan (VOID) transaksi {{ $o->code }} senilai Rp {{ number_format($o->total, 0, ',', '.') }}?"
                                              data-confirm-title="Konfirmasi Pembatalan (Void)"
                                              data-confirm-type="danger"
                                              data-confirm-btn="Ya, Void Transaksi">
                                            @csrf
                                            <button type="submit"
                                                    title="Batalkan Transaksi"
                                                    class="px-2.5 py-1.5 text-[#C4553D] hover:bg-red-50 border border-transparent hover:border-red-200 font-mono text-[11px] uppercase tracking-wider font-semibold transition rounded-lg cursor-pointer">
                                                Void
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 px-4 text-center text-[#8A7B66]">
                                <div class="max-w-sm mx-auto space-y-3">
                                    <div class="w-16 h-16 rounded-2xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-3xl mx-auto shadow-inner">
                                        🧾
                                    </div>
                                    <div class="font-serif font-bold text-lg text-[#1F1812]">
                                        Tidak Ada Transaksi Ditemukan
                                    </div>
                                    <p class="text-xs text-[#8A7B66] leading-relaxed">
                                        Tidak ditemukan catatan transaksi untuk tanggal dan filter yang Anda pilih. Coba sesuaikan kata kunci pencarian atau rentang tanggal.
                                    </p>
                                    @if ($search || $status !== 'all' || $orderType !== 'all')
                                        <div class="pt-2">
                                            <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d')]) }}"
                                               class="inline-block px-4 py-2 rounded-xl bg-[#1F1812] text-white font-mono text-xs font-bold uppercase tracking-wider transition hover:bg-[#D9973E] hover:text-[#1F1812]">
                                                Reset Semua Filter
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if ($orders->hasPages())
            <div class="p-4 border-t border-[#E4DCCC] bg-[#FAF7F2]">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <!-- 5. POPUP MODAL RINCIAN TRANSAKSI INSTAN (QUICK DETAIL MODAL) -->
    <div x-show="showDetailModal"
         x-cloak
         @keydown.escape.window="if(showDetailModal) closeDetail()"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs transition-all duration-200">
        <div class="bg-[#1F1812] border border-[#3A3026] text-[#F7F3EC] w-full max-w-lg shadow-2xl rounded-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-150"
             @click.away="closeDetail()">
            
            <!-- Header Modal Detail -->
            <div class="p-4 bg-[#261E17] border-b border-[#3A3026] flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-xl bg-[#D9973E]/15 border border-[#D9973E]/30 flex items-center justify-center text-lg text-[#D9973E] shrink-0">
                        📋
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="font-mono text-sm font-bold text-[#F7F3EC]" x-text="selectedOrder?.code"></h3>
                            <span class="px-2 py-0.5 rounded-full font-mono text-[9px] uppercase font-bold"
                                  :class="selectedOrder?.status === 'paid' ? 'bg-[#5F7F42]/20 text-[#5F7F42] border border-[#5F7F42]/40' : 'bg-[#C4553D]/20 text-[#C4553D] border border-[#C4553D]/40'"
                                  x-text="selectedOrder?.status?.toUpperCase()"></span>
                        </div>
                        <div class="text-[11px] text-[#8A7B66] font-mono mt-0.5" x-text="selectedOrder?.created_at_fmt"></div>
                    </div>
                </div>
                <button type="button"
                        @click="closeDetail()"
                        class="w-8 h-8 rounded-full bg-[#1F1812] hover:bg-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC] flex items-center justify-center transition cursor-pointer text-sm">
                    ✕
                </button>
            </div>

            <!-- Body Modal Detail -->
            <div class="p-5 overflow-y-auto max-h-[65vh] space-y-4">
                <!-- Meta Transaksi Grid -->
                <div class="grid grid-cols-2 gap-2 bg-[#140E0A] border border-[#3A3026] rounded-xl p-3 text-xs">
                    <div>
                        <span class="text-[#8A7B66] block text-[10px] font-mono uppercase tracking-wider">Pelanggan</span>
                        <span class="font-semibold text-[#F7F3EC] mt-0.5 block truncate" x-text="selectedOrder?.customer_name"></span>
                    </div>
                    <div>
                        <span class="text-[#8A7B66] block text-[10px] font-mono uppercase tracking-wider">Tipe Pesanan</span>
                        <span class="font-semibold text-[#F7F3EC] mt-0.5 block" x-text="selectedOrder?.order_type === 'dine_in' ? '🍽️ Dine In' : '🛍️ Take Away'"></span>
                    </div>
                    <div class="pt-2 border-t border-[#3A3026]/60">
                        <span class="text-[#8A7B66] block text-[10px] font-mono uppercase tracking-wider">Metode Bayar</span>
                        <span class="font-mono font-bold text-[#D9973E] mt-0.5 block uppercase" x-text="selectedOrder?.payment_method"></span>
                    </div>
                    <div class="pt-2 border-t border-[#3A3026]/60">
                        <span class="text-[#8A7B66] block text-[10px] font-mono uppercase tracking-wider">Kasir Bertugas</span>
                        <span class="text-[#F7F3EC] mt-0.5 block truncate" x-text="selectedOrder?.cashier_name"></span>
                    </div>
                </div>

                <!-- Kode Musik Request (Jika Ada) -->
                <template x-if="selectedOrder?.music_code">
                    <div class="bg-[#261E17] border border-[#D9973E]/30 rounded-xl p-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-xl">🎵</span>
                            <div class="min-w-0">
                                <div class="font-mono text-[9px] uppercase tracking-wider text-[#D9973E] font-bold">Kode Request Musik</div>
                                <div class="text-[11px] text-[#A89A85]">Dapat digunakan pelanggan untuk memilih lagu</div>
                            </div>
                        </div>
                        <div class="font-mono text-sm font-black px-2.5 py-1 bg-[#140E0A] border border-[#D9973E] text-[#D9973E] tracking-wider rounded-lg" x-text="selectedOrder?.music_code"></div>
                    </div>
                </template>

                <!-- Daftar Item yang Dipesan -->
                <div>
                    <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold mb-2 block">
                        Daftar Menu Dipesan
                    </label>
                    <div class="border border-[#3A3026] rounded-xl overflow-hidden divide-y divide-[#3A3026]">
                        <template x-for="(item, idx) in (selectedOrder?.items || [])" :key="idx">
                            <div class="p-3 bg-[#140E0A] flex items-center justify-between gap-3 text-xs">
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-[#F7F3EC]" x-text="item.name"></div>
                                    <div class="text-[11px] font-mono text-[#8A7B66] mt-0.5" x-text="item.qty + ' x ' + fmt(item.price)"></div>
                                </div>
                                <div class="font-mono text-xs font-bold text-[#F7F3EC] shrink-0" x-text="fmt(item.line_total)"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Rincian Biaya & Diskon -->
                <div class="bg-[#140E0A] border border-[#3A3026] rounded-xl p-3.5 space-y-2 text-xs font-mono">
                    <div class="flex items-center justify-between text-[#A89A85]">
                        <span>Subtotal</span>
                        <span class="text-[#F7F3EC]" x-text="fmt(selectedOrder?.subtotal || 0)"></span>
                    </div>
                    <template x-if="selectedOrder?.discount > 0">
                        <div class="flex items-center justify-between text-[#5F7F42]">
                            <span class="flex items-center gap-1.5">
                                <span>Diskon</span>
                                <template x-if="selectedOrder?.promo_code">
                                    <span class="px-1.5 py-0.2 rounded bg-[#5F7F42]/20 font-bold" x-text="'(' + selectedOrder?.promo_code + ')'"></span>
                                </template>
                            </span>
                            <span class="font-bold" x-text="'-' + fmt(selectedOrder?.discount || 0)"></span>
                        </div>
                    </template>
                    <div class="pt-2 border-t border-[#3A3026] flex items-center justify-between text-sm font-bold">
                        <span class="text-[#D9973E]">TOTAL AKHIR</span>
                        <span class="text-[#D9973E] text-base" x-text="fmt(selectedOrder?.total || 0)"></span>
                    </div>
                    <div class="flex items-center justify-between text-[#8A7B66] text-[11px] pt-1">
                        <span>Diterima (<span x-text="selectedOrder?.payment_method?.toUpperCase()"></span>)</span>
                        <span class="text-[#F7F3EC]" x-text="fmt(selectedOrder?.paid_amount || 0)"></span>
                    </div>
                    <template x-if="selectedOrder?.payment_method === 'cash'">
                        <div class="flex items-center justify-between text-[#8A7B66] text-[11px]">
                            <span>Kembalian</span>
                            <span class="text-[#5F7F42] font-bold" x-text="fmt(selectedOrder?.change_amount || 0)"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Footer Modal Detail -->
            <div class="p-4 bg-[#261E17] border-t border-[#3A3026] flex items-center justify-between gap-3">
                <button type="button"
                        @click="closeDetail()"
                        class="px-4 py-2.5 rounded-xl border border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC] hover:bg-[#1F1812] font-mono text-xs uppercase tracking-wider transition cursor-pointer">
                    Tutup (Esc)
                </button>
                <a :href="selectedOrder?.receipt_url"
                   target="_blank"
                   class="bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] hover:text-white py-2.5 px-5 rounded-xl font-mono text-xs uppercase tracking-wider font-bold transition-all shadow-md active:scale-98 flex items-center gap-2 cursor-pointer">
                    <span>🖨️</span>
                    <span>Cetak Struk Thermal</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function ordersPage() {
    return {
        selectedOrder: null,
        showDetailModal: false,

        openDetail(order) {
            this.selectedOrder = order;
            this.showDetailModal = true;
        },

        closeDetail() {
            this.showDetailModal = false;
            this.selectedOrder = null;
        },

        fmt(v) {
            return 'Rp ' + (v || 0).toLocaleString('id-ID');
        }
    };
}
</script>
@endsection
