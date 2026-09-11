@extends('kasir.app')

@section('title', 'Terminal Kasir')

@section('content')
<div x-data="pos()" class="flex flex-col lg:flex-row h-full w-full overflow-hidden select-none">

    <!-- 1. PANEL KIRI: GRID KATEGORI MODEL NAVBAR (TABLET POS CATEGORY GRID) -->
    <div class="w-full lg:w-48 xl:w-52 bg-[#EFE9DE] border-b lg:border-b-0 lg:border-r border-[#E4DCCC] flex flex-col shrink-0 overflow-y-auto">
        <!-- Header Panel Kategori -->
        <div class="p-3 border-b border-[#E4DCCC] bg-[#E8E1D5] flex items-center justify-between">
            <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold text-[#8A7B66]">Kategori</span>
            <span class="font-mono text-[10px] bg-[#D9973E] text-[#1F1812] px-1.5 py-0.5 font-bold" x-text="menus.length"></span>
        </div>

        <!-- Grid Kategori Tombol Tablet -->
        <div class="p-2 grid grid-cols-4 sm:grid-cols-7 lg:grid-cols-1 gap-1.5 flex-1 overflow-y-auto">
            <!-- Tombol SEMUA -->
            <button @click="cat = 'all'"
                    type="button"
                    :class="cat === 'all' ? 'bg-[#1F1812] text-[#F7F3EC] border-[#1F1812] shadow-md' : 'bg-white text-[#2A211A] border-[#E4DCCC] hover:border-[#B5762A] hover:bg-white/80'"
                    class="w-full text-left p-2.5 border flex items-center justify-between transition-all duration-150 active:scale-95 group">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-base shrink-0">✨</span>
                    <span class="font-mono text-xs uppercase tracking-wider font-medium truncate">Semua</span>
                </div>
                <span :class="cat === 'all' ? 'text-[#D9973E]' : 'text-[#8A7B66]'"
                      class="font-mono text-[10px] hidden lg:inline"
                      x-text="menus.length"></span>
            </button>

            @php
                $categoryIcons = [
                    'kopi' => '☕',
                    'non-kopi' => '🍫',
                    'cocktail' => '🍸',
                    'mocktail' => '🍹',
                    'tea-herbal' => '🍵',
                    'snack' => '🍟',
                    'pastry' => '🥐',
                ];
            @endphp

            @foreach($categories as $category)
                @php
                    $icon = $categoryIcons[$category->slug] ?? '🍽️';
                @endphp
                <button @click="cat = {{ $category->id }}"
                        type="button"
                        :class="cat === {{ $category->id }} ? 'bg-[#1F1812] text-[#F7F3EC] border-[#1F1812] shadow-md' : 'bg-white text-[#2A211A] border-[#E4DCCC] hover:border-[#B5762A] hover:bg-white/80'"
                        class="w-full text-left p-2.5 border flex items-center justify-between transition-all duration-150 active:scale-95 group">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-base shrink-0">{{ $icon }}</span>
                        <span class="font-mono text-xs uppercase tracking-wider font-medium truncate">{{ $category->name }}</span>
                    </div>
                    <span :class="cat === {{ $category->id }} ? 'text-[#D9973E]' : 'text-[#8A7B66]'"
                          class="font-mono text-[10px] hidden lg:inline">
                        {{ $category->menus_count }}
                    </span>
                </button>
            @endforeach
        </div>

        <!-- Status Cepat di Bawah Panel Kiri -->
        <div class="p-3 border-t border-[#E4DCCC] bg-[#E8E1D5] hidden lg:block text-center">
            <div class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66]">Mode Kasir Cepat</div>
            <div class="font-mono text-[9px] text-[#A89A85] mt-0.5">Ketuk item untuk tambah</div>
        </div>
    </div>

    <!-- 2. PANEL TENGAH: SEARCH & GRID DAFTAR MENU (TABLET TOUCH GRID) -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-[#F7F3EC]">

        <!-- Top Search Bar & Active Category Status -->
        <div class="p-3 border-b border-[#E4DCCC] bg-white flex items-center gap-3 shrink-0">
            <div class="relative flex-1">
                <svg class="w-4 h-4 text-[#8A7B66] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input x-model="search"
                       type="text"
                       placeholder="Cari menu (kopi, latte, matcha, cocktail, pastry)..."
                       class="w-full pl-10 pr-9 py-2 bg-[#F7F3EC] border border-[#E4DCCC] text-sm text-[#2A211A] placeholder-[#8A7B66] focus:outline-none focus:border-[#B5762A] focus:bg-white transition-colors">
                <button x-show="search"
                        @click="search = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8A7B66] hover:text-[#2A211A] text-sm font-bold">
                    ✕
                </button>
            </div>
            <div class="hidden sm:flex items-center gap-2 shrink-0">
                <span class="font-mono text-xs text-[#8A7B66]" x-text="filteredMenus.length + ' menu'"></span>
            </div>
        </div>

        <!-- Grid Menu dengan Foto Resolusi Tinggi -->
        <div class="flex-1 p-3 overflow-y-auto">
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                <template x-for="menu in filteredMenus" :key="menu.id">
                    <button @click="add(menu.id)"
                            :disabled="!menu.available"
                            class="group text-left bg-white border border-[#E4DCCC] overflow-hidden flex flex-col justify-between transition-all duration-150 hover:border-[#B5762A] hover:shadow-md active:scale-[0.98]"
                            :class="!menu.available ? 'opacity-45 cursor-not-allowed' : 'cursor-pointer'">

                        <!-- Gambar Menu -->
                        <div class="relative w-full h-28 sm:h-32 bg-[#1F1812] overflow-hidden shrink-0">
                            <template x-if="menu.image">
                                <img :src="menu.image"
                                     :alt="menu.name"
                                     loading="lazy"
                                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                            </template>
                            <template x-if="!menu.image">
                                <div class="w-full h-full flex items-center justify-center bg-[#1F1812]">
                                    <span class="font-mono text-2xl text-[#A89A85]" x-text="menu.name.substring(0, 2).toUpperCase()"></span>
                                </div>
                            </template>

                            <!-- Badge Kategori -->
                            <div class="absolute top-2 left-2 bg-[#1F1812]/80 backdrop-blur-xs px-2 py-0.5 text-[9px] font-mono uppercase tracking-wider text-[#F7F3EC] border border-[#3A3026]">
                                <span x-text="menu.category_name"></span>
                            </div>

                            <!-- Badge Status Ketersediaan & Quick Stock Toggle -->
                            <div class="absolute top-2 right-2">
                                <button type="button"
                                        @click.stop="toggleStock(menu)"
                                        :title="menu.available ? 'Klik untuk tandai Stok Habis' : 'Klik untuk aktifkan (Stok Tersedia)'"
                                        class="px-1.5 py-0.5 font-mono text-[9px] uppercase font-bold tracking-wider transition border shadow-xs flex items-center gap-1"
                                        :class="menu.available ? 'bg-black/60 hover:bg-[#C4553D] text-[#5F7F42] hover:text-white border-white/20' : 'bg-[#C4553D] hover:bg-[#5F7F42] text-white border-[#C4553D]'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="menu.available ? 'bg-[#5F7F42]' : 'bg-white'"></span>
                                    <span x-text="menu.available ? 'Tersedia' : 'Habis'"></span>
                                </button>
                            </div>
                        </div>

                        <!-- Detail Menu -->
                        <div class="p-3 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="text-sm font-medium leading-snug line-clamp-1 group-hover:text-[#B5762A] transition-colors" x-text="menu.name"></h3>
                                <p class="mt-1 text-[11px] text-[#8A7B66] line-clamp-1" :title="menu.description || ''" x-text="menu.description || '-'"></p>
                            </div>
                            <div class="mt-3 flex items-baseline justify-between border-t border-[#E4DCCC]/60 pt-2">
                                <span class="font-mono text-sm font-semibold text-[#B5762A]" x-text="fmt(menu.price)"></span>
                                <span class="font-mono text-[10px] uppercase text-[#5F7F42] font-medium">+ Tambah</span>
                            </div>
                        </div>
                    </button>
                </template>
            </div>

            <!-- Kondisi jika tidak ditemukan menu dari pencarian -->
            <template x-if="filteredMenus.length === 0">
                <div class="py-16 text-center text-[#8A7B66]">
                    <div class="text-3xl mb-2">🔍</div>
                    <div class="font-mono text-sm uppercase tracking-wider">Menu tidak ditemukan</div>
                    <p class="text-xs text-[#A89A85] mt-1">Coba kata kunci pencarian lain atau pilih kategori Semua.</p>
                </div>
            </template>
        </div>
    </div>

    <!-- 3. PANEL KANAN: KERANJANG & CHECKOUT POS (ORDER TICKET) -->
    <div class="w-full lg:w-80 xl:w-96 bg-[#1F1812] text-[#F7F3EC] flex flex-col shrink-0 h-full border-t lg:border-t-0 lg:border-l border-[#3A3026]">

        <!-- Header Tiket Pesanan -->
        <div class="p-4 border-b border-[#3A3026] flex items-center justify-between bg-[#19130E] shrink-0">
            <div>
                <span class="font-mono text-[11px] uppercase tracking-[0.25em] font-semibold text-[#D9973E]">TIKET PESANAN</span>
                <div class="font-mono text-[10px] text-[#A89A85]" x-text="items.reduce((s, i) => s + i.qty, 0) + ' item dipilih'"></div>
            </div>
            <button x-show="items.length > 0"
                    @click="clearCart()"
                    class="font-mono text-[10px] uppercase tracking-wider text-[#C4553D] hover:underline">
                Kosongkan
            </button>
        </div>

        <!-- Switcher Tipe Pesanan & Nama Pelanggan -->
        <div class="p-3 border-b border-[#3A3026] bg-[#221B15] space-y-2 shrink-0">
            <div class="grid grid-cols-2 gap-1.5 p-1 bg-[#19130E] border border-[#3A3026]">
                <button type="button"
                        @click="orderType = 'dine_in'"
                        :class="orderType === 'dine_in' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow' : 'text-[#A89A85] hover:text-[#F7F3EC]'"
                        class="py-1.5 text-center font-mono text-[11px] uppercase tracking-wider transition-all">
                    Dine In
                </button>
                <button type="button"
                        @click="orderType = 'take_away'"
                        :class="orderType === 'take_away' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow' : 'text-[#A89A85] hover:text-[#F7F3EC]'"
                        class="py-1.5 text-center font-mono text-[11px] uppercase tracking-wider transition-all">
                    Take Away
                </button>
            </div>

            <div class="relative">
                <input x-model="customerName"
                       type="text"
                       placeholder="Nama Pelanggan (opsional / no meja)"
                       class="w-full bg-[#19130E] border border-[#3A3026] text-[#F7F3EC] placeholder-[#A89A85] px-3 py-1.5 text-xs focus:outline-none focus:border-[#D9973E]">
            </div>
        </div>

        <!-- Daftar Item di Keranjang (Scrollable) -->
        <div class="flex-1 p-3 overflow-y-auto space-y-2">
            <template x-if="items.length === 0">
                <div class="h-full flex flex-col items-center justify-center text-[#A89A85] py-12">
                    <div class="text-3xl mb-2 opacity-50">🛒</div>
                    <div class="font-mono text-xs uppercase tracking-wider">Keranjang Kosong</div>
                    <div class="text-[11px] mt-1 text-[#6A5E50] text-center px-4">Ketuk menu di sebelah kiri untuk memasukkan pesanan.</div>
                </div>
            </template>

            <template x-for="(item, idx) in items" :key="item.id">
                <div class="bg-[#2A211A] border border-[#3A3026] p-3 transition-colors hover:border-[#D9973E]/50">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-xs font-medium text-[#F7F3EC] leading-snug" x-text="item.name"></span>
                        <button @click="remove(idx)"
                                class="text-[#A89A85] hover:text-[#C4553D] text-sm leading-none p-1"
                                title="Hapus item">✕</button>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <!-- Kontrol Qty Tablet Touch -->
                        <div class="flex items-center border border-[#3A3026] bg-[#1F1812]">
                            <button @click="dec(idx)"
                                    type="button"
                                    class="w-7 h-7 flex items-center justify-center text-[#F7F3EC] hover:bg-[#3A3026] font-mono text-sm active:bg-[#D9973E] active:text-[#1F1812]">−</button>
                            <span class="w-8 text-center font-mono text-xs font-semibold text-[#F7F3EC]" x-text="item.qty"></span>
                            <button @click="inc(idx)"
                                    type="button"
                                    class="w-7 h-7 flex items-center justify-center text-[#F7F3EC] hover:bg-[#3A3026] font-mono text-sm active:bg-[#D9973E] active:text-[#1F1812]">+</button>
                        </div>
                        <div class="text-right">
                            <div class="font-mono text-xs text-[#D9973E] font-medium" x-text="fmt(item.price * item.qty)"></div>
                            <div class="font-mono text-[9px] text-[#A89A85]" x-text="'@ ' + fmt(item.price)"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Bagian Pembayaran & Ringkasan Transaksi (Fixed Bottom) -->
        <div class="p-3 border-t border-[#3A3026] bg-[#19130E] space-y-2.5 shrink-0">

            <!-- Metode Pembayaran -->
            <div>
                <label class="font-mono text-[9px] uppercase tracking-[0.2em] text-[#A89A85] mb-1 block">Metode Pembayaran</label>
                <div class="grid grid-cols-3 gap-1">
                    <button type="button"
                            @click="method = 'cash'; methodChange()"
                            :class="method === 'cash' ? 'bg-[#D9973E] text-[#1F1812] font-bold' : 'bg-[#2A211A] text-[#A89A85] hover:text-[#F7F3EC] border border-[#3A3026]'"
                            class="py-1.5 text-center font-mono text-[10px] uppercase tracking-wider transition-colors">
                        Tunai
                    </button>
                    <button type="button"
                            @click="method = 'qris'; methodChange()"
                            :class="method === 'qris' ? 'bg-[#D9973E] text-[#1F1812] font-bold' : 'bg-[#2A211A] text-[#A89A85] hover:text-[#F7F3EC] border border-[#3A3026]'"
                            class="py-1.5 text-center font-mono text-[10px] uppercase tracking-wider transition-colors">
                        QRIS
                    </button>
                    <button type="button"
                            @click="method = 'debit'; methodChange()"
                            :class="method === 'debit' ? 'bg-[#D9973E] text-[#1F1812] font-bold' : 'bg-[#2A211A] text-[#A89A85] hover:text-[#F7F3EC] border border-[#3A3026]'"
                            class="py-1.5 text-center font-mono text-[10px] uppercase tracking-wider transition-colors">
                        Debit
                    </button>
                </div>
            </div>

            <!-- Subtotal & Diskon -->
            <div class="space-y-1 text-xs">
                <div class="flex items-center justify-between text-[#A89A85]">
                    <span>Subtotal</span>
                    <span class="font-mono text-[#F7F3EC]" x-text="fmt(subtotal)"></span>
                </div>
                <div class="flex items-center justify-between gap-2" x-show="method === 'cash'">
                    <span class="text-[#A89A85] shrink-0">Diskon (Rp)</span>
                    <input x-model.number="discount"
                           @input="syncPaidIfNotCash()"
                           type="number"
                           min="0"
                           :max="subtotal"
                           placeholder="0"
                           class="w-24 bg-[#2A211A] border border-[#3A3026] text-[#F7F3EC] px-2 py-1 text-right font-mono text-xs focus:outline-none focus:border-[#D9973E]">
                </div>
            </div>

            <!-- Quick Cash Denominations (Khusus Pembayaran Tunai Tablet POS) -->
            <div x-show="method === 'cash' && items.length > 0" class="pt-1">
                <div class="grid grid-cols-4 gap-1">
                    <button type="button"
                            @click="setExactPaid()"
                            class="bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#D9973E] py-1 text-center font-mono text-[10px] font-semibold transition-colors">
                        Uang Pas
                    </button>
                    <button type="button"
                            @click="setCash(50000)"
                            class="bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#F7F3EC] py-1 text-center font-mono text-[10px] transition-colors">
                        50k
                    </button>
                    <button type="button"
                            @click="setCash(100000)"
                            class="bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#F7F3EC] py-1 text-center font-mono text-[10px] transition-colors">
                        100k
                    </button>
                    <button type="button"
                            @click="setCash(200000)"
                            class="bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#F7F3EC] py-1 text-center font-mono text-[10px] transition-colors">
                        200k
                    </button>
                </div>
            </div>

            <!-- Total Pembayaran -->
            <div class="border-t border-[#3A3026] pt-2 flex items-baseline justify-between">
                <div>
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">TOTAL AKHIR</span>
                    <div x-show="method === 'cash' && paid > 0" class="font-mono text-[11px] text-[#5F7F42] mt-0.5">
                        Kembalian: <span class="font-bold" x-text="fmt(Math.max(change, 0))"></span>
                    </div>
                </div>
                <div class="font-mono text-xl sm:text-2xl text-[#D9973E] font-bold" x-text="fmt(total)"></div>
            </div>

            <!-- Input Tunai jika Cash -->
            <div class="flex items-center justify-between gap-2" x-show="method === 'cash'">
                <span class="text-[#A89A85] text-xs shrink-0">Diterima (Rp)</span>
                <input x-model.number="paid"
                       type="number"
                       min="0"
                       class="w-32 bg-[#2A211A] border border-[#3A3026] text-[#F7F3EC] px-2 py-1 text-right font-mono text-sm focus:outline-none focus:border-[#D9973E]">
            </div>

            <!-- Notifikasi Error -->
            <p class="text-xs text-[#C4553D] bg-[#C4553D]/10 border border-[#C4553D]/30 p-2" x-show="error" x-text="error"></p>

            <!-- Tombol Proses Checkout (Touch Friendly) -->
            <button @click="submit()"
                    :disabled="items.length === 0 || submitting || (method === 'cash' && paid < total)"
                    type="button"
                    class="w-full bg-[#D9973E] text-[#1F1812] py-3 px-4 font-mono text-xs uppercase tracking-[0.2em] font-bold hover:bg-[#B5762A] hover:text-white transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed shadow-lg active:scale-[0.99] flex items-center justify-center gap-2">
                <span x-show="!submitting">PROSES BAYAR ›</span>
                <span x-show="submitting" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-[#1F1812]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Memproses Transaksi…
                </span>
            </button>
        </div>
    </div>

    <!-- 4. MODAL TRANSAKSI BERHASIL (SEAMLESS POS - MUSIK TIDAK TERPUTUS) -->
    <div x-show="showSuccessModal"
         x-cloak
         @keydown.escape.window="if(showSuccessModal) closeSuccessModal()"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs transition-all duration-200">
        <div class="bg-[#1F1812] border border-[#3A3026] text-[#F7F3EC] w-full max-w-lg shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200"
             @click.away="closeSuccessModal()">
            
            <!-- Header Modal -->
            <div class="p-4 bg-[#2A211A] border-b border-[#3A3026] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-[#5F7F42]/20 border border-[#5F7F42] flex items-center justify-center text-[#5F7F42] text-lg font-bold shrink-0">
                        ✓
                    </div>
                    <div>
                        <h3 class="font-mono text-sm uppercase tracking-wider font-bold text-[#F7F3EC]">Transaksi Berhasil</h3>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="font-mono text-xs text-[#D9973E] font-bold" x-text="completedOrder?.code"></span>
                            <span class="text-[#8A7B66] text-xs">•</span>
                            <span class="text-xs text-[#A89A85]" x-text="completedOrder?.order_type === 'dine_in' ? 'Makan di Tempat' : 'Bawa Pulang'"></span>
                            <template x-if="completedOrder?.customer_name">
                                <span class="text-xs text-[#A89A85]" x-text="'(' + completedOrder.customer_name + ')'"></span>
                            </template>
                        </div>
                    </div>
                </div>
                <button type="button"
                        @click="closeSuccessModal()"
                        class="text-[#8A7B66] hover:text-[#F7F3EC] p-1.5 transition-colors text-lg font-bold cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- Body Modal -->
            <div class="p-5 space-y-4">
                <!-- Banner Kembalian (Highlight Besar) -->
                <div class="bg-[#2A211A] border border-[#3A3026] p-4 text-center rounded-xs">
                    <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">KEMBALIAN PELANGGAN</div>
                    <div class="font-mono text-3xl font-bold text-[#5F7F42] mt-1" x-text="fmt(completedOrder?.change_amount || 0)"></div>
                    <div class="font-mono text-xs text-[#A89A85] mt-2 flex items-center justify-center gap-4">
                        <span>Total: <b class="text-[#F7F3EC]" x-text="fmt(completedOrder?.total || 0)"></b></span>
                        <span>•</span>
                        <span>Bayar (<span x-text="completedOrder?.payment_method?.toUpperCase()"></span>): <b class="text-[#F7F3EC]" x-text="fmt(completedOrder?.paid_amount || 0)"></b></span>
                    </div>
                </div>

                <!-- Card Musik Request Code -->
                <div class="bg-[#2A211A]/80 border border-[#D9973E]/30 p-3.5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="text-2xl shrink-0">🎵</span>
                        <div class="min-w-0">
                            <div class="font-mono text-[10px] uppercase tracking-wider text-[#D9973E] font-bold">Kode Request Musik</div>
                            <div class="text-[11px] text-[#A89A85] truncate">Pelanggan dapat memindai QR di struk atau masukkan kode:</div>
                        </div>
                    </div>
                    <div class="font-mono text-base font-black px-3 py-1 bg-[#1F1812] border border-[#D9973E] text-[#D9973E] tracking-widest shrink-0" x-text="completedOrder?.music_code || '-'"></div>
                </div>

                <!-- Status Audio & Background Print -->
                <div class="flex items-center gap-2 text-xs text-[#8A7B66] bg-[#1F1812] border border-[#3A3026] px-3 py-2">
                    <span class="text-base">🖨️</span>
                    <span class="leading-tight">
                        Struk otomatis dikirim ke printer di background. <b class="text-[#5F7F42]">Musik kafe tetap mengalun tanpa jeda.</b>
                    </span>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="p-4 bg-[#2A211A] border-t border-[#3A3026] flex items-center justify-between gap-2.5">
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="reprintReceipt()"
                            title="Cetak ulang struk thermal"
                            class="px-3 py-2 bg-[#1F1812] hover:bg-[#3A3026] border border-[#3A3026] text-[#F7F3EC] font-mono text-xs uppercase tracking-wider transition-colors flex items-center gap-1.5 cursor-pointer">
                        <span>🖨️</span>
                        <span>Cetak Ulang</span>
                    </button>
                    <button type="button"
                            @click="if(completedOrder?.receipt_url) window.open(completedOrder.receipt_url, '_blank')"
                            title="Buka struk di tab baru"
                            class="px-3 py-2 bg-[#1F1812] hover:bg-[#3A3026] border border-[#3A3026] text-[#8A7B66] hover:text-[#F7F3EC] font-mono text-xs uppercase tracking-wider transition-colors flex items-center gap-1 cursor-pointer">
                        <span>Lihat Struk</span>
                        <span>↗</span>
                    </button>
                </div>
                <button type="button"
                        @click="closeSuccessModal()"
                        class="flex-1 max-w-[200px] bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] font-mono text-xs uppercase tracking-[0.15em] font-bold py-2.5 px-4 transition-colors shadow-md text-center cursor-pointer">
                    ✓ Selesai (ESC)
                </button>
            </div>

        </div>
    </div>
</div>

<script>
function pos() {
    return {
        menus: @json($menuData),
        cat: 'all',
        search: '',
        items: [],
        orderType: 'dine_in',
        method: 'cash',
        discount: 0,
        paid: 0,
        customerName: '',
        error: '',
        submitting: false,
        showSuccessModal: false,
        completedOrder: null,

        get filteredMenus() {
            return this.menus.filter(m => {
                const matchCat = this.cat === 'all' || m.category_id === this.cat;
                const query = this.search.trim().toLowerCase();
                if (!query) return matchCat;
                const matchName = m.name.toLowerCase().includes(query);
                const matchDesc = m.description && m.description.toLowerCase().includes(query);
                const matchCatName = m.category_name && m.category_name.toLowerCase().includes(query);
                return matchCat && (matchName || matchDesc || matchCatName);
            });
        },

        get subtotal() { return this.items.reduce((s, i) => s + i.price * i.qty, 0); },
        get total() { return Math.max(this.subtotal - (parseInt(this.discount) || 0), 0); },
        get change() { return (parseInt(this.paid) || 0) - this.total; },

        fmt(v) { return 'Rp ' + (v || 0).toLocaleString('id-ID'); },

        add(id) {
            const m = this.menus.find(x => x.id === id);
            if (!m || !m.available) return;
            const found = this.items.find(i => i.id === id);
            if (found) {
                found.qty++;
            } else {
                this.items.push({ id: m.id, name: m.name, price: m.price, qty: 1 });
            }
            this.error = '';
            if (this.method === 'cash' && this.paid < this.total) {
                this.paid = this.total;
            }
        },
        inc(idx) {
            this.items[idx].qty++;
            if (this.method === 'cash' && this.paid < this.total) this.paid = this.total;
        },
        dec(idx) {
            this.items[idx].qty--;
            if (this.items[idx].qty <= 0) this.items.splice(idx, 1);
            if (this.method === 'cash' && this.paid > this.total && this.items.length === 0) this.paid = 0;
        },
        remove(idx) {
            this.items.splice(idx, 1);
            if (this.items.length === 0) {
                this.discount = 0;
                this.paid = 0;
            }
        },
        async clearCart() {
            const ok = await window.customConfirm({
                title: 'Kosongkan Pesanan',
                message: 'Hapus semua pesanan ini?',
                type: 'danger',
                confirmText: 'Kosongkan',
                cancelText: 'Batal'
            });
            if (ok) {
                this.items = [];
                this.discount = 0;
                this.paid = 0;
            }
        },
        setExactPaid() {
            this.paid = this.total;
        },
        async toggleStock(menu) {
            try {
                const res = await fetch('{{ url('/kasir/menu') }}/' + menu.id + '/toggle', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    menu.available = data.is_available;
                    if (!menu.available) {
                        const idx = this.items.findIndex(i => i.id === menu.id);
                        if (idx !== -1) {
                            this.items.splice(idx, 1);
                        }
                    }
                    if (window.customToast) {
                        window.customToast({
                            message: data.message,
                            type: menu.available ? 'success' : 'warning'
                        });
                    }
                }
            } catch (e) {
                console.error('Toggle stock error:', e);
            }
        },
        setCash(amount) {
            this.paid = amount;
        },
        syncPaidIfNotCash() {
            if (this.method !== 'cash') {
                this.paid = this.total;
            }
        },
        methodChange() {
            if (this.method !== 'cash') {
                this.paid = this.total;
                this.discount = 0;
            } else {
                this.paid = this.total;
            }
        },

        async submit() {
            if (this.items.length === 0) return;
            this.submitting = true;
            this.error = '';
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch(@js(route('kasir.orders.store', [], false)), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        items: this.items.map(i => ({ menu_id: i.id, qty: i.qty })),
                        order_type: this.orderType,
                        payment_method: this.method,
                        discount: parseInt(this.discount) || 0,
                        paid_amount: parseInt(this.paid) || 0,
                        customer_name: this.customerName || null,
                    }),
                });

                let data = null;
                const contentType = res.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    try {
                        data = await res.json();
                    } catch (jsonErr) {
                        data = null;
                    }
                }

                if (!res.ok) {
                    if (res.status === 419) {
                        this.error = 'Sesi telah kedaluwarsa. Silakan refresh halaman dan ulangi proses bayar.';
                    } else if (data && data.message) {
                        this.error = data.message;
                    } else {
                        this.error = 'Gagal memproses transaksi (HTTP ' + res.status + ').';
                    }
                    return;
                }

                if (data && data.receipt_url) {
                    this.completedOrder = data;
                    this.showSuccessModal = true;
                    // Cetak struk otomatis di background via hidden iframe tanpa reload / navigasi halaman
                    this.printReceiptSilently(data.receipt_url);
                    // Reset keranjang untuk transaksi berikutnya
                    this.items = [];
                    this.discount = 0;
                    this.paid = 0;
                    this.customerName = '';
                } else {
                    this.error = 'Respon server tidak valid atau data struk kosong.';
                }
            } catch (e) {
                console.error('POS Checkout Error:', e);
                this.error = 'Terjadi kesalahan jaringan atau koneksi diblokir: ' + (e.message || 'Silakan muat ulang halaman.');
            } finally {
                this.submitting = false;
            }
        },

        printReceiptSilently(url) {
            let frame = document.getElementById('pos-receipt-frame');
            if (!frame) {
                frame = document.createElement('iframe');
                frame.id = 'pos-receipt-frame';
                frame.style.position = 'fixed';
                frame.style.right = '0';
                frame.style.bottom = '0';
                frame.style.width = '1px';
                frame.style.height = '1px';
                frame.style.opacity = '0.01';
                frame.style.border = '0';
                frame.style.pointerEvents = 'none';
                document.body.appendChild(frame);
            }
            frame.src = 'about:blank';
            setTimeout(() => {
                frame.src = url;
            }, 50);
        },

        reprintReceipt() {
            if (this.completedOrder && this.completedOrder.receipt_url) {
                this.printReceiptSilently(this.completedOrder.receipt_url);
                if (window.customToast) {
                    window.customToast({
                        message: 'Mengirim perintah cetak struk...',
                        type: 'info'
                    });
                }
            }
        },

        closeSuccessModal() {
            this.showSuccessModal = false;
            this.completedOrder = null;
        },
    };
}
</script>
@endsection

