@extends('kasir.app')

@section('title', 'Kartu Riwayat Mutasi Stok')

@section('content')
<div class="space-y-6">

    <!-- 1. HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-[#E4DCCC]">
        <div>
            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                Kartu Riwayat Mutasi Stok
            </h1>
            <p class="font-mono text-xs text-[#8A7B66] mt-0.5">
                Audit trail lengkap setiap perubahan stok bahan: penjualan kasir, restock, pembatalan void, hingga bahan terbuang (waste).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('kasir.inventory.index') }}"
               class="px-3.5 py-2 bg-white border border-[#E4DCCC] hover:border-[#D9973E] text-[#1F1812] font-mono text-xs uppercase tracking-wider font-semibold transition shadow-xs flex items-center gap-1.5">
                <span>‹ Master Bahan Baku</span>
            </a>
        </div>
    </div>

    <!-- 2. FILTER & PENCARIAN -->
    <div class="bg-white border border-[#E4DCCC] p-4">
        <form method="GET" action="{{ route('kasir.inventory.history') }}" class="flex flex-wrap items-center gap-3">
            <!-- Filter Bahan Baku -->
            <div class="w-56">
                <select name="ingredient_id" onchange="this.form.submit()"
                        class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
                    <option value="all">Semua Bahan Baku</option>
                    @foreach ($ingredients as $ing)
                        <option value="{{ $ing->id }}" {{ (string) $ingredientId === (string) $ing->id ? 'selected' : '' }}>
                            {{ $ing->name }} ({{ $ing->unit }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Tipe Mutasi -->
            <div class="w-44">
                <select name="type" onchange="this.form.submit()"
                        class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
                    <option value="all">Semua Jenis Mutasi</option>
                    <option value="sale" {{ $type === 'sale' ? 'selected' : '' }}>Penjualan Order (-)</option>
                    <option value="purchase" {{ $type === 'purchase' ? 'selected' : '' }}>Restock / Pembelian (+)</option>
                    <option value="void_return" {{ $type === 'void_return' ? 'selected' : '' }}>Void Order (+)</option>
                    <option value="waste" {{ $type === 'waste' ? 'selected' : '' }}>Bahan Terbuang (-)</option>
                    <option value="adjustment" {{ $type === 'adjustment' ? 'selected' : '' }}>Stock Opname</option>
                </select>
            </div>

            <!-- Filter Tanggal -->
            <div class="w-40">
                <input type="date" name="date" value="{{ $date }}"
                       onchange="this.form.submit()"
                       class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
            </div>

            <button type="submit"
                    class="px-4 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold transition">
                Filter
            </button>
            @if ($ingredientId !== 'all' && $ingredientId || $type !== 'all' && $type || $date)
                <a href="{{ route('kasir.inventory.history') }}"
                   class="px-3 py-2 bg-[#F7F3EC] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- 3. TABEL MUTASI STOK -->
    <div class="bg-white border border-[#E4DCCC] overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#2A211A] text-[#F7F3EC] font-mono uppercase text-[10px] tracking-wider">
                        <th class="py-3 px-4">Waktu</th>
                        <th class="py-3 px-3">Bahan Baku</th>
                        <th class="py-3 px-3">Jenis Mutasi</th>
                        <th class="py-3 px-3 text-right">Jumlah Mutasi</th>
                        <th class="py-3 px-3 text-right">Stok Sebelum</th>
                        <th class="py-3 px-3 text-right">Stok Sesudah</th>
                        <th class="py-3 px-3 text-right">Total Nilai HPP</th>
                        <th class="py-3 px-4">Catatan & Referensi</th>
                        <th class="py-3 px-3">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4DCCC] font-mono text-[#1F1812]">
                    @forelse ($movements as $m)
                        @php
                            $isPlus = $m->quantity > 0;
                        @endphp
                        <tr class="hover:bg-[#F7F3EC]/70 transition">
                            <td class="py-3 px-4 text-[#8A7B66] whitespace-nowrap">
                                <div>{{ $m->created_at->translatedFormat('d M Y') }}</div>
                                <div class="text-[10px] text-[#A89A85]">{{ $m->created_at->format('H:i:s') }}</div>
                            </td>
                            <td class="py-3 px-3 font-sans">
                                <div class="font-bold text-[#1F1812]">{{ $m->ingredient->name ?? '-' }}</div>
                                <div class="font-mono text-[10px] text-[#8A7B66]">Satuan: {{ $m->ingredient->unit ?? '' }}</div>
                            </td>
                            <td class="py-3 px-3">
                                @if ($m->type === 'purchase')
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold border border-emerald-300">
                                        Restock Masuk
                                    </span>
                                @elseif ($m->type === 'sale')
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-bold border border-blue-300">
                                        Penjualan Kasir
                                    </span>
                                @elseif ($m->type === 'void_return')
                                    <span class="px-2 py-0.5 bg-purple-100 text-purple-800 text-[10px] font-bold border border-purple-300">
                                        Retur Void
                                    </span>
                                @elseif ($m->type === 'waste')
                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold border border-amber-300">
                                        Terbuang (Waste)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-800 text-[10px] font-bold border border-gray-300">
                                        Stock Opname
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right">
                                <span class="font-bold text-sm {{ $isPlus ? 'text-[#5F7F42]' : 'text-[#C84B31]' }}">
                                    {{ $isPlus ? '+' : '' }}{{ number_format($m->quantity, 2, ',', '.') }}
                                </span>
                                <span class="text-[10px] text-[#8A7B66]">{{ $m->ingredient->unit ?? '' }}</span>
                            </td>
                            <td class="py-3 px-3 text-right text-[#8A7B66]">
                                {{ number_format($m->stock_before, 2, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-right font-semibold text-[#1F1812]">
                                {{ number_format($m->stock_after, 2, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-right text-[#1F1812]">
                                Rp {{ number_format($m->total_cost, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 font-sans text-xs text-[#1F1812] max-w-xs">
                                {{ $m->notes ?? '-' }}
                            </td>
                            <td class="py-3 px-3 text-[#8A7B66] text-[11px] whitespace-nowrap">
                                {{ $m->user->name ?? 'Sistem' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-[#8A7B66]">
                                <div class="text-3xl mb-2">📋</div>
                                <div class="font-serif text-base font-bold text-[#1F1812]">Belum ada mutasi stok tercatat</div>
                                <div class="font-mono text-xs mt-1">Mutasi akan otomatis bertambah saat ada order POS, restock, atau waste.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($movements->hasPages())
            <div class="p-4 border-t border-[#E4DCCC]">
                {{ $movements->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
