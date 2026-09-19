@extends('kasir.app')

@section('title', 'Kartu Riwayat Mutasi Stok')

@section('content')
<div class="w-full p-4 sm:p-6 space-y-6">

    <!-- 1. HEADER HALAMAN -->
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-[#E4DCCC]">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                    Kartu Riwayat Mutasi Stok
                </h1>
                <span class="px-2.5 py-0.5 rounded-full bg-[#D9973E]/15 border border-[#D9973E]/30 text-[#B5762A] font-mono text-xs font-bold">
                    Audit Trail Stok
                </span>
            </div>
            <p class="font-sans text-xs text-[#8A7B66] mt-1">
                Audit trail lengkap setiap perubahan stok bahan: penjualan kasir, restock, pembatalan void, hingga bahan terbuang (waste).
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('kasir.inventory.index') }}"
               class="px-4 py-2.5 bg-white hover:bg-[#FAF7F2] text-[#1F1812] border border-[#E4DCCC] hover:border-[#D9973E] font-mono text-xs font-bold rounded-xl transition shadow-2xs flex items-center gap-1.5 active:scale-98">
                <span>‹ Master Bahan Baku</span>
            </a>
        </div>
    </header>

    <!-- 2. FILTER & PENCARIAN -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs">
        <form method="GET" action="{{ route('kasir.inventory.history') }}" class="flex flex-wrap items-center gap-3">
            <!-- Filter Bahan Baku -->
            <div class="flex-1 min-w-[200px] sm:max-w-xs">
                <select name="ingredient_id" onchange="this.form.submit()"
                        class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white px-3.5 py-2.5 text-xs font-mono text-[#1F1812] rounded-xl focus:outline-none transition shadow-2xs cursor-pointer">
                    <option value="all">Semua Bahan Baku</option>
                    @foreach ($ingredients as $ing)
                        <option value="{{ $ing->id }}" {{ (string) $ingredientId === (string) $ing->id ? 'selected' : '' }}>
                            {{ $ing->name }} ({{ $ing->unit }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Tipe Mutasi -->
            <div class="w-48">
                <select name="type" onchange="this.form.submit()"
                        class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white px-3.5 py-2.5 text-xs font-mono text-[#1F1812] rounded-xl focus:outline-none transition shadow-2xs cursor-pointer">
                    <option value="all">Semua Jenis Mutasi</option>
                    <option value="sale" {{ $type === 'sale' ? 'selected' : '' }}>Penjualan Order (-)</option>
                    <option value="purchase" {{ $type === 'purchase' ? 'selected' : '' }}>Restock / Pembelian (+)</option>
                    <option value="void_return" {{ $type === 'void_return' ? 'selected' : '' }}>Void Order (+)</option>
                    <option value="waste" {{ $type === 'waste' ? 'selected' : '' }}>Bahan Terbuang (-)</option>
                    <option value="adjustment" {{ $type === 'adjustment' ? 'selected' : '' }}>Stock Opname</option>
                </select>
            </div>

            <!-- Filter Tanggal -->
            <div class="w-44">
                <input type="date" name="date" value="{{ $date }}"
                       onchange="this.form.submit()"
                       class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white px-3.5 py-2.5 text-xs font-mono text-[#1F1812] rounded-xl focus:outline-none transition shadow-2xs">
            </div>

            <button type="submit"
                    class="px-5 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-xs active:scale-95 cursor-pointer">
                Filter
            </button>
            @if ($ingredientId !== 'all' && $ingredientId || $type !== 'all' && $type || $date)
                <a href="{{ route('kasir.inventory.history') }}"
                   class="px-4 py-2.5 bg-[#FAF7F2] hover:bg-white border border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs font-bold rounded-xl transition shadow-2xs">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- 3. TABEL MUTASI STOK -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#2A211A] text-[#F7F3EC] font-mono uppercase text-[10px] tracking-wider">
                        <th class="py-3.5 px-4 font-bold">Waktu</th>
                        <th class="py-3.5 px-3 font-bold">Bahan Baku</th>
                        <th class="py-3.5 px-3 font-bold">Jenis Mutasi</th>
                        <th class="py-3.5 px-3 text-right font-bold">Jumlah Mutasi</th>
                        <th class="py-3.5 px-3 text-right font-bold">Stok Sebelum</th>
                        <th class="py-3.5 px-3 text-right font-bold">Stok Sesudah</th>
                        <th class="py-3.5 px-3 text-right font-bold">Total Nilai HPP</th>
                        <th class="py-3.5 px-4 font-bold">Catatan & Referensi</th>
                        <th class="py-3.5 px-3 font-bold">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4DCCC] font-mono text-[#1F1812]">
                    @forelse ($movements as $m)
                        @php
                            $isPlus = $m->quantity > 0;
                        @endphp
                        <tr class="hover:bg-[#FAF7F2]/80 transition">
                            <td class="py-3.5 px-4 text-[#8A7B66] whitespace-nowrap">
                                <div class="font-medium text-[#1F1812]">{{ $m->created_at->translatedFormat('d M Y') }}</div>
                                <div class="text-[10px] text-[#8A7B66]">{{ $m->created_at->format('H:i:s') }}</div>
                            </td>
                            <td class="py-3.5 px-3 font-sans">
                                <div class="font-bold text-sm text-[#1F1812]">{{ $m->ingredient->name ?? '-' }}</div>
                                <div class="font-mono text-[10px] text-[#8A7B66]">Satuan: <span class="uppercase font-semibold text-[#D9973E]">{{ $m->ingredient->unit ?? '' }}</span></div>
                            </td>
                            <td class="py-3.5 px-3">
                                @if ($m->type === 'purchase')
                                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full border border-emerald-300 shadow-2xs">
                                        Restock Masuk
                                    </span>
                                @elseif ($m->type === 'sale')
                                    <span class="px-2.5 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-bold rounded-full border border-blue-300 shadow-2xs">
                                        Penjualan Kasir
                                    </span>
                                @elseif ($m->type === 'void_return')
                                    <span class="px-2.5 py-0.5 bg-purple-100 text-purple-800 text-[10px] font-bold rounded-full border border-purple-300 shadow-2xs">
                                        Retur Void
                                    </span>
                                @elseif ($m->type === 'waste')
                                    <span class="px-2.5 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold rounded-full border border-amber-300 shadow-2xs">
                                        Terbuang (Waste)
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 bg-gray-100 text-gray-800 text-[10px] font-bold rounded-full border border-gray-300 shadow-2xs">
                                        Stock Opname
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-right">
                                <span class="font-bold text-sm {{ $isPlus ? 'text-[#5F7F42]' : 'text-[#C4553D]' }}">
                                    {{ $isPlus ? '+' : '' }}{{ number_format($m->quantity, 2, ',', '.') }}
                                </span>
                                <span class="text-[10px] text-[#8A7B66] font-medium">{{ $m->ingredient->unit ?? '' }}</span>
                            </td>
                            <td class="py-3.5 px-3 text-right text-[#8A7B66]">
                                {{ number_format($m->stock_before, 2, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-3 text-right font-bold text-[#1F1812]">
                                {{ number_format($m->stock_after, 2, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-3 text-right font-medium text-[#1F1812]">
                                Rp {{ number_format($m->total_cost, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 font-sans text-xs text-[#1F1812] max-w-xs">
                                {{ $m->notes ?? '-' }}
                            </td>
                            <td class="py-3.5 px-3 text-[#8A7B66] text-[11px] whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded bg-[#FAF7F2] border border-[#E4DCCC]">
                                    {{ $m->user->name ?? 'Sistem' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-16 text-center text-[#8A7B66]">
                                <div class="max-w-sm mx-auto space-y-2.5">
                                    <div class="text-4xl opacity-40">📋</div>
                                    <div class="font-serif text-base font-bold text-[#1F1812]">Belum ada mutasi stok tercatat</div>
                                    <p class="text-xs text-[#8A7B66] leading-relaxed">
                                        Mutasi akan otomatis tercatat saat terjadi transaksi penjualan di POS, restock bahan, maupun pencatatan waste.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($movements->hasPages())
            <div class="p-4 border-t border-[#E4DCCC] bg-[#FAF7F2]">
                {{ $movements->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
