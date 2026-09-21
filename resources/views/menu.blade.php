@extends('layouts.public')

@section('title', 'Menu & Kandungan')

@section('content')
    <section class="max-w-6xl mx-auto px-4 sm:px-6 py-12"
             x-data="{
                 activeMenu: null,
                 isOpen: false,

                 openDetail(menu) {
                     this.activeMenu = menu;
                     this.isOpen = true;
                     document.body.style.overflow = 'hidden';
                 },

                 closeDetail() {
                     this.isOpen = false;
                     document.body.style.overflow = '';
                 },

                 getCaffeineLevel() {
                     if (!this.activeMenu || !this.activeMenu.nutrition?.caffeine) return { text: 'Bebas Kafein (0 mg)', level: 0, color: 'text-[#5F7F42] bg-[#5F7F42]/10 border-[#5F7F42]/20' };
                     const c = parseInt(this.activeMenu.nutrition.caffeine) || 0;
                     if (c === 0) return { text: 'Bebas Kafein (0 mg)', level: 0, color: 'text-[#5F7F42] bg-[#5F7F42]/10 border-[#5F7F42]/20' };
                     if (c <= 40) return { text: `Kafein Ringan (${c} mg)`, level: 1, color: 'text-sky-700 bg-sky-50 border-sky-200' };
                     if (c <= 90) return { text: `Kafein Sedang (${c} mg)`, level: 2, color: 'text-amber-800 bg-amber-50 border-amber-200' };
                     return { text: `Kafein Tinggi (${c} mg)`, level: 3, color: 'text-rose-800 bg-rose-50 border-rose-200' };
                 }
             }">

        <!-- Header (Mobile-First Cafe Atmosphere) -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-[#E4DCCC] pb-6 sm:pb-8">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#B5762A]/10 border border-[#B5762A]/25 text-[#B5762A] font-mono text-[10px] sm:text-xs font-bold uppercase tracking-wider mb-2.5">
                    <span>☕</span>
                    <span>Buku Menu Meja • {{ config('cafe.name') }}</span>
                </div>
                <h1 class="font-serif text-3xl sm:text-4xl tracking-tight font-bold text-[#1F1812]">Semua yang kami seduh & sajikan.</h1>
                <p class="mt-2 text-xs sm:text-sm text-[#7A6A58] max-w-2xl leading-relaxed">
                    Sentuh menu mana saja untuk melihat rincian komposisi bahan baku segar, takaran gizi, alergen, dan profil karakter rasa racikan barista.
                </p>
            </div>
            <div class="inline-flex items-center gap-2 font-mono text-xs text-[#B5762A] bg-[#B5762A]/10 border border-[#B5762A]/30 px-3 py-1.5 rounded-xl self-start sm:self-auto shrink-0 shadow-2xs">
                <span>💡</span>
                <span>Klik kartu untuk info gizi & bahan</span>
            </div>
        </div>

        <!-- STICKY MOBILE CATEGORY JUMP BAR (Mudah Dijangkau Jari Pelanggan di Meja Kafe) -->
        <div class="sticky top-16 z-30 bg-[#F7F3EC]/95 backdrop-blur-md py-2.5 -mx-4 px-4 sm:mx-0 sm:px-0 border-b border-[#E4DCCC]">
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar scroll-smooth py-0.5">
                @foreach ($categories as $catIdx => $category)
                    <a href="#category-{{ $category->id }}"
                       class="px-3 py-1.5 rounded-xl font-mono text-xs font-medium text-[#5C4D3C] bg-white hover:bg-[#1F1812] hover:text-white border border-[#E4DCCC] hover:border-[#1F1812] transition shrink-0 shadow-2xs flex items-center gap-1.5 active:scale-95">
                        <span class="text-[#B5762A] font-bold">{{ str_pad($catIdx + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <span>{{ $category->name }}</span>
                        <span class="text-[10px] text-[#8A7B66]">({{ $category->menus->count() }})</span>
                    </a>
                @endforeach
            </div>
        </div>

        @forelse ($categories as $index => $category)
            <div id="category-{{ $category->id }}" class="mt-10 sm:mt-14 scroll-mt-28 sm:scroll-mt-32">
                <div class="flex items-baseline gap-3 sm:gap-4 border-b border-[#E4DCCC]/60 pb-3">
                    <span class="font-mono text-xs sm:text-sm text-[#B5762A] font-semibold">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }} //</span>
                    <h2 class="font-serif text-xl sm:text-2xl tracking-tight font-bold text-[#1F1812]">{{ $category->name }}</h2>
                    <span class="font-mono text-xs text-[#8A7B66]">({{ $category->menus->count() }} pilihan)</span>
                </div>

                <div class="mt-5 sm:mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                    @foreach ($category->menus as $menu)
                        @php
                            $menuPayload = [
                                'id' => $menu->id,
                                'name' => $menu->name,
                                'category' => $category->name,
                                'price_fmt' => 'Rp ' . number_format($menu->price, 0, ',', '.'),
                                'description' => $menu->description ?? '',
                                'image_url' => $menu->image ? asset('storage/' . $menu->image) : null,
                                'initials' => strtoupper(substr($menu->name, 0, 2)),
                                'ingredients' => $menu->detailed_ingredients,
                                'nutrition' => $menu->detailed_nutrition,
                                'flavor_notes' => $menu->detailed_flavor_notes,
                                'barista_notes' => $menu->detailed_barista_notes,
                            ];
                        @endphp

                        <div @click="openDetail({{ json_encode($menuPayload) }})"
                             class="bg-[#FAF7F2] hover:bg-white border border-[#E8E1D5] hover:border-[#B5762A]/60 rounded-2xl h-full flex flex-col justify-between cursor-pointer group hover:shadow-md hover:-translate-y-0.5 active:scale-[0.99] transition-all duration-200 overflow-hidden">
                            <div class="flex-1 flex flex-col">
                                <!-- Smart Frame Image Container -->
                                <div class="p-2 sm:p-2.5 pb-0">
                                    <div class="relative aspect-[4/3] w-full overflow-hidden rounded-xl bg-[#2A211A] border border-[#E8E1D5] shadow-2xs group-hover:border-[#B5762A]/40 transition-colors">
                                        @if ($menu->image)
                                            <img src="{{ asset('storage/' . $menu->image) }}"
                                                 alt="{{ $menu->name }}"
                                                 loading="lazy"
                                                 class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500 ease-out">
                                        @else
                                            <div class="w-full h-full bg-gradient-to-br from-[#2A211A] via-[#1F1812] to-[#140E0A] flex flex-col items-center justify-center p-4 relative overflow-hidden">
                                                <!-- Ambient cafe watermark ring -->
                                                <div class="absolute -right-5 -bottom-5 w-24 h-24 rounded-full border border-[#D9973E]/10 pointer-events-none"></div>
                                                <div class="absolute -left-5 -top-5 w-24 h-24 rounded-full border border-[#D9973E]/10 pointer-events-none"></div>

                                                <span class="font-serif text-3xl sm:text-4xl font-bold text-[#D9973E] tracking-wider drop-shadow-xs">
                                                    {{ strtoupper(substr($menu->name, 0, 2)) }}
                                                </span>
                                                <span class="text-[8px] sm:text-[9px] font-mono uppercase tracking-[0.25em] text-[#A89A85] mt-1">
                                                    {{ config('cafe.name') }}
                                                </span>
                                            </div>
                                        @endif

                                        <!-- Inner Vignette Framing Ring -->
                                        <div class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-black/10 rounded-xl"></div>

                                        <!-- Smart Floating Category Tag -->
                                        <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5">
                                            <span class="font-mono text-[9px] uppercase tracking-wider font-semibold px-2 py-0.5 rounded-full bg-[#1F1812]/85 text-[#FAF7F2] backdrop-blur-xs border border-white/10 shadow-2xs">
                                                {{ $category->name }}
                                            </span>
                                        </div>

                                        <!-- Interactive Smart Hover Overlay -->
                                        <div class="absolute inset-0 bg-[#1F1812]/35 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-[2px]">
                                            <span class="bg-[#FAF7F2] text-[#1F1812] font-mono text-[11px] uppercase tracking-wider px-3.5 py-1.5 shadow-md border border-[#B5762A]/40 font-semibold rounded-lg">
                                                Lihat Bahan & Gizi ↗
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 sm:p-5 flex-1 flex flex-col">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold">{{ $category->name }}</span>
                                        <span class="inline-flex items-center gap-1 font-mono text-[9px] uppercase tracking-widest text-[#5F7F42] font-semibold">
                                            ● Tersedia
                                        </span>
                                    </div>

                                    <h3 class="mt-2 font-serif text-lg sm:text-xl font-bold tracking-tight text-[#1F1812] group-hover:text-[#B5762A] transition-colors leading-snug">
                                        {{ $menu->name }}
                                    </h3>

                                    @if ($menu->detailed_flavor_notes)
                                        <p class="mt-1 font-serif italic text-xs text-[#8A7B66] line-clamp-1">
                                            ✦ {{ str_replace(',', ' · ', $menu->detailed_flavor_notes) }}
                                        </p>
                                    @endif

                                    <p class="mt-2 text-xs sm:text-sm text-[#6B5A4B] font-serif italic leading-relaxed break-words line-clamp-2">
                                        {{ $menu->description ?? '' }}
                                    </p>
                                </div>
                            </div>

                            <div class="px-4 sm:px-5 pb-4 sm:pb-5 pt-3 border-t border-[#E8E1D5]/70 flex items-center justify-between gap-2 bg-[#FAF7F2]/40">
                                <span class="font-serif text-lg sm:text-xl font-bold text-[#B5762A]">
                                    Rp {{ number_format($menu->price, 0, ',', '.') }}
                                </span>
                                <span class="font-mono text-xs font-semibold text-[#B5762A] group-hover:text-[#1F1812] flex items-center gap-1 transition-colors">
                                    <span>Bahan & Gizi</span>
                                    <span class="text-xs">↗</span>
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="mt-10 text-[#8A7B66]">Menu akan segera tersedia.</p>
        @endforelse

        <!-- MODAL POPUP: DETAIL BAHAN & GIZI (MINIMALIS ARTISAN CAFE SHEET - TANPA KOTAK BERTUMPUK) -->
        <div x-show="isOpen"
             x-cloak
             @keydown.escape.window="closeDetail()"
             class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-[#0F0A07]/75 backdrop-blur-xs"
             style="display: none;">

            <!-- Backdrop Click Area -->
            <div class="fixed inset-0" @click="closeDetail()"></div>

            <!-- Modal Window (Seamless Paper/Card - No Nested Cards!) -->
            <div x-show="isOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95"
                 class="relative bg-[#FAF7F2] border-t sm:border border-[#D5CCC0] shadow-2xl rounded-t-[28px] sm:rounded-3xl max-w-full sm:max-w-md md:max-w-lg w-full max-h-[88dvh] sm:max-h-[82vh] flex flex-col overflow-hidden text-[#1F1812] z-10"
                 style="background-color: #FAF7F2 !important;">

                <!-- Mobile Drag Handle -->
                <div class="w-10 h-1 bg-[#D5CCC0] rounded-full mx-auto mt-3 mb-1 sm:hidden shrink-0"></div>

                <!-- Minimal Top Bar -->
                <div class="px-5 sm:px-6 pt-3.5 pb-2.5 flex items-center justify-between border-b border-[#E8E1D5] shrink-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="font-mono text-[10px] uppercase tracking-[0.25em] font-bold text-[#8A7B66]"
                              x-text="activeMenu?.category"></span>
                        <span class="text-[#D5CCC0]">&bull;</span>
                        <span class="font-serif italic text-xs text-[#7A6A58] truncate">
                            Informasi Kandungan & Karakteristik
                        </span>
                    </div>
                    <button @click="closeDetail()"
                            type="button"
                            class="w-7 h-7 rounded-full bg-stone-200/70 hover:bg-stone-300 text-[#5C4D3C] hover:text-[#1F1812] flex items-center justify-center transition-colors text-xs font-mono font-bold cursor-pointer active:scale-90"
                            title="Tutup">
                        ✕
                    </button>
                </div>

                <!-- Seamless Scrollable Content (Model Kafe Artisan - Rapi & Tertata Bersih) -->
                <div class="px-5 sm:px-6 py-4 overflow-y-auto space-y-4 flex-1">

                    <!-- Header Hero: Visual & Typographic Title -->
                    <div class="flex items-start gap-4">
                        <!-- Smart Frame Thumbnail -->
                        <div class="w-20 h-20 sm:w-24 sm:h-24 shrink-0 rounded-2xl p-1 bg-white border border-[#E8E1D5] shadow-xs">
                            <div class="w-full h-full rounded-xl overflow-hidden relative bg-gradient-to-br from-[#2A211A] via-[#1F1812] to-[#140E0A]">
                                <template x-if="activeMenu?.image_url">
                                    <img :src="activeMenu.image_url" :alt="activeMenu.name" class="w-full h-full object-cover object-center">
                                </template>
                                <template x-if="!activeMenu?.image_url">
                                    <div class="w-full h-full flex flex-col items-center justify-center text-[#D9973E]">
                                        <span class="font-serif text-2xl sm:text-3xl font-bold" x-text="activeMenu?.initials"></span>
                                    </div>
                                </template>
                                <div class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-black/10 rounded-xl"></div>
                            </div>
                        </div>

                        <!-- Title, Price & Flavor Tagline -->
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-1">
                                <h2 class="font-serif text-xl sm:text-2xl font-bold tracking-tight text-[#1F1812] leading-tight"
                                    x-text="activeMenu?.name"></h2>
                                <div class="font-serif text-lg sm:text-xl font-bold text-[#B5762A] whitespace-nowrap shrink-0"
                                     x-text="activeMenu?.price_fmt"></div>
                            </div>

                            <p class="mt-1 text-xs sm:text-sm text-[#5C4D3C] font-serif italic leading-relaxed line-clamp-2"
                               x-text="activeMenu?.description || 'Racikan istimewa barista dengan bahan baku segar pilihan.'"></p>
                        </div>
                    </div>

                    <!-- 3-Column Micro Spec Strip (Rapi, Seimbang, Tanpa Kotak Tebal) -->
                    <div class="grid grid-cols-3 divide-x divide-[#E8E1D5] border-y border-[#E8E1D5] py-2.5 text-center">
                        <div class="px-2">
                            <span class="block font-mono text-[9px] uppercase tracking-wider text-[#8A7B66] font-bold">Kafein</span>
                            <span class="font-mono text-xs sm:text-sm font-bold text-[#1F1812]" x-text="activeMenu?.nutrition?.caffeine || '0 mg'"></span>
                        </div>
                        <div class="px-2">
                            <span class="block font-mono text-[9px] uppercase tracking-wider text-[#8A7B66] font-bold">Kalori</span>
                            <span class="font-mono text-xs sm:text-sm font-bold text-[#1F1812]" x-text="activeMenu?.nutrition?.calories || '-'"></span>
                        </div>
                        <div class="px-2">
                            <span class="block font-mono text-[9px] uppercase tracking-wider text-[#8A7B66] font-bold">Gula</span>
                            <span class="font-mono text-xs sm:text-sm font-bold text-[#1F1812]"
                                  x-text="activeMenu?.nutrition?.sugar ? (activeMenu.nutrition.sugar.includes('(') ? activeMenu.nutrition.sugar.substring(0, activeMenu.nutrition.sugar.indexOf('(')).trim() : activeMenu.nutrition.sugar) : '0 g'"></span>
                        </div>
                    </div>

                    <!-- Komposisi & Bahan Baku Utama (Typographic Cafe List - 2 Kolom Rapi) -->
                    <div>
                        <div class="font-mono text-[10px] uppercase tracking-[0.25em] text-[#8A7B66] font-bold mb-2">
                            Komposisi & Bahan Baku Utama
                        </div>
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-y-1.5 gap-x-4 text-xs sm:text-sm font-serif text-[#1F1812]">
                            <template x-for="item in (activeMenu?.ingredients || [])" :key="item">
                                <li class="flex items-center gap-2">
                                    <span class="text-[#B5762A] text-[10px] shrink-0">✦</span>
                                    <span x-text="item" class="leading-tight"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Divider Garis Kafe Halus -->
                    <div class="border-t border-[#E8E1D5]"></div>

                    <!-- Karakter Rasa & Alergen (Penataan 2 Kolom Rapi) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm font-serif">
                        <div>
                            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold mb-1">
                                Catatan Rasa
                            </div>
                            <p class="italic text-[#2A211A] leading-relaxed"
                               x-text="activeMenu?.flavor_notes || 'Seimbang, segar, aroma khas racikan kafe.'"></p>
                        </div>

                        <div>
                            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold mb-1">
                                Info Alergen & Kustomisasi
                            </div>
                            <p class="leading-relaxed text-[#5C4D3C]">
                                <b class="text-[#1F1812] font-semibold" x-text="activeMenu?.nutrition?.allergens || 'Bebas alergen utama.'"></b>
                                <span class="text-xs text-[#8A7B66] block mt-0.5"
                                      x-text="(activeMenu?.nutrition?.serving ? 'Sajian: ' + activeMenu.nutrition.serving + ' • ' : '') + 'Bisa request susu nabati / less sweet ke kasir'"></span>
                            </p>
                        </div>
                    </div>

                </div>

                <!-- Minimal Bottom Bar -->
                <div class="px-5 sm:px-6 py-3 border-t border-[#E8E1D5] flex items-center justify-between gap-3 shrink-0 bg-[#FAF7F2] pb-[max(0.75rem,env(safe-area-inset-bottom))]"
                     style="background-color: #FAF7F2 !important;">
                    <span class="font-serif italic text-xs text-[#8A7B66]">Pesan langsung di kasir / meja</span>
                    <button @click="closeDetail()"
                            type="button"
                            class="px-5 py-1.5 border border-[#3A3026] text-[#1F1812] hover:bg-[#1F1812] hover:text-[#FAF7F2] rounded-xl font-mono text-xs uppercase tracking-wider font-semibold transition-all shadow-2xs active:scale-95 cursor-pointer">
                        Tutup
                    </button>
                </div>

            </div>
        </div>

    </section>
@endsection
