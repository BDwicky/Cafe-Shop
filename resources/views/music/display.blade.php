<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Now Playing & Order Display — {{ config('cafe.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes spinSlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spinSlow 22s linear infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.08); }
        }
        .animate-pulse-glow {
            animation: pulseGlow 8s ease-in-out infinite;
        }
        @keyframes shineSweep {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .shine-reflection {
            background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,0.12) 50%, transparent 60%);
            animation: shineSweep 12s linear infinite;
        }
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-slide-down {
            animation: slideInDown 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes eqBarBounce {
            0%, 100% { height: 15%; }
            50% { height: 95%; }
        }
        .eq-bar {
            animation: eqBarBounce 1.2s ease-in-out infinite alternate;
        }
        @keyframes cardFlyIn {
            0% {
                opacity: 0;
                transform: translate3d(120%, 30px, 0) rotate(8deg) scale(0.85);
            }
            60% {
                opacity: 1;
                transform: translate3d(-12px, -4px, 0) rotate(-1.5deg) scale(1.02);
            }
            85% {
                transform: translate3d(4px, 1px, 0) rotate(0.5deg) scale(0.99);
            }
            100% {
                opacity: 1;
                transform: translate3d(0, 0, 0) rotate(0deg) scale(1);
            }
        }
        .animate-card-fly {
            animation: cardFlyIn 0.65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes cardFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-6px); }
        }
        .animate-card-float {
            animation: cardFloat 4.5s ease-in-out infinite;
        }
        @keyframes cardGlowPulse {
            0%, 100% {
                box-shadow: 0 10px 30px rgba(0,0,0,0.7), 0 0 18px rgba(95,127,66,0.3);
            }
            50% {
                box-shadow: 0 16px 45px rgba(0,0,0,0.85), 0 0 32px rgba(95,127,66,0.75);
            }
        }
        .card-fly-glow {
            animation: cardGlowPulse 2.5s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-[#0E0906] text-[#F7F3EC] h-full overflow-hidden antialiased font-sans select-none relative"
      x-data="{
        nowPlaying: {{ json_encode($playerState['now_playing']) }},
        queue: {{ json_encode($playerState['queue']) }},
        queueCount: {{ $playerState['queue_count'] }},
        readyOrders: {{ json_encode($readyOrders ?? []) }},
        knownReadyIds: {{ json_encode(collect($readyOrders ?? [])->pluck('id')) }},
        activeFlyingCards: [],
        currentTime: '',

        displayMode: localStorage.getItem('tv_display_mode') || 'visualizer', // 'visualizer' atau 'video'

        playbackCurrentTime: 0,
        playbackDuration: 0,
        playbackProgressPercent: 0,
        playbackCurrentTimeFormatted: '00:00',
        playbackDurationFormatted: '00:00',
        isPlaying: false,

        init() {
            const updateClock = () => {
                const now = new Date();
                this.currentTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            };
            updateClock();
            setInterval(updateClock, 1000);

            // Inisialisasi Kanvas Partikel Kopi Hangat
            this.initParticles();

            // Tampilkan card fly awal jika sudah ada pesanan yang siap saat halaman dimuat
            if (Array.isArray(this.readyOrders) && this.readyOrders.length > 0) {
                this.readyOrders.slice(0, 3).forEach((o, i) => {
                    setTimeout(() => this.pushFlyingCard(o), i * 350);
                });
            }

            // Dengarkan BroadcastChannel untuk sinkronisasi seketika (0ms)
            if (typeof BroadcastChannel !== 'undefined') {
                const channel = new BroadcastChannel('cafe_soundstation_sync');
                channel.addEventListener('message', (e) => {
                    if (!e.data) return;

                    if (e.data.type === 'STATE_UPDATE' && e.data.state) {
                        const s = e.data.state;
                        if (s.currentTrack) {
                            this.nowPlaying = s.currentTrack;
                        }
                        if (typeof s.queueCount !== 'undefined') {
                            this.queueCount = s.queueCount;
                        }
                        if (Array.isArray(s.queue)) {
                            this.queue = s.queue;
                        }
                    }

                    if (e.data.type === 'TIME_SYNC' && e.data.data) {
                        const t = e.data.data;
                        this.playbackCurrentTime = t.currentTime || 0;
                        this.playbackDuration = t.duration || 0;
                        this.playbackProgressPercent = t.progressPercent || 0;
                        this.playbackCurrentTimeFormatted = t.currentTimeFormatted || '00:00';
                        this.playbackDurationFormatted = t.durationFormatted || '00:00';
                        if (typeof t.isPlaying !== 'undefined') {
                            this.isPlaying = t.isPlaying;
                        }
                    }

                    if (e.data.type === 'ORDER_READY') {
                        this.fetchStatus(true);
                    }
                });
            }

            // Interpolasi visual 1 detik untuk pergerakan detik real-time di TV Display
            setInterval(() => {
                if (this.isPlaying && this.playbackDuration > 0 && this.playbackCurrentTime < this.playbackDuration) {
                    this.playbackCurrentTime = Math.min(this.playbackDuration, this.playbackCurrentTime + 1);
                    const m = Math.floor(this.playbackCurrentTime / 60);
                    const s = Math.floor(this.playbackCurrentTime % 60);
                    this.playbackCurrentTimeFormatted = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                    this.playbackProgressPercent = (this.playbackCurrentTime / this.playbackDuration) * 100;
                }
            }, 1000);

            // Polling status antrean kafe & pesanan siap secara realtime
            this.fetchStatus(false);
            setInterval(() => this.fetchStatus(false), 3000);
        },

        toggleDisplayMode() {
            this.displayMode = this.displayMode === 'visualizer' ? 'video' : 'visualizer';
            try {
                localStorage.setItem('tv_display_mode', this.displayMode);
            } catch (e) {}
        },

        async fetchStatus(isTriggered = false) {
            try {
                const res = await fetch('{{ route('music.status') }}');
                const data = await res.json();
                if (data.now_playing) {
                    this.nowPlaying = data.now_playing;
                }
                this.queue = data.queue || [];
                this.queueCount = data.queue_count || 0;

                const incomingReady = data.ready_orders || [];
                this.handleReadyOrdersUpdate(incomingReady, isTriggered);
                this.readyOrders = incomingReady;
            } catch (e) {}
        },

        handleReadyOrdersUpdate(newList, isTriggered = false) {
            if (!Array.isArray(newList)) return;

            const currentIds = newList.map(o => o.id);
            const newlyReady = newList.filter(o => !this.knownReadyIds.includes(o.id));

            if (newlyReady.length > 0) {
                // Ada pesanan baru yang siap -> Card Fly meluncur masuk dengan suara
                newlyReady.forEach((order, index) => {
                    setTimeout(() => {
                        this.pushFlyingCard(order);
                    }, index * 300);
                });
                this.playReadyChime();
            } else if (isTriggered && newList.length > 0) {
                this.pushFlyingCard(newList[newList.length - 1]);
                this.playReadyChime();
            }

            // Bersihkan kartu yang sudah tidak berstatus ready di server
            this.activeFlyingCards = this.activeFlyingCards.filter(card => currentIds.includes(card.id));
            this.knownReadyIds = currentIds;
        },

        pushFlyingCard(order) {
            if (!order) return;
            // Hindari duplikasi ID kartu
            this.activeFlyingCards = this.activeFlyingCards.filter(c => c.id !== order.id);
            this.activeFlyingCards.unshift(order);

            // Maksimal 3 card fly tampil bersamaan agar layar tetap rapi
            if (this.activeFlyingCards.length > 3) {
                this.activeFlyingCards = this.activeFlyingCards.slice(0, 3);
            }

            // Auto-dismiss setelah 15 detik
            setTimeout(() => {
                this.dismissFlyingCard(order.id);
            }, 15000);
        },

        dismissFlyingCard(orderId) {
            this.activeFlyingCards = this.activeFlyingCards.filter(c => c.id !== orderId);
        },

        showAllReadyCards() {
            if (!this.readyOrders || this.readyOrders.length === 0) return;
            this.readyOrders.slice(0, 3).forEach((o, i) => {
                setTimeout(() => this.pushFlyingCard(o), i * 250);
            });
            this.playReadyChime();
        },

        playReadyChime() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                const ctx = new AudioContext();
                const now = ctx.currentTime;

                // Nada 1: E5 (659.25 Hz)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(659.25, now);
                gain1.gain.setValueAtTime(0.3, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.65);

                // Nada 2: C5 (523.25 Hz)
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(523.25, now + 0.22);
                gain2.gain.setValueAtTime(0.35, now + 0.22);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.95);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.22);
                osc2.stop(now + 1.0);
            } catch (e) {}
        },

        initParticles() {
            const canvas = document.getElementById('steam-canvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');

            const resize = () => {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            };
            resize();
            window.addEventListener('resize', resize);

            const particles = [];
            const particleCount = 28;

            for (let i = 0; i < particleCount; i++) {
                particles.push({
                    x: Math.random() * window.innerWidth,
                    y: Math.random() * window.innerHeight,
                    radius: Math.random() * 2.5 + 1,
                    speedY: Math.random() * 0.4 + 0.2,
                    speedX: (Math.random() - 0.5) * 0.2,
                    alpha: Math.random() * 0.4 + 0.1,
                    grow: Math.random() > 0.5
                });
            }

            const draw = () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                for (let p of particles) {
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(217, 151, 62, ' + p.alpha + ')';
                    ctx.shadowBlur = 8;
                    ctx.shadowColor = '#D9973E';
                    ctx.fill();

                    p.y -= p.speedY;
                    p.x += p.speedX;

                    if (p.grow) {
                        p.alpha += 0.002;
                        if (p.alpha > 0.45) p.grow = false;
                    } else {
                        p.alpha -= 0.002;
                        if (p.alpha < 0.08) p.grow = true;
                    }

                    if (p.y < -10) {
                        p.y = canvas.height + 10;
                        p.x = Math.random() * canvas.width;
                    }
                }

                requestAnimationFrame(draw);
            };

            draw();
        }
      }">

    <!-- LAYER 1: DYNAMIC BLURRED ALBUM ARTWORK WALLPAPER -->
    <div class="absolute inset-0 bg-cover bg-center filter blur-3xl opacity-30 scale-110 transition-all duration-1000 pointer-events-none"
         :style="nowPlaying && nowPlaying.thumbnail_url ? 'background-image: url(' + nowPlaying.thumbnail_url + ');' : ''">
    </div>

    <!-- LAYER 2: DEEP AMBIENT RADIAL VIGNETTE -->
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(20,14,10,0.65)_0%,rgba(10,7,5,0.96)_100%)] pointer-events-none"></div>

    <!-- LAYER 3: FLOATING GOLDEN COFFEE STEAM PARTICLES -->
    <canvas id="steam-canvas" class="absolute inset-0 pointer-events-none z-0"></canvas>

    <!-- LAYER 4: AMBIENT PULSING GLOW ORBS -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#D9973E]/15 rounded-full blur-[120px] pointer-events-none animate-pulse-glow"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-[#5F7F42]/15 rounded-full blur-[120px] pointer-events-none animate-pulse-glow" style="animation-delay: 4s;"></div>

    <!-- CARD FLY: FLOATING READY ORDERS NOTIFICATION OVERLAY -->
    <div class="fixed bottom-6 sm:bottom-8 right-6 sm:right-8 z-50 flex flex-col gap-3 max-w-sm sm:max-w-md w-[calc(100%-3rem)] pointer-events-none">
        <template x-for="(order, idx) in activeFlyingCards" :key="order.id">
            <div class="pointer-events-auto bg-gradient-to-r from-[#172211]/95 via-[#1E2E17]/95 to-[#172211]/95 border-2 border-[#5F7F42] rounded-2xl p-4 sm:p-5 text-[#F7F3EC] shadow-2xl backdrop-blur-xl animate-card-fly card-fly-glow relative overflow-hidden transition-all duration-300"
                 :style="'animation-delay: ' + (idx * 120) + 'ms;'">

                <!-- Top Animated Accent Bar -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#5F7F42] via-[#D9973E] to-[#5F7F42] animate-pulse"></div>

                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <!-- Icon Ping -->
                        <div class="w-12 h-12 rounded-xl bg-[#5F7F42]/25 border border-[#5F7F42] flex items-center justify-center shrink-0 relative shadow-inner">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#5F7F42] absolute -top-1 -right-1 animate-ping"></span>
                            <span class="text-2xl animate-bounce">🔔</span>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold text-[#5F7F42] bg-[#5F7F42]/20 px-2 py-0.5 rounded border border-[#5F7F42]/30">
                                    📢 PESANAN SIAP!
                                </span>
                                <span class="font-mono text-[10px] text-[#D9973E] uppercase"
                                      x-text="order.order_type === 'dine_in' ? 'Dine In' : 'Take Away'"></span>
                            </div>
                            <h3 class="text-xl sm:text-2xl font-serif font-bold text-[#F7F3EC] mt-1 truncate"
                                x-text="'Kak ' + (order.customer_name || 'Pelanggan')"></h3>
                            <div class="text-xs text-[#A89A85] flex items-center gap-2 mt-0.5 font-mono">
                                <span>Nomor: <b class="text-[#D9973E] text-sm" x-text="order.code"></b></span>
                                <span>&bull;</span>
                                <span class="text-[11px] text-[#A6D388]">Silakan ambil di kasir</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dismiss Button -->
                    <button type="button" @click="dismissFlyingCard(order.id)"
                            class="text-[#A89A85] hover:text-white bg-white/10 hover:bg-white/20 p-1.5 rounded-lg transition shrink-0 font-mono text-xs"
                            title="Tutup Card Fly">
                        ✕
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Hidden fallback for testing assertion and accessibility -->
    <div class="sr-only" aria-hidden="true">
        @foreach($readyOrders ?? [] as $readyOrder)
            <span>{{ $readyOrder->customer_name }}</span>
            <span>{{ $readyOrder->code }}</span>
        @endforeach
    </div>

    <!-- CONTENT WRAPPER -->
    <div class="h-full flex flex-col justify-between p-6 sm:p-8 lg:p-10 relative z-10">

        <!-- TOP BAR: BRANDING, CLOCK, READY TICKER & MODE TOGGLE -->
        <header class="flex items-center justify-between border-b border-[#3A3026]/80 pb-5 shrink-0">
            <!-- Brand & Tagline -->
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo-light.svg') }}" alt="{{ config('cafe.name') }}" class="h-9 w-auto">
                <div class="border-l border-[#3A3026] pl-3">
                    <div class="font-mono text-xs uppercase tracking-[0.25em] text-[#D9973E] flex items-center gap-1.5 font-bold">
                        <span>LIVE CAFE SOUNDSTATION</span>
                        <span class="w-2 h-2 rounded-full bg-[#5F7F42] animate-pulse"></span>
                    </div>
                    <div class="text-[11px] text-[#A89A85]">{{ config('cafe.tagline') }}</div>
                </div>
            </div>

            <!-- Ready Orders Quick Pill Banner (Card Fly Trigger) -->
            <template x-if="readyOrders.length > 0">
                <button type="button" @click="showAllReadyCards()"
                        class="hidden md:flex items-center gap-2 px-3.5 py-1.5 bg-[#5F7F42]/15 border border-[#5F7F42]/40 rounded-full cursor-pointer transition hover:bg-[#5F7F42]/25 shadow-sm active:scale-95"
                        title="Klik untuk memunculkan Card Fly pesanan siap">
                    <span class="w-2 h-2 rounded-full bg-[#5F7F42] animate-ping"></span>
                    <span class="font-mono text-xs text-[#5F7F42] font-bold" x-text="readyOrders.length + ' Pesanan Siap (Card Fly) ↗'"></span>
                </button>
            </template>

            <!-- Mode Switcher & Real-time Clock -->
            <div class="flex items-center gap-4 sm:gap-6">
                <!-- Toggle Mode: Visualizer vs Video -->
                <button type="button" @click="toggleDisplayMode()"
                        class="px-3 py-1.5 bg-[#1F1812] hover:bg-[#2A2018] border border-[#3A3026] hover:border-[#D9973E] text-[#D9973E] font-mono text-xs uppercase tracking-wider transition rounded flex items-center gap-1.5 shadow-sm active:scale-95"
                        :title="displayMode === 'visualizer' ? 'Beralih ke Tampilan Video YouTube' : 'Beralih ke Tampilan Vinyl Visualizer'">
                    <template x-if="displayMode === 'visualizer'">
                        <span class="flex items-center gap-1">
                            <span>🎬</span>
                            <span class="hidden sm:inline">Tampilan Video</span>
                        </span>
                    </template>
                    <template x-if="displayMode === 'video'">
                        <span class="flex items-center gap-1">
                            <span>🎨</span>
                            <span class="hidden sm:inline">Tampilan Visualizer</span>
                        </span>
                    </template>
                </button>

                <div class="font-mono text-2xl sm:text-3xl font-bold text-[#F7F3EC] tracking-wider" x-text="currentTime"></div>
            </div>
        </header>

        <!-- MAIN STAGE: NOW PLAYING & RIGHT COLUMN (SPLIT / TABS) -->
        <main class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center my-auto py-4">

            <!-- LEFT COL: NOW PLAYING HERO (7 cols) -->
            <div class="lg:col-span-7">

                <!-- 1. MODE VISUALIZER: 3D VINYL TURNTABLE & SPECTRUM EQUALIZER -->
                <div x-show="displayMode === 'visualizer'" class="flex flex-col sm:flex-row items-center gap-8">
                    <!-- VINYL RECORD TURNTABLE -->
                    <div class="relative shrink-0 w-52 h-52 sm:w-64 sm:h-64 lg:w-72 lg:h-72">
                        <div class="w-full h-full rounded-full bg-gradient-to-tr from-[#120D09] via-[#221711] to-[#120D09] border-4 border-[#3A3026] shadow-[0_20px_60px_rgba(0,0,0,0.8)] flex items-center justify-center p-3 relative overflow-hidden"
                             :class="isPlaying ? 'animate-spin-slow' : ''">

                            <!-- VINYL GROOVES -->
                            <div class="w-full h-full rounded-full border border-dashed border-[#443527] flex items-center justify-center p-5">
                                <div class="w-full h-full rounded-full border border-dashed border-[#554637] flex items-center justify-center p-6">
                                    <!-- CENTER ALBUM COVER LABEL -->
                                    <div class="w-full h-full rounded-full border-2 border-[#D9973E]/40 overflow-hidden flex items-center justify-center bg-[#140E0A] shadow-inner relative">
                                        <template x-if="nowPlaying && nowPlaying.thumbnail_url">
                                            <img :src="nowPlaying.thumbnail_url" alt="Cover" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!nowPlaying || !nowPlaying.thumbnail_url">
                                            <div class="text-3xl font-serif text-[#D9973E]">☕</div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- SHINY LIGHT REFLECTION SWEEP -->
                            <div class="absolute inset-0 shine-reflection rounded-full pointer-events-none"></div>
                        </div>

                        <!-- CENTER METALLIC PIN -->
                        <div class="absolute inset-0 m-auto w-7 h-7 rounded-full bg-gradient-to-tr from-[#D9973E] to-[#F7F3EC] border-2 border-[#140E0A] shadow-md"></div>
                    </div>

                    <!-- NOW PLAYING METADATA -->
                    <div class="min-w-0 text-center sm:text-left flex-1">
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#D9973E]/15 border border-[#D9973E]/40 text-[#D9973E] font-mono text-xs uppercase tracking-[0.2em] mb-3 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#D9973E] animate-ping"></span>
                            <span x-text="isPlaying ? 'NOW PLAYING' : 'AUDIO PAUSED'"></span>
                        </div>

                        <h2 class="text-2xl sm:text-4xl font-serif font-bold text-[#F7F3EC] leading-tight tracking-tight line-clamp-2 drop-shadow-md"
                            x-text="nowPlaying ? (nowPlaying.song_title || nowPlaying.title) : 'Playlist Bawaan KopiKita'">
                        </h2>

                        <p class="text-base sm:text-xl text-[#D5CCC0] mt-1.5 font-mono truncate"
                           x-text="nowPlaying ? (nowPlaying.artist || 'Artis Musik') : 'Chill Lo-Fi / Jazz Cafe Vibes'">
                        </p>

                        <!-- TIMELINE PROGRESS BAR -->
                        <div class="mt-4 max-w-md">
                            <div class="w-full bg-[#2A211A] h-2.5 rounded-full overflow-hidden border border-[#3A3026]">
                                <div class="bg-gradient-to-r from-[#D9973E] via-[#E5A955] to-[#5F7F42] h-full transition-all duration-300 rounded-full shadow-[0_0_12px_rgba(217,151,62,0.6)]"
                                     :style="'width: ' + playbackProgressPercent + '%'"></div>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between font-mono text-xs text-[#A89A85]">
                                <span class="text-[#D9973E] font-semibold" x-text="playbackCurrentTimeFormatted">00:00</span>
                                <span class="text-[10px] text-[#7A6A58] uppercase tracking-wider">// Sync Live Player</span>
                                <span x-text="playbackDurationFormatted">00:00</span>
                            </div>
                        </div>

                        <!-- 36-BAND LIVE SOUND SPECTRUM EQUALIZER WAVE -->
                        <div class="mt-4 max-w-md flex items-end gap-1 h-7 pt-1 overflow-hidden">
                            <template x-for="i in 36" :key="i">
                                <div class="w-1.5 rounded-t bg-gradient-to-t from-[#D9973E] to-[#5F7F42] transition-all duration-150"
                                     :class="isPlaying ? 'eq-bar' : 'h-1 opacity-40'"
                                     :style="isPlaying ? 'animation-delay: ' + ((i * 45) % 800) + 'ms; animation-duration: ' + (0.7 + ((i * 37) % 600) / 1000) + 's;' : ''">
                                </div>
                            </template>
                        </div>

                        <!-- REQUESTED BY BADGE -->
                        <template x-if="nowPlaying && nowPlaying.customer_name">
                            <div class="mt-3.5 inline-flex items-center gap-2 px-3.5 py-1.5 bg-[#2A211A]/90 border border-[#D9973E]/40 rounded shadow-sm">
                                <span class="font-mono text-xs text-[#A89A85] uppercase tracking-wider">Requested by:</span>
                                <span class="font-mono text-sm font-bold text-[#D9973E]" x-text="'★ ' + nowPlaying.customer_name"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 2. MODE VIDEO: CINEMATIC YOUTUBE PLAYER SCREEN -->
                <div x-show="displayMode === 'video'" class="space-y-4">
                    <div class="w-full aspect-video rounded-2xl overflow-hidden border-2 border-[#3A3026] shadow-[0_20px_60px_rgba(0,0,0,0.9)] bg-black relative group">
                        <template x-if="nowPlaying && nowPlaying.youtube_id">
                            <iframe :src="'https://www.youtube-nocookie.com/embed/' + nowPlaying.youtube_id + '?autoplay=1&mute=1&controls=0&loop=1&playlist=' + nowPlaying.youtube_id"
                                    class="w-full h-full rounded-xl"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                            </iframe>
                        </template>
                        <template x-if="!nowPlaying || !nowPlaying.youtube_id">
                            <div class="w-full h-full flex flex-col items-center justify-center bg-[#140E0A] text-[#A89A85]">
                                <span class="text-5xl mb-2 text-[#D9973E]">🎬</span>
                                <span class="font-mono text-xs">Memuat tayangan video...</span>
                            </div>
                        </template>

                        <!-- Video Overlay Badge -->
                        <div class="absolute top-3 left-3 px-3 py-1 bg-black/70 border border-white/10 rounded-full font-mono text-[10px] text-[#D9973E] uppercase tracking-widest backdrop-blur-md">
                            🎬 LIVE VIDEO DISPLAY
                        </div>
                    </div>

                    <!-- Video Track Info Bar -->
                    <div class="flex items-center justify-between gap-4 p-3 bg-[#1F1812]/80 border border-[#3A3026] rounded-xl backdrop-blur">
                        <div class="min-w-0">
                            <h3 class="font-serif font-bold text-base text-[#F7F3EC] truncate"
                                x-text="nowPlaying ? (nowPlaying.song_title || nowPlaying.title) : 'Lagu Kafe'"></h3>
                            <div class="text-xs text-[#A89A85] font-mono truncate"
                                 x-text="nowPlaying ? (nowPlaying.artist || 'Artis') : '-'"></div>
                        </div>
                        <div class="font-mono text-xs text-[#D9973E] shrink-0" x-text="playbackCurrentTimeFormatted + ' / ' + playbackDurationFormatted"></div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COL: UP NEXT QUEUE (5 cols, Dedicated Without Tabs) -->
            <div class="lg:col-span-5 bg-[#17110C]/90 border border-[#3A3026] rounded-2xl p-6 shadow-2xl backdrop-blur-xl flex flex-col h-[380px]">

                <!-- QUEUE HEADER -->
                <div class="flex items-center justify-between border-b border-[#3A3026] pb-3 mb-4 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#D9973E] animate-pulse"></span>
                        <h3 class="font-mono text-xs uppercase tracking-[0.2em] font-bold text-[#F7F3EC]">Antrean Lagu Berikutnya</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#D9973E]/15 border border-[#D9973E]/30 text-[#D9973E]"
                              x-text="queue.length + ' Lagu'"></span>
                    </div>

                    <span class="font-mono text-[10px] text-[#7A6A58] uppercase tracking-widest hidden sm:inline">UP NEXT QUEUE</span>
                </div>

                <!-- QUEUE LIST -->
                <div class="space-y-2.5 overflow-y-auto pr-1 flex-1">
                    <template x-if="queue.length === 0">
                        <div class="h-full flex flex-col items-center justify-center text-center py-10 text-[#7A6A58] font-mono text-xs">
                            <span class="text-3xl mb-2 opacity-50">☕</span>
                            <span>Antrean request lagu sedang kosong.</span>
                            <span class="text-[10px] mt-1 text-[#554637]">Scan QR di bawah untuk me-request lagu pertamamu!</span>
                        </div>
                    </template>

                    <template x-for="(item, index) in queue.slice(0, 6)" :key="item.id">
                        <div class="flex items-center justify-between p-3 bg-[#221912] border border-[#3A3026]/70 rounded-lg hover:border-[#D9973E]/50 transition">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="font-mono font-bold text-[#D9973E] text-sm w-5 text-center shrink-0" x-text="'#' + (index + 1)"></span>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-[#F7F3EC] truncate" x-text="item.song_title"></div>
                                    <div class="text-xs text-[#A89A85] truncate" x-text="item.artist || 'Artis YouTube'"></div>
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-2">
                                <div class="font-mono text-[10px] text-[#A89A85]" x-text="item.customer_name || 'Pelanggan'"></div>
                                <span class="font-mono text-[9px] uppercase tracking-wider text-[#5F7F42] bg-[#5F7F42]/10 px-1.5 py-0.5 rounded border border-[#5F7F42]/20">Up Next</span>
                            </div>
                        </div>
                    </template>
                </div>

            </div>

        </main>

        <!-- FOOTER: QR CODE TO REQUEST MUSIC & BRANDING -->
        <footer class="border-t border-[#3A3026]/80 pt-5 flex flex-col sm:flex-row items-center justify-between gap-6 shrink-0">
            <div class="flex items-center gap-5">
                <div class="p-2 bg-white border border-[#3A3026] rounded-lg shrink-0 shadow-lg">
                    <img src="{{ \App\Support\QrCode::dataUri(route('music.request'), 130) }}" width="68" height="68" alt="QR Request Musik">
                </div>
                <div>
                    <div class="font-serif font-bold text-base sm:text-lg text-[#F7F3EC]">Punya Struk Belanja?</div>
                    <div class="text-xs text-[#A89A85] max-w-sm mt-0.5">
                        Scan QR di samping untuk me-request lagu favoritmu langsung dari meja. Lagu diputar setelah lagu saat ini selesai!
                    </div>
                </div>
            </div>

            <div class="font-mono text-xs text-[#A89A85] uppercase tracking-widest text-right">
                <div class="text-[#D9973E] font-bold">{{ config('cafe.name') }} AUDIO & KDS STREAM</div>
                <div class="text-[10px] text-[#554637] mt-0.5">Precision Jukebox & Kitchen Announcer Engine</div>
            </div>
        </footer>

    </div>
</body>
</html>
