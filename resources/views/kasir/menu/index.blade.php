@extends('kasir.app')

@section('title', 'Kelola Menu')

@section('content')
<div class="space-y-6">

    <!-- 1. HEADER & TOMBOL AKSI -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-[#E4DCCC]">
        <div>
            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                Kelola Menu Kafe
            </h1>
            <p class="font-mono text-xs text-[#8A7B66] mt-0.5">
                Atur daftar katalog minuman & hidangan, sesuaikan harga, foto, dan ketersediaan stok kasir.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('kasir.menu.create') }}"
               class="px-4 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold transition shadow-sm flex items-center gap-2">
                <span>+ Tambah Menu Baru ›</span>
            </a>
        </div>
    </div>

    <!-- FLASH STATUS NOTIFICATION -->
    @if (session('status'))
        <div class="p-3.5 bg-[#5F7F42]/10 border border-[#5F7F42] text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-[#5F7F42] font-bold text-sm">✓</span>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
        </div>
    @endif

    <!-- 2. KARTU STATISTIK RINGKASAN MENU -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- Total Menu -->
        <div class="bg-white border border-[#E4DCCC] p-4 sm:p-5 shadow-xs transition hover:border-[#D9973E]/50">
            <div class="flex items-center justify-between text-[#8A7B66]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Total Katalog</span>
                <span class="text-base">☕</span>
            </div>
            <div class="mt-2.5 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-bold text-[#1F1812]">
                    {{ number_format($stats['total'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono">menu</span>
            </div>
            <div class="mt-1 text-[11px] text-[#8A7B66] font-mono">
                Semua varian terdaftar
            </div>
        </div>

        <!-- Menu Tersedia -->
        <div class="bg-white border border-[#E4DCCC] p-4 sm:p-5 shadow-xs transition hover:border-[#5F7F42]/50">
            <div class="flex items-center justify-between text-[#5F7F42]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Siap Dijual</span>
                <span class="text-base">✓</span>
            </div>
            <div class="mt-2.5 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-bold text-[#5F7F42]">
                    {{ number_format($stats['available'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono">aktif</span>
            </div>
            <div class="mt-1 text-[11px] text-[#5F7F42] font-mono">
                Tampil di Terminal POS
            </div>
        </div>

        <!-- Menu Habis / Kosong -->
        <div class="bg-white border border-[#E4DCCC] p-4 sm:p-5 shadow-xs transition hover:border-[#C4553D]/50">
            <div class="flex items-center justify-between text-[#C4553D]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Stok Kosong</span>
                <span class="text-base">🚫</span>
            </div>
            <div class="mt-2.5 flex items-baseline gap-2">
                <span class="font-mono text-3xl sm:text-4xl font-bold {{ $stats['sold_out'] > 0 ? 'text-[#C4553D]' : 'text-[#8A7B66]' }}">
                    {{ number_format($stats['sold_out'], 0, ',', '.') }}
                </span>
                <span class="text-xs text-[#8A7B66] font-mono">habis</span>
            </div>
            <div class="mt-1 text-[11px] text-[#8A7B66] font-mono">
                {{ $stats['sold_out'] > 0 ? 'Dinonaktifkan sementara' : 'Semua stok ready' }}
            </div>
        </div>

        <!-- Total Kategori -->
        <div class="bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] p-4 sm:p-5 shadow-sm relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-[#D9973E]/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center justify-between text-[#A89A85]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Grup Kategori</span>
                <span class="text-base text-[#D9973E]">🏷️</span>
            </div>
            <div class="mt-2.5 font-mono text-3xl sm:text-4xl font-bold text-[#D9973E]">
                {{ $stats['categories_count'] }}
            </div>
            <div class="mt-1 text-[11px] text-[#A89A85] font-mono">
                Kategori terorganisir
            </div>
        </div>
    </div>

    <!-- 3. KATEGORI FILTER & MANAJEMEN KATEGORI -->
    <div x-data="{ showCategoryForm: false }" class="bg-white border border-[#E4DCCC] p-4 sm:p-5 shadow-xs space-y-3">
        <div class="flex items-center justify-between">
            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold">
                Filter Berdasarkan Kategori
            </span>
            <button type="button" @click="showCategoryForm = !showCategoryForm"
                    class="font-mono text-[11px] uppercase tracking-wider text-[#D9973E] hover:underline font-bold flex items-center gap-1 cursor-pointer">
                <span x-text="showCategoryForm ? '✕ Tutup Form' : '+ Tambah Kategori Baru'"></span>
            </button>
        </div>

        <!-- Form Tambah Kategori (Collapsible) -->
        <div x-show="showCategoryForm" x-transition class="p-3 bg-[#FAF7F2] border border-[#EAE2D5] rounded-none">
            <form method="POST" action="{{ route('kasir.categories.store') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                @csrf
                <input type="text" name="name" required placeholder="Contoh: Signature Coffee, Pastry, Non-Coffee..." maxlength="60"
                       class="flex-1 bg-white border border-[#D5CCC0] focus:border-[#D9973E] focus:outline-none px-3.5 py-2 text-sm text-[#1F1812]">
                <button type="submit"
                        class="px-4 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-[11px] uppercase tracking-wider font-bold transition shadow-xs cursor-pointer">
                    Simpan Kategori
                </button>
            </form>
        </div>

        <!-- Chip List Kategori yang Bisa Diklik Langsung untuk Memfilter -->
        <div class="flex flex-wrap items-center gap-1.5 pt-1">
            <!-- Chip: Semua Kategori -->
            <a href="{{ route('kasir.menu.index', ['search' => $search, 'category_id' => 'all', 'status' => $status]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 border text-xs font-mono transition {{ (empty($categoryId) || $categoryId === 'all') ? 'bg-[#1F1812] text-white border-[#1F1812] font-bold shadow-xs' : 'bg-[#FAF7F2] border-[#E5DDD0] text-[#5C4D3C] hover:border-[#1F1812]' }}">
                <span>Semua Kategori</span>
                <span class="px-1.5 py-0.2 rounded text-[10px] {{ (empty($categoryId) || $categoryId === 'all') ? 'bg-white/20 text-white' : 'bg-[#EAE2D5] text-[#5C4D3C]' }}">
                    {{ $stats['total'] }}
                </span>
            </a>

            <!-- Chip Kategori Individual -->
            @foreach ($categories as $cat)
                <div class="inline-flex items-center border transition {{ (string)$categoryId === (string)$cat->id ? 'bg-[#D9973E] text-[#1F1812] border-[#D9973E] font-bold shadow-xs' : 'bg-[#FAF7F2] border-[#E5DDD0] text-[#5C4D3C] hover:border-[#1F1812]' }}">
                    <a href="{{ route('kasir.menu.index', ['search' => $search, 'category_id' => $cat->id, 'status' => $status]) }}"
                       class="px-3 py-1.5 text-xs font-mono flex items-center gap-1.5">
                        <span>{{ $cat->name }}</span>
                        <span class="px-1.5 py-0.2 rounded text-[10px] {{ (string)$categoryId === (string)$cat->id ? 'bg-black/15 text-[#1F1812]' : 'bg-[#EAE2D5] text-[#5C4D3C]' }}">
                            {{ $cat->menus_count }}
                        </span>
                    </a>

                    @if ($cat->menus_count === 0)
                        <form method="POST" action="{{ route('kasir.categories.destroy', $cat) }}"
                              data-confirm="Hapus kategori '{{ $cat->name }}'?"
                              data-confirm-title="Hapus Kategori"
                              data-confirm-type="danger"
                              data-confirm-btn="Hapus Kategori"
                              class="pr-2 pl-0.5">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Hapus kategori kosong" class="text-[#C4553D] hover:bg-red-50 p-0.5 rounded text-xs font-bold leading-none cursor-pointer">
                                ✕
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- 4. SEARCH & FILTER BAR -->
    <div class="bg-white border border-[#E4DCCC] p-4 shadow-xs">
        <form method="GET" action="{{ route('kasir.menu.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <input type="hidden" name="category_id" value="{{ $categoryId }}">

            <!-- Input Cari Menu -->
            <div class="flex-1 max-w-md relative">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama atau deskripsi menu..."
                       class="w-full pl-9 pr-3.5 py-2 bg-[#FAF7F2] border border-[#D5CCC0] text-sm text-[#1F1812] focus:outline-none focus:border-[#D9973E]">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#8A7B66] text-xs">
                    🔍
                </span>
            </div>

            <!-- Filter Status Ketersediaan -->
            <div class="flex items-center gap-2 font-mono text-xs">
                <span class="text-[#8A7B66] text-[11px] uppercase tracking-wider">Status:</span>
                <a href="{{ route('kasir.menu.index', ['search' => $search, 'category_id' => $categoryId, 'status' => 'all']) }}"
                   class="px-2.5 py-1.5 border transition {{ (empty($status) || $status === 'all') ? 'bg-[#1F1812] text-white border-[#1F1812] font-bold' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#5C4D3C]' }}">
                    Semua
                </a>
                <a href="{{ route('kasir.menu.index', ['search' => $search, 'category_id' => $categoryId, 'status' => 'available']) }}"
                   class="px-2.5 py-1.5 border transition {{ $status === 'available' ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#5F7F42]' }}">
                    ✓ Tersedia
                </a>
                <a href="{{ route('kasir.menu.index', ['search' => $search, 'category_id' => $categoryId, 'status' => 'sold_out']) }}"
                   class="px-2.5 py-1.5 border transition {{ $status === 'sold_out' ? 'bg-[#C4553D] text-white border-[#C4553D] font-bold' : 'bg-[#FAF7F2] border-[#D5CCC0] text-[#C4553D]' }}">
                    ✕ Habis
                </a>

                @if ($search || ($categoryId && $categoryId !== 'all') || ($status && $status !== 'all'))
                    <a href="{{ route('kasir.menu.index') }}"
                       class="ml-2 text-[#C4553D] font-mono hover:underline text-xs flex items-center gap-1">
                        <span>✕ Reset</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- 5. TABEL MENU DENGAN INTERACTIVE TOGGLE STOK -->
    <div class="bg-white border border-[#E4DCCC] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-[#FAF7F2] border-b border-[#E4DCCC] font-mono text-[10px] uppercase tracking-[0.18em] text-[#7A6A58] select-none">
                        <th class="px-4 py-3.5">Produk</th>
                        <th class="px-4 py-3.5">Kategori</th>
                        <th class="px-4 py-3.5 text-right">Harga</th>
                        <th class="px-4 py-3.5 text-center">Status Stok</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EAE2D5]">
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
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-12 h-12 rounded-lg overflow-hidden border border-[#E4DCCC] bg-[#FAF7F2] shrink-0 flex items-center justify-center">
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
                                            <div class="text-xs text-[#8A7B66] truncate max-w-sm mt-0.5">
                                                {{ $m->description }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Kategori -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="px-2.5 py-1 bg-[#FAF7F2] border border-[#E4DCCC] rounded text-xs font-mono text-[#5C4D3C]">
                                    {{ $m->category ? $m->category->name : 'Uncategorized' }}
                                </span>
                            </td>

                            <!-- Harga -->
                            <td class="px-4 py-3.5 text-right whitespace-nowrap font-mono text-sm font-bold text-[#1F1812]">
                                Rp {{ number_format($m->price, 0, ',', '.') }}
                            </td>

                            <!-- Switch Toggle Status Modern -->
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
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
                                    <span class="font-mono text-[11px] font-semibold w-16 text-left"
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

                            <!-- Aksi -->
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('kasir.menu.edit', $m) }}"
                                       class="px-2.5 py-1 bg-[#FAF7F2] hover:bg-[#1F1812] text-[#1F1812] hover:text-[#F7F3EC] border border-[#D5CCC0] hover:border-[#1F1812] font-mono text-[10px] uppercase tracking-wider font-semibold transition rounded">
                                        Edit ›
                                    </a>

                                    <form method="POST" action="{{ route('kasir.menu.destroy', $m) }}" class="inline"
                                          data-confirm="Hapus menu '{{ $m->name }}' dari katalog kafe?"
                                          data-confirm-title="Konfirmasi Hapus Menu"
                                          data-confirm-type="danger"
                                          data-confirm-btn="Ya, Hapus Menu">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 text-[#C4553D] hover:bg-red-50 border border-transparent hover:border-red-200 font-mono text-[10px] uppercase tracking-wider font-semibold transition rounded cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center text-[#8A7B66]">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <div class="text-3xl opacity-40">☕</div>
                                    <div class="font-serif font-bold text-base text-[#1F1812]">
                                        Menu Tidak Ditemukan
                                    </div>
                                    <p class="text-xs text-[#8A7B66] leading-relaxed">
                                        Tidak ada menu yang sesuai dengan pencarian atau filter kategori yang dipilih.
                                    </p>
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

</div>
@endsection
