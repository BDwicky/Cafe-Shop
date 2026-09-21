@php
    $quotes = [
        ['quote' => 'Secangkir kopi yang diseduh dengan senyuman mampu mengubah seluruh hari seseorang menjadi lebih baik.', 'theme' => 'Pelayanan Tulus'],
        ['quote' => 'Ketelitian dalam menakar rasa adalah bentuk cinta yang tertuang dalam setiap tetes espresso.', 'theme' => 'Dedikasi Rasa'],
        ['quote' => 'Pelayanan ramah di meja kasir adalah aroma kehangatan pertama yang dirasakan setiap tamu.', 'theme' => 'Senyum Hangat'],
        ['quote' => 'Awali shift hari ini dengan hati gembira: seduh yang terbaik, sambut dengan tulus.', 'theme' => 'Semangat Pagi'],
        ['quote' => 'Kualitas terbaik lahir dari ketulusan dan konsistensi pada hal-hal kecil setiap harinya.', 'theme' => 'Standar Mutu'],
        ['quote' => 'Bukan sekadar menyajikan minuman, kita sedang menciptakan momen berharga untuk setiap tamu.', 'theme' => 'Momen Berharga'],
        ['quote' => 'Senyum hangatmu di kasir hari ini bisa jadi alasan seseorang merasa harinya diselamatkan.', 'theme' => 'Ketulusan'],
        ['quote' => 'Kompak dalam tim, gesit dalam melayani, dan sempurna dalam setiap racikan meja.', 'theme' => 'Kekompakan Tim'],
        ['quote' => 'Setiap biji kopi punya cerita perjalanan, dan tangan kitalah yang menyempurnakan aromanya.', 'theme' => 'Seni Menyeduh'],
        ['quote' => 'Hari yang luar biasa selalu berawal dari secangkir racikan yang dibuat dengan sepenuh hati.', 'theme' => 'Inspirasi Shift'],
        ['quote' => 'Kerja keras yang dilandasi rasa bangga akan selalu membuahkan senyum puas pelanggan.', 'theme' => 'Kebanggaan Kerja'],
        ['quote' => 'Jadilah staf yang selalu diingat tamu bukan hanya karena kopinya, tapi karena kehangatan jiwanya.', 'theme' => 'Kesan Abadi'],
        ['quote' => 'Detail kecil dalam kebersihan bar dan kelembutan microfoam adalah tanda profesional sejati.', 'theme' => 'Profesionalisme'],
        ['quote' => 'Semangat baru di setiap antrean pesanan: cepat, tepat, dan penuh keakraban.', 'theme' => 'Kecepatan & Akurasi'],
        ['quote' => 'Ketika lelah datang, ingatlah kita adalah pembawa energi positif bagi puluhan orang hari ini.', 'theme' => 'Energi Positif'],
        ['quote' => 'Rasa kopi yang konsisten dibangun dari kedisiplinan dan rasa hormat pada setiap proses seduh.', 'theme' => 'Konsistensi'],
        ['quote' => 'Satu sapaan tulus saat menyambut tamu mampu meluluhkan kepenatan hari mereka.', 'theme' => 'Sapaan Ramah'],
        ['quote' => 'Jadikan setiap cangkir yang keluar dari bar hari ini sebagai karya terbaik yang kamu persembahkan.', 'theme' => 'Karya Terbaik'],
        ['quote' => 'Kebersamaan dan saling dukung di balik bar adalah kunci kelancaran ritme kedai kopi.', 'theme' => 'Sinergi Tim'],
        ['quote' => 'Fokus, teliti, dan berikan yang terbaik: shift hari ini pasti penuh pencapaian luar biasa.', 'theme' => 'Fokus & Sukses'],
        ['quote' => 'Bekerja dengan ceria membuat setiap racikan minuman terasa jauh lebih nikmat dan bernyawa.', 'theme' => 'Keceriaan'],
        ['quote' => 'Setiap kendala operasional adalah kesempatan membuktikan bahwa tim kita solid dan tangguh.', 'theme' => 'Ketangguhan'],
        ['quote' => 'Kopi yang hebat tidak hanya memanjakan lidah, tetapi juga membangkitkan inspirasi bagi yang meminumnya.', 'theme' => 'Sumber Inspirasi'],
        ['quote' => 'Antusiasme kita saat membuka kedai pagi ini adalah magnet yang mengundang berkah sepanjang hari.', 'theme' => 'Antusiasme Pagi'],
        ['quote' => 'Lakukan setiap tanggung jawab dengan tulus, dari menyiapkan cangkir bersih hingga tetes terakhir.', 'theme' => 'Tanggung Jawab'],
        ['quote' => 'Ketelitian input kasir dan senyum manis adalah pondasi kepercayaan para pelanggan setia kita.', 'theme' => 'Kepercayaan Tamu'],
        ['quote' => 'Suasana kafe yang nyaman berakar dari keharmonisan dan tawa riang staf di dalamnya.', 'theme' => 'Harmoni Kedai'],
        ['quote' => 'Jangan pernah sepelekan kekuatan ucapan terima kasih tulus saat menyodorkan struk dan pesanan.', 'theme' => 'Rasa Terima Kasih'],
        ['quote' => 'Belajar dan bertumbuhlah setiap hari: jadilah barista dan staf yang lebih hebat dari kemarin.', 'theme' => 'Pertumbuhan Diri'],
        ['quote' => 'Terima kasih atas dedikasi dan kerja kerasmu hari ini, energimu membuat kafe ini hidup.', 'theme' => 'Apresiasi Tim'],
        ['quote' => 'Tutup shift dengan rasa bangga: hari ini kita telah menghadirkan banyak senyum dan kehangatan.', 'theme' => 'Rasa Syukur'],
    ];

    $today = now()->setTimezone('Asia/Jakarta');
    $dayIndex = (int) $today->format('z') % count($quotes);
    $todayQuote = $quotes[$dayIndex];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Kasir — {{ config('cafe.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes loginFadeIn {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulseGlow {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.12; }
            50% { transform: scale(1.08) rotate(4deg); opacity: 0.22; }
        }
        @keyframes floatSteam {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .animate-login-card {
            animation: loginFadeIn 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .animate-pulse-glow {
            animation: pulseGlow 10s ease-in-out infinite;
        }
        .animate-float-steam {
            animation: floatSteam 5s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-[#F7F3EC] text-[#2A211A] antialiased selection:bg-[#B5762A] selection:text-white">
    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">

        <!-- ===================================================================
             PANEL KIRI ESPRESSO (Desktop & Tablet Lebar)
             =================================================================== -->
        <div class="bg-[#1F1812] text-[#F7F3EC] hidden md:flex flex-col justify-between p-10 lg:p-14 relative overflow-hidden">
            <!-- Background Ambient Watermarks with Pulse Animation -->
            <div class="absolute -right-20 -bottom-20 w-96 h-96 rounded-full border border-[#D9973E]/15 pointer-events-none animate-pulse-glow"></div>
            <div class="absolute -left-20 -top-20 w-96 h-96 rounded-full border border-[#D9973E]/10 pointer-events-none animate-pulse-glow" style="animation-delay: -5s;"></div>
            <div class="absolute right-1/4 top-1/3 w-64 h-64 rounded-full bg-[#D9973E]/5 blur-3xl pointer-events-none"></div>

            <!-- Top Brand Header with Interactive Logo -->
            <a href="{{ route('landing') }}" class="flex items-center gap-3.5 group relative z-10 w-fit">
                <div class="w-12 h-12 rounded-xl bg-[#261D16] border border-[#3A2D22] flex items-center justify-center p-2.5 shadow-sm group-hover:border-[#D9973E] group-hover:scale-105 transition-all duration-300">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="{{ config('cafe.name') }}" class="h-full w-full object-contain">
                </div>
                <div>
                    <div class="font-bold tracking-tight text-lg text-[#FAF7F2] group-hover:text-[#D9973E] transition-colors leading-tight">{{ config('cafe.name') }}</div>
                    <div class="font-mono text-[9px] uppercase tracking-[0.25em] text-[#D9973E] font-semibold mt-0.5 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42] animate-pulse"></span>
                        <span>Staff & POS Portal</span>
                    </div>
                </div>
            </a>

            <!-- Content Area with Tagline & Daily Motivation -->
            <div class="relative z-10 my-auto py-8">
                <div class="font-serif text-3xl lg:text-4xl tracking-tight font-bold leading-tight text-[#FAF7F2] drop-shadow-xs">
                    {{ config('cafe.tagline') }}
                </div>
                <p class="mt-3.5 text-xs lg:text-sm text-[#A89A85] leading-relaxed max-w-md">
                    Portal terpadu operasional kasir, manajemen tiket pesanan, update stok bahan baku, dan sound station kafe.
                </p>

                <!-- Daily Motivation Box (Desktop) with Hover Micro-interaction -->
                <div class="mt-8 p-5 rounded-2xl bg-[#261D16]/90 hover:bg-[#261D16] border border-[#3A2D22] hover:border-[#D9973E]/40 relative overflow-hidden backdrop-blur-xs shadow-lg transition-all duration-300 group">
                    <!-- Subtle Glow Accent inside card -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-[#D9973E]/5 rounded-full blur-xl pointer-events-none group-hover:bg-[#D9973E]/10 transition-all duration-500"></div>

                    <div class="flex items-center justify-between gap-2 mb-2.5 relative z-10">
                        <div class="flex items-center gap-2">
                            <span class="text-[#D9973E] text-xs animate-float-steam">✦</span>
                            <span class="font-mono text-[9px] uppercase tracking-[0.25em] text-[#D9973E] font-bold">
                                Motivasi Shift Hari Ini
                            </span>
                        </div>
                        <span class="font-mono text-[9px] uppercase tracking-wider text-[#A89A85]">
                            {{ $today->translatedFormat('l, d F Y') }}
                        </span>
                    </div>

                    <p class="font-serif italic text-sm lg:text-base text-[#FAF7F2] leading-relaxed relative z-10">
                        “{{ $todayQuote['quote'] }}”
                    </p>

                    <div class="mt-3.5 flex items-center justify-between text-[10px] font-mono text-[#A89A85] border-t border-[#3A2D22]/80 pt-2.5 relative z-10">
                        <span>Fokus: <b class="text-[#FAF7F2] font-medium">{{ $todayQuote['theme'] }}</b></span>
                        <span class="text-[#D9973E] font-semibold flex items-center gap-1">
                            <span>☕</span>
                            <span>Semangat Bertugas!</span>
                        </span>
                    </div>
                </div>

                <!-- Shift Status Pill -->
                <div class="mt-6 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[#261D16] border border-[#3A2D22] font-mono text-[11px] uppercase tracking-[0.2em] text-[#D9973E] shadow-2xs">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#5F7F42] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[#5F7F42]"></span>
                    </span>
                    <span>Terminal Kasir — Staff Only</span>
                </div>
            </div>

            <!-- Bottom Left Info -->
            <div class="relative z-10 font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85] flex items-center justify-between border-t border-[#32261C] pt-4">
                <span>{{ config('cafe.name') }}</span>
                <span>{{ config('cafe.address') }}</span>
            </div>
        </div>

        <!-- ===================================================================
             PANEL KANAN FORM (Mobile & Desktop)
             =================================================================== -->
        <div class="flex items-center justify-center p-5 sm:p-10 relative">
            <div class="w-full max-w-sm">

                <!-- Form Card with Alpine.js Interactions & Entrance Animation -->
                <form method="POST" action="{{ route('kasir.authenticate') }}"
                      x-data="{
                          showPassword: false,
                          isSubmitting: false,
                          emailVal: '{{ old('email', '') }}',
                          passVal: '',
                          quickFill(role) {
                              this.emailVal = role === 'kasir' ? 'kasir' : 'owner';
                              this.passVal = 'password';
                              this.$nextTick(() => { document.getElementById('email').focus(); });
                          }
                      }"
                      @submit="isSubmitting = true"
                      class="animate-login-card bg-white border border-[#E4DCCC] p-6 sm:p-8 shadow-[0_8px_30px_rgba(42,33,26,0.06)] rounded-2xl relative overflow-hidden transition-all">

                    <!-- Brand Logo & Identity Header -->
                    <div class="flex items-center gap-3.5 mb-6 pb-5 border-b border-[#E4DCCC]">
                        <a href="{{ route('landing') }}" class="w-12 h-12 rounded-xl bg-[#1F1812] border border-[#3A2D22] flex items-center justify-center p-2.5 shadow-xs shrink-0 hover:scale-105 active:scale-95 transition-all duration-300 group" title="Kembali ke Beranda">
                            <img src="{{ asset('images/logo-mark.svg') }}" alt="{{ config('cafe.name') }}" class="h-full w-full object-contain group-hover:scale-110 transition-transform">
                        </a>
                        <div class="min-w-0">
                            <div class="font-bold tracking-tight text-base text-[#1F1812] leading-tight">{{ config('cafe.name') }}</div>
                            <div class="font-mono text-[9px] uppercase tracking-[0.22em] text-[#B5762A] font-semibold mt-0.5">Staff & Cashier Portal</div>
                        </div>
                    </div>

                    <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold">Autentikasi Pegawai</div>
                    <h1 class="mt-1 font-serif text-2xl tracking-tight font-bold text-[#1F1812]">Masuk ke Terminal</h1>

                    @if (session('status'))
                        <div class="mt-3.5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
                            <span class="font-bold">✓</span>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @csrf

                    <!-- Input Email / Username with Focus Glow Animation -->
                    <div class="mt-6">
                        <label for="email" class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold block">
                            Email / Username
                        </label>
                        <input id="email" name="email" type="text" x-model="emailVal" required autofocus
                               placeholder="kasir@kopikita.test atau kasir"
                               class="mt-1.5 w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#B5762A] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#B5762A]/20 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-200 text-[#1F1812]">
                        @error('email')<p class="mt-1.5 text-xs text-[#C4553D]">{{ $message }}</p>@enderror
                    </div>

                    <!-- Input Password with Interactive Show/Hide Eye Toggle -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between">
                            <label for="password" class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] font-bold">
                                Password
                            </label>
                            <span class="text-[10px] font-mono text-[#8A7B66]">Default: password</span>
                        </div>
                        <div class="relative mt-1.5">
                            <input id="password" name="password" :type="showPassword ? 'text' : 'password'" x-model="passVal" required
                                   placeholder="••••••••"
                                   class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#B5762A] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#B5762A]/20 rounded-xl px-3.5 pr-10 py-2.5 text-sm transition-all duration-200 text-[#1F1812]">
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#8A7B66] hover:text-[#1F1812] transition-colors p-1 cursor-pointer"
                                    title="Lihat / Sembunyikan Password">
                                <template x-if="!showPassword">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </template>
                                <template x-if="showPassword">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    </svg>
                                </template>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Quick Helper Fill -->
                    <div class="mt-4 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-xs text-[#7A6A58] cursor-pointer select-none">
                            <input type="checkbox" name="remember" class="rounded accent-[#B5762A] cursor-pointer">
                            <span>Ingat saya</span>
                        </label>

                        <!-- Quick Role Autofill Buttons for fast shift login -->
                        <div class="flex items-center gap-1.5 font-mono text-[10px]">
                            <button type="button" @click="quickFill('kasir')"
                                    class="px-2 py-0.5 rounded-md bg-[#FAF7F2] hover:bg-[#B5762A] hover:text-white border border-[#E8E1D5] text-[#8A7B66] transition-colors cursor-pointer">
                                ⚡ Kasir
                            </button>
                            <button type="button" @click="quickFill('owner')"
                                    class="px-2 py-0.5 rounded-md bg-[#FAF7F2] hover:bg-[#B5762A] hover:text-white border border-[#E8E1D5] text-[#8A7B66] transition-colors cursor-pointer">
                                🔑 Owner
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button with Shine Animation & Spinner State -->
                    <button type="submit"
                            :disabled="isSubmitting"
                            class="mt-6 w-full bg-[#1F1812] hover:bg-[#B5762A] text-white rounded-xl px-6 py-3.5 font-mono text-xs uppercase tracking-[0.2em] font-bold transition-all duration-200 shadow-sm active:scale-[0.98] cursor-pointer group relative overflow-hidden flex items-center justify-center gap-2">
                        <!-- Shimmer Light Reflection -->
                        <span class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/20 to-transparent pointer-events-none"></span>

                        <template x-if="!isSubmitting">
                            <span class="flex items-center gap-1.5">
                                <span>Masuk Terminal</span>
                                <span class="group-hover:translate-x-1 transition-transform duration-200">›</span>
                            </span>
                        </template>
                        <template x-if="isSubmitting">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Memproses...</span>
                            </span>
                        </template>
                    </button>

                    <!-- Link Kembali ke Halaman Utama -->
                    <div class="mt-6 pt-4 border-t border-[#E8E1D5]/70 text-center">
                        <a href="{{ route('landing') }}" class="group font-mono text-xs text-[#8A7B66] hover:text-[#B5762A] transition inline-flex items-center gap-1.5">
                            <span class="group-hover:-translate-x-1 transition-transform duration-200">←</span>
                            <span>Kembali ke Halaman Utama</span>
                        </a>
                    </div>
                </form>

                <!-- Daily Motivation Card (Mobile / Responsive) with Subtle Hover Animation -->
                <div class="mt-4 p-4 rounded-xl bg-white border border-[#E8E1D5] hover:border-[#B5762A]/40 shadow-xs text-[#2A211A] transition-all duration-300">
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[#B5762A] text-xs">✦</span>
                            <span class="font-mono text-[9px] uppercase tracking-[0.2em] text-[#B5762A] font-bold">
                                Motivasi Hari Ini • {{ $today->translatedFormat('d M') }}
                            </span>
                        </div>
                        <span class="font-mono text-[9px] uppercase text-[#8A7B66] font-semibold">
                            {{ $todayQuote['theme'] }}
                        </span>
                    </div>
                    <p class="font-serif italic text-xs text-[#4A3E33] leading-relaxed">
                        “{{ $todayQuote['quote'] }}”
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
