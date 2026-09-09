@extends('kasir.app')

@section('title', 'Kelola Menu')

@section('content')
<div>
    <div class="flex items-baseline justify-between mb-4">
        <h1 class="text-2xl tracking-tight font-medium">Kelola Menu</h1>
        <a href="{{ route('kasir.menu.create') }}"
           class="bg-[#1F1812] text-[#F7F3EC] hover:bg-[#B5762A] px-5 py-2.5 font-mono text-[11px] uppercase tracking-[0.15em]">Tambah Menu ›</a>
    </div>

    @if (session('status'))
        <div class="mb-4 border border-[#E4DCCC] bg-white px-4 py-3 text-sm">{{ session('status') }}</div>
    @endif

    <!-- Form kategori -->
    <div class="bg-white border border-[#E4DCCC] p-5 mb-6">
        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] mb-3">Kategori</div>
        <div class="flex flex-wrap items-center gap-2 mb-4">
            @foreach ($categories as $cat)
                <span class="inline-flex items-center gap-2 border border-[#E4DCCC] px-3 py-1.5 text-sm">
                    {{ $cat->name }}
                    <span class="font-mono text-[10px] text-[#8A7B66]">{{ $cat->menus_count }}</span>
                    @if ($cat->menus_count === 0)
                        <form method="POST" action="{{ route('kasir.categories.destroy', $cat) }}" onsubmit="return confirm('Hapus kategori ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-[#C4553D] hover:underline text-xs">×</button>
                        </form>
                    @endif
                </span>
            @endforeach
        </div>
        <form method="POST" action="{{ route('kasir.categories.store') }}" class="flex items-center gap-2">
            @csrf
            <input type="text" name="name" required placeholder="Nama kategori baru" maxlength="60"
                   class="bg-white border border-[#E4DCCC] focus:border-[#B5762A] focus:outline-none px-3 py-2 text-sm w-64">
            <button class="border border-[#2A211A] px-4 py-2 font-mono text-[11px] uppercase tracking-[0.15em] hover:bg-[#2A211A] hover:text-[#F7F3EC]">Tambah Kategori</button>
        </form>
    </div>

    <!-- Tabel menu -->
    <div class="bg-white border border-[#E4DCCC] overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-[#E4DCCC] font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] text-left">
                    <th class="px-4 py-3">Gambar</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3 text-right">Harga</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($menus as $m)
                    <tr class="border-b border-[#E4DCCC] last:border-0">
                        <td class="px-4 py-3">
                            @if ($m->image)
                                <img src="{{ asset('storage/' . $m->image) }}" alt="{{ $m->name }}" class="w-12 h-12 object-cover border border-[#E4DCCC]">
                            @else
                                <div class="w-12 h-12 bg-[#1F1812] text-[#F7F3EC] flex items-center justify-center font-mono text-xs">{{ strtoupper(substr($m->name, 0, 1)) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $m->name }}</td>
                        <td class="px-4 py-3">{{ $m->category->name }}</td>
                        <td class="px-4 py-3 text-right font-mono">Rp {{ number_format($m->price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full {{ $m->is_available ? 'bg-[#5F7F42]' : 'bg-[#8A7B66]' }}"></span>
                                <span class="font-mono text-[10px] uppercase tracking-[0.15em]">{{ $m->is_available ? 'Tersedia' : 'Habis' }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <form method="POST" action="{{ route('kasir.menu.toggle', $m) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button class="font-mono text-[11px] uppercase tracking-[0.15em] hover:text-[#B5762A]">{{ $m->is_available ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                            <a href="{{ route('kasir.menu.edit', $m) }}" class="ml-3 font-mono text-[11px] uppercase tracking-[0.15em] hover:text-[#B5762A]">Edit ›</a>
                            <form method="POST" action="{{ route('kasir.menu.destroy', $m) }}" class="inline ml-3" onsubmit="return confirm('Hapus menu ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="font-mono text-[11px] uppercase tracking-[0.15em] text-[#C4553D] hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-[#8A7B66]">Belum ada menu. Tambahkan menu pertama.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $menus->links() }}</div>
</div>
@endsection
