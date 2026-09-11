@extends('kasir.app')

@section('title', $menu->exists ? 'Edit Menu' : 'Tambah Menu')

@section('content')
<div class="max-w-3xl space-y-6"
     x-data="{
        priceInput: '{{ old('price', $menu->price ?? '') }}',
        previewUrl: '{{ $menu->image ? asset('storage/' . $menu->image) : '' }}',
        isAvailable: {{ old('is_available', $menu->exists ? ($menu->is_available ? 'true' : 'false') : 'true') }},
        handleImageChange(e) {
            const file = e.target.files[0];
            if (file) {
                this.previewUrl = URL.createObjectURL(file);
            }
        },
        removeImage() {
            this.previewUrl = '';
            $refs.fileInput.value = '';
        }
     }">

    <!-- 1. HEADER HALAMAN -->
    <div class="flex items-center justify-between pb-2 border-b border-[#E4DCCC]">
        <div>
            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                {{ $menu->exists ? 'Edit Menu: ' . $menu->name : 'Tambah Menu Baru' }}
            </h1>
            <p class="font-mono text-xs text-[#8A7B66] mt-0.5">
                {{ $menu->exists ? 'Perbarui informasi rincian produk dan status ketersediaan.' : 'Lengkapi formulir di bawah ini untuk mendaftarkan menu baru.' }}
            </p>
        </div>

        <a href="{{ route('kasir.menu.index') }}"
           class="px-3.5 py-2 border border-[#D5CCC0] hover:bg-[#FAF7F2] text-[#1F1812] font-mono text-xs uppercase tracking-wider font-semibold transition rounded shadow-xs flex items-center gap-1.5">
            <span>‹ Kembali</span>
        </a>
    </div>

    <!-- 2. FORM INPUT MENU -->
    <form method="POST" enctype="multipart/form-data"
          action="{{ $menu->exists ? route('kasir.menu.update', $menu) : route('kasir.menu.store') }}"
          class="bg-white border border-[#E4DCCC] p-6 sm:p-8 shadow-sm space-y-6">
        @csrf
        @if ($menu->exists)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- KOLOM KIRI: INFO UTAMA -->
            <div class="space-y-5">
                <!-- Nama Menu -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                        Nama Menu <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $menu->name) }}" required maxlength="100"
                           placeholder="Contoh: Kopi Susu Aren, Croissant Butter..."
                           class="w-full bg-[#FAF7F2] border border-[#D5CCC0] focus:border-[#D9973E] focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2.5 text-sm text-[#1F1812] transition shadow-inner font-medium">
                    @error('name')
                        <p class="mt-1 text-xs text-[#C4553D] font-mono">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kategori Menu -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                        Kategori <span class="text-red-500">*</span>
                    </label>
                    <select name="category_id" required
                            class="w-full bg-[#FAF7F2] border border-[#D5CCC0] focus:border-[#D9973E] focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2.5 text-sm text-[#1F1812] transition shadow-inner">
                        <option value="" disabled {{ !old('category_id', $menu->category_id) ? 'selected' : '' }}>Pilih Kategori...</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $menu->category_id) == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-xs text-[#C4553D] font-mono">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Harga Menu -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                        Harga Jual (Rp) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8A7B66] font-mono text-sm font-bold">
                            Rp
                        </span>
                        <input type="number" name="price" x-model="priceInput" required min="0" step="500"
                               placeholder="Contoh: 25000"
                               class="w-full pl-11 pr-3.5 py-2.5 bg-[#FAF7F2] border border-[#D5CCC0] focus:border-[#D9973E] focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 text-sm font-mono font-bold text-[#1F1812] transition shadow-inner text-right">
                    </div>
                    <!-- Live Rupiah formatting preview -->
                    <template x-if="priceInput && !isNaN(priceInput)">
                        <div class="mt-1 text-right font-mono text-[11px] text-[#5F7F42] font-semibold"
                             x-text="'Format: Rp ' + Number(priceInput).toLocaleString('id-ID')"></div>
                    </template>
                    @error('price')
                        <p class="mt-1 text-xs text-[#C4553D] font-mono">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status Ketersediaan Stok (Switch Toggle) -->
                <div class="pt-2 border-t border-[#EAE2D5]">
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-2">
                        Status Ketersediaan:
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_available" :value="isAvailable ? '1' : '0'">
                        <button type="button"
                                @click="isAvailable = !isAvailable"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="isAvailable ? 'bg-[#5F7F42]' : 'bg-[#D5CCC0]'">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                  :class="isAvailable ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                        <span class="font-mono text-xs font-bold"
                              :class="isAvailable ? 'text-[#5F7F42]' : 'text-[#8A7B66]'"
                              x-text="isAvailable ? 'Tersedia Dijual (Aktif)' : 'Stok Kosong (Nonaktif)'">
                        </span>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: FOTO & DESKRIPSI -->
            <div class="space-y-5">
                <!-- Upload & Live Image Preview -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                        Foto Produk (Opsional):
                    </label>

                    <div class="border-2 border-dashed border-[#D5CCC0] rounded-xl p-4 text-center bg-[#FAF7F2] relative hover:border-[#D9973E] transition">
                        <!-- Preview Image if exists or selected -->
                        <template x-if="previewUrl">
                            <div class="space-y-3">
                                <div class="w-36 h-36 mx-auto rounded-lg overflow-hidden border border-[#D5CCC0] shadow-sm bg-black">
                                    <img :src="previewUrl" alt="Preview Foto Menu" class="w-full h-full object-cover">
                                </div>
                                <div class="flex justify-center gap-2">
                                    <button type="button" @click="removeImage()"
                                            class="px-2.5 py-1 text-[#C4553D] hover:bg-red-50 border border-red-200 text-xs font-mono font-semibold rounded transition cursor-pointer">
                                        Hapus Foto
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Upload Prompt when no preview -->
                        <template x-if="!previewUrl">
                            <div class="py-6 space-y-2">
                                <div class="text-3xl text-[#8A7B66]">📷</div>
                                <div class="text-xs font-medium text-[#1F1812]">
                                    Klik tombol di bawah untuk memilih foto menu
                                </div>
                                <div class="text-[10px] text-[#8A7B66] font-mono">
                                    Format: JPG, PNG, WEBP (Maksimal 2MB)
                                </div>
                            </div>
                        </template>

                        <div class="mt-3">
                            <input type="file" name="image" x-ref="fileInput" @change="handleImageChange($event)" accept="image/*"
                                   class="w-full text-xs text-[#7A6A58] file:mr-3 file:py-1.5 file:px-3 file:rounded-none file:border-0 file:text-xs file:font-mono file:uppercase file:tracking-wider file:font-bold file:bg-[#1F1812] file:text-[#F7F3EC] hover:file:bg-[#D9973E] hover:file:text-[#1F1812] cursor-pointer">
                        </div>
                    </div>
                    @error('image')
                        <p class="mt-1 text-xs text-[#C4553D] font-mono">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Deskripsi Menu -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                        Deskripsi Singkat (Opsional):
                    </label>
                    <textarea name="description" rows="3" maxlength="500"
                              placeholder="Penjelasan rasa, komposisi bahan, atau catatan penyajian..."
                              class="w-full bg-[#FAF7F2] border border-[#D5CCC0] focus:border-[#D9973E] focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2.5 text-sm text-[#1F1812] transition shadow-inner">{{ old('description', $menu->description) }}</textarea>
                    <div class="text-[10px] text-[#8A7B66] font-mono mt-0.5">
                        Maksimal 500 karakter.
                    </div>
                </div>
            </div>

        </div>

        <!-- 3. TOMBOL SUBMIT & BATAL -->
        <div class="pt-5 border-t border-[#EAE2D5] flex items-center justify-end gap-3">
            <a href="{{ route('kasir.menu.index') }}"
               class="px-5 py-2.5 border border-[#D5CCC0] hover:bg-[#FAF7F2] text-[#1F1812] font-mono text-xs uppercase tracking-wider font-semibold transition">
                Batal
            </a>

            <button type="submit"
                    class="px-7 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-widest font-bold transition shadow-md cursor-pointer active:scale-98">
                {{ $menu->exists ? 'Simpan Perubahan ›' : 'Tambahkan Menu ke Katalog ›' }}
            </button>
        </div>
    </form>
</div>
@endsection
