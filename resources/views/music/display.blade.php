<!DOCTYPE html>
<html lang="id" class="h-full w-full overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0E0906">
    <title>Now Playing & Order Display — {{ config('cafe.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            min-width: 100vw !important;
            min-height: 100vh !important;
            max-width: 100vw !important;
            max-height: 100vh !important;
            overflow: hidden !important;
            box-sizing: border-box !important;
            background-color: #0E0906;
        }
        *, *::before, *::after {
            box-sizing: border-box;
        }
        #steam-canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }
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
        .no-scrollbar::-webkit-scrollbar {
            display: none !important;
        }
        .no-scrollbar {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
        #tv-player-wrap iframe, #tv-yt-player {
            width: 100% !important;
            height: 100% !important;
            border: none !important;
            pointer-events: none !important;
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
<body class="bg-[#0E0906] text-[#F7F3EC] w-screen h-screen min-w-full min-h-screen overflow-hidden antialiased font-sans select-none relative m-0 p-0"
      x-data="{
        nowPlaying: {{ json_encode($playerState['now_playing']) }},
        queue: {{ json_encode($playerState['queue']) }},
        queueCount: {{ $playerState['queue_count'] }},
        readyOrders: {{ json_encode($readyOrders ?? []) }},
        knownReadyIds: {{ json_encode(collect($readyOrders ?? [])->pluck('id')) }},
        activeFlyingCards: [],
        currentTime: '',

        displayMode: localStorage.getItem('tv_display_mode') || 'visualizer', // 'visualizer' atau 'video'
        isFullscreen: false,
        tvSoundEnabled: localStorage.getItem('tv_sound_enabled') === 'true', // Default false (hening untuk mencegah dobel announcer)

        playbackCurrentTime: 0,
        playbackDuration: 0,
        playbackProgressPercent: 0,
        playbackCurrentTimeFormatted: '00:00',
        playbackDurationFormatted: '00:00',
        isPlaying: false,

        // YOUTUBE TV VIDEO PLAYER INSTANCE (100% SYNC DENGAN AUDIO KASIR)
        tvPlayer: null,
        tvPlayerReady: false,
        currentTvVideoId: '',
        _isManualPausing: false,

        init() {
            this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
            const updateFs = () => {
                this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
            };
            document.addEventListener('fullscreenchange', updateFs);
            document.addEventListener('webkitfullscreenchange', updateFs);
            document.addEventListener('msfullscreenchange', updateFs);

            const updateClock = () => {
                const now = new Date();
                this.currentTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            };
            updateClock();
            setInterval(updateClock, 1000);

            // Inisialisasi Kanvas Partikel Kopi Hangat
            this.initParticles();

            // Dengarkan sinkronisasi BroadcastChannel dari Sound Station utama
            if (typeof BroadcastChannel !== 'undefined') {
                const bc = new BroadcastChannel('cafe_soundstation_sync');
                bc.onmessage = (e) => {
                    const data = e.data;
                    if (!data) return;

                    if (data.type === 'TIME_SYNC' && data.data) {
                        const t = data.data;
                        const dur = Number(t.duration || 0) > 0 ? Number(t.duration) : (this.nowPlaying?.duration_seconds || this.playbackDuration);
                        this.playbackDuration = dur;
                        this.playbackCurrentTime = Number(t.currentTime || 0);
                        this.playbackProgressPercent = Number(t.progressPercent || 0);
                        this.playbackCurrentTimeFormatted = t.currentTimeFormatted || this.formatSeconds(this.playbackCurrentTime);
                        this.playbackDurationFormatted = t.durationFormatted || this.formatSeconds(this.playbackDuration);
                        if (typeof t.isPlaying !== 'undefined') {
                            this.isPlaying = !!t.isPlaying;
                        }
                        this.syncTvPlayerState();
                    } else if (data.type === 'STATE_UPDATE' && data.state) {
                        const s = data.state;
                        if (s.currentTrack) this.nowPlaying = s.currentTrack;
                        if (typeof s.isPlaying !== 'undefined') this.isPlaying = !!s.isPlaying;
                        if (typeof s.queueCount !== 'undefined') this.queueCount = s.queueCount;
                        if (Array.isArray(s.queue)) this.queue = s.queue;
                        this.syncTvPlayerState();
                    } else if (data.type === 'SYNC_STATE') {
                        this.applySyncData(data);
                        this.syncTvPlayerState();
                    } else if (data.type === 'TRACK_CHANGED') {
                        this.nowPlaying = data.track;
                        this.isPlaying = true;
                        this.syncTvPlayerState();
                    } else if (data.type === 'QUEUE_UPDATED') {
                        this.fetchStatus();
                    } else if (data.type === 'ORDER_READY') {
                        this.triggerNewReadyOrderNotification(data.orderId, data.isRecall);
                    }
                };
            }

            // Dengarkan event window kustom
            window.addEventListener('soundstation:sync', (e) => {
                this.applySyncData(e.detail);
                this.syncTvPlayerState();
            });
            window.addEventListener('soundstation:timesync', (e) => {
                if (e.detail) {
                    const t = e.detail;
                    const dur = Number(t.duration || 0) > 0 ? Number(t.duration) : (this.nowPlaying?.duration_seconds || this.playbackDuration);
                    this.playbackDuration = dur;
                    this.playbackCurrentTime = Number(t.currentTime || 0);
                    this.playbackProgressPercent = Number(t.progressPercent || 0);
                    this.playbackCurrentTimeFormatted = t.currentTimeFormatted || this.formatSeconds(this.playbackCurrentTime);
                    this.playbackDurationFormatted = t.durationFormatted || this.formatSeconds(this.playbackDuration);
                    if (typeof t.isPlaying !== 'undefined') this.isPlaying = !!t.isPlaying;
                    this.syncTvPlayerState();
                }
            });

            // Polling status lagu & pesanan siap setiap 3 detik
            this.fetchStatus();
            setInterval(() => this.fetchStatus(), 3000);

            // Muat YouTube Iframe API untuk sinkronisasi Live Video Mode dengan Audio Kasir
            this.loadYouTubeApi();

            // Progress bar interpolator (60fps halus)
            setInterval(() => {
                if (this.isPlaying && this.playbackDuration > 0) {
                    this.playbackCurrentTime = Math.min(this.playbackCurrentTime + 0.1, this.playbackDuration);
                    this.playbackProgressPercent = Math.min(100, (this.playbackCurrentTime / this.playbackDuration) * 100);
                    this.playbackCurrentTimeFormatted = this.formatSeconds(Math.floor(this.playbackCurrentTime));
                }
            }, 100);

            // Sinkronisasi Video TV dengan status playback audio Kasir setiap 1 detik
            setInterval(() => {
                this.syncTvPlayerState();
            }, 1000);
        },

        loadYouTubeApi() {
            if (!window.YT) {
                if (!document.querySelector('script[src*="youtube.com/iframe_api"]')) {
                    const tag = document.createElement('script');
                    tag.src = 'https://www.youtube.com/iframe_api';
                    const firstScriptTag = document.getElementsByTagName('script')[0];
                    if (firstScriptTag && firstScriptTag.parentNode) {
                        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
                    } else {
                        document.head.appendChild(tag);
                    }
                }
            }
            if (window.YT && window.YT.Player) {
                this.initTvPlayer();
            } else {
                const prevHandler = window.onYouTubeIframeAPIReady;
                window.onYouTubeIframeAPIReady = () => {
                    if (typeof prevHandler === 'function') {
                        try { prevHandler(); } catch (e) {}
                    }
                    this.initTvPlayer();
                };
            }
        },

        initTvPlayer() {
            if (this.tvPlayer) return;
            const targetEl = document.getElementById('tv-yt-player');
            if (!targetEl) return;

            const initialVideoId = this.nowPlaying ? this.nowPlaying.youtube_id : '';
            this.currentTvVideoId = initialVideoId;

            try {
                this.tvPlayer = new YT.Player('tv-yt-player', {
                    height: '100%',
                    width: '100%',
                    videoId: initialVideoId || undefined,
                    playerVars: {
                        autoplay: 1,
                        controls: 0,
                        disablekb: 1,
                        fs: 0,
                        modestbranding: 1,
                        rel: 0,
                        playsinline: 1,
                        mute: 1, // TV selalu hening secara default agar audio utama tetap diputar oleh tab Kasir
                        origin: window.location.origin
                    },
                    events: {
                        onReady: (event) => {
                            this.tvPlayerReady = true;
                            event.target.mute();

                            if (this.nowPlaying && this.nowPlaying.youtube_id) {
                                const startSec = Math.max(0, Math.floor(this.playbackCurrentTime || 0));
                                if (startSec > 0 && startSec < 86400) {
                                    event.target.loadVideoById({
                                        videoId: this.nowPlaying.youtube_id,
                                        startSeconds: startSec
                                    });
                                } else {
                                    event.target.loadVideoById(this.nowPlaying.youtube_id);
                                }
                                if (this.isPlaying) {
                                    event.target.playVideo();
                                } else {
                                    event.target.pauseVideo();
                                }
                            }
                        },
                        onStateChange: (event) => {
                            // Jika video di-pause YouTube atau buffer tetapi musik kasir sedang jalan,
                            // sinkronkan kembali tanpa desync
                            if (event.data === YT.PlayerState.PAUSED && this.isPlaying && !this._isManualPausing) {
                                setTimeout(() => {
                                    if (this.isPlaying && this.tvPlayer && typeof this.tvPlayer.playVideo === 'function') {
                                        this.syncTvPlayerState();
                                    }
                                }, 600);
                            }
                        }
                    }
                });
            } catch (e) {
                console.warn('[TV YouTube Init Error]', e);
            }
        },

        syncTvPlayerState() {
            if (!this.tvPlayer || !this.tvPlayerReady) {
                if (!this.tvPlayer) this.loadYouTubeApi();
                return;
            }
            if (!this.nowPlaying || !this.nowPlaying.youtube_id) return;

            try {
                // 1. Sinkronisasi ganti lagu jika videoId berbeda
                if (this.currentTvVideoId !== this.nowPlaying.youtube_id) {
                    this.currentTvVideoId = this.nowPlaying.youtube_id;
                    const startSec = Math.max(0, Math.floor(this.playbackCurrentTime || 0));
                    if (startSec > 0 && startSec < 86400) {
                        this.tvPlayer.loadVideoById({
                            videoId: this.nowPlaying.youtube_id,
                            startSeconds: startSec
                        });
                    } else {
                        this.tvPlayer.loadVideoById(this.nowPlaying.youtube_id);
                    }
                }

                // 2. Sinkronisasi Play / Pause state secara real-time
                const state = (typeof this.tvPlayer.getPlayerState === 'function') ? this.tvPlayer.getPlayerState() : -1;
                if (this.isPlaying) {
                    if (state === YT.PlayerState.PAUSED || state === YT.PlayerState.CUED) {
                        this.tvPlayer.playVideo();
                    }
                } else {
                    if (state === YT.PlayerState.PLAYING || state === YT.PlayerState.BUFFERING) {
                        this._isManualPausing = true;
                        this.tvPlayer.pauseVideo();
                        setTimeout(() => { this._isManualPausing = false; }, 400);
                    }
                }

                // 3. Sinkronisasi Time Drift (apabila video TV ketinggalan atau mendahului > 2.5 detik)
                if (this.isPlaying && typeof this.tvPlayer.getCurrentTime === 'function' && this.playbackDuration > 0 && this.playbackCurrentTime < 86400) {
                    const tvCurTime = this.tvPlayer.getCurrentTime() || 0;
                    const drift = Math.abs(tvCurTime - this.playbackCurrentTime);
                    if (drift > 2.5) {
                        this.tvPlayer.seekTo(this.playbackCurrentTime, true);
                    }
                }
            } catch (e) {}
        },

        applySyncData(data) {
            if (data.nowPlaying !== undefined) this.nowPlaying = data.nowPlaying;
            if (data.isPlaying !== undefined) this.isPlaying = data.isPlaying;
            if (data.currentTime !== undefined) {
                this.playbackCurrentTime = Number(data.currentTime);
                this.playbackCurrentTimeFormatted = this.formatSeconds(Math.floor(this.playbackCurrentTime));
            }
            if (data.duration !== undefined) {
                this.playbackDuration = Number(data.duration);
                this.playbackDurationFormatted = this.formatSeconds(Math.floor(this.playbackDuration));
            }
            if (data.progressPercent !== undefined) {
                this.playbackProgressPercent = Number(data.progressPercent);
            }
        },

        async fetchStatus() {
            try {
                const res = await fetch('{{ route('music.status') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) {
                    return;
                }
                const data = await res.json();
                this.nowPlaying = data.now_playing;
                this.queue = data.queue || [];
                this.queueCount = data.queue_count || 0;

                // Sync playback state (untuk TV eksternal / Smart TV tanpa BroadcastChannel)
                let targetDuration = 0;
                if (data.playback && Number(data.playback.duration || 0) > 0) {
                    targetDuration = Number(data.playback.duration);
                } else if (data.now_playing && Number(data.now_playing.duration_seconds || 0) > 0) {
                    targetDuration = Number(data.now_playing.duration_seconds);
                } else if (this.nowPlaying && Number(this.nowPlaying.duration_seconds || 0) > 0) {
                    targetDuration = Number(this.nowPlaying.duration_seconds);
                }

                if (targetDuration > 0) {
                    this.playbackDuration = targetDuration;
                    this.playbackDurationFormatted = this.formatSeconds(this.playbackDuration);
                }

                if (data.playback) {
                    this.isPlaying = typeof data.playback.is_playing !== 'undefined' ? !!data.playback.is_playing : true;
                    let elapsed = 0;
                    const serverUpdated = Number(data.playback.updated_at || 0);
                    if (serverUpdated > 0) {
                        const diffSec = (Date.now() - serverUpdated) / 1000;
                        if (diffSec >= 0 && diffSec <= 15) {
                            elapsed = diffSec;
                        }
                    }

                    let cur = Number(data.playback.current_time || 0) + (this.isPlaying ? elapsed : 0);
                    if (this.playbackDuration > 0) {
                        cur = Math.min(this.playbackDuration, Math.max(0, cur));
                    }
                    this.playbackCurrentTime = cur;
                    this.playbackProgressPercent = this.playbackDuration > 0 ? (this.playbackCurrentTime / this.playbackDuration) * 100 : 0;
                    this.playbackCurrentTimeFormatted = this.formatSeconds(this.playbackCurrentTime);
                } else if (data.now_playing) {
                    this.isPlaying = true;
                    if (!this.playbackCurrentTime || this.playbackCurrentTime === 0) {
                        this.playbackCurrentTimeFormatted = '00:00';
                    }
                }

                // Cek pesanan siap baru
                if (data.ready_orders) {
                    this.checkNewReadyOrders(data.ready_orders);
                }
            } catch (e) {
                console.error('[TV Display] Sync Error:', e);
            }
        },

        checkNewReadyOrders(newOrders) {
            this.readyOrders = newOrders;
            newOrders.forEach(order => {
                if (!this.knownReadyIds.includes(order.id)) {
                    this.knownReadyIds.push(order.id);
                    this.spawnFlyingCard(order);
                }
            });
        },

        async triggerNewReadyOrderNotification(orderId, isRecall = false) {
            try {
                const res = await fetch('{{ route('music.status') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) return;
                const data = await res.json();
                this.readyOrders = data.ready_orders || [];
                const matched = this.readyOrders.find(o => o.id === orderId);
                if (matched) {
                    this.spawnFlyingCard(matched, isRecall);
                }
            } catch (e) {}
        },

        spawnFlyingCard(order, isRecall = false) {
            this.activeFlyingCards = this.activeFlyingCards.filter(c => c.id !== order.id);
            const cardObj = {
                ...order,
                isRecall: isRecall,
                uniqueKey: Date.now() + '-' + order.id
            };
            this.activeFlyingCards.push(cardObj);

            this.playReadyChime();

            setTimeout(() => {
                this.activeFlyingCards = this.activeFlyingCards.filter(c => c.uniqueKey !== cardObj.uniqueKey);
            }, 18000);
        },

        dismissFlyingCard(cardKey) {
            this.activeFlyingCards = this.activeFlyingCards.filter(c => c.uniqueKey !== cardKey);
        },

        showAllReadyCards() {
            if (this.readyOrders.length === 0) return;
            this.activeFlyingCards = [];
            this.readyOrders.forEach((ro, idx) => {
                setTimeout(() => {
                    this.spawnFlyingCard(ro);
                }, idx * 250);
            });
        },

        formatSeconds(sec) {
            const num = Math.max(0, Math.floor(Number(sec) || 0));
            if (num > 86400 * 7) return 'LIVE';
            const h = Math.floor(num / 3600);
            const m = Math.floor((num % 3600) / 60);
            const s = num % 60;
            if (h > 0) {
                return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            }
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        },

        playReadyChime() {
            if (!this.tvSoundEnabled) return; // Silent by default (mencegah tabrakan/dobel suara dengan Tab Kasir)
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

        toggleTvSound() {
            this.tvSoundEnabled = !this.tvSoundEnabled;
            try {
                localStorage.setItem('tv_sound_enabled', this.tvSoundEnabled ? 'true' : 'false');
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
        },

        toggleDisplayMode() {
            this.displayMode = this.displayMode === 'visualizer' ? 'video' : 'visualizer';
            try {
                localStorage.setItem('tv_display_mode', this.displayMode);
            } catch (e) {}
            if (this.displayMode === 'video') {
                this.$nextTick(() => {
                    this.loadYouTubeApi();
                    this.syncTvPlayerState();
                });
            }
        },

        toggleFullscreen() {
            if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
                const docElm = document.documentElement;
                if (docElm.requestFullscreen) {
                    docElm.requestFullscreen().catch(() => {});
                } else if (docElm.webkitRequestFullscreen) {
                    docElm.webkitRequestFullscreen();
                } else if (docElm.msRequestFullscreen) {
                    docElm.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen().catch(() => {});
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        }
      }">

    <!-- BACKGROUND AMBIENT LAYERS (Strictly contained, no overflow on right/bottom) -->
    <div class="fixed inset-0 w-full h-full overflow-hidden pointer-events-none z-0" style="contain: strict;">
        <!-- LAYER 1: DYNAMIC BLURRED ALBUM ARTWORK WALLPAPER -->
        <div class="absolute inset-0 bg-cover bg-center filter blur-3xl opacity-30 scale-105 transition-all duration-1000"
             :style="nowPlaying && nowPlaying.thumbnail_url ? 'background-image: url(' + nowPlaying.thumbnail_url + ');' : ''">
        </div>

        <!-- LAYER 2: DEEP AMBIENT RADIAL VIGNETTE -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(20,14,10,0.65)_0%,rgba(10,7,5,0.96)_100%)]"></div>

        <!-- LAYER 3: FLOATING GOLDEN COFFEE STEAM PARTICLES -->
        <canvas id="steam-canvas" class="absolute inset-0 w-full h-full block"></canvas>

        <!-- LAYER 4: AMBIENT PULSING GLOW ORBS -->
        <div class="absolute -top-24 -left-24 w-80 h-80 bg-[#D9973E]/15 rounded-full blur-[100px] animate-pulse-glow"></div>
        <div class="absolute -bottom-24 -right-24 w-80 h-80 bg-[#5F7F42]/15 rounded-full blur-[100px] animate-pulse-glow" style="animation-delay: 4s;"></div>
    </div>

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
                                <span class="font-mono text-[10px] uppercase tracking-wider px-2 py-0.5 rounded bg-[#5F7F42] text-[#140E0A] font-bold"
                                      x-text="order.isRecall ? 'PANGGILAN ULANG' : 'PESANAN SUDAH SIAP'"></span>
                                <span class="text-[10px] font-mono text-[#A89A85]" x-text="order.elapsed_minutes ? (order.elapsed_minutes + ' mnt') : 'Baru saja'"></span>
                            </div>

                            <h4 class="text-lg sm:text-xl font-bold font-serif text-white mt-1 leading-snug truncate"
                                x-text="order.customer_name ? ('Kak ' + order.customer_name) : 'Pelanggan'"></h4>

                            <div class="text-xs text-[#5F7F42] font-mono font-bold mt-0.5">
                                Kode Tiket: <span class="text-[#D9973E] tracking-wider" x-text="order.code"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Close Button -->
                    <button type="button" @click="dismissFlyingCard(order.uniqueKey)"
                            class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 text-[#A89A85] hover:text-white flex items-center justify-center text-sm transition shrink-0">
                        ✕
                    </button>
                </div>

                <!-- Callout Subtext -->
                <div class="mt-3 pt-2.5 border-t border-[#3A3026]/80 flex items-center justify-between text-xs text-[#C4B6A3]">
                    <span class="flex items-center gap-1.5 font-medium">
                        <span>☕</span>
                        <span>Silakan ambil di <strong>Meja Kasir</strong> sekarang</span>
                    </span>
                    <span class="text-[10px] text-[#A89A85] font-mono uppercase">Terima Kasih</span>
                </div>
            </div>
        </template>
    </div>

    <!-- CONTENT WRAPPER (Fills full viewport without dead space) -->
    <div class="w-full h-full min-h-screen max-h-screen flex flex-col justify-between p-4 sm:p-6 lg:p-8 xl:p-10 relative z-10 box-border overflow-hidden"
         @dblclick="toggleFullscreen()"
         title="Klik dua kali untuk beralih Layar Penuh">

        <!-- TOP BAR: BRANDING, CLOCK, READY TICKER & MODE TOGGLE -->
        <header class="w-full flex items-center justify-between border-b border-[#3A3026]/80 pb-4 shrink-0">
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

            <!-- Mode Switcher, Sound Toggle, Fullscreen & Real-time Clock -->
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- Toggle Mode: Visualizer vs Video -->
                <button type="button" @click="toggleDisplayMode()"
                        class="px-2.5 py-1.5 bg-[#1F1812] hover:bg-[#2A2018] border border-[#3A3026] hover:border-[#D9973E] text-[#D9973E] font-mono text-xs uppercase tracking-wider transition rounded flex items-center gap-1.5 shadow-sm active:scale-95"
                        :title="displayMode === 'visualizer' ? 'Beralih ke Tampilan Video YouTube' : 'Beralih ke Tampilan Vinyl Visualizer'">
                    <span x-show="displayMode === 'visualizer'" class="flex items-center gap-1">
                        <span>🎬</span>
                        <span class="hidden sm:inline">Video</span>
                    </span>
                    <span x-show="displayMode === 'video'" class="flex items-center gap-1">
                        <span>🎨</span>
                        <span class="hidden sm:inline">Visualizer</span>
                    </span>
                </button>

                <!-- Toggle Suara Notifikasi TV (Mute untuk cegah dobel suara dengan Kasir) -->
                <button type="button" @click="toggleTvSound()"
                        class="w-9 h-9 flex items-center justify-center rounded bg-[#1F1812] hover:bg-[#2A2018] border border-[#3A3026] hover:border-[#D9973E] text-[#D9973E] transition shadow-sm active:scale-95"
                        :title="tvSoundEnabled ? 'Suara Notifikasi TV: AKTIF (Klik untuk Heningkan)' : 'Suara Notifikasi TV: HENING (Klik untuk Aktifkan)'">
                    <span class="text-sm" x-text="tvSoundEnabled ? '🔔' : '🔕'"></span>
                </button>

                <!-- Toggle Fullscreen Simpel & Elegan (Icon Only) -->
                <button type="button" @click="toggleFullscreen()"
                        class="w-9 h-9 flex items-center justify-center rounded bg-[#1F1812] hover:bg-[#2A2018] border border-[#3A3026] hover:border-[#D9973E] text-[#D9973E] transition shadow-sm active:scale-95"
                        :title="isFullscreen ? 'Keluar Layar Penuh (Esc)' : 'Layar Penuh (F11)'">
                    <svg x-show="!isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <svg x-show="isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v4m0 0H5m4 0L4 2m11 1v4m0 0h4m-4 0l5-5M9 21v-4m0 0H5m4 0l-5 5m11-1v-4m0 0h4m-4 0l5 5" />
                    </svg>
                </button>

                <div class="font-mono text-2xl sm:text-3xl font-bold text-[#F7F3EC] tracking-wider ml-1" x-text="currentTime"></div>
            </div>
        </header>

        <!-- MAIN STAGE: NOW PLAYING & RIGHT COLUMN (Dynamically fills vertical space) -->
        <main class="w-full flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 xl:gap-10 items-center my-auto py-2 sm:py-3">

            <!-- LEFT COL: NOW PLAYING HERO (7 cols) -->
            <div class="lg:col-span-7 flex flex-col justify-center">

                <!-- 1. MODE VISUALIZER: 3D VINYL TURNTABLE & SPECTRUM EQUALIZER -->
                <div x-show="displayMode === 'visualizer'" class="flex flex-col sm:flex-row items-center gap-6 sm:gap-8 xl:gap-10">
                    <!-- VINYL RECORD TURNTABLE -->
                    <div class="relative shrink-0 w-56 h-56 sm:w-64 sm:h-64 md:w-72 md:h-72 lg:w-80 lg:h-80 xl:w-96 xl:h-96">
                        <div class="w-full h-full rounded-full bg-gradient-to-tr from-[#120D09] via-[#221711] to-[#120D09] border-4 border-[#3A3026] shadow-[0_20px_60px_rgba(0,0,0,0.8)] flex items-center justify-center p-3 relative overflow-hidden"
                             :class="isPlaying ? 'animate-spin-slow' : ''">

                            <!-- VINYL GROOVES -->
                            <div class="w-full h-full rounded-full border border-dashed border-[#443527] flex items-center justify-center p-4 sm:p-5">
                                <div class="w-full h-full rounded-full border border-dashed border-[#554637] flex items-center justify-center p-5 sm:p-6">
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
                        <div class="absolute inset-0 m-auto w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gradient-to-tr from-[#D9973E] to-[#F7F3EC] border-2 border-[#140E0A] shadow-md"></div>
                    </div>

                    <!-- NOW PLAYING METADATA -->
                    <div class="min-w-0 text-center sm:text-left flex-1 w-full max-w-xl xl:max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#D9973E]/15 border border-[#D9973E]/40 text-[#D9973E] font-mono text-xs uppercase tracking-[0.2em] mb-3 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#D9973E] animate-ping"></span>
                            <span x-text="isPlaying ? 'NOW PLAYING' : 'AUDIO PAUSED'"></span>
                        </div>

                        <h2 class="text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-serif font-bold text-[#F7F3EC] leading-tight tracking-tight line-clamp-2 drop-shadow-md"
                            x-text="nowPlaying ? (nowPlaying.song_title || nowPlaying.title) : 'Playlist Bawaan KopiKita'">
                        </h2>

                        <p class="text-base sm:text-lg lg:text-xl text-[#D5CCC0] mt-1.5 font-mono truncate"
                           x-text="nowPlaying ? (nowPlaying.artist || 'Artis Musik') : 'Chill Lo-Fi / Jazz Cafe Vibes'">
                        </p>

                        <!-- TIMELINE PROGRESS BAR -->
                        <div class="mt-4 w-full">
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

                        <!-- 42-BAND LIVE SOUND SPECTRUM EQUALIZER WAVE -->
                        <div class="mt-4 w-full flex items-end gap-1 h-8 pt-1 overflow-hidden">
                            <template x-for="i in 42" :key="i">
                                <div class="flex-1 min-w-[2px] rounded-t bg-gradient-to-t from-[#D9973E] to-[#5F7F42] transition-all duration-150"
                                     :class="isPlaying ? 'eq-bar' : 'h-1 opacity-40'"
                                     :style="isPlaying ? 'animation-delay: ' + ((i * 38) % 800) + 'ms; animation-duration: ' + (0.65 + ((i * 31) % 650) / 1000) + 's;' : ''">
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

                <!-- 2. MODE VIDEO: CINEMATIC YOUTUBE PLAYER SCREEN (100% SYNC DENGAN AUDIO KASIR) -->
                <div x-show="displayMode === 'video'" class="space-y-4 w-full">
                    <div class="w-full aspect-video rounded-2xl overflow-hidden border-2 border-[#3A3026] shadow-[0_20px_60px_rgba(0,0,0,0.9)] bg-black relative group">
                        <!-- YOUTUBE VIDEO HOST (POINTER-EVENTS-NONE AGAR TIDAK BISA DI-PAUSE SECARA MANUAL DI LAYAR TV) -->
                        <div class="w-full h-full relative">
                            <div id="tv-player-wrap" class="w-full h-full pointer-events-none" x-show="nowPlaying && nowPlaying.youtube_id">
                                <div id="tv-yt-player" class="w-full h-full"></div>
                            </div>

                            <template x-if="!nowPlaying || !nowPlaying.youtube_id">
                                <div class="w-full h-full flex flex-col items-center justify-center bg-[#140E0A] text-[#A89A85]">
                                    <span class="text-5xl mb-2 text-[#D9973E]">🎬</span>
                                    <span class="font-mono text-xs">Memuat tayangan video...</span>
                                </div>
                            </template>

                            <!-- OVERLAY INDIKATOR KETIKA KASIR MENJEDA MUSIK -->
                            <div x-show="!isPlaying && nowPlaying"
                                 class="absolute inset-0 bg-black/65 backdrop-blur-xs flex items-center justify-center pointer-events-none transition-opacity">
                                <div class="px-5 py-2.5 bg-[#1F1812]/95 border border-[#D9973E]/60 rounded-full text-[#D9973E] font-mono text-xs font-bold flex items-center gap-2 shadow-2xl animate-pulse">
                                    <span class="w-2 h-2 rounded-full bg-[#D9973E] animate-ping"></span>
                                    <span>⏸️ AUDIO & VIDEO TERJEDA DI KASIR</span>
                                </div>
                            </div>
                        </div>

                        <!-- Video Overlay Badge -->
                        <div class="absolute top-3 left-3 px-3 py-1 bg-black/70 border border-white/10 rounded-full font-mono text-[10px] text-[#D9973E] uppercase tracking-widest backdrop-blur-md pointer-events-none flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#D9973E] animate-pulse"></span>
                            <span>LIVE CINEMATIC SYNC</span>
                        </div>
                    </div>

                    <!-- Video Track Info Bar -->
                    <div class="flex items-center justify-between gap-4 p-3 bg-[#1F1812]/80 border border-[#3A3026] rounded-xl backdrop-blur w-full">
                        <div class="min-w-0">
                            <h3 class="font-serif font-bold text-base text-[#F7F3EC] truncate"
                                x-text="nowPlaying ? (nowPlaying.song_title || nowPlaying.title) : 'Lagu Kafe'"></h3>
                            <div class="text-xs text-[#A89A85] font-mono truncate"
                                 x-text="nowPlaying ? (nowPlaying.artist || 'Artis') : '-'"></div>
                        </div>
                        <div class="font-mono text-xs text-[#D9973E] shrink-0 font-bold" x-text="playbackCurrentTimeFormatted + ' / ' + playbackDurationFormatted"></div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COL: LIVE READY ORDERS & UP NEXT QUEUE (5 cols, fills height harmoniously) -->
            <div class="lg:col-span-5 bg-[#17110C]/90 border border-[#3A3026] rounded-2xl p-5 sm:p-6 shadow-2xl backdrop-blur-xl flex flex-col h-full min-h-[420px] max-h-[580px] xl:max-h-[640px] w-full justify-between">

                <!-- TOP SECTION: PESANAN SIAP (TAMPIL DI ATAS JIKA ADA PESANAN SIAP) -->
                <template x-if="readyOrders.length > 0">
                    <div class="mb-4 bg-gradient-to-r from-[#1E2E17] to-[#142010] border-2 border-[#5F7F42] rounded-xl p-3.5 shadow-lg shrink-0">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#5F7F42] animate-ping"></span>
                                <span class="font-mono text-xs font-bold text-[#5F7F42] uppercase tracking-wider">🔔 Pesanan Siap di Kasir</span>
                            </div>
                            <span class="font-mono text-[11px] font-bold text-[#F7F3EC] bg-[#5F7F42]/30 px-2 py-0.5 rounded-full" x-text="readyOrders.length + ' Pesanan'"></span>
                        </div>
                        <div class="flex flex-wrap gap-2 max-h-24 overflow-y-auto">
                            <template x-for="ro in readyOrders" :key="ro.id">
                                <div class="px-2.5 py-1 bg-[#25391C] border border-[#5F7F42]/60 rounded-lg text-xs font-mono text-white flex items-center gap-1.5 shadow-sm">
                                    <span class="font-bold text-[#D9973E]" x-text="ro.code"></span>
                                    <span class="text-white/80" x-text="ro.customer_name ? ('(' + ro.customer_name + ')') : ''"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- QUEUE SECTION HEADER -->
                <div class="flex items-center justify-between border-b border-[#3A3026] pb-3 mb-3 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#D9973E] animate-pulse"></span>
                        <h3 class="font-mono text-xs uppercase tracking-[0.2em] font-bold text-[#F7F3EC]">Antrean Lagu Berikutnya</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#D9973E]/15 border border-[#D9973E]/30 text-[#D9973E]"
                              x-text="queue.length + ' Lagu'"></span>
                    </div>

                    <span class="font-mono text-[10px] text-[#7A6A58] uppercase tracking-widest hidden sm:inline">UP NEXT QUEUE</span>
                </div>

                <!-- QUEUE LIST (FLEX-1 EXPANDABLE, INVISIBLE SCROLL) -->
                <div class="space-y-1.5 overflow-y-auto pr-0 flex-1 min-h-[140px] no-scrollbar">
                    <template x-if="queue.length === 0">
                        <div class="h-full flex flex-col items-center justify-center text-center py-8 text-[#7A6A58] font-mono text-xs">
                            <span class="text-3xl mb-2 opacity-50">☕</span>
                            <span>Antrean request lagu sedang kosong.</span>
                            <span class="text-[10px] mt-1 text-[#554637]">Scan QR di bawah untuk me-request lagu pertamamu!</span>
                        </div>
                    </template>

                    <template x-for="(item, index) in queue" :key="item.id">
                        <div class="flex items-center justify-between py-1 px-2.5 bg-[#1F1711] border border-[#3A3026]/70 rounded hover:border-[#D9973E]/50 transition">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="font-mono font-bold text-[#D9973E] text-[10px] w-3.5 text-center shrink-0" x-text="'#' + (index + 1)"></span>
                                <div class="min-w-0">
                                    <div class="text-[11px] font-medium text-[#F7F3EC] truncate leading-snug" x-text="item.song_title"></div>
                                    <div class="text-[9px] text-[#A89A85] truncate leading-tight" x-text="item.artist || 'Artis YouTube'"></div>
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-2">
                                <div class="font-mono text-[8.5px] text-[#A89A85] truncate max-w-[85px]" x-text="item.customer_name || 'Pelanggan'"></div>
                                <span class="font-mono text-[7.5px] uppercase tracking-wider text-[#5F7F42] bg-[#5F7F42]/10 px-1 py-0.2 rounded border border-[#5F7F42]/20">Next</span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- SUBTLE CARD FOOTNOTE -->
                <div class="mt-3 pt-2 border-t border-[#3A3026]/60 flex items-center justify-between text-[10px] font-mono text-[#7A6A58] shrink-0">
                    <span>* Lagu berputar bergiliran sesuai antrean</span>
                    <span class="text-[#D9973E]/80">Auto-skip jika video diblokir</span>
                </div>

            </div>

        </main>

        <!-- FOOTER: QR CODE TO REQUEST MUSIC & BRANDING -->
        <footer class="w-full border-t border-[#3A3026]/80 pt-3 sm:pt-4 flex flex-col sm:flex-row items-center justify-between gap-4 sm:gap-6 shrink-0">
            <div class="flex items-center gap-4 sm:gap-5">
                <div class="p-2 bg-white border border-[#3A3026] rounded-lg shrink-0 shadow-lg">
                    <img src="{{ \App\Support\QrCode::dataUri(route('music.request'), 130) }}" width="64" height="64" alt="QR Request Musik">
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
