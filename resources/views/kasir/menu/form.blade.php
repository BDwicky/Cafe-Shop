@extends('kasir.app')

@section('title', $menu->exists ? 'Edit Menu' : 'Tambah Menu')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-baseline justify-between mb-4">
        <h1 class="text-2xl tracking-tight font-medium">{{ $menu->exists ? 'Edit Menu' : 'Tambah Menu' }}</h1>
        <a href="{{ route('kasir.menu.index') }}" class="font-mono text-[11px] uppercase tracking-[0.15em] text-[#8A7B66] hover:text-[#B5762A]">‹ Kembali</a>
    </div>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $menu->exists ? route('kasir.menu.update', $menu) : route('kasir.menu.store') }}"
          class="bg-white border border-[#E4DCCC] p-6 space-y-5">
        @csrf
        @if ($menu->exists) @method('PUT') @endif

        <div>
            <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Nama Menu</label>
            <input type="text" name="name" value="{{ old('name', $menu->name) }}" required maxlength="100"
                   class="mt-1 w-full bg-white border border-[#E4DCCC] focus:border-[#B5762A] focus:outline-none px-3 py-2.5 text-sm">
            @error('name')<p class="mt-1 text-xs text-[#C4553D]">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Kategori</label>
                <select name="category_id" required
                        class="mt-1 w-full bg-white border border-[#E4DCCC] focus:border-[#B5762A] focus:outline-none px-3 py-2.5 text-sm">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $menu->category_id) == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<p class="mt-1 text-xs text-[#C4553D]">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Harga (Rp)</label>
                <input type="number" name="price" value="{{ old('price', $menu->price) }}" required min="0" step="500"
                       class="mt-1 w-full bg-white border border-[#E4DCCC] focus:border-[#B5762A] focus:outline-none px-3 py-2.5 text-sm font-mono text-right">
                @error('price')<p class="mt-1 text-xs text-[#C4553D]">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Deskripsi (opsional)</label>
            <textarea name="description" rows="3" maxlength="500"
                      class="mt-1 w-full bg-white border border-[#E4DCCC] focus:border-[#B5762A] focus:outline-none px-3 py-2.5 text-sm">{{ old('description', $menu->description) }}</textarea>
        </div>

        <div>
            <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Foto Produk</label>
            @if ($menu->image)
                <div class="mt-1 flex items-center gap-3">
                    <img src="{{ asset('storage/' . $menu->image) }}" class="w-16 h-16 object-cover border border-[#E4DCCC]">
                    <span class="text-xs text-[#8A7B66]">Ganti gambar (opsional):</span>
                </div>
            @endif
            <input type="file" name="image" accept="image/*"
                   class="mt-1 w-full text-sm border border-[#E4DCCC] px-3 py-2.5">
            <p class="mt-1 text-xs text-[#8A7B66]">Format: foto produk disamping atau top-down, background polos. Maks 2MB.</p>
            @error('image')<p class="mt-1 text-xs text-[#C4553D]">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $menu->is_available ?? true)) class="accent-[#B5762A]">
            Tersedia dijual
        </label>

        <button class="w-full bg-[#1F1812] text-[#F7F3EC] hover:bg-[#B5762A] px-6 py-3.5 font-mono text-xs uppercase tracking-[0.2em]">
            {{ $menu->exists ? 'Simpan Perubahan' : 'Tambah Menu' }} ›
        </button>
    </form>
</div>
@endsection
