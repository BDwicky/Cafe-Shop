@extends('kasir.app')

@section('title', 'Terminal Kasir')

@section('content')
<div x-data="pos()" class="flex-1 min-h-0 h-full w-full flex flex-col md:flex-row overflow-hidden select-none">

    <!-- 1. PANEL KIRI: DAFTAR KATEGORI POS (VERTICAL SIDEBAR) -->
    <div class="w-48 sm:w-52 xl:w-56 bg-[#EFE9DE] border-r border-[#E4DCCC] flex flex-col shrink-0 h-full">
        
        <!-- Header Panel Kategori dengan Tombol Navigasi Overlay -->
        <div class="p-3 sm:p-3.5 min-h-[65px] border-b border-[#E4DCCC] bg-[#E8E1D5] flex items-center justify-between gap-2 shrink-0">
            <div class="flex items-center gap-2.5 min-w-0">
                <button type="button"
                        @click="window.dispatchEvent(new CustomEvent('toggle-terminal-sidebar'))"
                        title="Buka Navigasi Utama Kasir (Alt+M)"
                        class="p-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] rounded-lg transition shadow-xs flex items-center justify-center shrink-0 cursor-pointer active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="min-w-0">
                    <span class="font-sans text-xs sm:text-[13px] font-bold uppercase tracking-wider text-[#1F1812] block leading-tight">Kategori</span>
                    <span class="font-mono text-[10px] text-[#8A7B66] block leading-tight mt-0.5">Pilih menu</span>
                </div>
            </div>
            
            <span class="font-mono text-[10.5px] sm:text-xs bg-[#D9973E] text-[#1F1812] px-2.5 py-1 rounded-full font-bold shrink-0 shadow-2xs" x-text="menus.length + ' item'"></span>
        </div>

        @php
            $categoryColors = [
                'all' => [
                    'svg' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>',
                    'color' => '#D9973E',
                    'bg_light' => '#FDF4E7',
                    'text_light' => '#B4721D',
                    'border_light' => '#F5DCB8',
                ],
                'kopi' => [
                    'svg' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 1v3M10 1v3M14 1v3"/></svg>',
                    'color' => '#8B4513',
                    'bg_light' => '#F8EFEA',
                    'text_light' => '#783A0F',
                    'border_light' => '#E8CEBE',
                ],
                'non-kopi' => [
                    'svg' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11l-2 9H7l-2-9h14zM5 11V7a2 2 0 012-2h10a2 2 0 012 2v4M14 2l-2 5"/></svg>',
                    'color' => '#7C3AED',
                    'bg_light' => '#F5F3FF',
                    'text_light' => '#6D28D9',
                    'border_light' => '#DDD6FE',
                ],
                'cocktail' => [
                    'svg' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h14l-7 8-7-8zM12 12v7M8 19h8"/></svg>',
                    'color' => '#E11D48',
                    'bg_light' => '#FFF1F2',
                    'text_light' => '#BE123C',
                    'border_light' => '#FECDD3',
                ],
                'mocktail' => [
                    'svg' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4h10l-1.5 15a2 2 0 01-2 2h-3a2 2 0 01-2-2L7 4zM6 8h12M14 2l-2 6"/></svg>',
                    'color' => '#EA580C',
                    'bg_light' => '#FFF7ED',
                    'text_light' => '#C2410C',
                    'border_light' => '#FFEDD5',
                ],
                'tea-herbal' => [
                    'svg' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v18M12 3c-4.5 0-8 3.5-8 8 0 5 4 8 8 8 4.5 0 8-3.5 8-8 0-5-4-8-8-8zM12 8c2.5 0 4.5 1.5 5 4"/></svg>',
                    'color' => '#16A34A',
                    'bg_light' => '#F0FDF4',
                    'text_light' => '#15803D',
                    'border_light' => '#BBF7D0',
                ],
                'snack' => [
                    'svg' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 9h14l-1.5 11a2 2 0 01-2 2H8.5a2 2 0 01-2-2L5 9zM8 9V4M12 9V3M16 9V5"/></svg>',
                    'color' => '#D97706',
                    'bg_light' => '#FEFCE8',
                    'text_light' => '#A16207',
                    'border_light' => '#FEF08A',
                ],
                'pastry' => [
                    'svg' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14a8 8 0 0116 0M4 14h16M7 14v4a2 2 0 002 2h6a2 2 0 002-2v-4M9 10a3 3 0 016 0"/></svg>',
                    'color' => '#B45309',
                    'bg_light' => '#FFFBEB',
                    'text_light' => '#92400E',
                    'border_light' => '#FDE68A',
                ],
            ];
            $defaultColor = [
                'svg' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6a7 7 0 00-7 7h14a7 7 0 00-7-7zM12 3v3M4 17h16a1 1 0 011 1v1H3v-1a1 1 0 011-1z"/></svg>',
                'color' => '#4B5563',
                'bg_light' => '#F3F4F6',
                'text_light' => '#374151',
                'border_light' => '#E5E7EB',
            ];
        @endphp

        <!-- Daftar Tombol Kategori: Selalu Kolom Vertikal Rapi & Ergonomis -->
        <div class="p-2.5 sm:p-3 flex flex-col gap-2 sm:gap-2.5 flex-1 overflow-y-auto">
            <!-- Tombol SEMUA MENU -->
            @php $allMeta = $categoryColors['all']; @endphp
            <button @click="cat = 'all'"
                    type="button"
                    :style="cat === 'all' ? 'border-left: 5px solid {{ $allMeta['color'] }};' : ''"
                    :class="cat === 'all' 
                        ? 'bg-[#1F1812] text-[#F7F3EC] border-[#1F1812] shadow-sm' 
                        : 'bg-white text-[#2A211A] border-[#E4DCCC] hover:border-[#D9973E]/70 hover:bg-[#FAF6EE] shadow-2xs'"
                    class="w-full text-left py-3 sm:py-3.5 px-3 sm:px-3.5 rounded-xl border transition-all duration-150 active:scale-[0.98] group flex items-center justify-between shrink-0 cursor-pointer min-h-[56px] sm:min-h-[60px]">
                <div class="flex items-center gap-3 min-w-0">
                    <span :style="cat === 'all' 
                              ? 'background-color: {{ $allMeta['color'] }}; color: #1F1812;' 
                              : 'background-color: {{ $allMeta['bg_light'] }}; color: {{ $allMeta['text_light'] }}; border: 1px solid {{ $allMeta['border_light'] }};'"
                          class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center shrink-0 transition-all font-bold shadow-2xs">
                        {!! $allMeta['svg'] !!}
                    </span>
                    <div class="min-w-0">
                        <span class="font-sans text-[13.5px] sm:text-sm font-bold tracking-tight block truncate"
                              :class="cat === 'all' ? 'text-[#F7F3EC]' : 'text-[#2A211A] group-hover:text-[#1F1812]'">
                            Semua Menu
                        </span>
                        <span class="font-mono text-[10px] block leading-none mt-0.5"
                              :class="cat === 'all' ? 'text-[#D9973E]' : 'text-[#8A7B66]'">
                            Semua item
                        </span>
                    </div>
                </div>
                <span :style="cat === 'all' 
                          ? 'background-color: {{ $allMeta['color'] }}; color: #1F1812;' 
                          : 'background-color: {{ $allMeta['bg_light'] }}; color: {{ $allMeta['text_light'] }}; border: 1px solid {{ $allMeta['border_light'] }};'"
                      class="font-mono text-[11px] font-bold px-2.5 py-1 rounded-full shrink-0 transition-all shadow-2xs"
                      x-text="menus.length"></span>
            </button>

            <!-- Loop Kategori Dinamis dari Database -->
            @foreach($categories as $category)
                @php
                    $meta = $categoryColors[$category->slug] ?? $defaultColor;
                @endphp
                <button @click="cat = {{ $category->id }}"
                        type="button"
                        :style="cat === {{ $category->id }} ? 'border-left: 5px solid {{ $meta['color'] }};' : ''"
                        :class="cat === {{ $category->id }} 
                            ? 'bg-[#1F1812] text-[#F7F3EC] border-[#1F1812] shadow-sm' 
                            : 'bg-white text-[#2A211A] border-[#E4DCCC] hover:border-[#D9973E]/70 hover:bg-[#FAF6EE] shadow-2xs'"
                        class="w-full text-left py-3 sm:py-3.5 px-3 sm:px-3.5 rounded-xl border transition-all duration-150 active:scale-[0.98] group flex items-center justify-between shrink-0 cursor-pointer min-h-[56px] sm:min-h-[60px]">
                    <div class="flex items-center gap-3 min-w-0">
                        <span :style="cat === {{ $category->id }} 
                                  ? 'background-color: {{ $meta['color'] }}; color: #ffffff;' 
                                  : 'background-color: {{ $meta['bg_light'] }}; color: {{ $meta['text_light'] }}; border: 1px solid {{ $meta['border_light'] }};'"
                              class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center shrink-0 transition-all font-bold shadow-2xs">
                            {!! $meta['svg'] !!}
                        </span>
                        <div class="min-w-0">
                            <span class="font-sans text-[13.5px] sm:text-sm font-bold tracking-tight block truncate"
                                  :class="cat === {{ $category->id }} ? 'text-[#F7F3EC]' : 'text-[#2A211A] group-hover:text-[#1F1812]'">
                                {{ $category->name }}
                            </span>
                            <span class="font-mono text-[10px] block leading-none mt-0.5"
                                  :class="cat === {{ $category->id }} ? 'text-[#D9973E]' : 'text-[#8A7B66]'">
                                {{ $category->menus_count }} menu
                            </span>
                        </div>
                    </div>
                    <span :style="cat === {{ $category->id }} 
                              ? 'background-color: {{ $meta['color'] }}; color: #ffffff;' 
                              : 'background-color: {{ $meta['bg_light'] }}; color: {{ $meta['text_light'] }}; border: 1px solid {{ $meta['border_light'] }};'"
                          class="font-mono text-[11px] font-bold px-2.5 py-1 rounded-full shrink-0 transition-all shadow-2xs">
                        {{ $category->menus_count }}
                    </span>
                </button>
            @endforeach
        </div>

        <!-- Status Cepat di Bawah Panel Kiri -->
        <div class="p-3 border-t border-[#E4DCCC] bg-[#E8E1D5] hidden md:block text-center shrink-0">
            <div class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66]">Mode Kasir Cepat</div>
            <div class="font-mono text-[9px] text-[#A89A85] mt-0.5">Ketuk item untuk tambah</div>
        </div>
    </div>

    <!-- 2. PANEL TENGAH: SEARCH & GRID DAFTAR MENU (TABLET TOUCH GRID) -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-[#F7F3EC]">

        <!-- Top Search Bar & Active Category Status -->
        <div class="p-3 sm:p-3.5 min-h-[65px] border-b border-[#E4DCCC] bg-white flex items-center gap-3 shrink-0">
            <div class="relative flex-1">
                <svg class="w-4 h-4 text-[#8A7B66] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input x-model="search"
                       type="text"
                       placeholder="Cari menu (kopi, latte, matcha, cocktail, pastry)..."
                       class="w-full pl-10 pr-9 py-2 bg-[#F7F3EC] border border-[#E4DCCC] rounded-lg text-sm text-[#2A211A] placeholder-[#8A7B66] focus:outline-none focus:border-[#B5762A] focus:bg-white transition-colors">
                <button x-show="search"
                        @click="search = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8A7B66] hover:text-[#2A211A] text-sm font-bold">
                    ✕
                </button>
            </div>
            <div class="hidden sm:flex items-center gap-2 shrink-0">
                <span class="font-mono text-xs text-[#8A7B66] bg-[#F7F3EC] px-2.5 py-1 rounded-full border border-[#E4DCCC]" x-text="filteredMenus.length + ' menu'"></span>
                <!-- Shortcut Cepat ke Layar Dapur (KDS) -->
                <a href="{{ route('kasir.kitchen.index') }}"
                   title="Buka Layar Dapur (KDS) (Alt+K)"
                   class="px-2.5 py-1 bg-[#EFE9DE] hover:bg-[#5F7F42] border border-[#E4DCCC] hover:border-[#5F7F42] text-[#2A211A] hover:text-white rounded-lg text-xs font-mono transition-all flex items-center gap-1.5 shrink-0 group shadow-2xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42] group-hover:bg-white animate-pulse"></span>
                    <svg class="w-3.5 h-3.5 text-[#5F7F42] group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span class="font-semibold">Dapur</span>
                    <span class="px-1 py-0.2 rounded bg-white/70 group-hover:bg-white/20 text-[#8A7B66] group-hover:text-white text-[10px] font-mono">Alt+K</span>
                </a>
            </div>
        </div>

        <!-- Grid Menu dengan Foto Resolusi Tinggi & Label Nama Jelas -->
        <div class="flex-1 p-3.5 sm:p-4 overflow-y-auto">
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5 sm:gap-4">
                <template x-for="menu in filteredMenus" :key="menu.id">
                    <div @click="selectMenu(menu)"
                         @keydown.enter.prevent="selectMenu(menu)"
                         @keydown.space.prevent="selectMenu(menu)"
                         role="button"
                         tabindex="0"
                         class="group text-left bg-white border border-[#E4DCCC] rounded-xl overflow-hidden flex flex-col justify-between transition-all duration-200 hover:border-[#D9973E] hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] select-none"
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
                                    <span class="font-mono text-2xl text-[#A89A85]" x-text="menu.name ? menu.name.substring(0, 2).toUpperCase() : '☕'"></span>
                                </div>
                            </template>

                            <!-- Badge Kategori -->
                            <div class="absolute top-2 left-2 bg-[#1F1812]/85 backdrop-blur-xs px-2.5 py-0.5 text-[9px] font-mono uppercase tracking-wider text-[#F7F3EC] border border-[#3A3026] rounded-full">
                                <span x-text="menu.category_name"></span>
                            </div>

                            <!-- Badge Status Ketersediaan & Quick Stock Toggle -->
                            <div class="absolute top-2 right-2">
                                <button type="button"
                                        @click.stop="toggleStock(menu)"
                                        :title="menu.available ? 'Klik untuk tandai Stok Habis' : 'Klik untuk aktifkan (Stok Tersedia)'"
                                        class="px-2 py-0.5 font-mono text-[9px] uppercase font-bold tracking-wider transition border shadow-xs flex items-center gap-1.5 rounded-full cursor-pointer"
                                        :class="menu.available ? 'bg-black/75 hover:bg-[#C4553D] text-[#5F7F42] hover:text-white border-white/20' : 'bg-[#C4553D] hover:bg-[#5F7F42] text-white border-[#C4553D]'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="menu.available ? 'bg-[#5F7F42]' : 'bg-white'"></span>
                                    <span x-text="menu.available ? 'Tersedia' : 'Habis'"></span>
                                </button>
                            </div>

                            <!-- Overlay Label Nama Menu di Atas Gambar -->
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent p-2.5 pt-4 pointer-events-none">
                                <div class="text-[11px] sm:text-xs font-bold text-white truncate leading-tight drop-shadow-sm" x-text="menu.name"></div>
                            </div>
                        </div>

                        <!-- Detail Menu (Nama, Deskripsi, Harga, Tombol Tambah) -->
                        <div class="p-3 bg-white flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-[#1F1812] leading-snug line-clamp-2 group-hover:text-[#B5762A] transition-colors" x-text="menu.name"></h3>
                                <p class="mt-1 text-[11px] text-[#8A7B66] line-clamp-1" :title="menu.description || ''" x-text="menu.description || '-'"></p>
                            </div>
                            <div class="mt-2.5 flex items-baseline justify-between border-t border-[#E4DCCC] pt-2">
                                <span class="font-mono text-sm font-bold text-[#B5762A]" x-text="fmt(menu.price)"></span>
                                <span class="font-mono text-[10px] uppercase font-semibold text-[#5F7F42] bg-[#5F7F42]/10 px-2.5 py-1 rounded-full border border-[#5F7F42]/25 group-hover:bg-[#5F7F42] group-hover:text-white transition-colors">+ Tambah</span>
                            </div>
                        </div>
                    </div>
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
    <div class="w-full md:w-[330px] lg:w-[360px] xl:w-[400px] bg-[#1F1812] text-[#F7F3EC] flex flex-col shrink-0 h-full border-t md:border-t-0 md:border-l border-[#3A3026]">

        <!-- Header Tiket Pesanan (Tinggi Selaras min-h-[65px]) -->
        <div class="p-3 sm:p-3.5 min-h-[65px] border-b border-[#3A3026] flex items-center justify-between bg-[#19130E] shrink-0">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-8 h-8 rounded-lg bg-[#D9973E]/15 border border-[#D9973E]/30 flex items-center justify-center text-[#D9973E] shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <span class="font-mono text-xs uppercase tracking-[0.2em] font-bold text-[#D9973E] block leading-tight">TIKET PESANAN</span>
                    <span class="font-mono text-[10px] text-[#A89A85] block leading-tight mt-0.5" x-text="items.reduce((s, i) => s + i.qty, 0) + ' item dipilih'"></span>
                </div>
            </div>
            <button x-show="items.length > 0"
                    @click="clearCart()"
                    type="button"
                    title="Kosongkan Keranjang"
                    class="font-mono text-[11px] px-2.5 py-1 rounded-lg bg-[#C4553D]/10 hover:bg-[#C4553D]/20 text-[#E11D48] border border-[#C4553D]/30 transition-all flex items-center gap-1.5 cursor-pointer active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Kosongkan
            </button>
        </div>

        <!-- Switcher Tipe Pesanan & Nama Pelanggan -->
        <div class="p-3 sm:p-3.5 border-b border-[#3A3026] bg-[#221B15] space-y-2.5 shrink-0">
            <!-- Segmented Pill Toggle -->
            <div class="grid grid-cols-2 gap-1.5 p-1 bg-[#140E0A] border border-[#3A3026] rounded-xl">
                <button type="button"
                        @click="orderType = 'dine_in'"
                        :class="orderType === 'dine_in' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-sm' : 'text-[#A89A85] hover:text-[#F7F3EC]'"
                        class="py-2 text-center font-sans text-xs font-semibold tracking-wide rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/>
                    </svg>
                    Dine In
                </button>
                <button type="button"
                        @click="orderType = 'take_away'"
                        :class="orderType === 'take_away' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-sm' : 'text-[#A89A85] hover:text-[#F7F3EC]'"
                        class="py-2 text-center font-sans text-xs font-semibold tracking-wide rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    Take Away
                </button>
            </div>

            <!-- Nama Pelanggan / No Meja Input -->
            <div class="relative">
                <svg class="w-4 h-4 text-[#8A7B66] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <input x-model="customerName"
                       type="text"
                       placeholder="Nama Pelanggan (opsional / no meja)"
                       class="w-full bg-[#140E0A] border border-[#3A3026] text-[#F7F3EC] placeholder-[#8A7B66] pl-9 pr-3.5 py-2 text-xs rounded-xl focus:outline-none focus:border-[#D9973E] focus:ring-1 focus:ring-[#D9973E]/40 transition-all">
            </div>
        </div>

        <!-- Daftar Item di Keranjang (Scrollable) -->
        <div class="flex-1 p-3 sm:p-3.5 overflow-y-auto space-y-2.5">
            <template x-if="items.length === 0">
                <div class="h-full flex flex-col items-center justify-center text-[#A89A85] py-12">
                    <div class="w-14 h-14 rounded-2xl bg-[#261E17] border border-[#3A3026] flex items-center justify-center text-[#D9973E] text-2xl mb-3 shadow-inner">
                        🛒
                    </div>
                    <div class="font-mono text-xs uppercase tracking-wider font-semibold text-[#F7F3EC]">Keranjang Kosong</div>
                    <div class="text-[11px] mt-1 text-[#8A7B66] text-center px-6 max-w-xs leading-relaxed">Ketuk item menu di sebelah kiri untuk memasukkan ke tiket pesanan.</div>
                </div>
            </template>

            <template x-for="item in sortedItems" :key="item.option_key || item.id">
                <div class="bg-[#261E17] border border-[#3A3026] rounded-xl p-3 sm:p-3.5 transition-all hover:border-[#D9973E]/60 shadow-2xs">
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="flex items-start gap-2.5 min-w-0 flex-1 cursor-pointer"
                             @click="editItem(item)"
                             title="Klik untuk ubah opsi Hot/Ice, Gula, Catatan">
                            <!-- Icon Kategori -->
                            <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 [&_svg]:w-3.5 [&_svg]:h-3.5 font-bold shadow-2xs mt-0.5"
                                  :style="'background-color: ' + getCatMeta(item.category_slug).bg_light + '; color: ' + getCatMeta(item.category_slug).color + '; border: 1px solid ' + getCatMeta(item.category_slug).border_light + ';'"
                                  :title="item.category_name || ''"
                                  x-html="getCatMeta(item.category_slug).svg">
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[13px] font-semibold text-[#F7F3EC] leading-snug hover:text-[#D9973E] transition" x-text="item.name"></span>
                                    <!-- Tombol Edit Opsi Minuman / Catatan -->
                                    <span class="text-[10px] text-[#D9973E] px-1.5 py-0.5 rounded-md bg-[#D9973E]/10 border border-[#D9973E]/30 inline-flex items-center gap-1">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Opsi
                                    </span>
                                </div>
                                <span class="font-mono text-[9.5px] text-[#8A7B66] block leading-none mt-0.5" x-text="item.category_name"></span>
                                
                                <!-- Modifier Pills (Hot/Ice, Sugar, Notes) -->
                                <template x-if="item.note_string">
                                    <div class="mt-1 flex items-center gap-1 flex-wrap">
                                        <span class="inline-flex items-center gap-1 font-mono text-[10px] px-2 py-0.5 rounded-md bg-[#19130E] border border-[#3A3026] text-[#D9973E] font-medium leading-tight"
                                              x-text="item.note_string"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <button @click.stop="remove(item)"
                                type="button"
                                class="w-6 h-6 rounded-full flex items-center justify-center text-[#8A7B66] hover:text-[#E11D48] hover:bg-[#E11D48]/15 text-xs transition cursor-pointer shrink-0 mt-0.5"
                                title="Hapus item">✕</button>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between">
                        <!-- Kontrol Qty Touch Friendly -->
                        <div class="flex items-center border border-[#3A3026] bg-[#140E0A] rounded-lg overflow-hidden shadow-2xs">
                            <button @click="dec(item)"
                                    type="button"
                                    class="w-8 h-8 flex items-center justify-center text-[#F7F3EC] hover:bg-[#3A3026] font-mono text-sm font-bold active:bg-[#D9973E] active:text-[#1F1812] transition cursor-pointer">−</button>
                            <span class="w-8 text-center font-mono text-xs font-bold text-[#F7F3EC]" x-text="item.qty"></span>
                            <button @click="inc(item)"
                                    type="button"
                                    class="w-8 h-8 flex items-center justify-center text-[#F7F3EC] hover:bg-[#3A3026] font-mono text-sm font-bold active:bg-[#D9973E] active:text-[#1F1812] transition cursor-pointer">+</button>
                        </div>
                        <div class="text-right">
                            <div class="font-mono text-sm text-[#D9973E] font-bold" x-text="fmt(item.price * item.qty)"></div>
                            <div class="font-mono text-[10px] text-[#8A7B66]" x-text="'@ ' + fmt(item.price)"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Bagian Pembayaran & Ringkasan Transaksi (Fixed Bottom) -->
        <div class="p-3 sm:p-3.5 border-t border-[#3A3026] bg-[#140E0A] space-y-2.5 shrink-0">

            <!-- Metode Pembayaran -->
            <div>
                <label class="font-mono text-[9px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold mb-1.5 block">Metode Pembayaran</label>
                <div class="grid grid-cols-3 gap-1.5">
                    <button type="button"
                            @click="method = 'cash'; methodChange()"
                            :class="method === 'cash' ? 'bg-[#D9973E] text-[#1F1812] font-bold border-[#D9973E] shadow-sm' : 'bg-[#221A14] text-[#A89A85] hover:text-[#F7F3EC] border-[#3A3026] hover:border-[#8A7B66]'"
                            class="py-2 text-center font-mono text-[11px] uppercase tracking-wider font-semibold rounded-xl border transition-all cursor-pointer">
                        Tunai
                    </button>
                    <button type="button"
                            @click="method = 'qris'; methodChange()"
                            :class="method === 'qris' ? 'bg-[#D9973E] text-[#1F1812] font-bold border-[#D9973E] shadow-sm' : 'bg-[#221A14] text-[#A89A85] hover:text-[#F7F3EC] border-[#3A3026] hover:border-[#8A7B66]'"
                            class="py-2 text-center font-mono text-[11px] uppercase tracking-wider font-semibold rounded-xl border transition-all cursor-pointer">
                        QRIS
                    </button>
                    <button type="button"
                            @click="method = 'debit'; methodChange()"
                            :class="method === 'debit' ? 'bg-[#D9973E] text-[#1F1812] font-bold border-[#D9973E] shadow-sm' : 'bg-[#221A14] text-[#A89A85] hover:text-[#F7F3EC] border-[#3A3026] hover:border-[#8A7B66]'"
                            class="py-2 text-center font-mono text-[11px] uppercase tracking-wider font-semibold rounded-xl border transition-all cursor-pointer">
                        Debit
                    </button>
                </div>
            </div>

            <!-- Subtotal & Baris Promo / Diskon Ringkas -->
            <div class="space-y-1.5 text-xs py-0.5">
                <div class="flex items-center justify-between text-[#A89A85]">
                    <span>Subtotal</span>
                    <span class="font-mono text-[#F7F3EC] font-semibold" x-text="fmt(subtotal)"></span>
                </div>

                <!-- Tombol Buka Modal Promo / Diskon -->
                <div class="flex items-center justify-between gap-2">
                    <button type="button"
                            @click="showPromoModal = true"
                            class="flex-1 py-2 px-3 rounded-xl border transition-all flex items-center justify-between text-left cursor-pointer active:scale-[0.99]"
                            :class="discount > 0 
                                ? 'bg-[#5F7F42]/10 border-[#5F7F42]/50 hover:border-[#5F7F42]' 
                                : 'bg-[#1C150F] border-[#3A3026] hover:border-[#D9973E]/60 text-[#A89A85] hover:text-[#F7F3EC]'">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-sm">🎟️</span>
                            <div class="min-w-0">
                                <template x-if="discount === 0">
                                    <span class="font-sans text-xs text-[#A89A85]">Gunakan Promo / Diskon ›</span>
                                </template>
                                <template x-if="discount > 0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-mono text-xs font-bold text-[#F7F3EC]" 
                                              x-text="appliedPromo ? appliedPromo.code : 'Diskon Manual'"></span>
                                        <span class="text-[9.5px] px-1.5 py-0.2 rounded bg-[#5F7F42]/20 text-[#5F7F42] font-semibold">Aktif</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <template x-if="discount > 0">
                                <span class="font-mono text-xs font-bold text-[#5F7F42]" x-text="'-' + fmt(discount)"></span>
                            </template>
                            <span class="text-[10px] text-[#8A7B66]" x-show="discount === 0">Pilih ›</span>
                        </div>
                    </button>

                    <!-- Tombol Cepat Hapus Diskon jika sedang aktif -->
                    <template x-if="discount > 0">
                        <button type="button"
                                @click="removePromo(); manualDiscount = 0"
                                title="Hapus Diskon"
                                class="w-8 h-8 rounded-xl bg-[#221A14] hover:bg-[#C4553D] text-[#8A7B66] hover:text-white border border-[#3A3026] flex items-center justify-center text-xs transition cursor-pointer shrink-0">
                            ✕
                        </button>
                    </template>
                </div>
            </div>

            <!-- Quick Cash Denominations (Khusus Pembayaran Tunai Tablet POS) -->
            <div x-show="method === 'cash' && items.length > 0" class="pt-0.5">
                <div class="grid grid-cols-4 gap-1.5">
                    <button type="button"
                            @click="setExactPaid()"
                            class="bg-[#221A14] hover:bg-[#2F241C] border border-[#D9973E]/40 text-[#D9973E] py-1.5 text-center font-mono text-[11px] font-bold rounded-lg transition-all active:scale-95 cursor-pointer">
                        Uang Pas
                    </button>
                    <button type="button"
                            @click="setCash(50000)"
                            class="bg-[#221A14] hover:bg-[#2F241C] border border-[#3A3026] text-[#F7F3EC] py-1.5 text-center font-mono text-[11px] font-semibold rounded-lg transition-all active:scale-95 cursor-pointer">
                        50k
                    </button>
                    <button type="button"
                            @click="setCash(100000)"
                            class="bg-[#221A14] hover:bg-[#2F241C] border border-[#3A3026] text-[#F7F3EC] py-1.5 text-center font-mono text-[11px] font-semibold rounded-lg transition-all active:scale-95 cursor-pointer">
                        100k
                    </button>
                    <button type="button"
                            @click="setCash(200000)"
                            class="bg-[#221A14] hover:bg-[#2F241C] border border-[#3A3026] text-[#F7F3EC] py-1.5 text-center font-mono text-[11px] font-semibold rounded-lg transition-all active:scale-95 cursor-pointer">
                        200k
                    </button>
                </div>
            </div>

            <!-- Kartu Total Pembayaran -->
            <div class="bg-[#221A14] border border-[#3A3026] rounded-xl p-3 flex items-baseline justify-between shadow-2xs">
                <div>
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold block">TOTAL AKHIR</span>
                    <div x-show="method === 'cash' && paid > 0" class="font-mono text-xs text-[#5F7F42] font-bold mt-0.5">
                        Kembalian: <span x-text="fmt(Math.max(change, 0))"></span>
                    </div>
                </div>
                <div class="font-mono text-2xl sm:text-[26px] text-[#D9973E] font-bold tracking-tight" x-text="fmt(total)"></div>
            </div>

            <!-- Input Tunai jika Cash (Touch-Friendly Tablet POS: Bebas Pop-up Keyboard Tablet) -->
            <div class="flex items-center justify-between gap-2 bg-[#1B140E] border border-[#3A3026] hover:border-[#D9973E]/70 rounded-xl px-3 py-2 transition-colors cursor-pointer group"
                 x-show="method === 'cash'"
                 @click="openNumpad()">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-[#2A2016] group-hover:bg-[#D9973E] text-[#D9973E] group-hover:text-[#1F1812] flex items-center justify-center text-xs font-mono font-bold transition shrink-0 shadow-2xs">
                        🔢
                    </span>
                    <div class="min-w-0">
                        <span class="text-[#FAF7F2] text-xs font-semibold block leading-tight">Diterima</span>
                        <span class="text-[#8A7B66] text-[10px] font-mono block leading-tight">Ketuk untuk keypad</span>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="font-mono text-xs text-[#8A7B66]">Rp</span>
                    <input type="text"
                           readonly
                           inputmode="none"
                           :value="(parseInt(paid) || 0).toLocaleString('id-ID')"
                           class="w-28 sm:w-32 bg-[#221A14] border border-[#3A3026] group-hover:border-[#D9973E] text-[#FAF7F2] px-2.5 py-1.5 text-right font-mono text-sm font-bold rounded-lg focus:outline-none cursor-pointer select-none transition-colors">
                </div>
            </div>

            <!-- Notifikasi Error -->
            <p class="text-xs text-[#E11D48] bg-[#E11D48]/10 border border-[#E11D48]/30 rounded-xl p-2.5 flex items-center gap-2" x-show="error" x-text="error"></p>

            <!-- Tombol Proses Checkout (Touch Friendly) -->
            <button @click="submit()"
                    :disabled="items.length === 0 || submitting || (method === 'cash' && paid < total)"
                    type="button"
                    class="w-full bg-[#D9973E] text-[#1F1812] py-3.5 px-4 rounded-xl font-mono text-xs uppercase tracking-[0.2em] font-bold hover:bg-[#B5762A] hover:text-white transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed shadow-lg active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer">
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
         @keydown.window="if(showSuccessModal) handleSuccessModalKey($event)"
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
                        title="Tutup (ESC / Enter)"
                        class="text-[#8A7B66] hover:text-[#F7F3EC] p-1.5 transition-colors text-lg font-bold cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- Body Modal -->
            <div class="p-5 space-y-3.5">
                <!-- Banner Kembalian (Highlight Besar) -->
                <div class="bg-[#2A211A] border border-[#3A3026] p-4 text-center rounded-xl">
                    <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">KEMBALIAN PELANGGAN</div>
                    <div class="font-mono text-3xl font-bold text-[#5F7F42] mt-1" x-text="fmt(completedOrder?.change_amount || 0)"></div>
                    <div class="font-mono text-xs text-[#A89A85] mt-2 flex items-center justify-center gap-4">
                        <span>Total: <b class="text-[#F7F3EC]" x-text="fmt(completedOrder?.total || 0)"></b></span>
                        <span>•</span>
                        <span>Bayar (<span x-text="completedOrder?.payment_method?.toUpperCase()"></span>): <b class="text-[#F7F3EC]" x-text="fmt(completedOrder?.paid_amount || 0)"></b></span>
                    </div>
                </div>

                <!-- Shortcut Pintas Layar Dapur (KDS) -->
                <div class="bg-[#261E17] border border-[#5F7F42]/50 rounded-xl p-3 flex items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-8 h-8 rounded-lg bg-[#5F7F42]/20 border border-[#5F7F42]/60 text-[#5F7F42] flex items-center justify-center font-bold text-sm shrink-0">
                            👨‍🍳
                        </span>
                        <div class="min-w-0">
                            <div class="font-mono text-xs font-bold text-[#FAF7F2] flex items-center gap-1.5">
                                <span>Pesanan Masuk ke Layar Dapur</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42] animate-ping"></span>
                            </div>
                            <div class="text-[11px] text-[#A89A85] truncate">Barista & Dapur langsung memproses pesanan ini.</div>
                        </div>
                    </div>
                    <button type="button"
                            @click="goToKitchen()"
                            title="Buka Layar Dapur (Tekan K)"
                            class="px-3 py-1.5 rounded-lg bg-[#5F7F42] hover:bg-[#4E6B35] text-white font-mono text-xs font-bold transition flex items-center gap-1.5 shadow-sm active:scale-95 cursor-pointer shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span>Pantau</span>
                        <span class="px-1 py-0.2 rounded bg-[#1F1812]/50 text-[#D9973E] text-[10px] font-mono">K</span>
                    </button>
                </div>

                <!-- Card Musik Request Code -->
                <div class="bg-[#2A211A]/80 border border-[#D9973E]/30 p-3 flex items-center justify-between gap-3 rounded-xl">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="text-xl shrink-0">🎵</span>
                        <div class="min-w-0">
                            <div class="font-mono text-[10px] uppercase tracking-wider text-[#D9973E] font-bold">Kode Request Musik</div>
                            <div class="text-[11px] text-[#A89A85] truncate">Pelanggan dapat memindai QR di struk atau masukkan kode:</div>
                        </div>
                    </div>
                    <div class="font-mono text-base font-black px-3 py-1 bg-[#1F1812] border border-[#D9973E] text-[#D9973E] tracking-widest shrink-0 rounded-lg" x-text="completedOrder?.music_code || '-'"></div>
                </div>

                <!-- Status Audio & Background Print -->
                <div class="flex items-center gap-2 text-xs text-[#8A7B66] bg-[#1F1812] border border-[#3A3026] px-3 py-2 rounded-xl">
                    <span class="text-base">🖨️</span>
                    <span class="leading-tight">
                        Struk otomatis dicetak di background. <b class="text-[#5F7F42]">Musik kafe tetap mengalun tanpa jeda.</b>
                    </span>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="p-4 bg-[#2A211A] border-t border-[#3A3026] flex flex-wrap sm:flex-nowrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <!-- Tombol Cepat Layar Dapur (KDS) -->
                    <button type="button"
                            @click="goToKitchen()"
                            title="Buka Layar Dapur (Tekan K)"
                            class="px-3 py-2 bg-[#1F1812] hover:bg-[#5F7F42] border border-[#5F7F42]/60 hover:border-[#5F7F42] text-[#FAF7F2] font-mono text-xs uppercase tracking-wider transition-colors flex items-center gap-1.5 rounded-xl cursor-pointer group shadow-xs">
                        <span class="text-sm">👨‍🍳</span>
                        <span class="font-bold">Dapur</span>
                        <span class="px-1 rounded bg-[#2A211A] text-[#5F7F42] group-hover:text-white text-[10px] font-bold border border-[#3A3026]">K</span>
                    </button>
                    <!-- Tombol Cetak Ulang Struk (P) -->
                    <button type="button"
                            @click="reprintReceipt()"
                            title="Cetak ulang struk thermal (Tekan P)"
                            class="px-2.5 py-2 bg-[#1F1812] hover:bg-[#3A3026] border border-[#3A3026] text-[#F7F3EC] font-mono text-xs uppercase tracking-wider transition-colors flex items-center gap-1.5 rounded-xl cursor-pointer">
                        <span>🖨️</span>
                        <span>Cetak Ulang</span>
                        <span class="px-1 rounded bg-[#2A211A] text-[#A89A85] text-[10px] font-bold border border-[#3A3026]">P</span>
                    </button>
                    <!-- Lihat Struk Baru -->
                    <button type="button"
                            @click="if(completedOrder?.receipt_url) window.open(completedOrder.receipt_url, '_blank')"
                            title="Buka struk di tab baru"
                            class="px-2.5 py-2 bg-[#1F1812] hover:bg-[#3A3026] border border-[#3A3026] text-[#8A7B66] hover:text-[#F7F3EC] font-mono text-xs uppercase tracking-wider transition-colors flex items-center gap-1 rounded-xl cursor-pointer">
                        <span>Struk</span>
                        <span>↗</span>
                    </button>
                </div>
                <button type="button"
                        @click="closeSuccessModal()"
                        class="flex-1 sm:max-w-[200px] bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] font-mono text-xs uppercase tracking-[0.15em] font-bold py-2.5 px-4 transition-colors shadow-md text-center rounded-xl cursor-pointer active:scale-98">
                    ✓ Transaksi Baru (Enter)
                </button>
            </div>

        </div>
    </div>

    <!-- 5. MODAL CEPAT KUSTOMISASI MINUMAN & MENU (HOT/ICE, SUGAR LEVEL, NOTES) -->
    <div x-show="showModifierModal"
         x-cloak
         @keydown.escape.window="if(showModifierModal) showModifierModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs transition-all duration-200">
        <div class="bg-[#1F1812] border border-[#3A3026] text-[#F7F3EC] w-full max-w-md shadow-2xl rounded-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-150"
             @click.away="showModifierModal = false">
            
            <!-- Header Modal Kustomisasi -->
            <div class="p-4 bg-[#261E17] border-b border-[#3A3026] flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 [&_svg]:w-5 [&_svg]:h-5 font-bold shadow-sm"
                          :style="'background-color: ' + getCatMeta(modifierMenu?.category_slug).bg_light + '; color: ' + getCatMeta(modifierMenu?.category_slug).color + '; border: 1px solid ' + getCatMeta(modifierMenu?.category_slug).border_light + ';'"
                          x-html="getCatMeta(modifierMenu?.category_slug).svg">
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-sans text-base font-bold text-[#F7F3EC] truncate" x-text="modifierMenu?.name"></h3>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="font-mono text-xs text-[#D9973E] font-bold" x-text="fmt(modifierMenu?.price)"></span>
                            <span class="text-[#8A7B66] text-xs">•</span>
                            <span class="font-mono text-[11px] text-[#A89A85]" x-text="modifierMenu?.category_name"></span>
                        </div>
                    </div>
                </div>
                <button type="button"
                        @click="showModifierModal = false"
                        class="w-8 h-8 rounded-full bg-[#1F1812] hover:bg-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC] flex items-center justify-center transition cursor-pointer text-sm">
                    ✕
                </button>
            </div>

            <!-- Konten Opsi Kustomisasi -->
            <div class="p-4 overflow-y-auto max-h-[70vh] space-y-4">
                
                <!-- Opsi Khusus Minuman: Suhu / Temperature -->
                <template x-if="modifierMenu?.is_drink">
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85] font-bold mb-2 block">Suhu Minuman</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button"
                                    @click="modOptions.temperature = 'ice'"
                                    :class="modOptions.temperature === 'ice' 
                                        ? 'bg-[#3B82F6]/20 border-[#3B82F6] text-[#93C5FD] font-bold shadow-sm' 
                                        : 'bg-[#261E17] border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC]'"
                                    class="py-2.5 px-3 rounded-xl border transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                                <span class="text-base">🧊</span>
                                <span class="font-sans text-xs font-semibold">Ice (Dingin)</span>
                            </button>
                            <button type="button"
                                    @click="modOptions.temperature = 'hot'"
                                    :class="modOptions.temperature === 'hot' 
                                        ? 'bg-[#EF4444]/20 border-[#EF4444] text-[#FCA5A5] font-bold shadow-sm' 
                                        : 'bg-[#261E17] border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC]'"
                                    class="py-2.5 px-3 rounded-xl border transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                                <span class="text-base">🔥</span>
                                <span class="font-sans text-xs font-semibold">Hot (Panas)</span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Opsi Khusus Minuman: Level Gula (Sugar Level) -->
                <template x-if="modifierMenu?.is_drink">
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85] font-bold mb-2 block">Level Gula (Sugar)</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button"
                                    @click="modOptions.sugar = 'normal'"
                                    :class="modOptions.sugar === 'normal' 
                                        ? 'bg-[#D9973E] border-[#D9973E] text-[#1F1812] font-bold shadow-sm' 
                                        : 'bg-[#261E17] border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC]'"
                                    class="py-2 px-2 rounded-xl border transition-all text-center font-sans text-xs cursor-pointer active:scale-98">
                                <div class="font-semibold">Normal</div>
                                <div class="text-[10px] opacity-80 font-mono">100% Gula</div>
                            </button>
                            <button type="button"
                                    @click="modOptions.sugar = 'less'"
                                    :class="modOptions.sugar === 'less' 
                                        ? 'bg-[#D9973E] border-[#D9973E] text-[#1F1812] font-bold shadow-sm' 
                                        : 'bg-[#261E17] border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC]'"
                                    class="py-2 px-2 rounded-xl border transition-all text-center font-sans text-xs cursor-pointer active:scale-98">
                                <div class="font-semibold">Less Sugar</div>
                                <div class="text-[10px] opacity-80 font-mono">50% Gula</div>
                            </button>
                            <button type="button"
                                    @click="modOptions.sugar = 'no'"
                                    :class="modOptions.sugar === 'no' 
                                        ? 'bg-[#D9973E] border-[#D9973E] text-[#1F1812] font-bold shadow-sm' 
                                        : 'bg-[#261E17] border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC]'"
                                    class="py-2 px-2 rounded-xl border transition-all text-center font-sans text-xs cursor-pointer active:scale-98">
                                <div class="font-semibold">No Sugar</div>
                                <div class="text-[10px] opacity-80 font-mono">0% Gula</div>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Opsi Khusus Minuman Dingin: Level Es (Ice Level) -->
                <template x-if="modifierMenu?.is_drink && modOptions.temperature === 'ice'">
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85] font-bold mb-2 block">Level Es (Ice)</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button"
                                    @click="modOptions.ice_level = 'normal'"
                                    :class="modOptions.ice_level === 'normal' 
                                        ? 'bg-[#D9973E] border-[#D9973E] text-[#1F1812] font-bold shadow-sm' 
                                        : 'bg-[#261E17] border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC]'"
                                    class="py-2 px-2 rounded-xl border transition-all text-center font-sans text-xs cursor-pointer active:scale-98">
                                <div class="font-semibold">Normal Ice</div>
                            </button>
                            <button type="button"
                                    @click="modOptions.ice_level = 'less'"
                                    :class="modOptions.ice_level === 'less' 
                                        ? 'bg-[#D9973E] border-[#D9973E] text-[#1F1812] font-bold shadow-sm' 
                                        : 'bg-[#261E17] border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC]'"
                                    class="py-2 px-2 rounded-xl border transition-all text-center font-sans text-xs cursor-pointer active:scale-98">
                                <div class="font-semibold">Less Ice</div>
                            </button>
                            <button type="button"
                                    @click="modOptions.ice_level = 'none'"
                                    :class="modOptions.ice_level === 'none' 
                                        ? 'bg-[#D9973E] border-[#D9973E] text-[#1F1812] font-bold shadow-sm' 
                                        : 'bg-[#261E17] border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC]'"
                                    class="py-2 px-2 rounded-xl border transition-all text-center font-sans text-xs cursor-pointer active:scale-98">
                                <div class="font-semibold">No Ice</div>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Catatan Tambahan -->
                <div>
                    <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85] font-bold mb-1.5 block">Catatan Tambahan (Opsional)</label>
                    <div class="relative">
                        <input x-model="modOptions.note"
                               @keydown.enter.prevent="saveModifier()"
                               type="text"
                               placeholder="misal: ekstra shot, oat milk, pisah saus..."
                               class="w-full bg-[#140E0A] border border-[#3A3026] text-[#F7F3EC] placeholder-[#8A7B66] px-3.5 py-2.5 text-xs rounded-xl focus:outline-none focus:border-[#D9973E] focus:ring-1 focus:ring-[#D9973E]/40 transition-all">
                    </div>
                </div>

                <!-- Stepper Jumlah / Quantity -->
                <div class="flex items-center justify-between pt-2 border-t border-[#3A3026]/70">
                    <span class="font-mono text-xs text-[#A89A85] font-semibold">Jumlah Item</span>
                    <div class="flex items-center border border-[#3A3026] bg-[#140E0A] rounded-xl overflow-hidden shadow-2xs">
                        <button @click="if(modOptions.qty > 1) modOptions.qty--"
                                type="button"
                                class="w-9 h-9 flex items-center justify-center text-[#F7F3EC] hover:bg-[#3A3026] font-mono text-base font-bold active:bg-[#D9973E] active:text-[#1F1812] transition cursor-pointer">−</button>
                        <span class="w-10 text-center font-mono text-sm font-bold text-[#F7F3EC]" x-text="modOptions.qty"></span>
                        <button @click="modOptions.qty++"
                                type="button"
                                class="w-9 h-9 flex items-center justify-center text-[#F7F3EC] hover:bg-[#3A3026] font-mono text-base font-bold active:bg-[#D9973E] active:text-[#1F1812] transition cursor-pointer">+</button>
                    </div>
                </div>
            </div>

            <!-- Footer Aksi Modal -->
            <div class="p-4 bg-[#261E17] border-t border-[#3A3026] flex items-center justify-between gap-3">
                <button type="button"
                        @click="showModifierModal = false"
                        class="px-4 py-2.5 rounded-xl border border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC] hover:bg-[#1F1812] font-mono text-xs uppercase tracking-wider transition cursor-pointer">
                    Batal
                </button>
                <button type="button"
                        @click="saveModifier()"
                        class="flex-1 bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] hover:text-white py-2.5 px-4 rounded-xl font-mono text-xs uppercase tracking-wider font-bold transition-all shadow-md active:scale-98 flex items-center justify-center gap-2 cursor-pointer">
                    <span x-text="editingItemIndex !== null ? 'Simpan Perubahan' : '+ Tambahkan ke Pesanan'"></span>
                    <span class="font-bold font-mono" x-text="'(' + fmt((modifierMenu?.price || 0) * (modOptions.qty || 1)) + ')'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 6. MODAL KODE PROMO & DISKON PESANAN -->
    <div x-show="showPromoModal"
         x-cloak
         @keydown.escape.window="if(showPromoModal) showPromoModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs transition-all duration-200">
        <div class="bg-[#1F1812] border border-[#3A3026] text-[#F7F3EC] w-full max-w-md shadow-2xl rounded-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-150"
             @click.away="showPromoModal = false">
            
            <!-- Header Modal Promo -->
            <div class="p-4 bg-[#261E17] border-b border-[#3A3026] flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-[#D9973E]/15 border border-[#D9973E]/30 flex items-center justify-center text-[#D9973E] text-base shrink-0">
                        🎟️
                    </span>
                    <div>
                        <h3 class="font-sans text-sm font-bold text-[#F7F3EC]">Promo & Diskon Pesanan</h3>
                        <div class="text-[11px] text-[#8A7B66] flex items-center gap-1.5 mt-0.5">
                            <span>Subtotal saat ini:</span>
                            <b class="font-mono text-[#D9973E]" x-text="fmt(subtotal)"></b>
                        </div>
                    </div>
                </div>
                <button type="button"
                        @click="showPromoModal = false"
                        class="w-8 h-8 rounded-full bg-[#1F1812] hover:bg-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC] flex items-center justify-center transition cursor-pointer text-sm">
                    ✕
                </button>
            </div>

            <!-- Switcher Tab: Kode Promo vs Diskon Manual -->
            <div class="p-3 bg-[#19130E] border-b border-[#3A3026]">
                <div class="grid grid-cols-2 gap-1.5 p-1 bg-[#140E0A] border border-[#3A3026] rounded-xl">
                    <button type="button"
                            @click="discountMode = 'promo'"
                            :class="discountMode === 'promo' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-sm' : 'text-[#A89A85] hover:text-[#F7F3EC]'"
                            class="py-2 text-center font-sans text-xs font-semibold rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>🏷️</span>
                        <span>Kode Promo</span>
                    </button>
                    <button type="button"
                            @click="discountMode = 'manual'"
                            :class="discountMode === 'manual' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-sm' : 'text-[#A89A85] hover:text-[#F7F3EC]'"
                            class="py-2 text-center font-sans text-xs font-semibold rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>✏️</span>
                        <span>Diskon Manual</span>
                    </button>
                </div>
            </div>

            <!-- Body Modal -->
            <div class="p-4 overflow-y-auto max-h-[60vh] space-y-4">

                <!-- TAB 1: KODE PROMO -->
                <div x-show="discountMode === 'promo'" class="space-y-3.5">
                    <!-- Input Form Kode Promo -->
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85] font-bold mb-1.5 block">
                            Masukkan Kode Promo
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input x-model="promoInput"
                                       @keydown.enter.prevent="applyPromo()"
                                       type="text"
                                       placeholder="misal: KOPIHEMAT, DISKON10..."
                                       class="w-full bg-[#140E0A] border border-[#3A3026] text-[#F7F3EC] placeholder-[#8A7B66] uppercase font-mono px-3.5 py-2.5 text-xs rounded-xl focus:outline-none focus:border-[#D9973E] transition-all">
                            </div>
                            <button type="button"
                                    @click="applyPromo()"
                                    :disabled="promoLoading || !promoInput.trim()"
                                    class="px-4 py-2.5 bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] font-mono text-xs font-bold rounded-xl transition disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer flex items-center gap-1.5 shrink-0 shadow-sm active:scale-98">
                                <span x-show="!promoLoading">Terapkan</span>
                                <span x-show="promoLoading" class="animate-spin text-xs">⏳</span>
                            </button>
                        </div>

                        <!-- Pesan Feedback (Error / Sukses) -->
                        <template x-if="promoMessage">
                            <div class="mt-2 text-xs p-2.5 rounded-xl flex items-center gap-2"
                                 :class="promoStatus === 'error' ? 'text-[#E11D48] bg-[#E11D48]/10 border border-[#E11D48]/30' : 'text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/30'">
                                <span x-text="promoStatus === 'error' ? '⚠️' : '✓'"></span>
                                <span x-text="promoMessage"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Promo Aktif Saat Ini (Jika Ada) -->
                    <template x-if="appliedPromo">
                        <div class="bg-[#241B13] border border-[#5F7F42] rounded-xl p-3 shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-lg bg-[#5F7F42]/20 text-[#5F7F42] flex items-center justify-center font-bold text-sm shrink-0 mt-0.5">✓</div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-sm font-bold text-[#F7F3EC] tracking-wider" x-text="appliedPromo.code"></span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-[#5F7F42]/20 text-[#5F7F42] font-semibold"
                                                  x-text="appliedPromo.type === 'percentage' ? (appliedPromo.discount_value + '%') : 'Potongan Tunai'"></span>
                                        </div>
                                        <div class="text-xs text-[#A89A85] mt-0.5 font-medium" x-text="appliedPromo.name"></div>
                                        <div class="text-[11px] text-[#8A7B66] mt-1" x-text="appliedPromo.description"></div>
                                    </div>
                                </div>
                                <button type="button"
                                        @click="removePromo()"
                                        title="Hapus Promo"
                                        class="px-2.5 py-1 rounded-lg bg-[#3A3026] hover:bg-[#C4553D] text-[#A89A85] hover:text-white text-xs font-mono transition cursor-pointer shrink-0">
                                    Hapus
                                </button>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-[#3A3026] flex items-center justify-between text-xs">
                                <span class="text-[#8A7B66]">Total Potongan Promo:</span>
                                <span class="font-mono text-sm font-bold text-[#5F7F42]" x-text="'-' + fmt(discount)"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Daftar Rekomendasi Promo Kafe -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold block">
                                Daftar Promo Tersedia
                            </label>
                            @if(auth()->user()?->isOwner())
                            <a href="{{ route('kasir.promos.index') }}" target="_blank" class="font-mono text-[10px] text-[#D9973E] hover:underline flex items-center gap-1 transition">
                                <span>Kelola Kupon</span>
                                <span>↗</span>
                            </a>
                            @endif
                        </div>
                        <div class="space-y-2">
                            <template x-for="p in activePromos" :key="p.id">
                                <div class="bg-[#140E0A] border rounded-xl p-3 transition-all cursor-pointer hover:border-[#D9973E]"
                                     :class="appliedPromo?.code === p.code ? 'border-[#5F7F42] bg-[#241B13]' : 'border-[#3A3026]'"
                                     @click="applyPromo(p.code)">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono text-xs font-bold text-[#D9973E] px-2 py-0.5 rounded-md bg-[#D9973E]/10 border border-[#D9973E]/30 tracking-wider" x-text="p.code"></span>
                                                <span class="font-sans text-xs font-semibold text-[#F7F3EC]" x-text="p.name"></span>
                                            </div>
                                            <p class="text-[11px] text-[#8A7B66] mt-1" x-text="p.description || '-'"></p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <button type="button"
                                                    class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition"
                                                    :class="appliedPromo?.code === p.code 
                                                        ? 'bg-[#5F7F42] text-white' 
                                                        : 'bg-[#221A14] hover:bg-[#D9973E] text-[#D9973E] hover:text-[#1F1812] border border-[#3A3026]'">
                                                <span x-text="appliedPromo?.code === p.code ? '✓ Dipakai' : 'Gunakan'"></span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-t border-[#3A3026]/60 flex items-center justify-between text-[10px] text-[#8A7B66]">
                                        <span x-text="p.min_order > 0 ? ('Min. Belanja ' + fmt(p.min_order)) : 'Tanpa Min. Belanja'"></span>
                                        <span x-text="p.max_discount ? ('Maks. ' + fmt(p.max_discount)) : ''"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: DISKON MANUAL -->
                <div x-show="discountMode === 'manual'" class="space-y-4">
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85] font-bold mb-1.5 block">
                            Potongan Nominal Manual (Rp)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-mono text-xs text-[#8A7B66]">Rp</span>
                            <input x-model.number="manualDiscount"
                                   @input="if(method !== 'cash') paid = total"
                                   type="number"
                                   min="0"
                                   :max="subtotal"
                                   placeholder="0"
                                   class="w-full bg-[#140E0A] border border-[#3A3026] text-[#F7F3EC] pl-10 pr-3.5 py-2.5 font-mono text-sm font-bold rounded-xl focus:outline-none focus:border-[#D9973E]">
                        </div>
                    </div>

                    <!-- Preset Nominal Cepat -->
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold mb-2 block">
                            Pilihan Nominal Cepat
                        </label>
                        <div class="grid grid-cols-4 gap-2">
                            <button type="button"
                                    @click="manualDiscount = 5000; if(method !== 'cash') paid = total"
                                    class="py-2 px-1 text-center font-mono text-xs font-semibold rounded-xl border border-[#3A3026] bg-[#140E0A] hover:bg-[#2A2016] text-[#F7F3EC] hover:text-[#D9973E] transition cursor-pointer">
                                5k
                            </button>
                            <button type="button"
                                    @click="manualDiscount = 10000; if(method !== 'cash') paid = total"
                                    class="py-2 px-1 text-center font-mono text-xs font-semibold rounded-xl border border-[#3A3026] bg-[#140E0A] hover:bg-[#2A2016] text-[#F7F3EC] hover:text-[#D9973E] transition cursor-pointer">
                                10k
                            </button>
                            <button type="button"
                                    @click="manualDiscount = 15000; if(method !== 'cash') paid = total"
                                    class="py-2 px-1 text-center font-mono text-xs font-semibold rounded-xl border border-[#3A3026] bg-[#140E0A] hover:bg-[#2A2016] text-[#F7F3EC] hover:text-[#D9973E] transition cursor-pointer">
                                15k
                            </button>
                            <button type="button"
                                    @click="manualDiscount = 20000; if(method !== 'cash') paid = total"
                                    class="py-2 px-1 text-center font-mono text-xs font-semibold rounded-xl border border-[#3A3026] bg-[#140E0A] hover:bg-[#2A2016] text-[#F7F3EC] hover:text-[#D9973E] transition cursor-pointer">
                                20k
                            </button>
                        </div>
                    </div>

                    <!-- Preset Persentase Cepat -->
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold mb-2 block">
                            Pilihan Persentase Cepat
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button"
                                    @click="manualDiscount = Math.round(subtotal * 0.05); if(method !== 'cash') paid = total"
                                    class="py-2 px-1 text-center font-mono text-xs font-semibold rounded-xl border border-[#3A3026] bg-[#140E0A] hover:bg-[#2A2016] text-[#F7F3EC] hover:text-[#D9973E] transition cursor-pointer">
                                5% (<span x-text="fmt(Math.round(subtotal * 0.05))"></span>)
                            </button>
                            <button type="button"
                                    @click="manualDiscount = Math.round(subtotal * 0.10); if(method !== 'cash') paid = total"
                                    class="py-2 px-1 text-center font-mono text-xs font-semibold rounded-xl border border-[#3A3026] bg-[#140E0A] hover:bg-[#2A2016] text-[#F7F3EC] hover:text-[#D9973E] transition cursor-pointer">
                                10% (<span x-text="fmt(Math.round(subtotal * 0.10))"></span>)
                            </button>
                            <button type="button"
                                    @click="manualDiscount = Math.round(subtotal * 0.20); if(method !== 'cash') paid = total"
                                    class="py-2 px-1 text-center font-mono text-xs font-semibold rounded-xl border border-[#3A3026] bg-[#140E0A] hover:bg-[#2A2016] text-[#F7F3EC] hover:text-[#D9973E] transition cursor-pointer">
                                20% (<span x-text="fmt(Math.round(subtotal * 0.20))"></span>)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="p-4 bg-[#261E17] border-t border-[#3A3026] flex items-center justify-between gap-3">
                <div>
                    <div class="text-[10px] text-[#8A7B66] font-mono uppercase tracking-wider">Total Diskon</div>
                    <div class="font-mono text-base font-bold text-[#5F7F42]" x-text="fmt(discount)"></div>
                </div>
                <button type="button"
                        @click="showPromoModal = false"
                        class="bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] hover:text-white py-2.5 px-5 rounded-xl font-mono text-xs uppercase tracking-wider font-bold transition-all shadow-md active:scale-98 cursor-pointer">
                    Selesai & Terapkan
                </button>
            </div>
        </div>
    </div>

    <!-- 7. MODAL OVERLAY NUMBER PAD (TOUCH NUMPAD NOMINAL DITERIMA - TABLET POS FRIENDLY) -->
    <div x-show="showNumpadModal"
         x-cloak
         @keydown.window="if(showNumpadModal) handleNumpadKey($event)"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80 backdrop-blur-xs transition-all duration-200">
        <div class="bg-[#1C1611] border border-[#3A2D22] text-[#F7F3EC] w-full max-w-sm sm:max-w-md shadow-2xl rounded-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-150 select-none"
             @click.away="closeNumpad()">
            
            <!-- Header Modal Numpad -->
            <div class="p-3.5 sm:p-4 bg-[#261D16] border-b border-[#3A2D22] flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-[#D9973E]/15 border border-[#D9973E]/30 flex items-center justify-center text-[#D9973E] text-base shrink-0 font-mono">
                        🔢
                    </span>
                    <div>
                        <h3 class="font-sans text-sm font-bold text-[#F7F3EC]">Nominal Uang Diterima</h3>
                        <div class="text-[11px] text-[#A89A85] flex items-center gap-1.5 mt-0.5">
                            <span>Total Tagihan:</span>
                            <b class="font-mono text-[#D9973E]" x-text="fmt(total)"></b>
                        </div>
                    </div>
                </div>
                <button type="button"
                        @click="closeNumpad()"
                        title="Tutup (ESC)"
                        class="text-[#8A7B66] hover:text-[#F7F3EC] p-1.5 transition-colors text-lg font-bold cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- Body: Display Layar Nominal & Kembalian -->
            <div class="p-4 space-y-3.5">
                <!-- Layar Display Nominal -->
                <div class="bg-[#140E0A] border-2 border-[#D9973E] rounded-xl p-3 sm:p-3.5 shadow-inner">
                    <div class="flex items-center justify-between text-[10px] font-mono uppercase tracking-wider text-[#8A7B66] mb-1">
                        <span>Uang Diterima Kasir</span>
                        <span class="text-[#D9973E] font-bold">Rupiah (IDR)</span>
                    </div>
                    <div class="flex items-baseline justify-end gap-1 overflow-x-auto scrollbar-none py-1">
                        <span class="font-mono text-base sm:text-lg text-[#8A7B66] font-semibold">Rp</span>
                        <span class="font-mono text-3xl sm:text-4xl font-black text-[#F7F3EC] tracking-tight text-right"
                              x-text="(parseInt(numpadInput) || 0).toLocaleString('id-ID')"></span>
                    </div>

                    <!-- Live Kembalian / Status Pembayaran -->
                    <div class="mt-2 pt-2 border-t border-[#261D16] flex items-center justify-between text-xs font-mono">
                        <span class="text-[#A89A85]">
                            <template x-if="(parseInt(numpadInput) || 0) >= total">
                                <span class="text-[#5F7F42] font-semibold flex items-center gap-1">
                                    <span>✓</span> Kembalian:
                                </span>
                            </template>
                            <template x-if="(parseInt(numpadInput) || 0) < total">
                                <span class="text-[#E11D48] font-semibold flex items-center gap-1">
                                    <span>⚠</span> Kurang:
                                </span>
                            </template>
                        </span>
                        <span class="font-bold text-sm"
                              :class="(parseInt(numpadInput) || 0) >= total ? 'text-[#5F7F42]' : 'text-[#E11D48]'"
                              x-text="(parseInt(numpadInput) || 0) >= total ? fmt((parseInt(numpadInput) || 0) - total) : fmt(total - (parseInt(numpadInput) || 0))"></span>
                    </div>
                </div>

                <!-- Baris Preset Pecahan Uang Cepat -->
                <div class="space-y-1.5">
                    <div class="text-[10px] font-mono uppercase tracking-wider text-[#8A7B66] font-semibold">
                        Pecahan Uang Kertas & Uang Pas
                    </div>
                    <div class="grid grid-cols-4 gap-1.5">
                        <button type="button"
                                @click="numpadExact()"
                                class="py-2 px-1 text-center font-mono text-xs font-bold rounded-xl border border-[#D9973E]/50 bg-[#261D16] hover:bg-[#D9973E] text-[#D9973E] hover:text-[#1F1812] transition-all cursor-pointer active:scale-95 shadow-2xs">
                            Uang Pas
                        </button>
                        <button type="button"
                                @click="numpadSet(50000)"
                                class="py-2 px-1 text-center font-mono text-xs font-semibold rounded-xl border border-[#3A2D22] bg-[#1E1711] hover:bg-[#32261C] text-[#FAF7F2] hover:text-[#D9973E] transition cursor-pointer active:scale-95">
                            50.000
                        </button>
                        <button type="button"
                                @click="numpadSet(100000)"
                                class="py-2 px-1 text-center font-mono text-xs font-semibold rounded-xl border border-[#3A2D22] bg-[#1E1711] hover:bg-[#32261C] text-[#FAF7F2] hover:text-[#D9973E] transition cursor-pointer active:scale-95">
                            100.000
                        </button>
                        <button type="button"
                                @click="numpadSet(200000)"
                                class="py-2 px-1 text-center font-mono text-xs font-semibold rounded-xl border border-[#3A2D22] bg-[#1E1711] hover:bg-[#32261C] text-[#FAF7F2] hover:text-[#D9973E] transition cursor-pointer active:scale-95">
                            200.000
                        </button>
                    </div>
                    <!-- Tambah Cepat (+5k, +10k, +20k, +50k) -->
                    <div class="grid grid-cols-4 gap-1.5 pt-0.5">
                        <button type="button"
                                @click="numpadAdd(5000)"
                                class="py-1.5 px-1 text-center font-mono text-[11px] rounded-lg border border-[#32261C] bg-[#18120D] hover:bg-[#2A2017] text-[#A89A85] hover:text-[#FAF7F2] transition cursor-pointer active:scale-95">
                            +5.000
                        </button>
                        <button type="button"
                                @click="numpadAdd(10000)"
                                class="py-1.5 px-1 text-center font-mono text-[11px] rounded-lg border border-[#32261C] bg-[#18120D] hover:bg-[#2A2017] text-[#A89A85] hover:text-[#FAF7F2] transition cursor-pointer active:scale-95">
                            +10.000
                        </button>
                        <button type="button"
                                @click="numpadAdd(20000)"
                                class="py-1.5 px-1 text-center font-mono text-[11px] rounded-lg border border-[#32261C] bg-[#18120D] hover:bg-[#2A2017] text-[#A89A85] hover:text-[#FAF7F2] transition cursor-pointer active:scale-95">
                            +20.000
                        </button>
                        <button type="button"
                                @click="numpadAdd(50000)"
                                class="py-1.5 px-1 text-center font-mono text-[11px] rounded-lg border border-[#32261C] bg-[#18120D] hover:bg-[#2A2017] text-[#A89A85] hover:text-[#FAF7F2] transition cursor-pointer active:scale-95">
                            +50.000
                        </button>
                    </div>
                </div>

                <!-- Grid Touch Keypad Number (3 Kolom x 4 Baris) -->
                <div class="grid grid-cols-3 gap-2 pt-1">
                    <button type="button" @click="numpadPress('7')"
                            class="py-3 sm:py-3.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] font-mono text-xl sm:text-2xl font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">7</button>
                    <button type="button" @click="numpadPress('8')"
                            class="py-3 sm:py-3.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] font-mono text-xl sm:text-2xl font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">8</button>
                    <button type="button" @click="numpadPress('9')"
                            class="py-3 sm:py-3.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] font-mono text-xl sm:text-2xl font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">9</button>

                    <button type="button" @click="numpadPress('4')"
                            class="py-3 sm:py-3.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] font-mono text-xl sm:text-2xl font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">4</button>
                    <button type="button" @click="numpadPress('5')"
                            class="py-3 sm:py-3.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] font-mono text-xl sm:text-2xl font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">5</button>
                    <button type="button" @click="numpadPress('6')"
                            class="py-3 sm:py-3.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] font-mono text-xl sm:text-2xl font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">6</button>

                    <button type="button" @click="numpadPress('1')"
                            class="py-3 sm:py-3.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] font-mono text-xl sm:text-2xl font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">1</button>
                    <button type="button" @click="numpadPress('2')"
                            class="py-3 sm:py-3.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] font-mono text-xl sm:text-2xl font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">2</button>
                    <button type="button" @click="numpadPress('3')"
                            class="py-3 sm:py-3.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] font-mono text-xl sm:text-2xl font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">3</button>

                    <button type="button" @click="numpadPress('C')"
                            title="Reset 0"
                            class="py-3 sm:py-3.5 bg-[#2C1814] hover:bg-[#C4553D] border border-[#4A2620] text-[#E57373] hover:text-white font-mono text-lg sm:text-xl font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">C</button>
                    <button type="button" @click="numpadPress('0')"
                            class="py-3 sm:py-3.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#FAF7F2] hover:text-[#D9973E] font-mono text-xl sm:text-2xl font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">0</button>
                    <button type="button" @click="numpadPress('000')"
                            title="Tambah 3 nol (ribu)"
                            class="py-3 sm:py-3.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#D9973E] font-mono text-base sm:text-lg font-bold rounded-xl transition-all active:scale-95 shadow-2xs cursor-pointer">000</button>
                </div>

                <!-- Tombol Hapus 1 Digit (Backspace) & Aksi Cepat -->
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" @click="numpadPress('backspace')"
                            class="col-span-1 py-2.5 bg-[#1C140E] hover:bg-[#2A1F16] border border-[#3A2D22] text-[#A89A85] hover:text-[#FAF7F2] font-mono text-xs uppercase tracking-wider rounded-xl transition cursor-pointer flex items-center justify-center gap-1.5 active:scale-95">
                        <span>⌫</span>
                        <span>Hapus</span>
                    </button>
                    <button type="button" @click="numpadPress('00')"
                            class="col-span-1 py-2.5 bg-[#221912] hover:bg-[#32261C] border border-[#3A2D22] text-[#A89A85] hover:text-[#FAF7F2] font-mono text-xs font-bold rounded-xl transition cursor-pointer flex items-center justify-center active:scale-95">
                        00
                    </button>
                    <button type="button" @click="closeNumpad()"
                            class="col-span-1 py-2.5 bg-[#1C140E] hover:bg-[#2A1F16] border border-[#3A2D22] text-[#A89A85] hover:text-[#FAF7F2] font-mono text-xs uppercase tracking-wider rounded-xl transition cursor-pointer flex items-center justify-center active:scale-95">
                        Tutup
                    </button>
                </div>
            </div>

            <!-- Footer: Aksi Terapkan / Bayar Langsung -->
            <div class="p-3.5 sm:p-4 bg-[#261D16] border-t border-[#3A2D22] flex items-center justify-between gap-2.5">
                <button type="button"
                        @click="closeNumpad()"
                        class="px-4 py-2.5 rounded-xl border border-[#3A2D22] text-[#A89A85] hover:text-[#FAF7F2] hover:bg-[#1C140E] font-mono text-xs uppercase tracking-wider transition cursor-pointer">
                    Batal
                </button>
                <div class="flex items-center gap-2 flex-1 justify-end">
                    <button type="button"
                            @click="closeNumpad()"
                            class="px-4 py-2.5 rounded-xl bg-[#2E2319] hover:bg-[#3D2F22] border border-[#4A392A] text-[#FAF7F2] font-mono text-xs uppercase tracking-wider font-bold transition shadow-xs cursor-pointer active:scale-98">
                        ✓ Terapkan
                    </button>
                    <button type="button"
                            @click="applyNumpadAndSubmit()"
                            :disabled="items.length === 0 || submitting || (parseInt(numpadInput) || 0) < total"
                            class="flex-1 max-w-[180px] bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] hover:text-white py-2.5 px-3 rounded-xl font-mono text-xs uppercase tracking-[0.1em] font-bold transition-all shadow-md active:scale-98 disabled:opacity-40 disabled:cursor-not-allowed text-center cursor-pointer">
                        <span x-show="!submitting">Bayar Sekarang ›</span>
                        <span x-show="submitting">Memproses…</span>
                    </button>
                </div>
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
        showModifierModal: false,
        showPromoModal: false,
        editingItemIndex: null,
        modifierMenu: null,
        modOptions: {
            temperature: 'ice',
            sugar: 'normal',
            ice_level: 'normal',
            note: '',
            qty: 1,
        },
        orderType: 'dine_in',
        method: 'cash',
        activePromos: @json($activePromos ?? []),
        promoInput: '',
        appliedPromo: null,
        promoLoading: false,
        promoMessage: '',
        promoStatus: '',
        discountMode: 'promo',
        manualDiscount: 0,
        showPromoHints: false,
        paid: 0,
        customerName: '',
        error: '',
        submitting: false,
        showSuccessModal: false,
        completedOrder: null,
        showNumpadModal: false,
        numpadInput: '0',

        get sortedItems() {
            return [...this.items].sort((a, b) => {
                const catDiff = (a.category_order ?? 999) - (b.category_order ?? 999);
                if (catDiff !== 0) return catDiff;
                return a.name.localeCompare(b.name);
            });
        },

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
        get discount() {
            if (this.discountMode === 'manual') {
                return Math.min(Math.max(parseInt(this.manualDiscount) || 0, 0), this.subtotal);
            }
            if (!this.appliedPromo) return 0;
            if (this.subtotal < (this.appliedPromo.min_order || 0)) return 0;

            if (this.appliedPromo.type === 'percentage') {
                let calc = Math.round((this.subtotal * this.appliedPromo.discount_value) / 100);
                if (this.appliedPromo.max_discount && this.appliedPromo.max_discount > 0) {
                    calc = Math.min(calc, this.appliedPromo.max_discount);
                }
                return Math.min(calc, this.subtotal);
            } else {
                return Math.min(this.appliedPromo.discount_value || 0, this.subtotal);
            }
        },
        get total() { return Math.max(this.subtotal - this.discount, 0); },
        get change() { return (parseInt(this.paid) || 0) - this.total; },

        fmt(v) { return 'Rp ' + (v || 0).toLocaleString('id-ID'); },

        getCatMeta(slug) {
            const colors = @json($categoryColors);
            return colors[slug] || colors['all'] || {
                svg: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6a7 7 0 00-7 7h14a7 7 0 00-7-7zM12 3v3M4 17h16a1 1 0 011 1v1H3v-1a1 1 0 011-1z"/></svg>',
                color: '#D9973E',
                bg_light: '#FDF4E7',
                text_light: '#B4721D',
                border_light: '#F5DCB8',
            };
        },

        selectMenu(m) {
            if (!m || !m.available) return;
            this.addDirect(m, 1);
        },

        addDirect(m, qty = 1) {
            if (m.is_drink) {
                const defaultOpts = {
                    temperature: 'ice',
                    sugar: 'normal',
                    ice_level: 'normal',
                    note: '',
                    qty: qty,
                };
                const optionKey = `${m.id}_ice_normal_normal_`;
                const found = this.items.find(i => i.option_key === optionKey);
                if (found) {
                    found.qty += qty;
                } else {
                    this.items.push({
                        id: m.id,
                        name: m.name,
                        price: m.price,
                        qty: qty,
                        category_id: m.category_id,
                        category_slug: m.category_slug,
                        category_name: m.category_name,
                        category_order: m.category_order ?? 999,
                        is_drink: true,
                        options: defaultOpts,
                        note_string: '🧊 Ice • Normal Sugar',
                        option_key: optionKey,
                    });
                }
            } else {
                const optionKey = `${m.id}_direct`;
                const found = this.items.find(i => i.option_key === optionKey);
                if (found) {
                    found.qty += qty;
                } else {
                    this.items.push({
                        id: m.id,
                        name: m.name,
                        price: m.price,
                        qty: qty,
                        category_id: m.category_id,
                        category_slug: m.category_slug,
                        category_name: m.category_name,
                        category_order: m.category_order ?? 999,
                        is_drink: false,
                        options: null,
                        note_string: '',
                        option_key: optionKey,
                    });
                }
            }

            this.error = '';
            if (this.method === 'cash' && this.paid < this.total) {
                this.paid = this.total;
            }
        },

        saveModifier() {
            if (!this.modifierMenu) return;
            const m = this.modifierMenu;
            const opts = { ...this.modOptions };

            const noteParts = [];
            if (m.is_drink) {
                if (opts.temperature === 'hot') {
                    noteParts.push('🔥 Hot');
                } else if (opts.temperature === 'ice') {
                    const iceText = opts.ice_level === 'normal' ? '🧊 Ice' : `🧊 Ice (${opts.ice_level === 'less' ? 'Less Ice' : 'No Ice'})`;
                    noteParts.push(iceText);
                }

                if (opts.sugar === 'less') {
                    noteParts.push('Less Sugar');
                } else if (opts.sugar === 'no') {
                    noteParts.push('No Sugar');
                } else if (opts.sugar === 'normal') {
                    noteParts.push('Normal Sugar');
                }
            }

            const noteClean = (opts.note || '').trim();
            if (noteClean) {
                noteParts.push(noteClean);
            }
            const noteString = noteParts.join(' • ');
            const optionKey = m.is_drink
                ? `${m.id}_${opts.temperature}_${opts.sugar}_${opts.ice_level}_${noteClean.toLowerCase()}`
                : `${m.id}_${noteClean.toLowerCase()}`;

            if (this.editingItemIndex !== null) {
                const existingOtherIndex = this.items.findIndex((it, idx) => idx !== this.editingItemIndex && it.option_key === optionKey);
                if (existingOtherIndex !== -1) {
                    this.items[existingOtherIndex].qty += opts.qty;
                    this.items.splice(this.editingItemIndex, 1);
                } else {
                    const item = this.items[this.editingItemIndex];
                    if (item) {
                        item.options = opts;
                        item.note_string = noteString;
                        item.option_key = optionKey;
                        item.qty = opts.qty;
                    }
                }
            } else {
                const existing = this.items.find(i => i.option_key === optionKey);
                if (existing) {
                    existing.qty += opts.qty;
                } else {
                    this.items.push({
                        id: m.id,
                        name: m.name,
                        price: m.price,
                        qty: opts.qty,
                        category_id: m.category_id,
                        category_slug: m.category_slug,
                        category_name: m.category_name,
                        category_order: m.category_order ?? 999,
                        is_drink: m.is_drink,
                        options: opts,
                        note_string: noteString,
                        option_key: optionKey,
                    });
                }
            }

            this.showModifierModal = false;
            this.modifierMenu = null;
            this.editingItemIndex = null;
            this.error = '';
            if (this.method === 'cash' && this.paid < this.total) {
                this.paid = this.total;
            }
        },

        editItem(item) {
            const idx = this.items.indexOf(item);
            if (idx === -1) return;
            const m = this.menus.find(x => x.id === item.id);
            if (!m) return;
            this.editingItemIndex = idx;
            this.modifierMenu = m;
            this.modOptions = {
                temperature: item.options?.temperature || (m.is_drink ? 'ice' : ''),
                sugar: item.options?.sugar || (m.is_drink ? 'normal' : ''),
                ice_level: item.options?.ice_level || (m.is_drink ? 'normal' : ''),
                note: item.options?.note || (!m.is_drink ? (item.note_string || '') : ''),
                qty: item.qty,
            };
            this.showModifierModal = true;
        },

        add(id) {
            const m = this.menus.find(x => x.id === id);
            if (m) this.selectMenu(m);
        },

        inc(target) {
            const item = typeof target === 'object' ? target : this.items[target];
            if (!item) return;
            item.qty++;
            if (this.method === 'cash' && this.paid < this.total) this.paid = this.total;
        },

        dec(target) {
            const item = typeof target === 'object' ? target : this.items[target];
            if (!item) return;
            item.qty--;
            if (item.qty <= 0) {
                const idx = this.items.indexOf(item);
                if (idx !== -1) this.items.splice(idx, 1);
            }
            if (this.method === 'cash' && this.paid > this.total && this.items.length === 0) this.paid = 0;
        },

        remove(target) {
            const item = typeof target === 'object' ? target : this.items[target];
            if (!item) return;
            const idx = this.items.indexOf(item);
            if (idx !== -1) this.items.splice(idx, 1);
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
                this.removePromo();
                this.manualDiscount = 0;
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
            this.paid = this.total;
        },

        async applyPromo(code = null) {
            const targetCode = (code || this.promoInput || '').trim();
            if (!targetCode) {
                this.promoMessage = 'Masukkan kode promo terlebih dahulu.';
                this.promoStatus = 'error';
                return;
            }
            if (this.subtotal <= 0) {
                this.promoMessage = 'Tambahkan menu ke keranjang terlebih dahulu.';
                this.promoStatus = 'error';
                return;
            }
            this.promoLoading = true;
            this.promoMessage = '';
            this.promoStatus = '';
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch(@js(route('kasir.promo.check', [], false)), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        code: targetCode,
                        subtotal: this.subtotal,
                    }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.appliedPromo = data;
                    this.discountMode = 'promo';
                    this.promoInput = data.code;
                    this.promoStatus = 'success';
                    this.promoMessage = data.message;
                    this.showPromoHints = false;
                    this.showPromoModal = false;
                    this.paid = this.total;
                    if (window.customToast) {
                        window.customToast({
                            message: `Kode promo ${data.code} berhasil diterapkan! Hemat ${this.fmt(this.discount)}`,
                            type: 'success'
                        });
                    }
                } else {
                    this.promoStatus = 'error';
                    this.promoMessage = data.message || 'Kode promo tidak dapat digunakan.';
                }
            } catch (e) {
                this.promoStatus = 'error';
                this.promoMessage = 'Gagal memeriksa kode promo.';
            } finally {
                this.promoLoading = false;
            }
        },

        removePromo() {
            this.appliedPromo = null;
            this.promoInput = '';
            this.promoMessage = '';
            this.promoStatus = '';
            this.paid = this.total;
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
                        items: this.items.map(i => ({ menu_id: i.id, qty: i.qty, note: i.note_string || null })),
                        order_type: this.orderType,
                        payment_method: this.method,
                        promo_code: (this.discountMode === 'promo' && this.appliedPromo) ? this.appliedPromo.code : null,
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

                    // Update badge KDS di sidebar secara realtime
                    if (typeof data.kds_count !== 'undefined') {
                        window.dispatchEvent(new CustomEvent('kds:count', { detail: { count: Number(data.kds_count) } }));
                        if (typeof BroadcastChannel !== 'undefined') {
                            try {
                                const ch = new BroadcastChannel('cafe_soundstation_sync');
                                ch.postMessage({ type: 'KDS_COUNT_UPDATE', count: Number(data.kds_count) });
                            } catch (e) {}
                        }
                    }

                    // Cetak struk otomatis di background via hidden iframe tanpa reload / navigasi halaman
                    this.printReceiptSilently(data.receipt_url);
                    // Reset keranjang untuk transaksi berikutnya
                    this.items = [];
                    this.removePromo();
                    this.manualDiscount = 0;
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

        handleSuccessModalKey(e) {
            if (!this.showSuccessModal) return;
            const key = (e.key || '').toLowerCase();
            if (key === 'escape' || key === 'enter') {
                e.preventDefault();
                this.closeSuccessModal();
            } else if (key === 'k') {
                e.preventDefault();
                this.goToKitchen();
            } else if (key === 'p') {
                e.preventDefault();
                this.reprintReceipt();
            }
        },

        goToKitchen() {
            this.closeSuccessModal();
            const kitchenUrl = @js(route('kasir.kitchen.index'));
            if (typeof swapKasirPage === 'function') {
                swapKasirPage(kitchenUrl, true);
            } else {
                window.location.href = kitchenUrl;
            }
        },

        closeSuccessModal() {
            this.showSuccessModal = false;
            this.completedOrder = null;
        },

        openNumpad() {
            if (this.method !== 'cash') return;
            const initial = (this.paid > 0) ? this.paid : (this.total > 0 ? this.total : 0);
            this.numpadInput = String(initial > 0 ? initial : '0');
            this.paid = parseInt(this.numpadInput) || 0;
            this.showNumpadModal = true;
        },

        closeNumpad() {
            this.showNumpadModal = false;
        },

        numpadPress(val) {
            let current = String(this.numpadInput || '0');
            if (val === 'C') {
                current = '0';
            } else if (val === 'backspace') {
                current = current.slice(0, -1);
                if (!current || current === '') current = '0';
            } else if (val === '000') {
                if (current !== '0' && current.length < 9) {
                    current += '000';
                }
            } else if (val === '00') {
                if (current !== '0' && current.length < 10) {
                    current += '00';
                }
            } else {
                // Digit 0-9
                if (current === '0') {
                    current = String(val);
                } else if (current.length < 11) {
                    current += String(val);
                }
            }
            this.numpadInput = current;
            this.paid = parseInt(current) || 0;
        },

        numpadSet(amount) {
            this.paid = amount;
            this.numpadInput = String(amount);
        },

        numpadAdd(amount) {
            const current = parseInt(this.numpadInput) || 0;
            const next = current + amount;
            this.paid = next;
            this.numpadInput = String(next);
        },

        numpadExact() {
            this.setExactPaid();
            this.numpadInput = String(this.paid);
        },

        applyNumpadAndSubmit() {
            this.closeNumpad();
            if (this.items.length > 0 && this.paid >= this.total && !this.submitting) {
                this.submit();
            }
        },

        handleNumpadKey(e) {
            if (!this.showNumpadModal) return;
            const key = e.key;
            if (key === 'Escape') {
                e.preventDefault();
                this.closeNumpad();
            } else if (key === 'Enter') {
                e.preventDefault();
                if (this.paid >= this.total && this.items.length > 0) {
                    this.applyNumpadAndSubmit();
                } else {
                    this.closeNumpad();
                }
            } else if (key >= '0' && key <= '9') {
                e.preventDefault();
                this.numpadPress(key);
            } else if (key === 'Backspace') {
                e.preventDefault();
                this.numpadPress('backspace');
            } else if (key === 'c' || key === 'C' || key === 'Delete') {
                e.preventDefault();
                this.numpadPress('C');
            }
        },
    };
}
</script>
@endsection

