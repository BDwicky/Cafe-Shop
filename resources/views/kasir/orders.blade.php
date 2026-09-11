@extends('kasir.app')

@section('title', 'Riwayat Transaksi')

@section('content')
<div class="space-y-6">

    <!-- 1. HEADER HALAMAN -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-[#E4DCCC]">
        <div>
            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                Riwayat Transaksi
            </h1>
            <p class="font-mono text-xs text-[#8A7B66] mt-0.5">
                Pantau seluruh transaksi kasir, rincian hidangan, dan status pembayaran harian.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('kasir.terminal') }}"
               class="px-4 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-[11px] uppercase tracking-wider font-bold transition shadow-xs flex items-center gap-2">
                <span>+ Buka Terminal POS ›</span>
            </a>
        </div>
    </div>

    <!-- 2. KARTU STATISTIK RINGKASAN HARI TERPILIH -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- Total Transaksi -->
        <div class="bg-white border border-[#E4DCCC] p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/50">
            <div class="flex items-center justify-between text-[#8A7B66]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Total Pesanan</span>
                <span class="text-base">📋</span>
            </div>
            <div class="mt-2.5 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-bold text-[#1F1812]">
                    {{ number_format($stats['total_orders'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono">transaksi</span>
            </div>
            <div class="mt-1.5 text-[11px] text-[#8A7B66] font-mono flex items-center gap-1.5">
                <span class="h-1.5 w-1.5 rounded-full bg-[#5F7F42]"></span>
                <span>{{ $stats['paid_orders'] }} Berhasil</span>
                @if ($stats['void_count'] > 0)
                    <span class="text-[#C4553D]">&bull; {{ $stats['void_count'] }} Void</span>
                @endif
            </div>
        </div>

        <!-- Omzet Bersih -->
        <div class="bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] p-4 sm:p-5 shadow-sm relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-[#D9973E]/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center justify-between text-[#A89A85]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Omzet Bersih (Paid)</span>
                <span class="text-base text-[#D9973E]">💰</span>
            </div>
            <div class="mt-2.5 font-mono text-2xl sm:text-3xl font-bold text-[#D9973E] truncate">
                Rp {{ number_format($stats['net_omzet'], 0, ',', '.') }}
            </div>
            <div class="mt-1.5 text-[11px] text-[#A89A85] font-mono">
                Tanggal: {{ $date->translatedFormat('d M Y') }}
            </div>
        </div>

        <!-- Rata-rata per Transaksi -->
        <div class="bg-white border border-[#E4DCCC] p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/50">
            <div class="flex items-center justify-between text-[#8A7B66]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Rata-rata Basket</span>
                <span class="text-base">☕</span>
            </div>
            <div class="mt-2.5 font-mono text-2xl sm:text-3xl font-bold text-[#1F1812] truncate">
                Rp {{ number_format($stats['avg_basket'], 0, ',', '.') }}
            </div>
            <div class="mt-1.5 text-[11px] text-[#8A7B66] font-mono">
                Nilai per struk sukses
            </div>
        </div>

        <!-- Transaksi Void -->
        <div class="bg-white border border-[#E4DCCC] p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/50">
            <div class="flex items-center justify-between text-[#8A7B66]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Pesanan Dibatalkan</span>
                <span class="text-base text-[#C4553D]">🚫</span>
            </div>
            <div class="mt-2.5 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-bold {{ $stats['void_count'] > 0 ? 'text-[#C4553D]' : 'text-[#8A7B66]' }}">
                    {{ number_format($stats['void_count'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono">void</span>
            </div>
            <div class="mt-1.5 text-[11px] text-[#8A7B66] font-mono">
                {{ $stats['void_count'] > 0 ? 'Dibatalkan oleh kasir' : 'Tidak ada pembatalan' }}
            </div>
        </div>
    </div>

    <!-- 3. BAR FILTER & PENCARIAN -->
    <div class="bg-white border border-[#E4DCCC] p-4 sm:p-5 shadow-xs space-y-3.5">
        <form method="GET" action="{{ route('kasir.orders.index') }}" class="space-y-3">
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3 justify-between">
                <!-- Pilihan Tanggal & Shortcut Cepat -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-semibold shrink-0">
                        Tanggal:
                    </span>
                    <input type="date" name="date" value="{{ $date->format('Y-m-d') }}"
                           class="bg-[#FAF7F2] border border-[#D5CCC0] px-3 py-2 text-sm text-[#1F1812] focus:outline-none focus:border-[#D9973E] font-mono">

                    <!-- Shortcut Tanggal -->
                    <div class="flex items-center gap-1">
                        <a href="{{ route('kasir.orders.index', ['date' => now()->format('Y-m-d'), 'status' => $status, 'order_type' => $orderType]) }}"
                           class="px-2.5 py-1.5 border text-xs font-mono transition {{ $date->isToday() ? 'bg-[#1F1812] text-white border-[#1F1812] font-bold' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#5C4D3C] hover:border-[#1F1812]' }}">
                            Hari Ini
                        </a>
                        <a href="{{ route('kasir.orders.index', ['date' => now()->subDay()->format('Y-m-d'), 'status' => $status, 'order_type' => $orderType]) }}"
                           class="px-2.5 py-1.5 border text-xs font-mono transition {{ $date->isYesterday() ? 'bg-[#1F1812] text-white border-[#1F1812] font-bold' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#5C4D3C] hover:border-[#1F1812]' }}">
                            Kemarin
                        </a>
                    </div>
                </div>

                <!-- Kolom Search -->
                <div class="flex-1 max-w-md relative">
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Cari kode transaksi (KKI-...) atau nama..."
                           class="w-full pl-9 pr-3.5 py-2 bg-[#FAF7F2] border border-[#D5CCC0] text-sm text-[#1F1812] focus:outline-none focus:border-[#D9973E] font-mono">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#8A7B66] text-xs">
                        🔍
                    </span>
                </div>

                <!-- Tombol Terapkan -->
                <button type="submit"
                        class="px-4 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-[11px] uppercase tracking-wider font-bold transition shadow-xs cursor-pointer">
                    Terapkan Filter
                </button>
            </div>

            <!-- Filter Status & Tipe Order -->
            <div class="pt-2 border-t border-[#EAE2D5] flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Status Filter -->
                    <div class="flex items-center gap-1.5 font-mono">
                        <span class="text-[#7A6A58] text-[10px] uppercase tracking-wider">Status:</span>
                        <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => 'all', 'order_type' => $orderType]) }}"
                           class="px-2 py-0.5 rounded border transition {{ $status === 'all' ? 'bg-[#1F1812] text-white border-[#1F1812] font-bold' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#5C4D3C]' }}">
                            Semua
                        </a>
                        <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => 'paid', 'order_type' => $orderType]) }}"
                           class="px-2 py-0.5 rounded border transition {{ $status === 'paid' ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#5F7F42]' }}">
                            ✓ Paid
                        </a>
                        <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => 'voided', 'order_type' => $orderType]) }}"
                           class="px-2 py-0.5 rounded border transition {{ $status === 'voided' ? 'bg-[#C4553D] text-white border-[#C4553D] font-bold' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#C4553D]' }}">
                            ✕ Void
                        </a>
                    </div>

                    <span class="text-[#D5CCC0]">|</span>

                    <!-- Tipe Order Filter -->
                    <div class="flex items-center gap-1.5 font-mono">
                        <span class="text-[#7A6A58] text-[10px] uppercase tracking-wider">Tipe:</span>
                        <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => $status, 'order_type' => 'all']) }}"
                           class="px-2 py-0.5 rounded border transition {{ $orderType === 'all' ? 'bg-[#1F1812] text-white border-[#1F1812] font-bold' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#5C4D3C]' }}">
                            Semua
                        </a>
                        <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => $status, 'order_type' => 'dine_in']) }}"
                           class="px-2 py-0.5 rounded border transition {{ $orderType === 'dine_in' ? 'bg-[#D9973E] text-[#1F1812] border-[#D9973E] font-bold' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#5C4D3C]' }}">
                            🍽️ Dine In
                        </a>
                        <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d'), 'search' => $search, 'status' => $status, 'order_type' => 'take_away']) }}"
                           class="px-2 py-0.5 rounded border transition {{ $orderType === 'take_away' ? 'bg-[#D9973E] text-[#1F1812] border-[#D9973E] font-bold' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#5C4D3C]' }}">
                            🛍️ Take Away
                        </a>
                    </div>
                </div>

                @if ($search || $status !== 'all' || $orderType !== 'all')
                    <a href="{{ route('kasir.orders.index', ['date' => $date->format('Y-m-d')]) }}"
                       class="text-[#C4553D] font-mono hover:underline text-xs flex items-center gap-1">
                        <span>✕ Reset Filter</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- 4. TABEL RIWAYAT TRANSAKSI -->
    <div class="bg-white border border-[#E4DCCC] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-[#FAF7F2] border-b border-[#E4DCCC] font-mono text-[10px] uppercase tracking-[0.18em] text-[#7A6A58] select-none">
                        <th class="px-4 py-3.5">Waktu</th>
                        <th class="px-4 py-3.5">Kode & Pelanggan</th>
                        <th class="px-4 py-3.5">Rincian Menu</th>
                        <th class="px-4 py-3.5">Tipe & Bayar</th>
                        <th class="px-4 py-3.5 text-right">Total</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EAE2D5]">
                    @forelse ($orders as $o)
                        <tr class="hover:bg-[#FAF7F2]/60 transition-colors {{ $o->status === 'voided' ? 'opacity-60 bg-red-50/20' : '' }}">
                            <!-- Jam & Kasir -->
                            <td class="px-4 py-3.5 whitespace-nowrap font-mono text-xs">
                                <div class="font-bold text-[#1F1812]">
                                    {{ $o->created_at->timezone('Asia/Jakarta')->format('H:i') }}
                                </div>
                                <div class="text-[10px] text-[#8A7B66]">
                                    {{ $o->created_at->timezone('Asia/Jakarta')->format('s') }}s
                                </div>
                            </td>

                            <!-- Kode & Pelanggan -->
                            <td class="px-4 py-3.5">
                                <div class="font-mono text-xs font-bold text-[#1F1812] flex items-center gap-1.5">
                                    <span>{{ $o->code }}</span>
                                </div>
                                <div class="text-xs text-[#5C4D3C] mt-0.5 flex items-center gap-1">
                                    <span class="font-semibold">{{ $o->customer_name ?: 'Pelanggan Walk-In' }}</span>
                                </div>
                            </td>

                            <!-- Rincian Menu Dipesan (Chips Preview) -->
                            <td class="px-4 py-3.5 max-w-xs">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($o->items->take(3) as $item)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-[#FAF7F2] border border-[#E5DDD0] text-[11px] text-[#1F1812] rounded">
                                            <b class="text-[#D9973E] font-mono">{{ $item->qty }}x</b>
                                            <span class="truncate max-w-[120px]">{{ $item->menu_name }}</span>
                                        </span>
                                    @endforeach
                                    @if ($o->items->count() > 3)
                                        <span class="inline-flex items-center px-1.5 py-0.5 bg-[#EAE2D5] text-[10px] font-mono text-[#5C4D3C] rounded">
                                            +{{ $o->items->count() - 3 }} lainnya
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Tipe & Bayar -->
                            <td class="px-4 py-3.5 whitespace-nowrap text-xs">
                                <div class="flex items-center gap-1 font-medium text-[#1F1812]">
                                    <span>{{ $o->order_type === 'dine_in' ? '🍽️ Dine In' : '🛍️ Take Away' }}</span>
                                </div>
                                <div class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] mt-0.5">
                                    {{ $o->payment_method }}
                                </div>
                            </td>

                            <!-- Total -->
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <div class="font-mono text-sm font-bold {{ $o->status === 'voided' ? 'line-through text-[#C4553D]' : 'text-[#1F1812]' }}">
                                    Rp {{ number_format($o->total, 0, ',', '.') }}
                                </div>
                                @if ($o->discount > 0)
                                    <div class="text-[10px] font-mono text-[#5F7F42]">
                                        Hemat Rp {{ number_format($o->discount, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <div class="inline-flex flex-col items-center gap-1">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 font-mono text-[10px] uppercase tracking-wider rounded font-bold {{ $o->status === 'paid' ? 'bg-[#5F7F42]/15 text-[#5F7F42] border border-[#5F7F42]/30' : 'bg-red-100 text-[#C4553D] border border-red-300' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $o->status === 'paid' ? 'bg-[#5F7F42]' : 'bg-[#C4553D]' }}"></span>
                                        <span>{{ $o->status === 'paid' ? 'Paid' : 'VOID' }}</span>
                                    </span>

                                    @if ($o->status === 'paid' && $o->prep_status)
                                        <span class="font-mono text-[9px] uppercase tracking-widest text-[#8A7B66]">
                                            @if ($o->prep_status === 'ready')
                                                <span class="text-[#5F7F42] font-semibold">● Siap</span>
                                            @elseif ($o->prep_status === 'preparing')
                                                <span class="text-[#D9973E] font-semibold">● Dapur</span>
                                            @elseif ($o->prep_status === 'completed')
                                                <span class="text-gray-500 font-semibold">● Selesai</span>
                                            @else
                                                <span class="text-blue-500 font-semibold">● Menunggu</span>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Aksi -->
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2.5">
                                    <!-- Tombol Rincian / Cetak Struk -->
                                    <a href="{{ route('kasir.receipt', $o) }}"
                                       class="px-2.5 py-1 bg-[#FAF7F2] hover:bg-[#1F1812] text-[#1F1812] hover:text-[#F7F3EC] border border-[#D5CCC0] hover:border-[#1F1812] font-mono text-[10px] uppercase tracking-wider font-semibold transition rounded">
                                        Struk ›
                                    </a>

                                    @if ($o->status === 'paid')
                                        <form method="POST" action="{{ route('kasir.orders.void', $o) }}" class="inline"
                                              data-confirm="Apakah Anda yakin ingin membatalkan (VOID) transaksi {{ $o->code }} dengan total Rp {{ number_format($o->total, 0, ',', '.') }}?"
                                              data-confirm-title="Konfirmasi Pembatalan (Void)"
                                              data-confirm-type="danger"
                                              data-confirm-btn="Ya, Void Transaksi">
                                            @csrf
                                            <button type="submit"
                                                    class="px-2.5 py-1 text-[#C4553D] hover:bg-red-50 border border-transparent hover:border-red-200 font-mono text-[10px] uppercase tracking-wider font-semibold transition rounded cursor-pointer">
                                                Void
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-[#8A7B66]">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <div class="text-3xl opacity-40">🧾</div>
                                    <div class="font-serif font-bold text-base text-[#1F1812]">
                                        Tidak Ada Transaksi Ditemukan
                                    </div>
                                    <p class="text-xs text-[#8A7B66] leading-relaxed">
                                        Tidak ada catatan transaksi untuk filter atau tanggal yang Anda pilih. Coba ubah rentang tanggal atau kata kunci pencarian.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($orders->hasPages())
            <div class="p-4 border-t border-[#E4DCCC] bg-[#FAF7F2]">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
