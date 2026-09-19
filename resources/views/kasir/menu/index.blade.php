@extends('kasir.app')

@section('title', 'Kelola Menu')

@section('content')
<div x-data="menuManager()" class="w-full p-4 sm:p-6 space-y-6">

    <!-- 1. HEADER & TOMBOL AKSI UTAMA -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-[#E4DCCC]">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                    Kelola Menu Kafe
                </h1>
                <span class="px-2.5 py-0.5 rounded-full bg-[#D9973E]/15 border border-[#D9973E]/30 text-[#B5762A] font-mono text-xs font-bold">
                    Katalog POS
                </span>
            </div>
            <p class="font-sans text-xs text-[#8A7B66] mt-1">
                Atur daftar hidangan & minuman kafe, sesuaikan harga, unggah foto, dan kelola ketersediaan stok kasir.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <!-- Tombol Tambah Kategori -->
            <button type="button"
                    @click="showCategoryModal = true"
                    class="px-3.5 py-2.5 bg-white hover:bg-[#FAF7F2] text-[#1F1812] border border-[#E4DCCC] hover:border-[#D9973E] font-mono text-xs font-bold rounded-xl transition shadow-2xs flex items-center gap-1.5 active:scale-98">
                <span class="text-[#D9973E]">+</span>
                <span>Kategori Baru</span>
            </button>

            <!-- Tombol Tambah Menu -->
            <a href="{{ route('kasir.menu.create') }}"
               class="px-4 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all shadow-md flex items-center gap-2 active:scale-98">
                <span>+ Tambah Menu Baru ›</span>
            </a>
        </div>
    </div>

    <!-- FLASH STATUS NOTIFICATION -->
    @if (session('status'))
        <div class="p-4 bg-[#5F7F42]/10 border border-[#5F7F42]/40 rounded-2xl text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-[#5F7F42] text-white flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-[#8A7B66] hover:text-[#1F1812] p-1 text-sm font-bold leading-none">✕</button>
        </div>
    @endif

    <!-- 2. KARTU STATISTIK RINGKASAN MENU (HERO STAT CARDS) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        <!-- Total Menu -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/60 flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#8A7B66]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">TOTAL KATALOG</span>
                <span class="w-8 h-8 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-sm shadow-2xs">☕</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-extrabold text-[#1F1812] tracking-tight">
                    {{ number_format($stats['total'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono font-medium">menu</span>
            </div>
            <div class="mt-2 text-[11px] text-[#8A7B66] font-mono flex items-center gap-1.5 pt-2 border-t border-[#F2EDE4]">
                <span>Semua varian terdaftar</span>
            </div>
        </div>

        <!-- Menu Siap Dijual -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-[#5F7F42]/60 flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#5F7F42]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">SIAP DIJUAL</span>
                <span class="w-8 h-8 rounded-xl bg-[#5F7F42]/10 border border-[#5F7F42]/30 flex items-center justify-center text-xs font-bold shadow-2xs text-[#5F7F42]">✓</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-extrabold text-[#5F7F42] tracking-tight">
                    {{ number_format($stats['available'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#5F7F42] font-mono font-medium">aktif</span>
            </div>
            <div class="mt-2 text-[11px] text-[#5F7F42] font-mono flex items-center gap-1.5 pt-2 border-t border-[#F2EDE4]">
                <span class="h-2 w-2 rounded-full bg-[#5F7F42]"></span>
                <span>Tampil di Terminal Kasir</span>
            </div>
        </div>

        <!-- Menu Habis / Kosong -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs transition hover:border-[#C4553D]/60 flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#C4553D]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold">STOK KOSONG</span>
                <span class="w-8 h-8 rounded-xl bg-[#C4553D]/10 border border-[#C4553D]/30 flex items-center justify-center text-xs font-bold shadow-2xs text-[#C4553D]">🚫</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-extrabold {{ $stats['sold_out'] > 0 ? 'text-[#C4553D]' : 'text-[#8A7B66]' }} tracking-tight">
                    {{ number_format($stats['sold_out'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono font-medium">habis</span>
            </div>
            <div class="mt-2 text-[11px] font-mono flex items-center gap-1.5 pt-2 border-t border-[#F2EDE4] {{ $stats['sold_out'] > 0 ? 'text-[#C4553D]' : 'text-[#8A7B66]' }}">
                <span>{{ $stats['sold_out'] > 0 ? 'Dinonaktifkan sementara' : 'Semua stok ready' }}</span>
            </div>
        </div>

        <!-- Total Kategori -->
        <div class="bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] rounded-2xl p-4 sm:p-5 shadow-md relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#A89A85]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold text-[#D9973E]">GRUP KATEGORI</span>
                <span class="w-8 h-8 rounded-xl bg-[#2A211A] border border-[#3A3026] flex items-center justify-center text-sm shadow-2xs">🏷️</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-extrabold text-[#D9973E] tracking-tight">
                    {{ $stats['categories_count'] }}
                </span>
                <span class="text-xs text-[#A89A85] font-mono font-medium">kategori</span>
            </div>
            <div class="mt-2 text-[11px] text-[#A89A85] font-mono flex items-center gap-1.5 pt-2 border-t border-[#3A3026]">
                <span>Struktur menu terorganisir</span>
            </div>
        </div>
    </div>

    <!-- 3. BAR PENCARIAN & FILTER KATEGORI -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
        
        <!-- Baris Atas: Input Pencarian & Status Filter -->
        <form method="GET" action="{{ route('kasir.menu.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <input type="hidden" name="category_id" value="{{ $categoryId }}">

            <!-- Input Cari Menu -->
            <div class="flex-1 max-w-lg relative">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama menu, minuman, atau deskripsi..."
                       class="w-full pl-10 pr-9 py-2.5 bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl text-xs font-mono text-[#1F1812] focus:outline-none transition shadow-2xs">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8A7B66] text-xs">
                    🔍
                </span>
                @if ($search)
                    <a href="{{ route('kasir.menu.index', ['category_id' => $categoryId, 'status' => $status]) }}"
                       class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#8A7B66] hover:text-[#C4553D] text-xs"
                       title="Hapus pencarian">
                        ✕
                    </a>
                @endif
            </div>

            <!-- Filter Status Ketersediaan (Pills) -->
            <div class="flex items-center flex-wrap gap-1.5 font-mono text-xs">
                <span class="text-[#8A7B66] text-[10px] uppercase tracking-wider font-bold mr-1">STATUS:</span>
                
                <a href="{{ route('kasir.menu.index', ['search' => $search, 'category_id' => $categoryId, 'status' => 'all']) }}"
                   class="px-3 py-1.5 rounded-xl border transition {{ (empty($status) || $status === 'all') ? 'bg-[#1F1812] text-white border-[#1F1812] font-bold shadow-2xs' : 'bg-[#FAF7F2] border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] hover:border-[#B5762A]' }}">
                    Semua
                </a>
                <a href="{{ route('kasir.menu.index', ['search' => $search, 'category_id' => $categoryId, 'status' => 'available']) }}"
                   class="px-3 py-1.5 rounded-xl border transition {{ $status === 'available' ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-2xs' : 'bg-[#FAF7F2] border-[#E4DCCC] text-[#5F7F42] hover:border-[#5F7F42]' }}">
                    ✓ Tersedia ({{ $stats['available'] }})
                </a>
                <a href="{{ route('kasir.menu.index', ['search' => $search, 'category_id' => $categoryId, 'status' => 'sold_out']) }}"
                   class="px-3 py-1.5 rounded-xl border transition {{ $status === 'sold_out' ? 'bg-[#C4553D] text-white border-[#C4553D] font-bold shadow-2xs' : 'bg-[#FAF7F2] border-[#E4DCCC] text-[#C4553D] hover:border-[#C4553D]' }}">
                    ✕ Habis ({{ $stats['sold_out'] }})
                </a>

                @if ($search || ($categoryId && $categoryId !== 'all') || ($status && $status !== 'all'))
                    <a href="{{ route('kasir.menu.index') }}"
                       class="ml-2 px-2.5 py-1 text-[#C4553D] hover:bg-red-50 font-mono text-xs rounded-lg transition border border-transparent hover:border-red-200">
                        ✕ Reset
                    </a>
                @endif
            </div>
        </form>

        <!-- Baris Bawah: Chip Kategori Filter yang Luas & Interaktif -->
        <div class="pt-3 border-t border-[#F2EDE4]">
            <div class="flex items-center justify-between mb-2">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold">
                    PILIH KATEGORI:
                </span>
                <span class="font-mono text-[10px] text-[#8A7B66]">
                    {{ $categories->count() }} Kategori Terdaftar
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <!-- Chip: Semua Kategori -->
                <a href="{{ route('kasir.menu.index', ['search' => $search, 'category_id' => 'all', 'status' => $status]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-mono transition {{ (empty($categoryId) || $categoryId === 'all') ? 'bg-[#1F1812] text-white border-[#1F1812] font-bold shadow-2xs' : 'bg-[#FAF7F2] border-[#E4DCCC] text-[#5C4D3C] hover:border-[#1F1812] hover:text-[#1F1812]' }}">
                    <span>Semua Kategori</span>
                    <span class="px-1.5 py-0.2 rounded-md text-[10px] font-bold {{ (empty($categoryId) || $categoryId === 'all') ? 'bg-white/20 text-white' : 'bg-[#EAE2D5] text-[#5C4D3C]' }}">
                        {{ $stats['total'] }}
                    </span>
                </a>

                <!-- Chip Kategori Individual -->
                @foreach ($categories as $cat)
                    <div class="inline-flex items-center rounded-xl border transition {{ (string)$categoryId === (string)$cat->id ? 'bg-[#D9973E] text-[#1F1812] border-[#D9973E] font-bold shadow-2xs' : 'bg-[#FAF7F2] border-[#E4DCCC] text-[#5C4D3C] hover:border-[#1F1812]' }}">
                        <a href="{{ route('kasir.menu.index', ['search' => $search, 'category_id' => $cat->id, 'status' => $status]) }}"
                           class="px-3 py-1.5 text-xs font-mono flex items-center gap-1.5">
                            <span>{{ $cat->name }}</span>
                            <span class="px-1.5 py-0.2 rounded-md text-[10px] font-bold {{ (string)$categoryId === (string)$cat->id ? 'bg-black/15 text-[#1F1812]' : 'bg-[#EAE2D5] text-[#5C4D3C]' }}">
                                {{ $cat->menus_count }}
                            </span>
                        </a>

                        <!-- Hapus Kategori Kosong jika belum memiliki menu -->
                        @if ($cat->menus_count === 0)
                            <form method="POST" action="{{ route('kasir.categories.destroy', $cat) }}"
                                  data-confirm="Hapus kategori kosong '{{ $cat->name }}'?"
                                  data-confirm-title="Hapus Kategori"
                                  data-confirm-type="danger"
                                  data-confirm-btn="Hapus Kategori"
                                  class="pr-2 pl-0.5">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Hapus kategori kosong ini" class="text-[#C4553D] hover:bg-red-100 p-0.5 rounded text-xs font-bold leading-none cursor-pointer">
                                    ✕
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    <!-- 4. TABEL KATALOG MENU LENGKAP -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-[#FAF7F2] border-b border-[#E4DCCC] font-mono text-[10px] uppercase tracking-[0.2em] text-[#7A6A58] select-none">
                        <th class="px-5 py-4">Menu & Hidangan</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4 text-right">Harga Jual</th>
                        <th class="px-5 py-4 text-center">Ketersediaan Stok</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2EDE4]">
                    @forelse ($menus as $m)
                        <tr class="hover:bg-[#FAF7F2]/60 transition-colors"
                            x-data="{
                                available: {{ $m->is_available ? 'true' : 'false' }},
                                loading: false,
                                async toggleStock() {
                                    if (this.loading) return;
                                    this.loading = true;
                                    try {
                                        const res = await fetch('{{ route('kasir.menu.toggle', $m) }}', {
                                            method: 'PATCH',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json'
                                            }
                                        });
                                        if (res.ok) {
                                            const data = await res.json();
                                            this.available = data.is_available;
                                            if (window.customToast) {
                                                window.customToast({
                                                    message: data.message,
                                                    type: this.available ? 'success' : 'warning'
                                                });
                                            }
                                        } else {
                                            $refs.fallbackForm.submit();
                                        }
                                    } catch (e) {
                                        $refs.fallbackForm.submit();
                                    } finally {
                                        this.loading = false;
                                    }
                                }
                            }">

                            <!-- Info Produk & Gambar -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-13 h-13 rounded-xl overflow-hidden border border-[#E4DCCC] bg-[#FAF7F2] shrink-0 flex items-center justify-center shadow-2xs">
                                        @if ($m->image)
                                            <img src="{{ asset('storage/' . $m->image) }}" alt="{{ $m->name }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full bg-[#1F1812] text-[#D9973E] flex items-center justify-center font-serif font-bold text-lg">
                                                {{ strtoupper(substr($m->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-serif font-bold text-sm text-[#1F1812] truncate">
                                            {{ $m->name }}
                                        </div>
                                        @if ($m->description)
                                            <div class="text-xs text-[#8A7B66] truncate max-w-md mt-0.5">
                                                {{ $m->description }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Kategori -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl text-xs font-mono font-medium text-[#5C4D3C]">
                                    {{ $m->category ? $m->category->name : 'Tanpa Kategori' }}
                                </span>
                            </td>

                            <!-- Harga Jual -->
                            <td class="px-5 py-4 text-right whitespace-nowrap font-mono text-sm font-bold text-[#1F1812]">
                                Rp {{ number_format($m->price, 0, ',', '.') }}
                            </td>

                            <!-- Switch Toggle Status Modern -->
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-2.5">
                                    <button type="button"
                                            @click="toggleStock()"
                                            :disabled="loading"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-50"
                                            :class="available ? 'bg-[#5F7F42]' : 'bg-[#D5CCC0]'">
                                        <span class="sr-only">Toggle Status Stok</span>
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                              :class="available ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                    <span class="font-mono text-xs font-bold w-18 text-left"
                                          :class="available ? 'text-[#5F7F42]' : 'text-[#8A7B66]'"
                                          x-text="available ? 'Tersedia' : 'Habis'">
                                    </span>
                                </div>

                                <!-- Form Fallback jika fetch bermasalah -->
                                <form x-ref="fallbackForm" method="POST" action="{{ route('kasir.menu.toggle', $m) }}" class="hidden">
                                    @csrf
                                    @method('PATCH')
                                </form>
                            </td>

                            <!-- Tombol Aksi -->
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button"
                                            @click="openEditModal({
                                                id: {{ $m->id }},
                                                name: @js($m->name),
                                                category_id: {{ $m->category_id ?? 'null' }},
                                                price: {{ $m->price }},
                                                is_available: {{ $m->is_available ? 'true' : 'false' }},
                                                image_url: @js($m->image ? asset('storage/' . $m->image) : ''),
                                                description: @js($m->description ?? '')
                                            })"
                                            class="px-3 py-1.5 bg-[#FAF7F2] hover:bg-[#1F1812] text-[#1F1812] hover:text-[#F7F3EC] border border-[#E4DCCC] hover:border-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold transition rounded-xl shadow-2xs cursor-pointer active:scale-95">
                                        Edit ›
                                    </button>

                                    <form method="POST" action="{{ route('kasir.menu.destroy', $m) }}" class="inline"
                                          data-confirm="Hapus menu '{{ $m->name }}' dari katalog kafe?"
                                          data-confirm-title="Konfirmasi Hapus Menu"
                                          data-confirm-type="danger"
                                          data-confirm-btn="Ya, Hapus Menu">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 text-[#C4553D] hover:bg-red-50 border border-transparent hover:border-red-200 font-mono text-xs uppercase tracking-wider font-semibold transition rounded-xl cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center text-[#8A7B66]">
                                <div class="max-w-sm mx-auto space-y-2.5">
                                    <div class="text-4xl opacity-40">☕</div>
                                    <div class="font-serif font-bold text-base text-[#1F1812]">
                                        Menu Tidak Ditemukan
                                    </div>
                                    <p class="text-xs text-[#8A7B66] leading-relaxed">
                                        Tidak ada menu yang sesuai dengan pencarian atau filter kategori yang dipilih.
                                    </p>
                                    <div class="pt-2">
                                        <a href="{{ route('kasir.menu.index') }}"
                                           class="inline-block px-4 py-2 bg-[#FAF7F2] border border-[#E4DCCC] hover:border-[#1F1812] text-xs font-mono font-bold rounded-xl transition">
                                            Reset Filter
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($menus->hasPages())
            <div class="p-4 border-t border-[#E4DCCC] bg-[#FAF7F2]">
                {{ $menus->links() }}
            </div>
        @endif
    </div>

    <!-- 5. MODAL POPUP TAMBAH KATEGORI BARU -->
    <div x-show="showCategoryModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="showCategoryModal = false">
        
        <div class="bg-white border border-[#E4DCCC] rounded-2xl w-full max-w-md shadow-2xl overflow-hidden p-6 space-y-5"
             @click.outside="showCategoryModal = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-[#E4DCCC]">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🏷️</span>
                    <h3 class="font-serif font-bold text-lg text-[#1F1812]">Tambah Kategori Baru</h3>
                </div>
                <button type="button" @click="showCategoryModal = false" class="text-[#8A7B66] hover:text-[#1F1812] p-1 text-sm font-bold">
                    ✕
                </button>
            </div>

            <form method="POST" action="{{ route('kasir.categories.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                        Nama Kategori <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required maxlength="60"
                           placeholder="Contoh: Signature Coffee, Pastry, Non-Coffee..."
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white rounded-xl px-3.5 py-2.5 text-sm text-[#1F1812] focus:outline-none transition shadow-2xs font-medium">
                </div>

                <div class="pt-2 flex items-center justify-end gap-2.5">
                    <button type="button"
                            @click="showCategoryModal = false"
                            class="px-4 py-2 border border-[#E4DCCC] hover:bg-[#FAF7F2] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs font-bold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-xs active:scale-95">
                        Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. MODAL POPUP EDIT MENU INTERAKTIF -->
    <div x-show="showEditModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs overflow-y-auto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="closeEditModal()">
        
        <div class="bg-white border border-[#E4DCCC] rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden p-6 sm:p-7 space-y-6 my-8"
             @click.outside="closeEditModal()">
            
            <!-- Header Modal -->
            <div class="flex items-center justify-between pb-3.5 border-b border-[#E4DCCC]">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl">☕</span>
                        <h3 class="font-serif font-bold text-xl text-[#1F1812]" x-text="'Edit Menu: ' + editForm.name">
                            Edit Menu
                        </h3>
                    </div>
                    <p class="font-sans text-xs text-[#8A7B66] mt-0.5">
                        Perbarui rincian produk, harga jual, foto, dan status ketersediaan di kasir.
                    </p>
                </div>
                <button type="button" @click="closeEditModal()" class="text-[#8A7B66] hover:text-[#1F1812] p-1.5 text-base font-bold transition rounded-lg">
                    ✕
                </button>
            </div>

            <!-- Form Edit Menu -->
            <form method="POST" :action="editForm.actionUrl" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <input type="hidden" name="_method" value="PUT">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Kolom Kiri: Nama, Kategori, Harga, Status -->
                    <div class="space-y-4">
                        <!-- Nama Menu -->
                        <div>
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                                Nama Menu <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" x-model="editForm.name" required maxlength="100"
                                   placeholder="Nama hidangan atau minuman..."
                                   class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2.5 rounded-xl text-sm text-[#1F1812] transition shadow-2xs font-medium">
                        </div>

                        <!-- Kategori -->
                        <div>
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                                Kategori <span class="text-red-500">*</span>
                            </label>
                            <select name="category_id" x-model="editForm.category_id" required
                                    class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2.5 rounded-xl text-sm text-[#1F1812] transition shadow-2xs cursor-pointer font-medium">
                                <option value="" disabled>Pilih Kategori...</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Harga Jual -->
                        <div>
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                                Harga Jual (Rp) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8A7B66] font-mono text-sm font-bold">
                                    Rp
                                </span>
                                <input type="number" name="price" x-model="editForm.price" required min="0" step="500"
                                       placeholder="Contoh: 25000"
                                       class="w-full pl-11 pr-3.5 py-2.5 bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 rounded-xl text-sm font-mono font-bold text-[#1F1812] transition shadow-2xs text-right">
                            </div>
                            <template x-if="editForm.price && !isNaN(editForm.price)">
                                <div class="mt-1 text-right font-mono text-[11px] text-[#5F7F42] font-semibold"
                                     x-text="'Format POS: Rp ' + Number(editForm.price).toLocaleString('id-ID')"></div>
                            </template>
                        </div>

                        <!-- Status Ketersediaan -->
                        <div class="pt-2 border-t border-[#F2EDE4]">
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                                Status Ketersediaan:
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="hidden" name="is_available" :value="editForm.is_available ? '1' : '0'">
                                <button type="button"
                                        @click="editForm.is_available = !editForm.is_available"
                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                        :class="editForm.is_available ? 'bg-[#5F7F42]' : 'bg-[#D5CCC0]'">
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                          :class="editForm.is_available ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                                <span class="font-mono text-xs font-bold"
                                      :class="editForm.is_available ? 'text-[#5F7F42]' : 'text-[#8A7B66]'"
                                      x-text="editForm.is_available ? 'Tersedia Dijual (Aktif)' : 'Stok Kosong (Nonaktif)'">
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Foto & Deskripsi -->
                    <div class="space-y-4">
                        <!-- Foto Menu -->
                        <div>
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                                Foto Produk (Opsional):
                            </label>

                            <div class="border-2 border-dashed border-[#E4DCCC] rounded-2xl p-4 text-center bg-[#FAF7F2] relative hover:border-[#D9973E] transition">
                                <!-- Preview Image -->
                                <template x-if="editForm.previewUrl">
                                    <div class="space-y-2.5">
                                        <div class="w-32 h-32 mx-auto rounded-xl overflow-hidden border border-[#E4DCCC] shadow-xs bg-black">
                                            <img :src="editForm.previewUrl" alt="Foto Menu" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <button type="button" @click="removeEditImage()"
                                                    class="px-2.5 py-0.5 text-[#C4553D] hover:bg-red-50 border border-red-200 text-xs font-mono font-bold rounded-lg transition cursor-pointer">
                                                ✕ Hapus Foto
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <!-- No Preview Prompt -->
                                <template x-if="!editForm.previewUrl">
                                    <div class="py-4 space-y-1.5">
                                        <div class="text-3xl text-[#8A7B66] opacity-60">📷</div>
                                        <div class="text-xs font-bold text-[#1F1812]">
                                            Pilih foto produk baru
                                        </div>
                                        <div class="text-[10px] text-[#8A7B66] font-mono">
                                            Format: JPG, PNG, WEBP (Max 2MB)
                                        </div>
                                    </div>
                                </template>

                                <div class="mt-2.5">
                                    <input type="file" name="image" x-ref="editFileInput" @change="handleEditImageChange($event)" accept="image/*"
                                           class="w-full text-xs text-[#7A6A58] file:mr-2.5 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-mono file:uppercase file:font-bold file:bg-[#1F1812] file:text-[#F7F3EC] hover:file:bg-[#D9973E] hover:file:text-[#1F1812] cursor-pointer">
                                </div>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                                Deskripsi Singkat (Opsional):
                            </label>
                            <textarea name="description" x-model="editForm.description" rows="3" maxlength="500"
                                      placeholder="Penjelasan rasa, resep, atau catatan penyajian..."
                                      class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl text-xs text-[#1F1812] transition shadow-2xs"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="pt-4 border-t border-[#F2EDE4] flex items-center justify-end gap-2.5">
                    <button type="button"
                            @click="closeEditModal()"
                            class="px-4 py-2 border border-[#E4DCCC] hover:bg-[#FAF7F2] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs font-bold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-xs active:scale-95">
                        Simpan Perubahan ›
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function menuManager() {
        return {
            showCategoryModal: false,
            showEditModal: false,
            editForm: {
                id: null,
                name: '',
                category_id: '',
                price: 0,
                is_available: true,
                previewUrl: '',
                description: '',
                actionUrl: ''
            },
            openEditModal(menu) {
                this.editForm.id = menu.id;
                this.editForm.name = menu.name || '';
                this.editForm.category_id = menu.category_id || '';
                this.editForm.price = menu.price || 0;
                this.editForm.is_available = !!menu.is_available;
                this.editForm.previewUrl = menu.image_url || '';
                this.editForm.description = menu.description || '';
                this.editForm.actionUrl = '{{ url('/kasir/menu') }}/' + menu.id;
                this.showEditModal = true;
            },
            closeEditModal() {
                this.showEditModal = false;
            },
            handleEditImageChange(e) {
                const file = e.target.files[0];
                if (file) {
                    this.editForm.previewUrl = URL.createObjectURL(file);
                }
            },
            removeEditImage() {
                this.editForm.previewUrl = '';
                if (this.$refs.editFileInput) {
                    this.$refs.editFileInput.value = '';
                }
            }
        };
    }
</script>
@endsection
