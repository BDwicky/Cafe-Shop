<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ config('cafe.name') }} — {{ config('cafe.tagline') }}">
    <title>@yield('title', config('cafe.name')) — {{ config('cafe.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F7F3EC] text-[#2A211A] antialiased">

    <!-- Topbar band espresso -->
    <header class="sticky top-0 z-40 bg-[#1F1812] text-[#F7F3EC] border-b border-[#3A3026]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center">
                <img src="{{ asset('images/logo-light.svg') }}" alt="{{ config('cafe.name') }}" class="h-8 w-auto">
            </a>
            <nav class="flex items-center gap-6 font-mono text-[11px] uppercase tracking-[0.2em] text-[#A89A85]">
                <a href="{{ route('menu.public') }}" class="hover:text-[#D9973E]">Menu ›</a>
                <a href="{{ route('landing') }}#lokasi" class="hover:text-[#D9973E] hidden sm:inline">Lokasi ›</a>
                <a href="https://wa.me/{{ config('cafe.wa_number') }}" target="_blank" rel="noopener" class="hover:text-[#D9973E]">Kontak ›</a>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <!-- Footer band espresso -->
    <footer class="bg-[#1F1812] text-[#F7F3EC] border-t border-[#3A3026] mt-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <img src="{{ asset('images/logo-light.svg') }}" alt="{{ config('cafe.name') }}" class="h-8 w-auto">
                <p class="mt-3 text-sm text-[#A89A85] leading-relaxed">{{ config('cafe.tagline') }}</p>
            </div>
            <div>
                <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">Jam Buka</div>
                <ul class="mt-3 space-y-1 text-sm text-[#A89A85]">
                    @foreach (\App\Support\OpeningHours::all() as $day => $h)
                        <li class="flex justify-between gap-6 border-b border-[#3A3026] pb-1">
                            <span>{{ $day }}</span>
                            <span class="font-mono">{{ $h ? $h[0] . '–' . $h[1] : 'Tutup' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">Kontak & Lokasi</div>
                <p class="mt-3 text-sm text-[#A89A85] leading-relaxed">{{ config('cafe.address') }}</p>
                <div class="mt-3 flex flex-col gap-1 font-mono text-[11px] uppercase tracking-[0.15em]">
                    <a href="https://wa.me/{{ config('cafe.wa_number') }}" target="_blank" rel="noopener" class="hover:text-[#D9973E]">WhatsApp ›</a>
                    <a href="https://instagram.com/{{ ltrim(config('cafe.instagram'), '@') }}" target="_blank" rel="noopener" class="hover:text-[#D9973E]">Instagram ›</a>
                </div>
            </div>
        </div>
        <div class="border-t border-[#3A3026]">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85] flex flex-wrap justify-between gap-2">
                <span>© {{ date('Y') }} {{ config('cafe.name') }}</span>
                <a href="{{ route('kasir.login') }}" class="hover:text-[#D9973E]">Staff Login</a>
            </div>
        </div>
    </footer>

    <!-- CUSTOM CONFIRMATION & ALERT MODAL DIALOG -->
    @include('components.modal-dialog')
</body>
</html>
