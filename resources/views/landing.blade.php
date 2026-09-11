@extends('layouts.public')

@section('title', 'Beranda')

@section('content')
    {{-- 1. HERO band gelap dengan background video --}}
    <section class="relative bg-[#1F1812] text-[#F7F3EC] min-h-[66vh] flex items-center overflow-hidden">
        @if (file_exists(public_path('images/hero.mp4')))
            {{-- Background video: autoplay muted loop (barista menuang susu, latte art) --}}
            <video class="absolute inset-0 w-full h-full object-cover"
                   autoplay muted loop playsinline preload="metadata"
                   poster="{{ asset('images/hero.jpg') }}">
                <source src="{{ asset('images/hero.mp4') }}" type="video/mp4">
            </video>
            <div class="absolute inset-0 bg-[#1F1812]/70"></div>
        @elseif (file_exists(public_path('images/hero.jpg')))
            <img src="{{ asset('images/hero.jpg') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-[#1F1812]/70"></div>
        @endif
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-20 w-full">
            <div class="font-mono text-[11px] uppercase tracking-[0.3em] text-[#A89A85]">Specialty Coffee // Kota Kamu</div>
            <h1 class="mt-4 text-4xl sm:text-6xl md:text-7xl tracking-tight font-medium leading-[1.05] max-w-3xl">{{ config('cafe.tagline') }}</h1>
            <p class="mt-6 max-w-xl text-[#A89A85] leading-relaxed">Biji single-origin pilihan, diseduh presisi oleh barista kami. Untuk pagi yang pelan, kerja yang fokus, dan obrolan yang panjang.</p>
            <div class="mt-8 flex flex-wrap gap-x-8 gap-y-3 font-mono text-xs uppercase tracking-[0.2em]">
                <a href="{{ route('menu.public') }}" class="text-[#D9973E] hover:text-[#F7F3EC]">Lihat Menu ›</a>
                <a href="https://wa.me/{{ config('cafe.wa_number') }}" target="_blank" rel="noopener" class="text-[#D9973E] hover:text-[#F7F3EC]">Hubungi Kami ›</a>
            </div>
        </div>
    </section>

    {{-- Strip status buka di paper --}}
    <div class="border-b border-[#E4DCCC] bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex items-center gap-2.5">
            <span class="h-1.5 w-1.5 rounded-full {{ \App\Support\OpeningHours::isOpen() ? 'bg-[#5F7F42]' : 'bg-[#B5762A]' }}"></span>
            <span class="font-mono text-[11px] uppercase tracking-[0.2em] text-[#8A7B66]">{{ \App\Support\OpeningHours::statusLine() }}</span>
        </div>
    </div>

    {{-- 2. MENU UNGGULAN di paper --}}
    <section class="max-w-6xl mx-auto px-4 sm:px-6 py-16">
        <div class="flex items-baseline justify-between mb-8">
            <h2 class="text-3xl tracking-tight font-medium">Menu Unggulan</h2>
            <a href="{{ route('menu.public') }}" class="font-mono text-[11px] uppercase tracking-[0.2em] text-[#B5762A] hover:text-[#2A211A]">Semua Menu ›</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($featured as $menu)
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
                            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">{{ $menu->category->name }}</div>
                            <h3 class="mt-1 text-lg tracking-tight font-medium">{{ $menu->name }}</h3>
                            <p class="mt-2 text-sm text-[#8A7B66] leading-relaxed break-words">{{ $menu->description ?? '' }}</p>
                        </div>
                    </div>
                    <div class="px-5 pb-5 flex items-center justify-between">
                        <span class="font-mono text-xl text-[#B5762A]">Rp {{ number_format($menu->price, 0, ',', '.') }}</span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#5F7F42]"></span>
                            <span class="font-mono text-[10px] uppercase tracking-[0.15em] text-[#5F7F42]">Tersedia</span>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 3. SUASANA — galeri video ambience --}}
    <section class="bg-[#1F1812] text-[#F7F3EC] border-y border-[#3A3026]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16">
            <div class="font-mono text-[11px] uppercase tracking-[0.3em] text-[#A89A85]">Suasana // Di Dapur Kami</div>
            <h2 class="mt-3 text-3xl tracking-tight font-medium">Dari biji, ke pastry, ke cangkirmu.</h2>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ([
                    ['file' => 'coffee-beans', 'tag' => 'BIJI // SINGLE-ORIGIN', 'title' => 'Biji segar, digiling harian', 'desc' => 'Kami menyimpan biji dalam batch kecil dan menggiling fresh sebelum diseduh.'],
                    ['file' => 'coffee-stir', 'tag' => 'SEDUH // PRESISI', 'title' => 'Presisi di setiap tahap', 'desc' => 'Distribusi rata, suhu & rasio terukur — konsisten dari cup pertama sampai terakhir.'],
                    ['file' => 'pastry-display', 'tag' => 'PASTRY // FRESH DAILY', 'title' => 'Pastry baru tiap pagi', 'desc' => 'Cinnamon roll, scone, dan babka diisi ke etalase setiap hari sebelum jam 8.'],
                ] as $v)
                    @if (file_exists(public_path("images/{$v['file']}.mp4")))
                        <figure class="group">
                            <div class="relative overflow-hidden border border-[#3A3026]">
                                <video class="w-full h-56 object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                                       autoplay muted loop playsinline preload="metadata"
                                       poster="{{ asset('images/hero.jpg') }}">
                                    <source src="{{ asset('images/' . $v['file'] . '.mp4') }}" type="video/mp4">
                                </video>
                                <span class="absolute top-3 left-3 bg-[#1F1812]/80 text-[#D9973E] font-mono text-[9px] uppercase tracking-[0.2em] px-2.5 py-1.5">{{ $v['tag'] }}</span>
                            </div>
                            <figcaption class="mt-3">
                                <h3 class="tracking-tight font-medium">{{ $v['title'] }}</h3>
                                <p class="mt-1 text-sm text-[#A89A85] leading-relaxed">{{ $v['desc'] }}</p>
                            </figcaption>
                        </figure>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. TENTANG --}}
    <section class="border-y border-[#E4DCCC] bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
            <div>
                <div class="font-mono text-[11px] uppercase tracking-[0.3em] text-[#8A7B66]">Tentang Kami</div>
                <h2 class="mt-3 text-3xl tracking-tight font-medium">Diseduh dengan presisi,<br>disajikan dengan hangat.</h2>
                <p class="mt-4 text-[#8A7B66] leading-relaxed">{{ config('cafe.name') }} adalah ruang kopi untuk semua — dari barista hingga yang baru kenal kopi. Kami mengutamakan biji lokal berkualitas, alat seduh presisi, dan suasana yang bikin betah.</p>
            </div>
            <div>
                @foreach ([
                    ['01', 'Biji single-origin', 'Di-roast lokal, digiling fresh sebelum diseduh.'],
                    ['02', 'Seduh presisi', 'Rasio & suhu terukur untuk rasa yang konsisten.'],
                    ['03', 'Ruang untuk semua', 'WiFi kencang, colokan di tiap meja, tanpa batas waktu.'],
                ] as $i => [$num, $title, $desc])
                    <div class="flex gap-4 py-4 {{ $i > 0 ? 'border-t border-[#E4DCCC]' : '' }}">
                        <span class="font-mono text-xs text-[#B5762A] pt-0.5">PROTOCOL // {{ $num }}</span>
                        <div>
                            <div class="font-medium">{{ $title }}</div>
                            <div class="text-sm text-[#8A7B66] mt-0.5">{{ $desc }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 5. LOKASI & JAM --}}
    <section id="lokasi" class="max-w-6xl mx-auto px-4 sm:px-6 py-16">
        <div class="font-mono text-[11px] uppercase tracking-[0.3em] text-[#8A7B66]">Lokasi & Jam</div>
        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-10">
            <div>
                <h2 class="text-3xl tracking-tight font-medium">Mampir ke sini.</h2>
                <p class="mt-4 text-[#8A7B66] leading-relaxed">{{ config('cafe.address') }}</p>
                <div class="mt-6 flex flex-wrap gap-x-8 gap-y-3 font-mono text-xs uppercase tracking-[0.2em]">
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(config('cafe.address')) }}" target="_blank" rel="noopener" class="text-[#D9973E] hover:text-[#2A211A]">Buka di Maps ›</a>
                    <a href="https://wa.me/{{ config('cafe.wa_number') }}" target="_blank" rel="noopener" class="text-[#D9973E] hover:text-[#2A211A]">WhatsApp Kami ›</a>
                </div>
            </div>
            <div class="bg-white border border-[#E4DCCC] p-6">
                <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] mb-3">Jam Operasional</div>
                <ul>
                    @foreach (\App\Support\OpeningHours::all() as $day => $h)
                        <li class="flex justify-between py-1.5 text-sm border-b border-[#E4DCCC] last:border-0">
                            <span>{{ $day }}</span>
                            <span class="font-mono {{ $h ? '' : 'text-[#C4553D]' }}">{{ $h ? $h[0] . '–' . $h[1] : 'Tutup' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
@endsection
