@extends('kasir.app')

@section('title', 'Stok & Inventaris Bahan Baku')

@section('content')
<div class="w-full p-4 sm:p-6 space-y-6" x-data="{
    showAddModal: false,
    showRestockModal: false,
    showWasteModal: false,
    showAdjustModal: false,
    showEditModal: false,
    activeIngredient: null,
    restockForm: {
        ingredient_id: '',
        name: '',
        unit: '',
        quantity: '',
        cost_per_unit: '',
        notes: '',
        record_as_expense: true
    },
    wasteForm: {
        ingredient_id: '',
        name: '',
        unit: '',
        quantity: '',
        notes: ''
    },
    adjustForm: {
        ingredient_id: '',
        name: '',
        unit: '',
        actual_stock: '',
        notes: ''
    },
    editForm: {
        id: '',
        name: '',
        code: '',
        category: '',
        unit: '',
        minimum_stock: '',
        cost_per_unit: ''
    },
    openRestock(item) {
        this.restockForm.ingredient_id = item.id;
        this.restockForm.name = item.name;
        this.restockForm.unit = item.unit;
        this.restockForm.quantity = '';
        this.restockForm.cost_per_unit = item.cost_per_unit;
        this.restockForm.notes = '';
        this.restockForm.record_as_expense = true;
        this.showRestockModal = true;
    },
    openWaste(item) {
        this.wasteForm.ingredient_id = item.id;
        this.wasteForm.name = item.name;
        this.wasteForm.unit = item.unit;
        this.wasteForm.quantity = '';
        this.wasteForm.notes = '';
        this.showWasteModal = true;
    },
    openAdjust(item) {
        this.adjustForm.ingredient_id = item.id;
        this.adjustForm.name = item.name;
        this.adjustForm.unit = item.unit;
        this.adjustForm.actual_stock = item.current_stock;
        this.adjustForm.notes = 'Penyesuaian stok opname';
        this.showAdjustModal = true;
    },
    openEdit(item) {
        this.editForm.id = item.id;
        this.editForm.name = item.name;
        this.editForm.code = item.code || '';
        this.editForm.category = item.category;
        this.editForm.unit = item.unit;
        this.editForm.minimum_stock = item.minimum_stock;
        this.editForm.cost_per_unit = item.cost_per_unit;
        this.showEditModal = true;
    }
}">

    <!-- 1. HEADER & TOMBOL AKSI CEPAT -->
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-[#E4DCCC]">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                    Stok & Inventaris Bahan Baku
                </h1>
                <span class="px-2.5 py-0.5 rounded-full bg-[#D9973E]/15 border border-[#D9973E]/30 text-[#B5762A] font-mono text-xs font-bold">
                    Logistik & HPP
                </span>
            </div>
            <p class="font-sans text-xs text-[#8A7B66] mt-1">
                Pantau ketersediaan biji kopi, susu, sirup, kemasan, serta HPP modal dan restock otomatis.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('kasir.inventory.recipes') }}"
               class="px-3.5 py-2.5 bg-white hover:bg-[#FAF7F2] text-[#1F1812] border border-[#E4DCCC] hover:border-[#D9973E] font-mono text-xs font-bold rounded-xl transition shadow-2xs flex items-center gap-1.5 active:scale-98">
                <span>📖</span>
                <span>Resep BOM Menu</span>
            </a>
            <a href="{{ route('kasir.inventory.history') }}"
               class="px-3.5 py-2.5 bg-white hover:bg-[#FAF7F2] text-[#1F1812] border border-[#E4DCCC] hover:border-[#D9973E] font-mono text-xs font-bold rounded-xl transition shadow-2xs flex items-center gap-1.5 active:scale-98">
                <span>📋</span>
                <span>Kartu Stok</span>
            </a>
            <button type="button" @click="showAddModal = true"
                    class="px-4 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all shadow-md flex items-center gap-1.5 active:scale-98 cursor-pointer">
                <span>+ Bahan Baru ›</span>
            </button>
        </div>
    </header>

    <!-- FLASH MESSAGES -->
    @if (session('success'))
        <div class="p-4 bg-[#5F7F42]/10 border border-[#5F7F42]/40 rounded-2xl text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-[#5F7F42] text-white flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-[#8A7B66] hover:text-[#1F1812] p-1 text-sm font-bold leading-none">✕</button>
        </div>
    @endif
    @if (session('error'))
        <div class="p-4 bg-[#C4553D]/10 border border-[#C4553D]/40 rounded-2xl text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-[#C4553D] text-white flex items-center justify-center font-bold text-xs shrink-0">⚠</span>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-[#8A7B66] hover:text-[#1F1812] p-1 text-sm font-bold leading-none">✕</button>
        </div>
    @endif

    <!-- 2. KARTU STATISTIK INVENTARIS (HERO STAT CARDS) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        <!-- Total Jenis Bahan -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/60 flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#8A7B66]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">TOTAL BAHAN BAKU</span>
                <span class="w-8 h-8 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-sm shadow-2xs">📦</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-extrabold text-[#1F1812] tracking-tight">
                    {{ number_format($stats['total_ingredients'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono font-medium">item</span>
            </div>
            <div class="mt-2 text-[11px] text-[#8A7B66] font-mono flex items-center gap-1.5 pt-2 border-t border-[#F2EDE4]">
                <span>Bahan aktif terdata</span>
            </div>
        </div>

        <!-- Stok Kritis / Menipis -->
        <div class="bg-white border rounded-2xl p-4 sm:p-5 shadow-xs transition flex flex-col justify-between {{ $stats['low_stock_count'] > 0 ? 'border-amber-400 bg-amber-50/20' : 'border-[#E4DCCC] hover:border-amber-400/60' }}">
            <div class="flex items-center justify-between text-[#D9973E]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">STOK MENIPIS</span>
                <span class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-sm shadow-2xs">⚠️</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-extrabold {{ $stats['low_stock_count'] > 0 ? 'text-[#D9973E]' : 'text-[#1F1812]' }} tracking-tight">
                    {{ number_format($stats['low_stock_count'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono font-medium">item</span>
            </div>
            <div class="mt-2 text-[11px] text-amber-700 font-mono flex items-center gap-1.5 pt-2 border-t border-[#F2EDE4]">
                <span>Di bawah batas minimum</span>
            </div>
        </div>

        <!-- Stok Habis -->
        <div class="bg-white border rounded-2xl p-4 sm:p-5 shadow-xs transition flex flex-col justify-between {{ $stats['out_of_stock_count'] > 0 ? 'border-red-400 bg-red-50/20' : 'border-[#E4DCCC] hover:border-red-400/60' }}">
            <div class="flex items-center justify-between text-[#C4553D]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">STOK HABIS (0)</span>
                <span class="w-8 h-8 rounded-xl bg-red-500/10 border border-red-500/30 flex items-center justify-center text-sm shadow-2xs">🚫</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-extrabold {{ $stats['out_of_stock_count'] > 0 ? 'text-[#C4553D]' : 'text-[#1F1812]' }} tracking-tight">
                    {{ number_format($stats['out_of_stock_count'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono font-medium">item</span>
            </div>
            <div class="mt-2 text-[11px] text-[#C4553D] font-mono flex items-center gap-1.5 pt-2 border-t border-[#F2EDE4]">
                <span>Menu terkait otomatis terkunci</span>
            </div>
        </div>

        <!-- Total Nilai Aset Stok -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-[#5F7F42]/60 flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#5F7F42]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">NILAI ASET STOK</span>
                <span class="w-8 h-8 rounded-xl bg-[#5F7F42]/10 border border-[#5F7F42]/30 flex items-center justify-center text-sm shadow-2xs">💰</span>
            </div>
            <div class="mt-3 flex items-baseline gap-1">
                <span class="font-mono text-xs text-[#5F7F42] font-bold">Rp</span>
                <span class="font-mono text-2xl sm:text-3xl font-extrabold text-[#1F1812] tracking-tight">
                    {{ number_format($stats['total_inventory_value'], 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-2 text-[11px] text-[#5F7F42] font-mono flex items-center gap-1.5 pt-2 border-t border-[#F2EDE4]">
                <span>Total modal bahan saat ini</span>
            </div>
        </div>
    </div>

    <!-- 3. FILTER & PENCARIAN -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs">
        <form method="GET" action="{{ route('kasir.inventory.index') }}" class="flex flex-wrap items-center gap-3">
            <!-- Search Input -->
            <div class="flex-1 min-w-[220px]">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8A7B66] text-xs">🔍</span>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Cari nama atau kode bahan..."
                           class="w-full pl-9 pr-3.5 py-2.5 bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl text-xs font-mono text-[#1F1812] focus:outline-none transition shadow-2xs">
                </div>
            </div>

            <!-- Filter Kategori -->
            <div class="w-48">
                <select name="category" onchange="this.form.submit()"
                        class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white px-3.5 py-2.5 text-xs font-mono text-[#1F1812] rounded-xl focus:outline-none transition shadow-2xs cursor-pointer">
                    <option value="all">Semua Kategori</option>
                    @foreach ($allCategories as $k => $lbl)
                        <option value="{{ $k }}" {{ $category === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status Stok -->
            <div class="w-48">
                <select name="status" onchange="this.form.submit()"
                        class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white px-3.5 py-2.5 text-xs font-mono text-[#1F1812] rounded-xl focus:outline-none transition shadow-2xs cursor-pointer">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="safe" {{ $status === 'safe' ? 'selected' : '' }}>Stok Aman</option>
                    <option value="low_stock" {{ $status === 'low_stock' ? 'selected' : '' }}>⚠️ Menipis (Bawah Min)</option>
                    <option value="out_of_stock" {{ $status === 'out_of_stock' ? 'selected' : '' }}>🚫 Habis (0)</option>
                </select>
            </div>

            <button type="submit"
                    class="px-5 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-xs cursor-pointer active:scale-95">
                Filter
            </button>
            @if ($search !== '' || $category !== 'all' || $status !== 'all')
                <a href="{{ route('kasir.inventory.index') }}"
                   class="px-4 py-2.5 bg-[#FAF7F2] hover:bg-white border border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs font-bold rounded-xl transition shadow-2xs">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- 4. TABEL MASTER BAHAN BAKU -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#2A211A] text-[#F7F3EC] font-mono uppercase text-[10px] tracking-wider">
                        <th class="py-3.5 px-4 font-bold">Bahan Baku</th>
                        <th class="py-3.5 px-3 font-bold">Kategori</th>
                        <th class="py-3.5 px-3 text-right font-bold">Stok Saat Ini</th>
                        <th class="py-3.5 px-3 text-right font-bold">Batas Min.</th>
                        <th class="py-3.5 px-3 text-right font-bold">HPP / Unit</th>
                        <th class="py-3.5 px-3 text-right font-bold">Nilai Stok</th>
                        <th class="py-3.5 px-3 text-center font-bold">Status</th>
                        <th class="py-3.5 px-4 text-center font-bold">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4DCCC] font-mono text-[#1F1812]">
                    @forelse ($ingredients as $item)
                        @php
                            $isOut = $item->current_stock <= 0;
                            $isLow = $item->current_stock > 0 && $item->current_stock <= $item->minimum_stock;
                            $val = round($item->current_stock * $item->cost_per_unit);
                        @endphp
                        <tr class="hover:bg-[#FAF7F2]/80 transition {{ $isOut ? 'bg-red-50/40' : ($isLow ? 'bg-amber-50/40' : '') }}">
                            <td class="py-3.5 px-4 font-sans">
                                <div class="font-bold text-sm text-[#1F1812]">{{ $item->name }}</div>
                                <div class="font-mono text-[10px] text-[#8A7B66] mt-0.5">
                                    Kode: <span class="font-semibold">{{ $item->code ?? '-' }}</span> &bull; Satuan: <span class="uppercase font-bold text-[#D9973E]">{{ $item->unit }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="px-2.5 py-0.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-full text-[10px] text-[#8A7B66] font-medium font-mono">
                                    {{ $item->category_name }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-right">
                                <span class="text-sm font-bold {{ $isOut ? 'text-[#C4553D]' : ($isLow ? 'text-[#D9973E]' : 'text-[#1F1812]') }}">
                                    {{ number_format($item->current_stock, 2, ',', '.') }}
                                </span>
                                <span class="text-[10px] text-[#8A7B66] font-medium">{{ $item->unit }}</span>
                            </td>
                            <td class="py-3.5 px-3 text-right text-[#8A7B66]">
                                {{ number_format($item->minimum_stock, 2, ',', '.') }} {{ $item->unit }}
                            </td>
                            <td class="py-3.5 px-3 text-right font-medium">
                                Rp {{ number_format($item->cost_per_unit, 0, ',', '.') }} <span class="text-[10px] text-[#8A7B66]">/{{ $item->unit }}</span>
                            </td>
                            <td class="py-3.5 px-3 text-right font-bold text-[#1F1812]">
                                Rp {{ number_format($val, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                @if ($isOut)
                                    <span class="px-2.5 py-0.5 bg-red-100 text-red-700 text-[10px] font-bold uppercase tracking-wider rounded-full border border-red-300 shadow-2xs">
                                        Habis
                                    </span>
                                @elseif ($isLow)
                                    <span class="px-2.5 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold uppercase tracking-wider rounded-full border border-amber-300 shadow-2xs">
                                        Menipis
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wider rounded-full border border-emerald-300 shadow-2xs">
                                        Aman
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                            @click="openRestock({{ json_encode($item) }})"
                                            title="Restock / Tambah Stok"
                                            class="px-2.5 py-1 bg-[#5F7F42] hover:bg-[#4d6635] text-white text-[11px] font-bold rounded-lg transition shadow-2xs active:scale-95 cursor-pointer">
                                        + Stok
                                    </button>
                                    <button type="button"
                                            @click="openWaste({{ json_encode($item) }})"
                                            title="Catat Terbuang / Rusak"
                                            class="px-2.5 py-1 bg-[#C4553D] hover:bg-[#a8442e] text-white text-[11px] font-bold rounded-lg transition shadow-2xs active:scale-95 cursor-pointer">
                                        Waste
                                    </button>
                                    <button type="button"
                                            @click="openAdjust({{ json_encode($item) }})"
                                            title="Stock Opname (Sesuaikan)"
                                            class="px-2.5 py-1 bg-[#2A211A] hover:bg-[#D9973E] text-white hover:text-[#1F1812] text-[11px] font-bold rounded-lg transition shadow-2xs active:scale-95 cursor-pointer">
                                        Opname
                                    </button>
                                    <button type="button"
                                            @click="openEdit({{ json_encode($item) }})"
                                            title="Edit Master Data"
                                            class="px-2.5 py-1 bg-white border border-[#E4DCCC] hover:border-[#1F1812] text-[#1F1812] text-[11px] font-bold rounded-lg transition shadow-2xs active:scale-95 cursor-pointer">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('kasir.inventory.destroy', $item) }}"
                                          onsubmit="return confirm('Hapus bahan {{ $item->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus Bahan"
                                                class="px-2 py-1 bg-white border border-red-200 text-red-600 hover:bg-red-50 text-[11px] font-bold rounded-lg transition shadow-2xs active:scale-95 cursor-pointer">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-[#8A7B66]">
                                <div class="max-w-sm mx-auto space-y-2.5">
                                    <div class="text-4xl opacity-40">📦</div>
                                    <div class="font-serif text-base font-bold text-[#1F1812]">Belum ada data bahan baku</div>
                                    <p class="text-xs text-[#8A7B66] leading-relaxed">
                                        Mulai catat bahan baku seperti biji kopi, susu, sirup, dan kemasan untuk menghitung HPP otomatis.
                                    </p>
                                    <div class="pt-2">
                                        <button type="button" @click="showAddModal = true"
                                                class="px-4 py-2 bg-[#1F1812] text-[#F7F3EC] font-mono text-xs uppercase font-bold hover:bg-[#D9973E] hover:text-[#1F1812] rounded-xl transition shadow-xs">
                                            + Tambah Bahan Baku Pertama
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ingredients->hasPages())
            <div class="p-4 border-t border-[#E4DCCC] bg-[#FAF7F2]">
                {{ $ingredients->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: TAMBAH BAHAN BAKU BARU (AMBER ACCENT) -->
    <div x-show="showAddModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showAddModal = false">
        <div class="bg-white border border-[#E4DCCC] border-t-4 border-t-[#D9973E] rounded-2xl w-full max-w-lg shadow-2xl p-6 sm:p-7 relative"
             @click.away="showAddModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#E4DCCC]">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📦</span>
                    <h3 class="font-serif font-bold text-xl text-[#1F1812]">Tambah Master Bahan Baku</h3>
                </div>
                <button type="button" @click="showAddModal = false" class="text-[#8A7B66] hover:text-[#1F1812] font-bold p-1 text-base leading-none">✕</button>
            </div>

            <form method="POST" action="{{ route('kasir.inventory.store') }}" class="mt-4 space-y-4 font-mono text-xs">
                @csrf
                <div>
                    <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">
                        Nama Bahan Baku <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="Contoh: Espresso Beans Arabica, Fresh Milk, Cup 16oz"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-medium">
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">Kode Bahan</label>
                        <input type="text" name="code" placeholder="Contoh: ING-COF-01"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-medium">
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">Kategori <span class="text-red-500">*</span></label>
                        <select name="category" required
                                class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs cursor-pointer font-medium">
                            @foreach ($allCategories as $k => $lbl)
                                <option value="{{ $k }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">Satuan Dasar <span class="text-red-500">*</span></label>
                        <select name="unit" required
                                class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs cursor-pointer font-medium">
                            <option value="gr">Gram (gr)</option>
                            <option value="ml">Mililiter (ml)</option>
                            <option value="pcs">Pcs / Lembar</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">Stok Awal</label>
                        <input type="number" step="0.01" name="current_stock" value="0" required
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs text-right font-bold">
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">Batas Min.</label>
                        <input type="number" step="0.01" name="minimum_stock" value="100" required
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs text-right font-bold">
                    </div>
                </div>

                <div>
                    <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">
                        Harga Beli / HPP per Satuan (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="cost_per_unit" value="0" required placeholder="Contoh: 280 (Rp 280/gr atau Rp 280.000/kg)"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-bold">
                    <span class="text-[10px] text-[#8A7B66] mt-1 block">Biaya modal bahan ini akan dihitung otomatis dalam resep menu.</span>
                </div>

                <div class="pt-3.5 flex items-center justify-end gap-2.5 border-t border-[#E4DCCC]">
                    <button type="button" @click="showAddModal = false"
                            class="px-4 py-2 bg-[#FAF7F2] hover:bg-white border border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] rounded-xl font-bold transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-bold uppercase tracking-wider rounded-xl transition shadow-xs active:scale-95 cursor-pointer">
                        Simpan Bahan ›
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: RESTOCK / KULAKAN BAHAN (GREEN ACCENT WITH LIVE COST PREVIEW) -->
    <div x-show="showRestockModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showRestockModal = false">
        <div class="bg-white border border-[#E4DCCC] border-t-4 border-t-[#5F7F42] rounded-2xl w-full max-w-md shadow-2xl p-6 sm:p-7 relative"
             @click.away="showRestockModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#E4DCCC]">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📥</span>
                        <h3 class="font-serif font-bold text-xl text-[#1F1812]">Restock Bahan Baku</h3>
                    </div>
                    <p class="font-mono text-xs text-[#5F7F42] font-bold mt-0.5" x-text="restockForm.name"></p>
                </div>
                <button type="button" @click="showRestockModal = false" class="text-[#8A7B66] hover:text-[#1F1812] font-bold p-1 text-base leading-none">✕</button>
            </div>

            <form method="POST" action="{{ route('kasir.inventory.restock') }}" class="mt-4 space-y-4 font-mono text-xs">
                @csrf
                <input type="hidden" name="ingredient_id" :value="restockForm.ingredient_id">

                <div>
                    <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">
                        Jumlah Masuk (<span x-text="restockForm.unit" class="text-[#5F7F42]"></span>) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" name="quantity" required x-model="restockForm.quantity"
                           placeholder="Masukkan jumlah yang dibeli"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#5F7F42] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-bold text-right">
                </div>

                <div>
                    <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">
                        Harga Beli Baru per Satuan (Rp) (Opsional)
                    </label>
                    <input type="number" name="cost_per_unit" x-model="restockForm.cost_per_unit"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#5F7F42] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-bold text-right">
                    <span class="text-[10px] text-[#8A7B66] mt-1 block">Biarkan jika harga modal per unit tidak berubah.</span>
                </div>

                <div>
                    <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">Catatan / Supplier</label>
                    <input type="text" name="notes" placeholder="Contoh: Beli di Toko Makmur, Nota #123"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#5F7F42] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-medium">
                </div>

                <!-- Live Total Cost Preview & Auto-Expense Checkbox -->
                <div class="p-3.5 bg-[#5F7F42]/10 border border-[#5F7F42]/30 rounded-xl space-y-1.5">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="record_as_expense" value="1" x-model="restockForm.record_as_expense"
                               class="accent-[#5F7F42] w-4 h-4 rounded">
                        <span class="font-bold text-[#1F1812]">Catat otomatis ke Pengeluaran Toko</span>
                    </label>
                    <div class="text-[11px] text-[#5C4D3C] pl-6 font-mono">
                        Total pembelian: <b class="text-[#5F7F42]" x-text="'Rp ' + (Number(restockForm.quantity || 0) * Number(restockForm.cost_per_unit || 0)).toLocaleString('id-ID')"></b>
                    </div>
                </div>

                <div class="pt-3.5 flex items-center justify-end gap-2.5 border-t border-[#E4DCCC]">
                    <button type="button" @click="showRestockModal = false"
                            class="px-4 py-2 bg-[#FAF7F2] hover:bg-white border border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] rounded-xl font-bold transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-[#5F7F42] hover:bg-[#4d6635] text-white font-bold uppercase tracking-wider rounded-xl transition shadow-xs active:scale-95 cursor-pointer">
                        Simpan Restock ›
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: CATAT WASTE / TUMPAH / RUSAK (RED ACCENT) -->
    <div x-show="showWasteModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showWasteModal = false">
        <div class="bg-white border border-[#E4DCCC] border-t-4 border-t-[#C4553D] rounded-2xl w-full max-w-md shadow-2xl p-6 sm:p-7 relative"
             @click.away="showWasteModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#E4DCCC]">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🗑️</span>
                        <h3 class="font-serif font-bold text-xl text-[#1F1812]">Catat Bahan Terbuang (Waste)</h3>
                    </div>
                    <p class="font-mono text-xs text-[#C4553D] font-bold mt-0.5" x-text="wasteForm.name"></p>
                </div>
                <button type="button" @click="showWasteModal = false" class="text-[#8A7B66] hover:text-[#1F1812] font-bold p-1 text-base leading-none">✕</button>
            </div>

            <form method="POST" action="{{ route('kasir.inventory.waste') }}" class="mt-4 space-y-4 font-mono text-xs">
                @csrf
                <input type="hidden" name="ingredient_id" :value="wasteForm.ingredient_id">

                <div>
                    <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">
                        Jumlah Terbuang (<span x-text="wasteForm.unit" class="text-[#C4553D]"></span>) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" name="quantity" required
                           placeholder="Contoh: 150 (tumpah/kedaluwarsa)"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#C4553D] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-bold text-right">
                </div>

                <div>
                    <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">
                        Alasan Terbuang / Keterangan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="notes" required rows="2" placeholder="Contoh: Susu basi / kaleng bocor / kopi tumpah saat kalibrasi mesin"
                              class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#C4553D] focus:bg-white rounded-xl px-3.5 py-2 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs"></textarea>
                </div>

                <div class="pt-3.5 flex items-center justify-end gap-2.5 border-t border-[#E4DCCC]">
                    <button type="button" @click="showWasteModal = false"
                            class="px-4 py-2 bg-[#FAF7F2] hover:bg-white border border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] rounded-xl font-bold transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-[#C4553D] hover:bg-[#a8442e] text-white font-bold uppercase tracking-wider rounded-xl transition shadow-xs active:scale-95 cursor-pointer">
                        Catat Waste ›
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: STOCK OPNAME / ADJUSTMENT (BLUE ACCENT) -->
    <div x-show="showAdjustModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showAdjustModal = false">
        <div class="bg-white border border-[#E4DCCC] border-t-4 border-t-blue-600 rounded-2xl w-full max-w-md shadow-2xl p-6 sm:p-7 relative"
             @click.away="showAdjustModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#E4DCCC]">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl">⚖️</span>
                        <h3 class="font-serif font-bold text-xl text-[#1F1812]">Stock Opname Fisik</h3>
                    </div>
                    <p class="font-mono text-xs text-blue-600 font-bold mt-0.5" x-text="adjustForm.name"></p>
                </div>
                <button type="button" @click="showAdjustModal = false" class="text-[#8A7B66] hover:text-[#1F1812] font-bold p-1 text-base leading-none">✕</button>
            </div>

            <form method="POST" action="{{ route('kasir.inventory.adjustment') }}" class="mt-4 space-y-4 font-mono text-xs">
                @csrf
                <input type="hidden" name="ingredient_id" :value="adjustForm.ingredient_id">

                <div>
                    <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">
                        Stok Fisik Sebenarnya (<span x-text="adjustForm.unit" class="text-blue-600"></span>) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" name="actual_stock" required x-model="adjustForm.actual_stock"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-blue-600 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-bold text-right">
                    <span class="text-[10px] text-[#8A7B66] mt-1 block">Sistem akan menghitung selisih plus/minus secara otomatis ke kartu stok.</span>
                </div>

                <div>
                    <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">
                        Alasan Penyesuaian <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="notes" required x-model="adjustForm.notes"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-blue-600 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-medium">
                </div>

                <div class="pt-3.5 flex items-center justify-end gap-2.5 border-t border-[#E4DCCC]">
                    <button type="button" @click="showAdjustModal = false"
                            class="px-4 py-2 bg-[#FAF7F2] hover:bg-white border border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] rounded-xl font-bold transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-[#1F1812] hover:bg-blue-600 text-[#F7F3EC] hover:text-white font-bold uppercase tracking-wider rounded-xl transition shadow-xs active:scale-95 cursor-pointer">
                        Sesuaikan Stok ›
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: EDIT MASTER DATA BAHAN (AMBER ACCENT) -->
    <div x-show="showEditModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showEditModal = false">
        <div class="bg-white border border-[#E4DCCC] border-t-4 border-t-[#D9973E] rounded-2xl w-full max-w-lg shadow-2xl p-6 sm:p-7 relative"
             @click.away="showEditModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#E4DCCC]">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <h3 class="font-serif font-bold text-xl text-[#1F1812]">Edit Master Bahan Baku</h3>
                </div>
                <button type="button" @click="showEditModal = false" class="text-[#8A7B66] hover:text-[#1F1812] font-bold p-1 text-base leading-none">✕</button>
            </div>

            <form method="POST" :action="'/kasir/inventory/' + editForm.id" class="mt-4 space-y-4 font-mono text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">
                        Nama Bahan Baku <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required x-model="editForm.name"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-medium">
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">Kode Bahan</label>
                        <input type="text" name="code" x-model="editForm.code"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-medium">
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">Kategori <span class="text-red-500">*</span></label>
                        <select name="category" required x-model="editForm.category"
                                class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs cursor-pointer font-medium">
                            @foreach ($allCategories as $k => $lbl)
                                <option value="{{ $k }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">Satuan Dasar <span class="text-red-500">*</span></label>
                        <select name="unit" required x-model="editForm.unit"
                                class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs cursor-pointer font-medium">
                            <option value="gr">Gram (gr)</option>
                            <option value="ml">Mililiter (ml)</option>
                            <option value="pcs">Pcs / Lembar</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">Batas Min. Alert</label>
                        <input type="number" step="0.01" name="minimum_stock" required x-model="editForm.minimum_stock"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs text-right font-bold">
                    </div>
                </div>

                <div>
                    <label class="block text-[#1F1812] font-bold mb-1.5 uppercase text-[11px] tracking-wider">
                        Harga Beli / HPP per Satuan (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="cost_per_unit" required x-model="editForm.cost_per_unit"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3.5 py-2.5 text-xs text-[#1F1812] focus:outline-none transition shadow-2xs font-bold">
                </div>

                <div class="pt-3.5 flex items-center justify-end gap-2.5 border-t border-[#E4DCCC]">
                    <button type="button" @click="showEditModal = false"
                            class="px-4 py-2 bg-[#FAF7F2] hover:bg-white border border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] rounded-xl font-bold transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-bold uppercase tracking-wider rounded-xl transition shadow-xs active:scale-95 cursor-pointer">
                        Perbarui Data ›
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
