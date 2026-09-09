@extends('layouts.public')

@section('title', 'Menu')

@section('content')
    <section class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
        <div class="font-mono text-[11px] uppercase tracking-[0.3em] text-[#8A7B66]">Menu {{ config('cafe.name') }}</div>
        <h1 class="mt-2 text-4xl tracking-tight font-medium">Semua yang kami seduh & sajikan.</h1>

        @forelse ($categories as $index => $category)
            <div class="mt-12">
                <div class="flex items-baseline gap-4">
                    <span class="font-mono text-sm text-[#B5762A]">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }} //</span>
                    <h2 class="text-2xl tracking-tight font-medium uppercase">{{ $category->name }}</h2>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($category->menus as $menu)
                        <div class="bg-white border border-[#E4DCCC] shadow-[0_1px_2px_rgba(42,33,26,0.06)] h-full flex flex-col justify-between">
                            <div>
                                @if ($menu->image)
                                    <img src="{{ asset('storage/' . $menu->image) }}" alt="{{ $menu->name }}" class="w-full h-44 object-cover">
                                @else
                                    <div class="w-full h-44 bg-[#1F1812] flex items-center justify-center">
                                        <span class="font-mono text-3xl text-[#A89A85]">{{ strtoupper(substr($menu->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div class="p-5">
                                    <h3 class="text-lg tracking-tight font-medium">{{ $menu->name }}</h3>
                                    <p class="mt-2 text-sm text-[#8A7B66] leading-relaxed h-9 line-clamp-2">{{ $menu->description ?? '' }}</p>
                                </div>
                            </div>
                            <div class="px-5 pb-5">
                                <span class="font-mono text-xl text-[#B5762A]">Rp {{ number_format($menu->price, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="mt-10 text-[#8A7B66]">Menu akan segera tersedia.</p>
        @endforelse
    </section>
@endsection
