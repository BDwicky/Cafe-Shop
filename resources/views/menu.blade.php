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
                 }
             }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-[#E4DCCC] pb-8">
            <div>
                <div class="font-mono text-[11px] uppercase tracking-[0.3em] text-[#8A7B66]">Menu {{ config('cafe.name') }}</div>
                <h1 class="mt-2 text-4xl tracking-tight font-medium">Semua yang kami seduh & sajikan.</h1>
                <p class="mt-2 text-sm text-[#8A7B66]">Klik pada kartu menu mana saja untuk melihat rincian lengkap bahan baku, nilai gizi, dan alergen.</p>
            </div>
            <div class="inline-flex items-center gap-2 font-mono text-xs text-[#B5762A] bg-[#B5762A]/10 border border-[#B5762A]/30 px-3 py-1.5 self-start sm:self-auto">
                <span>💡</span>
                <span>Klik kartu untuk info komposisi</span>
            </div>
        </div>

        @forelse ($categories as $index => $category)
            <div class="mt-14">
                <div class="flex items-baseline gap-4 border-b border-[#E4DCCC]/60 pb-3">
                    <span class="font-mono text-sm text-[#B5762A] font-semibold">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }} //</span>
                    <h2 class="text-2xl tracking-tight font-medium uppercase text-[#1F1812]">{{ $category->name }}</h2>
                    <span class="font-mono text-xs text-[#8A7B66]">({{ $category->menus->count() }} pilihan)</span>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
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
                             class="bg-white border border-[#E4DCCC] shadow-[0_1px_3px_rgba(42,33,26,0.06)] h-full flex flex-col justify-between cursor-pointer group hover:border-[#B5762A] hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                            <div>
                                <div class="relative overflow-hidden">
                                    @if ($menu->image)
                                        <img src="{{ asset('storage/' . $menu->image) }}"
                                             alt="{{ $menu->name }}"
                                             class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-48 bg-[#1F1812] flex items-center justify-center group-hover:bg-[#2A211A] transition-colors">
                                            <span class="font-mono text-3xl text-[#A89A85]">{{ strtoupper(substr($menu->name, 0, 2)) }}</span>
                                        </div>
                                    @endif

                                    <!-- Quick Hover Hint -->
                                    <div class="absolute inset-0 bg-[#1F1812]/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[2px]">
                                        <span class="bg-[#F7F3EC] text-[#1F1812] font-mono text-[11px] uppercase tracking-wider px-3.5 py-1.5 shadow-md border border-[#B5762A]/40 font-medium">
                                            Lihat Bahan & Gizi ↗
                                        </span>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">{{ $category->name }}</div>
                                        <span class="inline-flex items-center gap-1 font-mono text-[9px] uppercase tracking-widest text-[#5F7F42] bg-[#5F7F42]/10 px-2 py-0.5 border border-[#5F7F42]/20">
                                            Tersedia
                                        </span>
                                    </div>
                                    <h3 class="mt-1 text-lg tracking-tight font-medium text-[#1F1812] group-hover:text-[#B5762A] transition-colors">
                                        {{ $menu->name }}
                                    </h3>
                                    <p class="mt-2 text-sm text-[#8A7B66] leading-relaxed break-words">
                                        {{ $menu->description ?? '' }}
                                    </p>
                                </div>
                            </div>

                            <div class="px-5 pb-5 pt-2 border-t border-[#E4DCCC]/40 flex items-center justify-between">
                                <span class="font-mono text-xl font-medium text-[#B5762A]">
                                    Rp {{ number_format($menu->price, 0, ',', '.') }}
                                </span>
                                <span class="font-mono text-[11px] text-[#8A7B66] group-hover:text-[#B5762A] group-hover:underline inline-flex items-center gap-1 font-medium transition-colors">
                                    Bahan & Gizi ›
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="mt-10 text-[#8A7B66]">Menu akan segera tersedia.</p>
        @endforelse

        <!-- MODAL POPUP: DETAIL BAHAN & KANDUNGAN -->
        <div x-show="isOpen"
             x-cloak
             @keydown.escape.window="closeDetail()"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-[#1F1812]/80 backdrop-blur-sm"
             style="display: none;">

            <!-- Backdrop Click Area -->
            <div class="fixed inset-0" @click="closeDetail()"></div>

            <!-- Modal Window -->
            <div x-show="isOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            <!-- Modal Window (Melebar / Wide Modal) -->
            <div x-show="isOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="relative bg-[#F7F3EC] border border-[#E4DCCC] shadow-2xl max-w-4xl lg:max-w-5xl w-full max-h-[92vh] flex flex-col overflow-hidden text-[#1F1812] z-10">

                <!-- Modal Top Header -->
                <div class="bg-[#1F1812] text-[#F7F3EC] px-6 py-4 flex items-center justify-between border-b border-[#3A3026]">
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-[10px] uppercase tracking-[0.25em] text-[#D9973E] bg-[#D9973E]/15 border border-[#D9973E]/30 px-2.5 py-0.5"
                              x-text="activeMenu?.category"></span>
                        <span class="font-mono text-[11px] text-[#A89A85] hidden sm:inline">// Rincian Komposisi, Bahan & Informasi Nilai Gizi</span>
                    </div>
                    <button @click="closeDetail()"
                            type="button"
                            class="text-[#A89A85] hover:text-white p-1.5 transition-colors text-lg font-mono focus:outline-none rounded hover:bg-white/10 leading-none"
                            title="Tutup (Esc)">
                        ✕
                    </button>
                </div>

                <!-- Modal Scrollable Content -->
                <div class="overflow-y-auto p-6 sm:p-8 space-y-7">

                    <!-- Hero Info Section -->
                    <div class="flex flex-col md:flex-row gap-6 items-start bg-white border border-[#E4DCCC] p-5 sm:p-6 shadow-[0_1px_3px_rgba(42,33,26,0.04)]">
                        <!-- Image or Initials -->
                        <div class="w-full md:w-56 h-48 sm:h-52 flex-shrink-0 bg-[#1F1812] border border-[#E4DCCC] overflow-hidden relative shadow-inner">
                            <template x-if="activeMenu?.image_url">
                                <img :src="activeMenu.image_url" :alt="activeMenu.name" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!activeMenu?.image_url">
                                <div class="w-full h-full flex items-center justify-center">
                                    <span class="font-mono text-4xl text-[#A89A85]" x-text="activeMenu?.initials"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Title, Price, Description -->
                        <div class="flex-1 flex flex-col justify-between h-full min-w-0">
                            <div>
                                <div class="flex flex-wrap items-baseline justify-between gap-3">
                                    <h2 class="text-2xl sm:text-3xl font-medium tracking-tight text-[#1F1812]"
                                        x-text="activeMenu?.name"></h2>
                                    <span class="font-mono text-2xl sm:text-3xl font-bold text-[#B5762A] whitespace-nowrap"
                                          x-text="activeMenu?.price_fmt"></span>
                                </div>

                                <!-- Flavor Notes Pill -->
                                <div class="mt-2.5 inline-flex items-center gap-2 bg-[#EFE9DF] border border-[#DDD5C5] px-3 py-1.5 text-xs">
                                    <span class="text-[#B5762A] text-sm">☕</span>
                                    <span class="font-mono text-[10px] uppercase text-[#8A7B66] font-semibold tracking-wider">Profil Rasa:</span>
                                    <span class="text-xs text-[#2A211A] font-medium" x-text="activeMenu?.flavor_notes"></span>
                                </div>

                                <p class="mt-3.5 text-sm sm:text-[15px] text-[#5C5042] leading-relaxed"
                                   x-text="activeMenu?.description"></p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-[#E4DCCC]/60 flex items-center justify-between text-xs text-[#8A7B66]">
                                <span class="font-mono uppercase tracking-wider text-[10px]">Penyajian Spesial {{ config('cafe.name') }}</span>
                                <span class="inline-flex items-center gap-1.5 text-[#5F7F42] font-mono text-[11px] font-medium">
                                    <span class="w-2 h-2 rounded-full bg-[#5F7F42]"></span>
                                    Tersedia
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 2-COLUMN WIDE GRID: BAHAN (LEFT) vs KANDUNGAN GIZI & ALERGEN (RIGHT) -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                        <!-- KOLOM KIRI: BAHAN & KOMPOSISI -->
                        <div class="space-y-5">
                            <div class="border-b border-[#E4DCCC] pb-2 flex items-center gap-2 font-mono text-xs uppercase tracking-[0.2em] text-[#B5762A] font-semibold">
                                <svg class="w-4 h-4 text-[#B5762A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <span>Komposisi & Bahan Baku Utama</span>
                            </div>

                            <div class="bg-white border border-[#E4DCCC] p-4 sm:p-5 shadow-[0_1px_2px_rgba(42,33,26,0.03)] space-y-3">
                                <template x-for="(ing, idx) in (activeMenu?.ingredients || [])" :key="idx">
                                    <div class="flex items-start gap-3 text-sm pb-2.5 border-b border-[#E4DCCC]/40 last:border-0 last:pb-0">
                                        <span class="text-[#5F7F42] mt-0.5 font-bold text-sm">✓</span>
                                        <span class="text-[#2A211A] leading-relaxed font-normal" x-text="ing"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Tips Barista & Personalisasi -->
                            <div class="bg-[#1F1812] text-[#F7F3EC] p-4 sm:p-5 flex items-start gap-3.5 border-l-4 border-[#B5762A] shadow-md">
                                <span class="text-2xl">💡</span>
                                <div class="text-xs leading-relaxed">
                                    <div class="font-mono text-[10px] uppercase tracking-wider text-[#D9973E] font-semibold">Tips Barista & Personalisasi</div>
                                    <p class="mt-1 text-[#D2C8BA] text-[13px] leading-relaxed" x-text="activeMenu?.barista_notes"></p>
                                </div>
                            </div>
                        </div>

                        <!-- KOLOM KANAN: KANDUNGAN GIZI & ALERGEN -->
                        <div class="space-y-5">
                            <div class="border-b border-[#E4DCCC] pb-2 flex items-center gap-2 font-mono text-xs uppercase tracking-[0.2em] text-[#B5762A] font-semibold">
                                <svg class="w-4 h-4 text-[#B5762A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <span>Informasi Kandungan & Karakteristik</span>
                            </div>

                            <!-- Grid Metrik Kandungan (2x2) -->
                            <div class="grid grid-cols-2 gap-3.5">
                                <!-- Kalori -->
                                <div class="bg-white border border-[#E4DCCC] p-4 text-center shadow-[0_1px_2px_rgba(42,33,26,0.03)] hover:border-[#B5762A]/40 transition-colors">
                                    <div class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66]">Kalori</div>
                                    <div class="mt-1.5 font-mono text-xl sm:text-2xl font-bold text-[#1F1812]"
                                         x-text="activeMenu?.nutrition?.calories || '-'"></div>
                                    <div class="mt-0.5 text-[10px] text-[#8A7B66]">Energi per porsi</div>
                                </div>

                                <!-- Kafein -->
                                <div class="bg-white border border-[#E4DCCC] p-4 text-center shadow-[0_1px_2px_rgba(42,33,26,0.03)] hover:border-[#B5762A]/40 transition-colors">
                                    <div class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66]">Kafein</div>
                                    <div class="mt-1.5 font-mono text-xl sm:text-2xl font-bold text-[#1F1812]"
                                         x-text="activeMenu?.nutrition?.caffeine || '-'"></div>
                                    <div class="mt-0.5 text-[10px] text-[#8A7B66]">Estimasi kandungan</div>
                                </div>

                                <!-- Gula / Pemanis -->
                                <div class="bg-white border border-[#E4DCCC] p-4 text-center shadow-[0_1px_2px_rgba(42,33,26,0.03)] hover:border-[#B5762A]/40 transition-colors">
                                    <div class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66]">Kadar Gula</div>
                                    <div class="mt-1.5 font-mono text-xl sm:text-2xl font-bold text-[#1F1812]"
                                         x-text="activeMenu?.nutrition?.sugar || '-'"></div>
                                    <div class="mt-0.5 text-[10px] text-[#8A7B66]">Pemanis alami/resep</div>
                                </div>

                                <!-- Penyajian / Suhu -->
                                <div class="bg-white border border-[#E4DCCC] p-4 text-center shadow-[0_1px_2px_rgba(42,33,26,0.03)] hover:border-[#B5762A]/40 transition-colors">
                                    <div class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66]">Sajian</div>
                                    <div class="mt-1.5 font-mono text-sm sm:text-base font-semibold text-[#1F1812] leading-tight flex items-center justify-center min-h-[2rem]"
                                         x-text="activeMenu?.nutrition?.serving || 'Hot / Iced'"></div>
                                    <div class="mt-0.5 text-[10px] text-[#8A7B66]">Format temperatur</div>
                                </div>
                            </div>

                            <!-- Banner Alergen -->
                            <div class="bg-[#EFE9DF] border border-[#DDD5C5] p-4 sm:p-5 flex items-start gap-3.5 shadow-sm">
                                <span class="text-[#B5762A] text-xl mt-0.5">⚠️</span>
                                <div>
                                    <div class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] font-semibold">Informasi Alergen & Diet</div>
                                    <div class="mt-1 text-sm font-medium text-[#2A211A] leading-relaxed"
                                         x-text="activeMenu?.nutrition?.allergens || 'Bebas Alergen'"></div>
                                    <p class="mt-1 text-[11px] text-[#8A7B66]">Silakan informasikan kepada barista/kasir kami jika Anda memiliki alergi khusus sebelum pesanan dibuat.</p>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Modal Bottom Actions -->
                <div class="bg-white border-t border-[#E4DCCC] px-6 sm:px-8 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="font-mono text-xs text-[#8A7B66] flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#B5762A]"></span>
                        <span>Pesanan diproses langsung di Meja Kasir atau panggil barista</span>
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <button @click="closeDetail()"
                                type="button"
                                class="px-6 py-2.5 bg-[#1F1812] hover:bg-[#B5762A] text-white font-mono text-xs uppercase tracking-wider transition-colors shadow-sm font-medium">
                            Tutup [Esc]
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </section>
@endsection
