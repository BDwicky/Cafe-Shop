@extends('kasir.app')

@section('title', 'Resep Menu & Bill of Materials (BOM)')

@section('content')
<div class="w-full p-4 sm:p-6 space-y-6" x-data="{
    showRecipeModal: false,
    selectedMenu: null,
    menuRecipes: [],
    allIngredients: {{ json_encode($ingredients->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit, 'cost' => (int) $i->cost_per_unit])) }},
    openRecipeEditor(menu, recipes) {
        this.selectedMenu = menu;
        this.menuRecipes = (recipes || []).map(r => ({
            ingredient_id: r.ingredient_id,
            amount: r.amount
        }));
        if (this.menuRecipes.length === 0) {
            this.addIngredientRow();
        }
        this.showRecipeModal = true;
    },
    addIngredientRow() {
        if (this.allIngredients.length > 0) {
            this.menuRecipes.push({
                ingredient_id: this.allIngredients[0].id,
                amount: 1
            });
        }
    },
    removeIngredientRow(index) {
        this.menuRecipes.splice(index, 1);
    },
    getIngredient(id) {
        return this.allIngredients.find(i => i.id == id) || null;
    },
    calculateHpp() {
        let total = 0;
        this.menuRecipes.forEach(r => {
            let ing = this.getIngredient(r.ingredient_id);
            if (ing && r.amount > 0) {
                total += ing.cost * parseFloat(r.amount);
            }
        });
        return Math.round(total);
    },
    calculateMargin() {
        if (!this.selectedMenu || this.selectedMenu.price <= 0) return 0;
        let hpp = this.calculateHpp();
        let margin = ((this.selectedMenu.price - hpp) / this.selectedMenu.price) * 100;
        return Math.round(margin * 10) / 10;
    }
}">

    <!-- 1. HEADER SECTION -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2 border-b border-[#E4DCCC]">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-medium bg-[#FAF7F2] text-[#8A7B66] border border-[#E4DCCC] mb-2">
                <span class="w-2 h-2 rounded-full bg-[#D9973E]"></span>
                Bill of Materials & Kalkulasi HPP
            </div>
            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                Resep Menu & Bill of Materials
            </h1>
            <p class="font-sans text-xs sm:text-sm text-[#8A7B66] mt-1 max-w-2xl">
                Tentukan komposisi takaran bahan per porsi. Sistem akan otomatis memotong stok bahan baku saat menu dipesan dan menghitung margin laba bersih.
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('kasir.inventory.index') }}"
               class="px-4 py-2.5 bg-white border border-[#E4DCCC] hover:border-[#D9973E] text-[#1F1812] rounded-xl font-mono text-xs uppercase tracking-wider font-semibold transition shadow-xs flex items-center gap-2">
                <svg class="w-4 h-4 text-[#8A7B66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Master Bahan</span>
            </a>
            <a href="{{ route('kasir.inventory.history') }}"
               class="px-4 py-2.5 bg-[#FAF7F2] border border-[#E4DCCC] hover:border-[#D9973E] text-[#1F1812] rounded-xl font-mono text-xs uppercase tracking-wider font-semibold transition shadow-xs flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#8A7B66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Riwayat Mutasi</span>
            </a>
        </div>
    </div>

    <!-- FLASH STATUS NOTIFICATION -->
    @if (session('success'))
        <div class="p-4 rounded-2xl bg-[#5F7F42]/10 border border-[#5F7F42]/30 text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-full bg-[#5F7F42] text-white flex items-center justify-center font-bold text-xs shrink-0">✓</div>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="w-6 h-6 rounded-full hover:bg-black/5 text-[#8A7B66] hover:text-[#1F1812] flex items-center justify-center font-bold transition">✕</button>
        </div>
    @endif

    <!-- 2. FILTER & PENCARIAN -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 shadow-xs">
        <form method="GET" action="{{ route('kasir.inventory.recipes') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[220px] relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8A7B66]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama menu..."
                       class="w-full bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl pl-10 pr-3.5 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
            </div>

            <div class="w-full sm:w-56">
                <select name="category_id" onchange="this.form.submit()"
                        class="w-full bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3.5 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
                    <option value="all">Semua Kategori Menu</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string) $categoryId === (string) $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="px-5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#FAF7F2] hover:text-[#1F1812] rounded-xl font-mono text-xs uppercase tracking-wider font-bold transition shadow-xs">
                Filter
            </button>
            @if ($search !== '' || $categoryId !== 'all')
                <a href="{{ route('kasir.inventory.recipes') }}"
                   class="px-3.5 py-2 bg-[#FAF7F2] border border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] rounded-xl font-mono text-xs transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- 3. LIST MENU & RESEP BOM -->
    <div class="space-y-3.5">
        @forelse ($menus as $menu)
            @php
                $hpp = $menu->calculateHpp();
                $margin = $menu->margin_percent;
                $hasRecipe = $menu->recipes->count() > 0;
            @endphp
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 shadow-xs transition-all duration-200 hover:shadow-md hover:border-[#D9973E]/60 flex flex-col md:flex-row md:items-center md:justify-between gap-5">
                <!-- Info Menu -->
                <div class="flex items-start gap-4 flex-1">
                    @if ($menu->image)
                        <img src="{{ asset('storage/'.$menu->image) }}" alt="{{ $menu->name }}" class="w-16 h-16 rounded-xl object-cover border border-[#E4DCCC] shadow-xs shrink-0">
                    @else
                        <div class="w-16 h-16 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-2xl shrink-0 shadow-xs">
                            ☕
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-serif font-bold text-lg text-[#1F1812] truncate">{{ $menu->name }}</h3>
                            <span class="px-2.5 py-0.5 rounded-full bg-[#FAF7F2] border border-[#E4DCCC] text-[10px] font-mono text-[#8A7B66] font-semibold">
                                {{ $menu->category->name ?? '-' }}
                            </span>
                            @if (! $menu->is_available)
                                <span class="px-2.5 py-0.5 rounded-full bg-red-100 border border-red-200 text-red-700 text-[10px] font-mono font-bold uppercase">
                                    Stok Habis
                                </span>
                            @endif
                        </div>

                        <!-- Metrik Harga & Margin -->
                        <div class="font-mono text-xs text-[#8A7B66] mt-2 flex flex-wrap items-center gap-y-1 gap-x-3">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[#8A7B66]">Harga Jual:</span>
                                <span class="font-bold text-[#1F1812]">Rp {{ number_format($menu->price, 0, ',', '.') }}</span>
                            </div>
                            <span class="text-[#E4DCCC]">•</span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[#8A7B66]">HPP Modal:</span>
                                <span class="font-bold {{ $hasRecipe ? 'text-[#5F7F42]' : 'text-[#8A7B66]' }}">
                                    Rp {{ number_format($hpp, 0, ',', '.') }}
                                </span>
                            </div>
                            <span class="text-[#E4DCCC]">•</span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[#8A7B66]">Margin:</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono {{ $margin >= 50 ? 'bg-[#5F7F42]/10 text-[#5F7F42] border border-[#5F7F42]/30' : ($margin >= 25 ? 'bg-[#D9973E]/10 text-[#D9973E] border border-[#D9973E]/30' : 'bg-red-50 text-red-700 border border-red-200') }}">
                                    {{ $margin }}%
                                </span>
                            </div>
                        </div>

                        <!-- Ringkasan Bahan BOM -->
                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                            @if ($hasRecipe)
                                @foreach ($menu->recipes as $rcp)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#FAF7F2] border border-[#E4DCCC] font-mono text-[11px] text-[#1F1812]">
                                        <span class="font-semibold text-[#1F1812]">{{ $rcp->ingredient->name ?? 'Bahan' }}</span>
                                        <span class="text-[#8A7B66]">({{ (float) $rcp->amount }} {{ $rcp->ingredient->unit ?? '' }})</span>
                                    </span>
                                @endforeach
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-mono text-[11px] text-amber-800 bg-amber-50 border border-amber-200">
                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    Belum ada resep BOM (stok bahan baku tidak akan terpotong saat dipesan)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tombol Atur Resep -->
                <div class="shrink-0 flex items-center md:justify-end pt-3 md:pt-0 border-t md:border-t-0 border-[#E4DCCC]">
                    <button type="button"
                            @click="openRecipeEditor({{ json_encode($menu) }}, {{ json_encode($menu->recipes) }})"
                            class="w-full sm:w-auto px-4 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#FAF7F2] hover:text-[#1F1812] rounded-xl font-mono text-xs uppercase tracking-wider font-bold transition shadow-xs flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ $hasRecipe ? 'Ubah Resep BOM' : '+ Buat Resep BOM' }}</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-12 text-center text-[#8A7B66] shadow-xs">
                <div class="text-4xl mb-3">🍽️</div>
                <div class="font-serif text-lg font-bold text-[#1F1812]">Tidak Ada Menu Ditemukan</div>
                <p class="font-sans text-xs text-[#8A7B66] mt-1">Coba sesuaikan kata kunci pencarian atau kategori menu.</p>
            </div>
        @endforelse

        @if ($menus->hasPages())
            <div class="p-4 bg-white border border-[#E4DCCC] rounded-2xl shadow-xs">
                {{ $menus->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: ATUR RESEP BOM MENU -->
    <div x-show="showRecipeModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#1F1812]/60 backdrop-blur-xs"
         @keydown.escape.window="showRecipeModal = false">
        <div class="bg-white border border-[#E4DCCC] rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden relative max-h-[90vh] flex flex-col animate-in fade-in zoom-in-95 duration-150"
             @click.away="showRecipeModal = false">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-[#FAF7F2] border-b border-[#E4DCCC] flex items-center justify-between">
                <div>
                    <h3 class="font-serif font-bold text-xl text-[#1F1812]">Atur Resep BOM (Bill of Materials)</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs font-sans text-[#8A7B66]">Menu:</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-white border border-[#E4DCCC] font-mono text-xs font-bold text-[#D9973E]" x-text="selectedMenu ? selectedMenu.name : ''"></span>
                    </div>
                </div>
                <button type="button" @click="showRecipeModal = false"
                        class="w-8 h-8 rounded-full bg-white border border-[#E4DCCC] hover:bg-[#FAF7F2] text-[#8A7B66] hover:text-[#1F1812] flex items-center justify-center font-bold transition">
                    ✕
                </button>
            </div>

            <!-- Form update resep -->
            <form method="POST" :action="'/kasir/inventory/recipes/' + (selectedMenu ? selectedMenu.id : '')"
                  class="flex-1 flex flex-col min-h-0">
                @csrf
                @method('PUT')

                <div class="p-6 flex-1 overflow-y-auto space-y-4">
                    <!-- Ringkasan Live Kalkulasi HPP -->
                    <div class="p-4 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] font-mono text-xs grid grid-cols-3 gap-3 shrink-0">
                        <div class="bg-white p-3 rounded-lg border border-[#E4DCCC]/70">
                            <div class="text-[10px] text-[#8A7B66] uppercase tracking-wider font-semibold">Harga Jual</div>
                            <div class="font-bold text-base text-[#1F1812] mt-1" x-text="'Rp ' + (selectedMenu ? selectedMenu.price.toLocaleString('id-ID') : 0)"></div>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-[#E4DCCC]/70">
                            <div class="text-[10px] text-[#8A7B66] uppercase tracking-wider font-semibold">Total HPP Modal</div>
                            <div class="font-bold text-base text-[#5F7F42] mt-1" x-text="'Rp ' + calculateHpp().toLocaleString('id-ID')"></div>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-[#E4DCCC]/70">
                            <div class="text-[10px] text-[#8A7B66] uppercase tracking-wider font-semibold">Margin Laba</div>
                            <div class="font-bold text-base text-[#D9973E] mt-1" x-text="calculateMargin() + '%'"></div>
                        </div>
                    </div>

                    <!-- Daftar Baris Bahan -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-1 border-b border-[#E4DCCC]">
                            <span class="text-[11px] font-mono font-semibold uppercase tracking-wider text-[#8A7B66]">
                                Komposisi Bahan per Porsi:
                            </span>
                            <span class="text-[11px] font-mono text-[#8A7B66]">Subtotal HPP</span>
                        </div>

                        <template x-for="(row, idx) in menuRecipes" :key="idx">
                            <div class="flex items-center gap-2.5 bg-[#FAF7F2]/70 p-2.5 rounded-xl border border-[#E4DCCC] transition-all hover:border-[#D9973E]/50">
                                <!-- Pilih Bahan -->
                                <div class="flex-1">
                                    <select :name="'recipes[' + idx + '][ingredient_id]'" x-model="row.ingredient_id"
                                            class="w-full bg-white border border-[#E4DCCC] rounded-lg px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
                                        <template x-for="ing in allIngredients" :key="ing.id">
                                            <option :value="ing.id" :selected="ing.id == row.ingredient_id"
                                                    x-text="ing.name + ' (' + ing.unit + ') - Rp ' + ing.cost.toLocaleString('id-ID') + '/' + ing.unit"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Input Takaran Amount -->
                                <div class="w-32 flex items-center gap-1.5">
                                    <input type="number" step="0.01" min="0.01" required
                                           :name="'recipes[' + idx + '][amount]'"
                                           x-model="row.amount"
                                           placeholder="Takaran"
                                           class="w-full bg-white border border-[#E4DCCC] rounded-lg px-2.5 py-2 text-xs font-mono text-right text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
                                    <span class="font-mono text-xs text-[#8A7B66] font-semibold w-10 shrink-0 truncate"
                                          x-text="getIngredient(row.ingredient_id) ? getIngredient(row.ingredient_id).unit : ''"></span>
                                </div>

                                <!-- Biaya Bahan per Porsi -->
                                <div class="w-24 text-right font-mono text-xs text-[#5F7F42] font-bold">
                                    <span x-text="'Rp ' + Math.round((getIngredient(row.ingredient_id) ? getIngredient(row.ingredient_id).cost : 0) * (parseFloat(row.amount) || 0)).toLocaleString('id-ID')"></span>
                                </div>

                                <!-- Hapus Baris -->
                                <button type="button" @click="removeIngredientRow(idx)"
                                        class="w-8 h-8 rounded-lg text-red-600 hover:bg-red-50 flex items-center justify-center font-bold text-sm transition">
                                    ✕
                                </button>
                            </div>
                        </template>

                        <button type="button" @click="addIngredientRow()"
                                class="w-full py-2.5 rounded-xl bg-[#FAF7F2] border-2 border-dashed border-[#D9973E]/50 hover:border-[#D9973E] text-[#D9973E] hover:bg-[#D9973E]/5 font-mono text-xs font-bold transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Tambah Bahan Baku Lain</span>
                        </button>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-[#FAF7F2] border-t border-[#E4DCCC] flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                    <span class="text-[11px] font-mono text-[#8A7B66] flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-[#D9973E]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Perubahan resep otomatis memotong stok pada pesanan baru.
                    </span>
                    <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                        <button type="button" @click="showRecipeModal = false"
                                class="px-4 py-2.5 rounded-xl bg-white border border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] hover:bg-[#FAF7F2] font-mono text-xs font-semibold transition">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-[#1F1812] hover:bg-[#D9973E] text-[#FAF7F2] hover:text-[#1F1812] font-mono text-xs font-bold uppercase tracking-wider transition shadow-sm">
                            Simpan Resep BOM
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
