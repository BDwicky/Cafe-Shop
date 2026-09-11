@extends('kasir.app')

@section('title', 'Resep Menu & Bill of Materials (BOM)')

@section('content')
<div class="space-y-6" x-data="{
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

    <!-- 1. HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-[#E4DCCC]">
        <div>
            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                Resep Menu & Bill of Materials (BOM)
            </h1>
            <p class="font-mono text-xs text-[#8A7B66] mt-0.5">
                Konfigurasi takaran bahan per porsi menu. Setiap transaksi di kasir akan otomatis memotong stok bahan dan mencatat HPP.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('kasir.inventory.index') }}"
               class="px-3.5 py-2 bg-white border border-[#E4DCCC] hover:border-[#D9973E] text-[#1F1812] font-mono text-xs uppercase tracking-wider font-semibold transition shadow-xs flex items-center gap-1.5">
                <span>‹ Master Bahan Baku</span>
            </a>
        </div>
    </div>

    <!-- FLASH STATUS -->
    @if (session('success'))
        <div class="p-3.5 bg-[#5F7F42]/10 border border-[#5F7F42] text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-[#5F7F42] font-bold text-sm">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
        </div>
    @endif

    <!-- 2. FILTER & PENCARIAN -->
    <div class="bg-white border border-[#E4DCCC] p-4">
        <form method="GET" action="{{ route('kasir.inventory.recipes') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama menu..."
                       class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
            </div>

            <div class="w-48">
                <select name="category_id" onchange="this.form.submit()"
                        class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
                    <option value="all">Semua Kategori Menu</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string) $categoryId === (string) $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="px-4 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold transition">
                Filter
            </button>
            @if ($search !== '' || $categoryId !== 'all')
                <a href="{{ route('kasir.inventory.recipes') }}"
                   class="px-3 py-2 bg-[#F7F3EC] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- 3. LIST MENU & RESEP BOM -->
    <div class="space-y-3">
        @forelse ($menus as $menu)
            @php
                $hpp = $menu->calculateHpp();
                $margin = $menu->margin_percent;
                $hasRecipe = $menu->recipes->count() > 0;
            @endphp
            <div class="bg-white border border-[#E4DCCC] p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/60 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Info Menu -->
                <div class="flex items-start gap-4">
                    @if ($menu->image)
                        <img src="{{ asset('storage/'.$menu->image) }}" alt="{{ $menu->name }}" class="w-14 h-14 object-cover border border-[#E4DCCC] shrink-0">
                    @else
                        <div class="w-14 h-14 bg-[#F7F3EC] border border-[#E4DCCC] flex items-center justify-center text-xl shrink-0">
                            ☕
                        </div>
                    @endif

                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-serif font-bold text-lg text-[#1F1812]">{{ $menu->name }}</h3>
                            <span class="px-2 py-0.5 bg-[#F7F3EC] border border-[#E4DCCC] text-[10px] font-mono text-[#8A7B66]">
                                {{ $menu->category->name ?? '-' }}
                            </span>
                            @if (! $menu->is_available)
                                <span class="px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-mono font-bold uppercase">
                                    Stok Habis
                                </span>
                            @endif
                        </div>
                        <div class="font-mono text-xs text-[#8A7B66] mt-1 flex flex-wrap items-center gap-3">
                            <span>Harga Jual: <strong class="text-[#1F1812]">Rp {{ number_format($menu->price, 0, ',', '.') }}</strong></span>
                            <span>•</span>
                            <span>HPP Modal: <strong class="{{ $hasRecipe ? 'text-[#5F7F42]' : 'text-[#8A7B66]' }}">Rp {{ number_format($hpp, 0, ',', '.') }}</strong></span>
                            <span>•</span>
                            <span>Margin Profit: <strong class="{{ $margin > 50 ? 'text-[#5F7F42]' : 'text-[#D9973E]' }}">{{ $margin }}%</strong></span>
                        </div>

                        <!-- Ringkasan Bahan BOM -->
                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                            @if ($hasRecipe)
                                @foreach ($menu->recipes as $rcp)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-[#F7F3EC] border border-[#E4DCCC] font-mono text-[10px] text-[#1F1812]">
                                        <span class="font-bold">{{ $rcp->ingredient->name ?? 'Bahan' }}</span>:
                                        <span>{{ (float) $rcp->amount }} {{ $rcp->ingredient->unit ?? '' }}</span>
                                    </span>
                                @endforeach
                            @else
                                <span class="font-mono text-[11px] text-amber-700 bg-amber-50 px-2 py-0.5 border border-amber-200">
                                    ⚠️ Belum ada resep BOM (stok bahan tidak terpotong otomatis)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tombol Atur Resep -->
                <div class="shrink-0 flex items-center justify-end">
                    <button type="button"
                            @click="openRecipeEditor({{ json_encode($menu) }}, {{ json_encode($menu->recipes) }})"
                            class="px-4 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold transition shadow-xs flex items-center gap-1.5">
                        <span>⚙️ {{ $hasRecipe ? 'Ubah Resep BOM' : '+ Atur Resep BOM' }}</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-white border border-[#E4DCCC] p-12 text-center text-[#8A7B66]">
                <div class="text-3xl mb-2">🍽️</div>
                <div class="font-serif text-base font-bold text-[#1F1812]">Tidak ada menu ditemukan</div>
            </div>
        @endforelse

        @if ($menus->hasPages())
            <div class="p-4 bg-white border border-[#E4DCCC]">
                {{ $menus->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: ATUR RESEP BOM MENU -->
    <div x-show="showRecipeModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showRecipeModal = false">
        <div class="bg-white border border-[#3A3026] w-full max-w-2xl shadow-2xl p-6 relative max-h-[90vh] flex flex-col"
             @click.away="showRecipeModal = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-[#E4DCCC]">
                <div>
                    <h3 class="font-serif font-bold text-xl text-[#1F1812]">Atur Resep BOM (Bill of Materials)</h3>
                    <p class="font-mono text-xs text-[#D9973E] font-semibold mt-0.5" x-text="selectedMenu ? selectedMenu.name : ''"></p>
                </div>
                <button type="button" @click="showRecipeModal = false" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
            </div>

            <!-- Form update resep -->
            <form method="POST" :action="'/kasir/inventory/recipes/' + (selectedMenu ? selectedMenu.id : '')"
                  class="mt-4 flex-1 flex flex-col min-h-0">
                @csrf
                @method('PUT')

                <!-- Ringkasan Live Kalkulasi HPP -->
                <div class="p-3.5 bg-[#F7F3EC] border border-[#E4DCCC] font-mono text-xs grid grid-cols-3 gap-3 mb-4 shrink-0">
                    <div>
                        <div class="text-[10px] text-[#8A7B66] uppercase">Harga Jual</div>
                        <div class="font-bold text-sm text-[#1F1812]" x-text="'Rp ' + (selectedMenu ? selectedMenu.price.toLocaleString('id-ID') : 0)"></div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[#8A7B66] uppercase">Total HPP Modal</div>
                        <div class="font-bold text-sm text-[#5F7F42]" x-text="'Rp ' + calculateHpp().toLocaleString('id-ID')"></div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[#8A7B66] uppercase">Margin Keuntungan</div>
                        <div class="font-bold text-sm text-[#D9973E]" x-text="calculateMargin() + '%'"></div>
                    </div>
                </div>

                <!-- Daftar Baris Bahan (Scrollable) -->
                <div class="flex-1 overflow-y-auto space-y-2.5 pr-1">
                    <div class="text-[11px] font-mono font-semibold uppercase tracking-wider text-[#8A7B66] pb-1 border-b border-[#E4DCCC]">
                        Bahan yang Dipakai per Porsi:
                    </div>

                    <template x-for="(row, idx) in menuRecipes" :key="idx">
                        <div class="flex items-center gap-2 bg-[#F7F3EC]/50 p-2 border border-[#E4DCCC]">
                            <!-- Pilih Bahan -->
                            <div class="flex-1">
                                <select :name="'recipes[' + idx + '][ingredient_id]'" x-model="row.ingredient_id"
                                        class="w-full bg-white border border-[#E4DCCC] px-2.5 py-1.5 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
                                    <template x-for="ing in allIngredients" :key="ing.id">
                                        <option :value="ing.id" :selected="ing.id == row.ingredient_id"
                                                x-text="ing.name + ' (' + ing.unit + ') - Rp ' + ing.cost.toLocaleString('id-ID') + '/' + ing.unit"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Input Takaran Amount -->
                            <div class="w-28 flex items-center gap-1">
                                <input type="number" step="0.01" min="0.01" required
                                       :name="'recipes[' + idx + '][amount]'"
                                       x-model="row.amount"
                                       placeholder="Takaran"
                                       class="w-full bg-white border border-[#E4DCCC] px-2 py-1.5 text-xs font-mono text-right text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
                                <span class="font-mono text-xs text-[#8A7B66] w-8 shrink-0"
                                      x-text="getIngredient(row.ingredient_id) ? getIngredient(row.ingredient_id).unit : ''"></span>
                            </div>

                            <!-- Biaya Bahan per Porsi -->
                            <div class="w-24 text-right font-mono text-xs text-[#5F7F42] font-semibold">
                                <span x-text="'Rp ' + Math.round((getIngredient(row.ingredient_id) ? getIngredient(row.ingredient_id).cost : 0) * (parseFloat(row.amount) || 0)).toLocaleString('id-ID')"></span>
                            </div>

                            <!-- Hapus Baris -->
                            <button type="button" @click="removeIngredientRow(idx)"
                                    class="p-1.5 text-red-600 hover:bg-red-50 text-xs font-bold transition">
                                ✕
                            </button>
                        </div>
                    </template>

                    <button type="button" @click="addIngredientRow()"
                            class="w-full py-2 bg-white border border-dashed border-[#D9973E] text-[#D9973E] hover:bg-[#D9973E]/10 font-mono text-xs font-bold transition">
                        + Tambah Bahan Lain
                    </button>
                </div>

                <div class="pt-4 flex items-center justify-between border-t border-[#E4DCCC] mt-4 shrink-0">
                    <span class="text-[11px] font-mono text-[#8A7B66]">
                        * Perubahan resep akan langsung berlaku pada order berikutnya.
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showRecipeModal = false"
                                class="px-4 py-2 bg-[#F7F3EC] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs font-bold uppercase tracking-wider transition">
                            Simpan Resep BOM
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
