<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Kasir') — {{ config('cafe.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F7F3EC] text-[#2A211A] antialiased">
    <!-- Topbar band espresso -->
    <header class="bg-[#1F1812] text-[#F7F3EC] border-b border-[#3A3026]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-12 flex items-center gap-6">
            <a href="{{ route('kasir.terminal') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo-light.svg') }}" alt="{{ config('cafe.name') }}" class="h-7 w-auto">
                <span class="font-mono text-xs tracking-[0.3em] uppercase text-[#A89A85]">// KASIR</span>
            </a>
            <nav class="hidden md:flex items-center gap-5 font-mono text-[11px] uppercase tracking-[0.15em] text-[#A89A85]">
                <a href="{{ route('kasir.terminal') }}" class="hover:text-[#D9973E]">Terminal</a>
                <a href="{{ route('kasir.orders.index') }}" class="hover:text-[#D9973E]">Riwayat</a>
                <a href="{{ route('kasir.menu.index') }}" class="hover:text-[#D9973E]">Menu</a>
                <a href="{{ route('kasir.laporan') }}" class="hover:text-[#D9973E]">Laporan</a>
            </nav>
            <div class="ml-auto flex items-center gap-4 text-xs text-[#A89A85]">
                <span class="hidden sm:inline">{{ auth()->user()->name ?? '' }}</span>
                <form method="POST" action="{{ route('kasir.logout') }}">
                    @csrf
                    <button class="font-mono text-[11px] uppercase tracking-[0.15em] hover:text-[#D9973E]">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
        @if (session('status'))
            <div class="mb-4 border border-[#E4DCCC] bg-white px-4 py-3 text-sm">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
