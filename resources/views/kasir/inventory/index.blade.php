@extends('kasir.app')

@section('title', 'Stok & Inventaris Bahan Baku')

@section('content')
<div class="space-y-6" x-data="{
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-[#E4DCCC]">
        <div>
            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                Stok & Inventaris Bahan Baku
            </h1>
            <p class="font-mono text-xs text-[#8A7B66] mt-0.5">
                Pantau ketersediaan biji kopi, susu, sirup, kemasan, serta HPP modal dan restock otomatis.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('kasir.inventory.recipes') }}"
               class="px-3 py-2 bg-white border border-[#E4DCCC] hover:border-[#D9973E] text-[#1F1812] font-mono text-xs uppercase tracking-wider font-semibold transition shadow-xs flex items-center gap-1.5">
                <span>📖 Resep BOM Menu</span>
            </a>
            <a href="{{ route('kasir.inventory.history') }}"
               class="px-3 py-2 bg-white border border-[#E4DCCC] hover:border-[#D9973E] text-[#1F1812] font-mono text-xs uppercase tracking-wider font-semibold transition shadow-xs flex items-center gap-1.5">
                <span>📋 Kartu Stok</span>
            </a>
            <button type="button" @click="showAddModal = true"
                    class="px-4 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold transition shadow-sm flex items-center gap-1.5">
                <span>+ Bahan Baru</span>
            </button>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if (session('success'))
        <div class="p-3.5 bg-[#5F7F42]/10 border border-[#5F7F42] text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-[#5F7F42] font-bold text-sm">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
        </div>
    @endif
    @if (session('error'))
        <div class="p-3.5 bg-[#C84B31]/10 border border-[#C84B31] text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-[#C84B31] font-bold text-sm">⚠</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
        </div>
    @endif

    <!-- 2. KARTU STATISTIK INVENTARIS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- Total Jenis Bahan -->
        <div class="bg-white border border-[#E4DCCC] p-4 shadow-xs">
            <div class="flex items-center justify-between text-[#8A7B66]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Total Bahan Baku</span>
                <span class="text-base">📦</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="font-mono text-2xl sm:text-3xl font-bold text-[#1F1812]">
                    {{ number_format($stats['total_ingredients'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono">item</span>
            </div>
            <div class="mt-1 text-[11px] text-[#8A7B66] font-mono">Bahan aktif terdata</div>
        </div>

        <!-- Stok Kritis / Menipis -->
        <div class="bg-white border border-[#E4DCCC] p-4 shadow-xs {{ $stats['low_stock_count'] > 0 ? 'border-amber-400 bg-amber-50/20' : '' }}">
            <div class="flex items-center justify-between text-[#D9973E]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Stok Menipis</span>
                <span class="text-base">⚠️</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="font-mono text-2xl sm:text-3xl font-bold {{ $stats['low_stock_count'] > 0 ? 'text-[#D9973E]' : 'text-[#1F1812]' }}">
                    {{ number_format($stats['low_stock_count'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono">item</span>
            </div>
            <div class="mt-1 text-[11px] text-[#8A7B66] font-mono">Di bawah batas minimum</div>
        </div>

        <!-- Stok Habis -->
        <div class="bg-white border border-[#E4DCCC] p-4 shadow-xs {{ $stats['out_of_stock_count'] > 0 ? 'border-red-400 bg-red-50/20' : '' }}">
            <div class="flex items-center justify-between text-[#C84B31]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Stok Habis (0)</span>
                <span class="text-base">🚫</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="font-mono text-2xl sm:text-3xl font-bold {{ $stats['out_of_stock_count'] > 0 ? 'text-[#C84B31]' : 'text-[#1F1812]' }}">
                    {{ number_format($stats['out_of_stock_count'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono">item</span>
            </div>
            <div class="mt-1 text-[11px] text-[#C84B31] font-mono">Menu terkait otomatis terkunci</div>
        </div>

        <!-- Total Nilai Aset Stok -->
        <div class="bg-white border border-[#E4DCCC] p-4 shadow-xs">
            <div class="flex items-center justify-between text-[#5F7F42]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Nilai Aset Stok</span>
                <span class="text-base">💰</span>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="font-mono text-xs text-[#5F7F42] font-semibold">Rp</span>
                <span class="font-mono text-2xl sm:text-3xl font-bold text-[#1F1812]">
                    {{ number_format($stats['total_inventory_value'], 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-1 text-[11px] text-[#8A7B66] font-mono">Total modal bahan saat ini</div>
        </div>
    </div>

    <!-- 3. FILTER & PENCARIAN -->
    <div class="bg-white border border-[#E4DCCC] p-4">
        <form method="GET" action="{{ route('kasir.inventory.index') }}" class="flex flex-wrap items-center gap-3">
            <!-- Search -->
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama atau kode bahan..."
                       class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
            </div>

            <!-- Filter Kategori -->
            <div class="w-44">
                <select name="category" onchange="this.form.submit()"
                        class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
                    <option value="all">Semua Kategori</option>
                    @foreach ($allCategories as $k => $lbl)
                        <option value="{{ $k }}" {{ $category === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status Stok -->
            <div class="w-44">
                <select name="status" onchange="this.form.submit()"
                        class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="safe" {{ $status === 'safe' ? 'selected' : '' }}>Stok Aman</option>
                    <option value="low_stock" {{ $status === 'low_stock' ? 'selected' : '' }}>⚠️ Menipis (Di Bawah Min)</option>
                    <option value="out_of_stock" {{ $status === 'out_of_stock' ? 'selected' : '' }}>🚫 Habis (0)</option>
                </select>
            </div>

            <button type="submit"
                    class="px-4 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold transition">
                Filter
            </button>
            @if ($search !== '' || $category !== 'all' || $status !== 'all')
                <a href="{{ route('kasir.inventory.index') }}"
                   class="px-3 py-2 bg-[#F7F3EC] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- 4. TABEL MASTER BAHAN BAKU -->
    <div class="bg-white border border-[#E4DCCC] overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#2A211A] text-[#F7F3EC] font-mono uppercase text-[10px] tracking-wider">
                        <th class="py-3 px-4">Bahan Baku</th>
                        <th class="py-3 px-3">Kategori</th>
                        <th class="py-3 px-3 text-right">Stok Saat Ini</th>
                        <th class="py-3 px-3 text-right">Batas Min.</th>
                        <th class="py-3 px-3 text-right">HPP / Unit</th>
                        <th class="py-3 px-3 text-right">Nilai Stok</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4DCCC] font-mono text-[#1F1812]">
                    @forelse ($ingredients as $item)
                        @php
                            $isOut = $item->current_stock <= 0;
                            $isLow = $item->current_stock > 0 && $item->current_stock <= $item->minimum_stock;
                            $val = round($item->current_stock * $item->cost_per_unit);
                        @endphp
                        <tr class="hover:bg-[#F7F3EC]/70 transition {{ $isOut ? 'bg-red-50/40' : ($isLow ? 'bg-amber-50/40' : '') }}">
                            <td class="py-3 px-4 font-sans">
                                <div class="font-bold text-sm text-[#1F1812]">{{ $item->name }}</div>
                                <div class="font-mono text-[10px] text-[#8A7B66]">
                                    Kode: {{ $item->code ?? '-' }} • Satuan: <span class="uppercase font-semibold">{{ $item->unit }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2 py-0.5 bg-[#F7F3EC] border border-[#E4DCCC] text-[10px] text-[#8A7B66]">
                                    {{ $item->category_name }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <span class="text-sm font-bold {{ $isOut ? 'text-[#C84B31]' : ($isLow ? 'text-[#D9973E]' : 'text-[#1F1812]') }}">
                                    {{ number_format($item->current_stock, 2, ',', '.') }}
                                </span>
                                <span class="text-[10px] text-[#8A7B66]">{{ $item->unit }}</span>
                            </td>
                            <td class="py-3 px-3 text-right text-[#8A7B66]">
                                {{ number_format($item->minimum_stock, 2, ',', '.') }} {{ $item->unit }}
                            </td>
                            <td class="py-3 px-3 text-right">
                                Rp {{ number_format($item->cost_per_unit, 0, ',', '.') }} <span class="text-[10px] text-[#8A7B66]">/{{ $item->unit }}</span>
                            </td>
                            <td class="py-3 px-3 text-right font-semibold text-[#1F1812]">
                                Rp {{ number_format($val, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if ($isOut)
                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-bold uppercase tracking-wider border border-red-300">
                                        Habis
                                    </span>
                                @elseif ($isLow)
                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold uppercase tracking-wider border border-amber-300">
                                        Menipis
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wider border border-emerald-300">
                                        Aman
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                            @click="openRestock({{ json_encode($item) }})"
                                            title="Restock / Tambah Stok"
                                            class="px-2 py-1 bg-[#5F7F42] hover:bg-[#4d6635] text-white text-[11px] font-bold transition">
                                        + Stok
                                    </button>
                                    <button type="button"
                                            @click="openWaste({{ json_encode($item) }})"
                                            title="Catat Terbuang / Rusak"
                                            class="px-2 py-1 bg-amber-700 hover:bg-amber-800 text-white text-[11px] font-bold transition">
                                        Waste
                                    </button>
                                    <button type="button"
                                            @click="openAdjust({{ json_encode($item) }})"
                                            title="Stock Opname (Sesuaikan)"
                                            class="px-2 py-1 bg-[#2A211A] hover:bg-[#D9973E] text-white hover:text-[#1F1812] text-[11px] transition">
                                        Opname
                                    </button>
                                    <button type="button"
                                            @click="openEdit({{ json_encode($item) }})"
                                            title="Edit Master Data"
                                            class="px-2 py-1 bg-white border border-[#E4DCCC] hover:border-[#1F1812] text-[#1F1812] text-[11px] transition">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('kasir.inventory.destroy', $item) }}"
                                          onsubmit="return confirm('Hapus bahan {{ $item->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus Bahan"
                                                class="px-2 py-1 bg-white border border-red-200 text-red-600 hover:bg-red-50 text-[11px] transition">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-[#8A7B66]">
                                <div class="text-3xl mb-2">📦</div>
                                <div class="font-serif text-base font-bold text-[#1F1812]">Belum ada data bahan baku</div>
                                <div class="font-mono text-xs mt-1">Mulai catat bahan baku seperti biji kopi, susu, sirup, dan kemasan.</div>
                                <button type="button" @click="showAddModal = true"
                                        class="mt-4 px-4 py-2 bg-[#1F1812] text-[#F7F3EC] font-mono text-xs uppercase font-bold hover:bg-[#D9973E] hover:text-[#1F1812] transition">
                                    + Tambah Bahan Baku Pertama
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ingredients->hasPages())
            <div class="p-4 border-t border-[#E4DCCC]">
                {{ $ingredients->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: TAMBAH BAHAN BAKU BARU -->
    <div x-show="showAddModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showAddModal = false">
        <div class="bg-white border border-[#3A3026] w-full max-w-lg shadow-2xl p-6 relative"
             @click.away="showAddModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-[#E4DCCC]">
                <h3 class="font-serif font-bold text-xl text-[#1F1812]">Tambah Master Bahan Baku</h3>
                <button type="button" @click="showAddModal = false" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('kasir.inventory.store') }}" class="mt-4 space-y-3 font-mono text-xs">
                @csrf
                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">Nama Bahan Baku <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Espresso Beans Arabica, Fresh Milk Diamond, Cup 16oz"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Kode Bahan</label>
                        <input type="text" name="code" placeholder="Contoh: ING-COF-01"
                               class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Kategori <span class="text-red-500">*</span></label>
                        <select name="category" required
                                class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                            @foreach ($allCategories as $k => $lbl)
                                <option value="{{ $k }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Satuan Dasar <span class="text-red-500">*</span></label>
                        <select name="unit" required
                                class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                            <option value="gr">Gram (gr)</option>
                            <option value="ml">Mililiter (ml)</option>
                            <option value="pcs">Pcs / Lembar</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Stok Awal</label>
                        <input type="number" step="0.01" name="current_stock" value="0" required
                               class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Batas Min. Alert</label>
                        <input type="number" step="0.01" name="minimum_stock" value="100" required
                               class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                    </div>
                </div>

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">Harga Beli / HPP per Satuan (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="cost_per_unit" value="0" required placeholder="Contoh: 280 (artinya Rp 280/gr atau Rp 280.000/kg)"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                    <span class="text-[10px] text-[#8A7B66]">Biaya modal bahan ini akan dihitung otomatis dalam resep menu.</span>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-[#E4DCCC]">
                    <button type="button" @click="showAddModal = false"
                            class="px-4 py-2 bg-[#F7F3EC] text-[#8A7B66] hover:text-[#1F1812]">Batal</button>
                    <button type="submit"
                            class="px-5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-bold uppercase tracking-wider">
                        Simpan Bahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: RESTOCK / KULAKAN BAHAN -->
    <div x-show="showRestockModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showRestockModal = false">
        <div class="bg-white border border-[#3A3026] w-full max-w-md shadow-2xl p-6 relative"
             @click.away="showRestockModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-[#E4DCCC]">
                <div>
                    <h3 class="font-serif font-bold text-xl text-[#1F1812]">Restock Bahan Baku</h3>
                    <p class="font-mono text-xs text-[#5F7F42] font-semibold mt-0.5" x-text="restockForm.name"></p>
                </div>
                <button type="button" @click="showRestockModal = false" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('kasir.inventory.restock') }}" class="mt-4 space-y-3 font-mono text-xs">
                @csrf
                <input type="hidden" name="ingredient_id" :value="restockForm.ingredient_id">

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">
                        Jumlah Masuk (<span x-text="restockForm.unit"></span>) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" name="quantity" required x-model="restockForm.quantity"
                           placeholder="Masukkan jumlah yang dibeli"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                </div>

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">Harga Beli Baru per Satuan (Rp) (Opsional)</label>
                    <input type="number" name="cost_per_unit" x-model="restockForm.cost_per_unit"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                    <span class="text-[10px] text-[#8A7B66]">Biarkan jika harga modal per unit tidak berubah.</span>
                </div>

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">Catatan / Supplier</label>
                    <input type="text" name="notes" placeholder="Contoh: Beli di Toko Bahagia, Nota #123"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                </div>

                <div class="p-3 bg-amber-50/70 border border-amber-200">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="record_as_expense" value="1" x-model="restockForm.record_as_expense"
                               class="accent-[#D9973E]">
                        <span class="font-semibold text-[#1F1812]">Catat otomatis ke Pengeluaran Toko</span>
                    </label>
                    <div class="text-[10px] text-[#8A7B66] mt-1 pl-5">
                        Total pembelian (<span x-text="Number(restockForm.quantity || 0) * Number(restockForm.cost_per_unit || 0)"></span> Rp) akan otomatis dibukukan di Laporan Biaya Toko.
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-[#E4DCCC]">
                    <button type="button" @click="showRestockModal = false"
                            class="px-4 py-2 bg-[#F7F3EC] text-[#8A7B66] hover:text-[#1F1812]">Batal</button>
                    <button type="submit"
                            class="px-5 py-2 bg-[#5F7F42] hover:bg-[#4d6635] text-white font-bold uppercase tracking-wider">
                        Simpan Restock
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: CATAT WASTE / TUMPAH / RUSAK -->
    <div x-show="showWasteModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showWasteModal = false">
        <div class="bg-white border border-[#3A3026] w-full max-w-md shadow-2xl p-6 relative"
             @click.away="showWasteModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-[#E4DCCC]">
                <div>
                    <h3 class="font-serif font-bold text-xl text-[#1F1812]">Catat Bahan Terbuang (Waste)</h3>
                    <p class="font-mono text-xs text-amber-700 font-semibold mt-0.5" x-text="wasteForm.name"></p>
                </div>
                <button type="button" @click="showWasteModal = false" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('kasir.inventory.waste') }}" class="mt-4 space-y-3 font-mono text-xs">
                @csrf
                <input type="hidden" name="ingredient_id" :value="wasteForm.ingredient_id">

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">
                        Jumlah Terbuang (<span x-text="wasteForm.unit"></span>) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" name="quantity" required
                           placeholder="Contoh: 150 (tumpah/kedaluwarsa)"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                </div>

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">Alasan Terbuang / Keterangan <span class="text-red-500">*</span></label>
                    <textarea name="notes" required rows="2" placeholder="Contoh: Susu basi / kaleng bocor / kopi tumpah saat kalibrasi mesin"
                              class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]"></textarea>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-[#E4DCCC]">
                    <button type="button" @click="showWasteModal = false"
                            class="px-4 py-2 bg-[#F7F3EC] text-[#8A7B66] hover:text-[#1F1812]">Batal</button>
                    <button type="submit"
                            class="px-5 py-2 bg-amber-700 hover:bg-amber-800 text-white font-bold uppercase tracking-wider">
                        Catat Waste
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: STOCK OPNAME (ADJUSTMENT) -->
    <div x-show="showAdjustModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showAdjustModal = false">
        <div class="bg-white border border-[#3A3026] w-full max-w-md shadow-2xl p-6 relative"
             @click.away="showAdjustModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-[#E4DCCC]">
                <div>
                    <h3 class="font-serif font-bold text-xl text-[#1F1812]">Stock Opname Fisik</h3>
                    <p class="font-mono text-xs text-[#D9973E] font-semibold mt-0.5" x-text="adjustForm.name"></p>
                </div>
                <button type="button" @click="showAdjustModal = false" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('kasir.inventory.adjustment') }}" class="mt-4 space-y-3 font-mono text-xs">
                @csrf
                <input type="hidden" name="ingredient_id" :value="adjustForm.ingredient_id">

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">
                        Stok Fisik Sebenarnya (<span x-text="adjustForm.unit"></span>) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" name="actual_stock" required x-model="adjustForm.actual_stock"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                    <span class="text-[10px] text-[#8A7B66]">Sistem akan menghitung selisih plus/minus secara otomatis ke kartu stok.</span>
                </div>

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">Alasan Penyesuaian <span class="text-red-500">*</span></label>
                    <input type="text" name="notes" required x-model="adjustForm.notes"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-[#E4DCCC]">
                    <button type="button" @click="showAdjustModal = false"
                            class="px-4 py-2 bg-[#F7F3EC] text-[#8A7B66] hover:text-[#1F1812]">Batal</button>
                    <button type="submit"
                            class="px-5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-bold uppercase tracking-wider">
                        Sesuaikan Stok
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: EDIT MASTER DATA BAHAN -->
    <div x-show="showEditModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showEditModal = false">
        <div class="bg-white border border-[#3A3026] w-full max-w-lg shadow-2xl p-6 relative"
             @click.away="showEditModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-[#E4DCCC]">
                <h3 class="font-serif font-bold text-xl text-[#1F1812]">Edit Master Bahan Baku</h3>
                <button type="button" @click="showEditModal = false" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
            </div>

            <form method="POST" :action="'/kasir/inventory/' + editForm.id" class="mt-4 space-y-3 font-mono text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">Nama Bahan Baku <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required x-model="editForm.name"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Kode Bahan</label>
                        <input type="text" name="code" x-model="editForm.code"
                               class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Kategori <span class="text-red-500">*</span></label>
                        <select name="category" required x-model="editForm.category"
                                class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                            @foreach ($allCategories as $k => $lbl)
                                <option value="{{ $k }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Satuan Dasar <span class="text-red-500">*</span></label>
                        <select name="unit" required x-model="editForm.unit"
                                class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                            <option value="gr">Gram (gr)</option>
                            <option value="ml">Mililiter (ml)</option>
                            <option value="pcs">Pcs / Lembar</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Batas Min. Alert</label>
                        <input type="number" step="0.01" name="minimum_stock" required x-model="editForm.minimum_stock"
                               class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                    </div>
                </div>

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">Harga Beli / HPP per Satuan (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="cost_per_unit" required x-model="editForm.cost_per_unit"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-[#E4DCCC]">
                    <button type="button" @click="showEditModal = false"
                            class="px-4 py-2 bg-[#F7F3EC] text-[#8A7B66] hover:text-[#1F1812]">Batal</button>
                    <button type="submit"
                            class="px-5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-bold uppercase tracking-wider">
                        Perbarui Data
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
