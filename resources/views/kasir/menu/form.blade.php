@extends('kasir.app')

@section('title', $menu->exists ? 'Edit Menu: ' . $menu->name : 'Tambah Menu Baru')

@section('content')
<div class="w-full max-w-4xl mx-auto p-4 sm:p-6 space-y-6"
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

    <!-- 1. HEADER HALAMAN & NAVIGASI KEMBALI -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-[#E4DCCC]">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                    {{ $menu->exists ? 'Edit Menu: ' . $menu->name : 'Tambah Menu Baru' }}
                </h1>
                <span class="px-2.5 py-0.5 rounded-full bg-[#D9973E]/15 border border-[#D9973E]/30 text-[#B5762A] font-mono text-xs font-bold">
                    {{ $menu->exists ? 'Perbarui Katalog' : 'Entri Baru' }}
                </span>
            </div>
            <p class="font-sans text-xs text-[#8A7B66] mt-1">
                {{ $menu->exists ? 'Perbarui rincian produk, harga jual, foto, dan status ketersediaan di kasir.' : 'Lengkapi formulir di bawah ini untuk mendaftarkan hidangan atau minuman baru ke sistem POS.' }}
            </p>
        </div>

        <a href="{{ route('kasir.menu.index') }}"
           class="px-4 py-2 bg-white hover:bg-[#FAF7F2] text-[#1F1812] border border-[#E4DCCC] hover:border-[#D9973E] font-mono text-xs font-bold rounded-xl transition shadow-2xs flex items-center gap-1.5 active:scale-98 self-start sm:self-auto">
            <span>‹ Kembali ke Katalog</span>
        </a>
    </div>

    <!-- 2. FORMULIR INPUT MENU -->
    <form method="POST" enctype="multipart/form-data"
          action="{{ $menu->exists ? route('kasir.menu.update', $menu) : route('kasir.menu.store') }}"
          class="bg-white border border-[#E4DCCC] rounded-2xl p-6 sm:p-8 shadow-xs space-y-6">
        @csrf
        @if ($menu->exists)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">

            <!-- KOLOM KIRI: INFO UTAMA PRODUK & HARGA -->
            <div class="space-y-5">
                <!-- Nama Menu -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                        Nama Menu <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $menu->name) }}" required maxlength="100"
                           placeholder="Contoh: Kopi Susu Gula Aren, Croissant Butter..."
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-4 py-2.5 rounded-xl text-sm text-[#1F1812] transition shadow-2xs font-medium">
                    @error('name')
                        <p class="mt-1.5 text-xs text-[#C4553D] font-mono">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kategori Menu -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                        Kategori <span class="text-red-500">*</span>
                    </label>
                    <select name="category_id" required
                            class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-4 py-2.5 rounded-xl text-sm text-[#1F1812] transition shadow-2xs cursor-pointer font-medium">
                        <option value="" disabled {{ !old('category_id', $menu->category_id) ? 'selected' : '' }}>Pilih Kategori...</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $menu->category_id) == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1.5 text-xs text-[#C4553D] font-mono">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Harga Jual -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                        Harga Jual (Rp) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-[#8A7B66] font-mono text-sm font-bold">
                            Rp
                        </span>
                        <input type="number" name="price" x-model="priceInput" required min="0" step="500"
                               placeholder="Contoh: 25000"
                               class="w-full pl-12 pr-4 py-2.5 bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 rounded-xl text-sm font-mono font-bold text-[#1F1812] transition shadow-2xs text-right">
                    </div>
                    <!-- Live Rupiah Formatting Preview -->
                    <template x-if="priceInput && !isNaN(priceInput)">
                        <div class="mt-1.5 text-right font-mono text-[11px] text-[#5F7F42] font-semibold"
                             x-text="'Format POS: Rp ' + Number(priceInput).toLocaleString('id-ID')"></div>
                    </template>
                    @error('price')
                        <p class="mt-1.5 text-xs text-[#C4553D] font-mono">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status Ketersediaan Stok (Pill Switch Toggle) -->
                <div class="pt-3 border-t border-[#F2EDE4]">
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
                    <p class="text-[11px] text-[#8A7B66] font-sans mt-1">
                        Jika nonaktif, menu ini tidak akan muncul di katalog pemesanan kasir.
                    </p>
                </div>
            </div>

            <!-- KOLOM KANAN: FOTO & DESKRIPSI -->
            <div class="space-y-5">
                <!-- Upload & Live Preview Foto Produk -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                        Foto Produk (Opsional):
                    </label>

                    <div class="border-2 border-dashed border-[#E4DCCC] rounded-2xl p-5 text-center bg-[#FAF7F2] relative hover:border-[#D9973E] transition">
                        <!-- Preview Image jika sudah ada atau baru dipilih -->
                        <template x-if="previewUrl">
                            <div class="space-y-3">
                                <div class="w-40 h-40 mx-auto rounded-2xl overflow-hidden border border-[#E4DCCC] shadow-xs bg-black">
                                    <img :src="previewUrl" alt="Preview Foto Menu" class="w-full h-full object-cover">
                                </div>
                                <div class="flex justify-center gap-2">
                                    <button type="button" @click="removeImage()"
                                            class="px-3 py-1 text-[#C4553D] hover:bg-red-50 border border-red-200 text-xs font-mono font-bold rounded-xl transition cursor-pointer">
                                        ✕ Hapus Foto
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Upload Prompt jika belum ada preview -->
                        <template x-if="!previewUrl">
                            <div class="py-6 space-y-2">
                                <div class="text-4xl text-[#8A7B66] opacity-60">📷</div>
                                <div class="text-xs font-bold text-[#1F1812]">
                                    Pilih foto hidangan atau minuman
                                </div>
                                <div class="text-[10px] text-[#8A7B66] font-mono">
                                    Format: JPG, PNG, WEBP (Maksimal 2MB)
                                </div>
                            </div>
                        </template>

                        <div class="mt-3">
                            <input type="file" name="image" x-ref="fileInput" @change="handleImageChange($event)" accept="image/*"
                                   class="w-full text-xs text-[#7A6A58] file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-mono file:uppercase file:tracking-wider file:font-bold file:bg-[#1F1812] file:text-[#F7F3EC] hover:file:bg-[#D9973E] hover:file:text-[#1F1812] cursor-pointer">
                        </div>
                    </div>
                    @error('image')
                        <p class="mt-1.5 text-xs text-[#C4553D] font-mono">{{ $message }}</p>
                    @enderror
                </div>

                    <!-- Deskripsi Menu -->
                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1.5">
                            Deskripsi Singkat (Opsional):
                        </label>
                        <textarea name="description" rows="2" maxlength="500"
                                  placeholder="Penjelasan rasa hidangan, komposisi bahan, atau catatan penyajian kasir..."
                                  class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-4 py-2.5 rounded-xl text-sm text-[#1F1812] transition shadow-2xs">{{ old('description', $menu->description) }}</textarea>
                        <div class="text-[10px] text-[#8A7B66] font-mono mt-1">
                            Maksimal 500 karakter.
                        </div>
                        @error('description')
                            <p class="mt-1 text-xs text-[#C4553D] font-mono">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

            </div>

            <!-- SEKSI KHUSUS: RINCIAN KOMPOSISI, BAHAN BAKU & NILAI GIZI (DITAMPILKAN DI HALAMAN PUBLIK /MENU) -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                <div class="flex items-center justify-between border-b border-[#E4DCCC] pb-3.5">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-[#5F7F42]/10 border border-[#5F7F42]/30 flex items-center justify-center text-[#5F7F42] text-base shrink-0">
                            🌿
                        </span>
                        <div>
                            <h3 class="font-serif font-bold text-base text-[#1F1812]">Rincian Komposisi & Nilai Gizi Publik</h3>
                            <p class="text-xs text-[#8A7B66]">Informasi ini ditampilkan di modal pop-up detail menu pada Halaman Publik (<span class="font-mono text-[#D9973E]">/menu</span>).</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold uppercase tracking-wider bg-[#FAF7F2] text-[#8A7B66] border border-[#E4DCCC]">
                        Publik & Transparan
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Kolom Kiri: Komposisi Bahan Utama (Per Baris) -->
                    <div class="space-y-2">
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold">
                            Komposisi & Bahan Baku Utama:
                        </label>
                        <p class="text-[11px] text-[#8A7B66] leading-relaxed">
                            Ketik 1 bahan per baris (tekan <strong>Enter</strong> untuk baris baru). Akan tampil dengan ikon centang hijau (✓) di kartu detail publik.
                        </p>
                        @php
                            $ingredientsValue = old('ingredients');
                            if ($ingredientsValue === null) {
                                if (!empty($menu->ingredients) && is_array($menu->ingredients)) {
                                    $ingredientsValue = implode("\n", $menu->ingredients);
                                } elseif ($menu->exists) {
                                    $ingredientsValue = implode("\n", $menu->detailed_ingredients);
                                } else {
                                    $ingredientsValue = '';
                                }
                            }
                        @endphp
                        <textarea name="ingredients" rows="5"
                                  placeholder="Contoh:&#10;100% Biji Kopi Arabika Single-Origin Gayo (Double Shot 18g)&#10;Fresh Milk Pasteurisasi Dingin (150ml)&#10;Sirup Gula Aren Organik Asli (20ml)"
                                  class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-4 py-2.5 rounded-xl text-sm font-sans text-[#1F1812] transition shadow-2xs leading-relaxed">{{ $ingredientsValue }}</textarea>
                        @error('ingredients')
                            <p class="mt-1 text-xs text-[#C4553D] font-mono">{{ $message }}</p>
                        @enderror

                        <!-- Catatan Rasa (Flavor Notes) -->
                        <div class="pt-2">
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                                Catatan Karakter Rasa (Flavor Notes):
                            </label>
                            @php
                                $flavorNotesValue = old('flavor_notes', $menu->flavor_notes ?? ($menu->exists ? $menu->detailed_flavor_notes : ''));
                            @endphp
                            <input type="text" name="flavor_notes" value="{{ $flavorNotesValue }}"
                                   placeholder="Contoh: Dark Chocolate, Toasted Almond, Crema Tebal, Clean Finish"
                                   class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-4 py-2.5 rounded-xl text-sm text-[#1F1812] transition shadow-2xs">
                            @error('flavor_notes')
                                <p class="mt-1 text-xs text-[#C4553D] font-mono">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Kolom Kanan: Informasi Nilai Gizi & Alergen -->
                    <div class="space-y-3.5">
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold">
                            Estimasi Nilai Gizi & Karakteristik:
                        </label>
                        @php
                            $nut = old('nutrition', $menu->nutrition ?? ($menu->exists ? $menu->detailed_nutrition : []));
                        @endphp

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Kalori -->
                            <div>
                                <label class="block text-[10px] font-mono text-[#8A7B66] uppercase tracking-wider mb-1">Kalori</label>
                                <input type="text" name="nutrition[calories]" value="{{ $nut['calories'] ?? '' }}"
                                       placeholder="Misal: 110 kkal"
                                       class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3 py-2 rounded-xl text-xs font-mono font-bold text-[#1F1812] transition">
                            </div>
                            <!-- Kafein -->
                            <div>
                                <label class="block text-[10px] font-mono text-[#8A7B66] uppercase tracking-wider mb-1">Kafein</label>
                                <input type="text" name="nutrition[caffeine]" value="{{ $nut['caffeine'] ?? '' }}"
                                       placeholder="Misal: 85 mg"
                                       class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3 py-2 rounded-xl text-xs font-mono font-bold text-[#1F1812] transition">
                            </div>
                            <!-- Gula -->
                            <div>
                                <label class="block text-[10px] font-mono text-[#8A7B66] uppercase tracking-wider mb-1">Kandungan Gula</label>
                                <input type="text" name="nutrition[sugar]" value="{{ $nut['sugar'] ?? '' }}"
                                       placeholder="Misal: 6 g"
                                       class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3 py-2 rounded-xl text-xs font-mono text-[#1F1812] transition">
                            </div>
                            <!-- Lemak -->
                            <div>
                                <label class="block text-[10px] font-mono text-[#8A7B66] uppercase tracking-wider mb-1">Kandungan Lemak</label>
                                <input type="text" name="nutrition[fat]" value="{{ $nut['fat'] ?? '' }}"
                                       placeholder="Misal: 3 g"
                                       class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3 py-2 rounded-xl text-xs font-mono text-[#1F1812] transition">
                            </div>
                        </div>

                        <!-- Alergen & Peringatan Konsumsi -->
                        <div>
                            <label class="block text-[10px] font-mono text-[#8A7B66] uppercase tracking-wider mb-1">Informasi Alergen</label>
                            <input type="text" name="nutrition[allergens]" value="{{ $nut['allergens'] ?? '' }}"
                                   placeholder="Misal: Dapat mengandung produk susu (dairy) atau Bebas Alergen"
                                   class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3 py-2 rounded-xl text-xs text-[#1F1812] transition">
                        </div>

                        <!-- Opsi Penyajian -->
                        <div>
                            <label class="block text-[10px] font-mono text-[#8A7B66] uppercase tracking-wider mb-1">Format Penyajian</label>
                            <input type="text" name="nutrition[serving]" value="{{ $nut['serving'] ?? '' }}"
                                   placeholder="Misal: Hot (200ml) / Iced (350ml)"
                                   class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3 py-2 rounded-xl text-xs text-[#1F1812] transition">
                        </div>
                    </div>
                </div>

        <!-- 3. TOMBOL SUBMIT & BATAL -->
        <div class="pt-6 border-t border-[#F2EDE4] flex items-center justify-end gap-3">
            <a href="{{ route('kasir.menu.index') }}"
               class="px-5 py-2.5 border border-[#E4DCCC] hover:bg-[#FAF7F2] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition">
                Batal
            </a>

            <button type="submit"
                    class="px-7 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-widest font-bold rounded-xl transition-all shadow-md cursor-pointer active:scale-98">
                {{ $menu->exists ? 'Simpan Perubahan ›' : 'Tambahkan Menu ke Katalog ›' }}
            </button>
        </div>
    </form>
</div>
@endsection
