<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Kasir — {{ config('cafe.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F7F3EC] text-[#2A211A] antialiased">
    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
        <!-- Band espresso kiri -->
        <div class="bg-[#1F1812] text-[#F7F3EC] hidden md:flex flex-col justify-between p-10">
            <div class="font-mono text-xs tracking-[0.3em] uppercase">{{ strtoupper(config('cafe.name')) }} <span class="text-[#A89A85]">// KASIR</span></div>
            <div>
                <div class="text-3xl tracking-tight font-medium leading-tight">{{ config('cafe.tagline') }}</div>
                <div class="mt-4 font-mono text-[11px] uppercase tracking-[0.2em] text-[#A89A85]">Point of Sale — Staff Only</div>
            </div>
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">{{ config('cafe.name') }} — {{ config('cafe.address') }}</div>
        </div>

        <!-- Form di atas paper -->
        <div class="flex items-center justify-center p-6">
            <form method="POST" action="{{ route('kasir.authenticate') }}" class="w-full max-w-sm bg-white border border-[#E4DCCC] p-8 shadow-[0_1px_2px_rgba(42,33,26,0.06)]">
                <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Login Kasir</div>
                <h1 class="mt-1 text-2xl tracking-tight font-medium">Masuk ke Terminal</h1>

                @csrf

                <div class="mt-6">
                    <label for="email" class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           class="mt-1 w-full bg-white border border-[#E4DCCC] focus:border-[#B5762A] focus:outline-none px-3 py-2.5 text-sm">
                    @error('email')<p class="mt-1 text-xs text-[#C4553D]">{{ $message }}</p>@enderror
                </div>

                <div class="mt-4">
                    <label for="password" class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Password</label>
                    <input id="password" name="password" type="password" required
                           class="mt-1 w-full bg-white border border-[#E4DCCC] focus:border-[#B5762A] focus:outline-none px-3 py-2.5 text-sm">
                </div>

                <label class="mt-4 flex items-center gap-2 text-sm text-[#8A7B66]">
                    <input type="checkbox" name="remember" class="accent-[#B5762A]"> Ingat saya
                </label>

                <button type="submit"
                        class="mt-6 w-full bg-[#1F1812] text-[#F7F3EC] hover:bg-[#B5762A] hover:text-white px-6 py-3.5 font-mono text-xs uppercase tracking-[0.2em]">
                    Masuk ›
                </button>
            </form>
        </div>
    </div>
</body>
</html>
