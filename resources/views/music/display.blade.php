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
            animation: spinSlow 20s linear infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.35; transform: scale(1); }
            50% { opacity: 0.65; transform: scale(1.08); }
        }
        .animate-pulse-glow {
            animation: pulseGlow 8s ease-in-out infinite;
        }
        @keyframes shineSweep {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .shine-reflection {
            background: linear-gradient(135deg, transparent 38%, rgba(255,255,255,0.15) 50%, transparent 62%);
            animation: shineSweep 10s linear infinite;
        }
        @keyframes eqBarBounce {
            0%, 100% { height: 12%; }
            50% { height: 98%; }
        }
        .eq-bar {
            animation: eqBarBounce 1.1s ease-in-out infinite alternate;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none !important;
        }
        .no-scrollbar {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
        #tv-player-wrap {
            width: 100% !important;
            height: 100% !important;
            overflow: hidden !important;
            position: relative !important;
            pointer-events: none !important;
            user-select: none !important;
            background-color: #000 !important;
        }
        #tv-player-wrap iframe, #tv-yt-player {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            transform: none !important;
            border: none !important;
            pointer-events: none !important;
            user-select: none !important;
        }
        .video-shield {
            position: absolute !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 25 !important;
            background: transparent !important;
            cursor: default !important;
            user-select: none !important;
            touch-action: none !important;
            pointer-events: auto !important;
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
        [x-cloak] {
            display: none !important;
        }
    </style>
    <script>
        function tvDisplayApp() {
            return {
                nowPlaying: @json($playerState['now_playing']),
                queue: @json($playerState['queue']),
                queueCount: {{ (int) ($playerState['queue_count'] ?? 0) }},
                readyOrders: @json($readyOrders ?? []),
                knownReadyIds: @json(collect($readyOrders ?? [])->pluck('id')),
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
                _lastSeekTime: 0,
                _currentRate: 1,
                _initialSynced: false,
                isAdzanMode: false,
                adzanPrayerName: '',
                _isTestAdzan: false,
                _isManualAdzan: false,

                get isNearTrackEnd() {
                    if (!this.playbackDuration || this.playbackDuration <= 8) return false;
                    if (this.playbackDuration >= 86400) return false; // Radio/Live 24/7

                    const remaining = this.playbackDuration - this.playbackCurrentTime;
                    const isEnded = this.tvPlayer && typeof this.tvPlayer.getPlayerState === 'function' && this.tvPlayer.getPlayerState() === 0;

                    // Tutup 6 detik sebelum video selesai atau saat status ended agar rekomendasi YouTube 100% tersembunyi
                    return (remaining > 0 && remaining <= 6.5) || isEnded;
                },

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
                                if (s.currentTrack) {
                                    this.nowPlaying = s.currentTrack;
                                    if (Array.isArray(this.queue)) {
                                        this.queue = this.queue.filter(item => item.id !== this.nowPlaying.id && item.id !== this.nowPlaying.request_id);
                                    }
                                }
                                if (typeof s.isPlaying !== 'undefined') this.isPlaying = !!s.isPlaying;
                                if (typeof s.queueCount !== 'undefined') this.queueCount = s.queueCount;
                                if (Array.isArray(s.queue)) {
                                    this.queue = s.queue.filter(item => !this.nowPlaying || (item.id !== this.nowPlaying.id && item.id !== this.nowPlaying.request_id));
                                }
                                this.syncTvPlayerState();
                            } else if (data.type === 'SYNC_STATE') {
                                this.applySyncData(data);
                                this.syncTvPlayerState();
                            } else if (data.type === 'TRACK_CHANGED') {
                                this.nowPlaying = data.track;
                                this.isPlaying = true;
                                if (this.nowPlaying && Array.isArray(this.queue)) {
                                    this.queue = this.queue.filter(item => item.id !== this.nowPlaying.id && item.id !== this.nowPlaying.request_id);
                                }
                                this.syncTvPlayerState();
                            } else if (data.type === 'QUEUE_UPDATED') {
                                this.fetchStatus();
                            } else if (data.type === 'ORDER_READY') {
                                this.triggerNewReadyOrderNotification(data.orderId, data.isRecall);
                            } else if (data.type === 'ADZAN_MODE_STARTED') {
                                if (!this.isAdzanMode) {
                                    this.playAdzanChime();
                                }
                                this.isAdzanMode = true;
                                this.adzanPrayerName = data.prayer || 'Adzan';
                                this._isTestAdzan = !!data.isTest;
                                this._isManualAdzan = !!data.isManual;
                            } else if (data.type === 'ADZAN_MODE_ENDED') {
                                this.isAdzanMode = false;
                                this.adzanPrayerName = '';
                                this._isTestAdzan = false;
                                this._isManualAdzan = false;
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

                    // Polling status lagu & pesanan siap setiap 2 detik
                    this.fetchStatus();
                    setInterval(() => this.fetchStatus(), 2000);

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

                disableCaptions() {
                    if (!this.tvPlayer) return;
                    try {
                        if (typeof this.tvPlayer.unloadModule === 'function') {
                            this.tvPlayer.unloadModule('captions');
                            this.tvPlayer.unloadModule('cc');
                        }
                        if (typeof this.tvPlayer.setOption === 'function') {
                            this.tvPlayer.setOption('captions', 'track', {});
                            this.tvPlayer.setOption('cc', 'track', {});
                            this.tvPlayer.setOption('captions', 'fontSize', 0);
                        }
                    } catch (e) {}
                },

                setOptimalQuality() {
                    if (!this.tvPlayer) return;
                    try {
                        const highResQualities = ['highres', 'hd1440', 'hd2160', 'hd2880', 'hd4320'];
                        const currentQuality = (typeof this.tvPlayer.getPlaybackQuality === 'function')
                            ? this.tvPlayer.getPlaybackQuality()
                            : null;

                        // Hanya turunkan jika YouTube secara otomatis memilih resolusi di atas 1080p (2K/4K/8K)
                        if (currentQuality && highResQualities.includes(currentQuality)) {
                            if (typeof this.tvPlayer.setPlaybackQuality === 'function') {
                                this.tvPlayer.setPlaybackQuality('hd1080');
                            }
                        }
                    } catch (e) {}
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
                                origin: window.location.origin,
                                cc_load_policy: 0,
                                iv_load_policy: 3,
                                cc_lang_pref: 'none'
                            },
                            events: {
                                onReady: (event) => {
                                    this.tvPlayerReady = true;
                                    event.target.mute();
                                    this.disableCaptions();

                                    if (this.nowPlaying && this.nowPlaying.youtube_id) {
                                        const startSec = Math.max(0, Math.floor(this.playbackCurrentTime || 0));
                                        this.tvPlayer.loadVideoById({
                                            videoId: this.nowPlaying.youtube_id,
                                            startSeconds: (startSec > 0 && startSec < 86400) ? startSec : 0,
                                            suggestedQuality: 'hd1080'
                                        });
                                        if (this.isPlaying) {
                                            event.target.playVideo();
                                        } else {
                                            event.target.pauseVideo();
                                        }
                                        setTimeout(() => {
                                            this.disableCaptions();
                                        }, 800);
                                    }
                                },
                                onStateChange: (event) => {
                                    // Matikan paksa closed caption saat video memutar
                                    if (event.data === YT.PlayerState.PLAYING) {
                                        this.disableCaptions();

                                        // Kalibrasi awal saat video baru mulai memutar jika buffering awal memakan waktu > 2 detik
                                        if (!this._initialSynced && this.playbackCurrentTime > 0) {
                                            this._initialSynced = true;
                                            const tvCurTime = (typeof this.tvPlayer.getCurrentTime === 'function') ? (this.tvPlayer.getCurrentTime() || 0) : 0;
                                            const drift = this.playbackCurrentTime - tvCurTime;
                                            if (drift > 2 && drift < 86400) {
                                                this._lastSeekTime = Date.now();
                                                this.tvPlayer.seekTo(this.playbackCurrentTime + 0.2, true);
                                            }
                                        }
                                    }

                                    if (event.data === YT.PlayerState.PAUSED && this.isPlaying && !this._isManualPausing) {
                                        setTimeout(() => {
                                            if (this.isPlaying && this.tvPlayer && typeof this.tvPlayer.playVideo === 'function') {
                                                this.tvPlayer.playVideo();
                                            }
                                        }, 800);
                                    }
                                },
                                onPlaybackQualityChange: (event) => {
                                    // Batasi maksimal Full HD (1080p) agar tidak memboroskan bandwidth atau buffering 4K
                                    const highResQualities = ['highres', 'hd1440', 'hd2160', 'hd2880', 'hd4320'];
                                    if (highResQualities.includes(event.data)) {
                                        if (this.tvPlayer && typeof this.tvPlayer.setPlaybackQuality === 'function') {
                                            this.tvPlayer.setPlaybackQuality('hd1080');
                                        }
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
                        if (this.currentTvVideoId !== this.nowPlaying.youtube_id) {
                            this.currentTvVideoId = this.nowPlaying.youtube_id;
                            this._initialSynced = false;
                            this._currentRate = 1;
                            const startSec = Math.max(0, Math.floor(this.playbackCurrentTime || 0));
                            this.tvPlayer.loadVideoById({
                                videoId: this.nowPlaying.youtube_id,
                                startSeconds: (startSec > 0 && startSec < 86400) ? startSec : 0,
                                suggestedQuality: 'hd1080'
                            });
                            this.disableCaptions();
                            setTimeout(() => {
                                this.disableCaptions();
                            }, 800);
                        }

                        const state = (typeof this.tvPlayer.getPlayerState === 'function') ? this.tvPlayer.getPlayerState() : -1;
                        if (this.isPlaying) {
                            if (state === YT.PlayerState.PAUSED || state === YT.PlayerState.CUED) {
                                this.tvPlayer.playVideo();
                            }
                        } else {
                            if (state === YT.PlayerState.PLAYING) {
                                this._isManualPausing = true;
                                this.tvPlayer.pauseVideo();
                                setTimeout(() => { this._isManualPausing = false; }, 500);
                            }
                        }

                        // Sinkronisasi posisi detik ultra-halus (Micro-Rate Auto Sync)
                        // Mengeliminasi delay sekecil mungkin tanpa memicu buffering loop
                        if (this.isPlaying && typeof this.tvPlayer.getCurrentTime === 'function' && this.playbackDuration > 0 && this.playbackCurrentTime < 86400) {
                            if (state !== YT.PlayerState.BUFFERING) {
                                const tvCurTime = this.tvPlayer.getCurrentTime() || 0;
                                const diff = this.playbackCurrentTime - tvCurTime; // > 0: TV tertinggal, < 0: TV mendahului
                                const absDrift = Math.abs(diff);
                                const now = Date.now();

                                // 1. Selisih drastis (> 5 detik, misal kasir menggeser scrubber/slider lagu)
                                if (absDrift > 5 && (!this._lastSeekTime || (now - this._lastSeekTime > 5000))) {
                                    this._lastSeekTime = now;
                                    this.tvPlayer.seekTo(this.playbackCurrentTime, true);
                                    if (this._currentRate !== 1 && typeof this.tvPlayer.setPlaybackRate === 'function') {
                                        this.tvPlayer.setPlaybackRate(1);
                                        this._currentRate = 1;
                                    }
                                }
                                // 2. Selisih halus (0.8s s/d 5s): Gunakan penyesuaian kecepatan putar (micro-rate)
                                // Mulus 100% TANPA buffering, TANPA spinner loading, dan gambar tidak pernah tersendat!
                                else if (absDrift >= 0.8 && absDrift <= 5) {
                                    if (diff > 0.8 && this._currentRate !== 1.25) {
                                        // TV sedikit tertinggal -> percepat 1.25x agar mengejar dalam beberapa detik
                                        if (typeof this.tvPlayer.setPlaybackRate === 'function') {
                                            this.tvPlayer.setPlaybackRate(1.25);
                                            this._currentRate = 1.25;
                                        }
                                    } else if (diff < -0.8 && this._currentRate !== 0.75) {
                                        // TV sedikit mendahului -> perlambat 0.75x agar audio kasir menyusul
                                        if (typeof this.tvPlayer.setPlaybackRate === 'function') {
                                            this.tvPlayer.setPlaybackRate(0.75);
                                            this._currentRate = 0.75;
                                        }
                                    }
                                }
                                // 3. Selisih presisi (< 0.8 detik): Video dan Audio sudah sinkron sempurna
                                else if (absDrift < 0.8 && this._currentRate !== 1) {
                                    if (typeof this.tvPlayer.setPlaybackRate === 'function') {
                                        this.tvPlayer.setPlaybackRate(1);
                                        this._currentRate = 1;
                                    }
                                }
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
                        if (!res.ok) return;
                        const data = await res.json();
                        this.nowPlaying = data.now_playing;
                        const rawQueue = data.queue || [];
                        this.queue = rawQueue.filter(item => !this.nowPlaying || (item.id !== this.nowPlaying.id && item.id !== this.nowPlaying.request_id));
                        this.queueCount = data.queue_count || 0;

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

                        this.syncTvPlayerState();

                        if (data.ready_orders) {
                            this.checkNewReadyOrders(data.ready_orders);
                        }

                        if (data.prayer_times && data.prayer_times.active_prayer && data.adzan_settings && data.adzan_settings.enabled) {
                            if (!this.isAdzanMode) {
                                this.playAdzanChime();
                            }
                            this.isAdzanMode = true;
                            this.adzanPrayerName = data.prayer_times.active_prayer.name;
                        } else if (!this._isTestAdzan && !this._isManualAdzan) {
                            this.isAdzanMode = false;
                            this.adzanPrayerName = '';
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
                    if (!this.tvSoundEnabled) return;
                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        if (!AudioContext) return;
                        const ctx = new AudioContext();
                        const now = ctx.currentTime;

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
                    const particleCount = 30;

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

                playAdzanChime() {
                    if (!this.tvSoundEnabled) return;
                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        if (!AudioContext) return;
                        const ctx = new AudioContext();
                        const now = ctx.currentTime;

                        // Nada lembut pengingat waktu sholat (F4 -> C5 -> A4)
                        const notes = [349.23, 523.25, 440.00];
                        notes.forEach((freq, i) => {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            const t = now + (i * 0.25);
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(freq, t);
                            gain.gain.setValueAtTime(0.2, t);
                            gain.gain.exponentialRampToValueAtTime(0.001, t + 0.8);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(t);
                            osc.stop(t + 0.85);
                        });
                    } catch (e) {}
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
            };
        }
    </script>
</head>
<body class="bg-[#0E0906] text-[#FAF7F2] w-screen h-screen min-w-full min-h-screen overflow-hidden antialiased font-sans select-none relative m-0 p-0"
      x-data="tvDisplayApp()"
      x-cloak>

    <!-- BACKGROUND AMBIENT LAYERS -->
    <div class="fixed inset-0 w-full h-full overflow-hidden pointer-events-none z-0" style="contain: strict;">
        <!-- LAYER 1: DYNAMIC BLURRED ALBUM ARTWORK WALLPAPER -->
        <div class="absolute inset-0 bg-cover bg-center filter blur-3xl opacity-35 scale-110 transition-all duration-1000"
             :style="nowPlaying && nowPlaying.thumbnail_url ? 'background-image: url(' + nowPlaying.thumbnail_url + ');' : ''">
        </div>

        <!-- LAYER 2: DEEP AMBIENT RADIAL VIGNETTE -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(20,14,10,0.6)_0%,rgba(10,7,5,0.97)_100%)]"></div>

        <!-- LAYER 3: FLOATING GOLDEN COFFEE STEAM PARTICLES -->
        <canvas id="steam-canvas" class="absolute inset-0 w-full h-full block"></canvas>

        <!-- LAYER 4: AMBIENT PULSING GLOW ORBS -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#D9973E]/15 rounded-full blur-[120px] animate-pulse-glow"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-[#5F7F42]/15 rounded-full blur-[120px] animate-pulse-glow" style="animation-delay: 4s;"></div>
    </div>

    <!-- ADZAN RESPECT BANNER OVERLAY (SURABAYA & SIDOARJO) -->
    <div x-show="isAdzanMode"
         x-cloak
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0 -translate-y-8 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-8 scale-95"
         class="fixed top-6 left-1/2 -translate-x-1/2 z-50 pointer-events-none max-w-xl w-[calc(100%-2rem)] px-4 select-none">
        <div class="w-full bg-[#18120C]/95 border-2 border-[#D9973E] rounded-2xl p-4 shadow-[0_20px_50px_rgba(0,0,0,0.85)] backdrop-blur-xl flex items-center justify-between gap-4">
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="w-12 h-12 rounded-xl bg-[#D9973E]/20 border border-[#D9973E] flex items-center justify-center shrink-0 shadow-inner">
                    <span class="text-2xl animate-pulse">🕌</span>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-[#D9973E] text-[#140E0A] font-mono text-[10px] font-extrabold uppercase tracking-wider">
                            Waktu Adzan
                        </span>
                        <span class="text-[11px] font-mono text-[#A89A85]">Surabaya & Sidoarjo</span>
                    </div>
                    <h3 class="text-base sm:text-lg font-serif font-bold text-white mt-0.5 truncate">
                        Memasuki Waktu Sholat <span class="text-[#D9973E]" x-text="adzanPrayerName"></span>
                    </h3>
                    <p class="text-xs text-[#C4B6A3] font-sans">
                        Volume musik otomatis diturunkan untuk menghormati adzan.
                    </p>
                </div>
            </div>
            <div class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-black/40 border border-[#3A2C20]">
                <span class="w-2 h-2 rounded-full bg-[#D9973E] animate-ping"></span>
                <span class="font-mono text-[10px] text-[#D9973E] font-bold uppercase">Hening Adzan</span>
            </div>
        </div>
    </div>

    <!-- CARD FLY: FLOATING READY ORDERS NOTIFICATION OVERLAY -->
    <div class="fixed bottom-6 sm:bottom-8 right-6 sm:right-8 z-50 flex flex-col gap-3 max-w-sm sm:max-w-md w-[calc(100%-3rem)] pointer-events-none">
        <template x-for="(order, idx) in activeFlyingCards" :key="order.id">
            <div class="pointer-events-auto bg-gradient-to-r from-[#172211]/95 via-[#1E2E17]/95 to-[#172211]/95 border-2 border-[#5F7F42] rounded-2xl p-4 sm:p-5 text-[#FAF7F2] shadow-2xl backdrop-blur-xl animate-card-fly card-fly-glow relative overflow-hidden transition-all duration-300"
                 :style="'animation-delay: ' + (idx * 120) + 'ms;'">

                <!-- Top Animated Accent Bar -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#5F7F42] via-[#D9973E] to-[#5F7F42] animate-pulse"></div>

                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-12 h-12 rounded-xl bg-[#5F7F42]/25 border border-[#5F7F42] flex items-center justify-center shrink-0 relative shadow-inner">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#5F7F42] absolute -top-1 -right-1 animate-ping"></span>
                            <span class="text-2xl animate-bounce">🔔</span>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-md bg-[#5F7F42] text-[#140E0A] font-bold"
                                      x-text="order.isRecall ? 'PANGGILAN ULANG' : 'PESANAN SUDAH SIAP'"></span>
                                <span class="text-[10px] font-mono text-[#A89A85]" x-text="order.elapsed_minutes ? (order.elapsed_minutes + ' mnt') : 'Baru saja'"></span>
                            </div>

                            <h4 class="text-lg sm:text-xl font-bold font-serif text-white mt-1 leading-snug truncate"
                                x-text="order.customer_name ? ('Kak ' + order.customer_name) : 'Pelanggan'"></h4>

                            <div class="text-xs text-[#85BF5C] font-mono font-bold mt-0.5">
                                Kode Tiket: <span class="text-[#D9973E] tracking-wider font-bold" x-text="order.code"></span>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="dismissFlyingCard(order.uniqueKey)"
                            class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 text-[#A89A85] hover:text-white flex items-center justify-center text-sm transition shrink-0 cursor-pointer">
                        ✕
                    </button>
                </div>

                <div class="mt-3 pt-2.5 border-t border-[#3A3026]/80 flex items-center justify-between text-xs text-[#E4DCCC]">
                    <span class="flex items-center gap-1.5 font-medium">
                        <span>☕</span>
                        <span>Silakan ambil di <strong>Meja Kasir</strong> sekarang</span>
                    </span>
                    <span class="text-[10px] text-[#A89A85] font-mono uppercase font-bold">Terima Kasih</span>
                </div>
            </div>
        </template>
    </div>

    <!-- CONTENT WRAPPER -->
    <div class="w-full h-full min-h-screen max-h-screen flex flex-col justify-between p-3 sm:p-5 lg:p-6 xl:p-8 relative z-10 box-border overflow-hidden">

        <!-- 1. TOP BAR -->
        <header class="w-full flex items-center justify-between border-b border-[#32261C] pb-3.5 shrink-0">
            <!-- Brand & Status -->
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/logo-light.svg') }}" alt="{{ config('cafe.name') }}" class="h-10 sm:h-12 w-auto shrink-0 drop-shadow-md">
                <div class="border-l border-[#32261C] pl-3.5">
                    <div class="font-mono text-xs sm:text-sm uppercase tracking-[0.22em] text-[#D9973E] flex items-center gap-2 font-bold">
                        <span>{{ config('cafe.name') }} SOUNDSTATION</span>
                        <span class="w-2 h-2 rounded-full bg-[#5F7F42] animate-pulse"></span>
                    </div>
                    <div class="text-xs text-[#A89A85] font-sans">{{ config('cafe.tagline') }}</div>
                </div>
            </div>

            <!-- Ready Orders Quick Pill Banner -->
            <template x-if="readyOrders.length > 0">
                <button type="button" @click="showAllReadyCards()"
                        class="hidden md:flex items-center gap-2 px-4 py-1.5 bg-[#5F7F42]/15 border border-[#5F7F42]/40 rounded-full cursor-pointer transition hover:bg-[#5F7F42]/25 shadow-sm active:scale-95"
                        title="Klik untuk memunculkan Card Fly pesanan siap">
                    <span class="w-2 h-2 rounded-full bg-[#5F7F42] animate-ping"></span>
                    <span class="font-mono text-xs text-[#85BF5C] font-bold" x-text="readyOrders.length + ' Pesanan Siap Diambil ↗'"></span>
                </button>
            </template>

            <!-- Mode Switcher, Sound Toggle, Fullscreen & Real-time Clock -->
            <div class="flex items-center gap-2.5 sm:gap-3">
                <!-- Toggle Mode: Visualizer vs Video -->
                <button type="button" @click="toggleDisplayMode()"
                        class="px-3 py-1.5 bg-[#261D16] hover:bg-[#32261C] border border-[#3A2D22] hover:border-[#D9973E] text-[#D9973E] font-mono text-xs uppercase tracking-wider transition rounded-xl flex items-center gap-1.5 shadow-sm active:scale-95 cursor-pointer"
                        :title="displayMode === 'visualizer' ? 'Beralih ke Tampilan Video YouTube' : 'Beralih ke Tampilan Vinyl Visualizer'">
                    <span x-show="displayMode === 'visualizer'" class="flex items-center gap-1.5">
                        <span>🎬</span>
                        <span class="hidden sm:inline font-bold">Video</span>
                    </span>
                    <span x-show="displayMode === 'video'" class="flex items-center gap-1.5">
                        <span>🎨</span>
                        <span class="hidden sm:inline font-bold">Vinyl</span>
                    </span>
                </button>

                <!-- Toggle Suara Notifikasi TV -->
                <button type="button" @click="toggleTvSound()"
                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-[#261D16] hover:bg-[#32261C] border border-[#3A2D22] hover:border-[#D9973E] text-[#D9973E] transition shadow-sm active:scale-95 cursor-pointer"
                        :title="tvSoundEnabled ? 'Suara Notifikasi TV: AKTIF (Klik untuk Heningkan)' : 'Suara Notifikasi TV: HENING (Klik untuk Aktifkan)'">
                    <span class="text-sm" x-text="tvSoundEnabled ? '🔔' : '🔕'"></span>
                </button>

                <!-- Toggle Fullscreen -->
                <button type="button" @click="toggleFullscreen()"
                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-[#261D16] hover:bg-[#32261C] border border-[#3A2D22] hover:border-[#D9973E] text-[#D9973E] transition shadow-sm active:scale-95 cursor-pointer"
                        :title="isFullscreen ? 'Keluar Layar Penuh (Esc)' : 'Layar Penuh (F11)'">
                    <svg x-show="!isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <svg x-show="isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v4m0 0H5m4 0L4 2m11 1v4m0 0h4m-4 0l5-5M9 21v-4m0 0H5m4 0l-5 5m11-1v-4m0 0h4m-4 0l5 5" />
                    </svg>
                </button>

                <!-- Real-time Clock -->
                <div class="font-mono text-2xl sm:text-3xl font-bold text-[#FAF7F2] tracking-wider ml-1" x-text="currentTime"></div>
            </div>
        </header>

        <!-- 2. MAIN STAGE (2 COLS: LEFT = NOW PLAYING HERO / VIDEO, RIGHT = LIVE ORDERS & QUEUE) -->
        <main class="w-full flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 lg:gap-8 items-stretch my-auto py-1.5 sm:py-2.5 overflow-hidden">

            <!-- LEFT COL: NOW PLAYING HERO (7 COLS) -->
            <div class="lg:col-span-7 xl:col-span-8 flex flex-col h-full min-h-0 justify-center">

                <!-- 1. MODE VISUALIZER: 3D VINYL TURNTABLE & SPECTRUM EQUALIZER -->
                <div x-show="displayMode === 'visualizer'" class="flex flex-col sm:flex-row items-center gap-6 sm:gap-8 lg:gap-10 h-full justify-center">
                    <!-- VINYL RECORD TURNTABLE WITH TONEARM -->
                    <div class="relative shrink-0 w-56 h-56 sm:w-64 sm:h-64 md:w-72 md:h-72 lg:w-80 lg:h-80 xl:w-96 xl:h-96">
                        <!-- Vinyl Turntable Base Shadow & Ring -->
                        <div class="w-full h-full rounded-full bg-gradient-to-tr from-[#120D09] via-[#221711] to-[#120D09] border-4 border-[#3A2D22] shadow-[0_20px_60px_rgba(0,0,0,0.85)] flex items-center justify-center p-3 relative overflow-hidden"
                             :class="isPlaying ? 'animate-spin-slow' : ''">

                            <!-- VINYL GROOVES (CONCENTRIC CIRCLES) -->
                            <div class="w-full h-full rounded-full border border-dashed border-[#443527]/80 flex items-center justify-center p-3.5 sm:p-4">
                                <div class="w-full h-full rounded-full border border-[#3A2D22] flex items-center justify-center p-3.5 sm:p-4">
                                    <div class="w-full h-full rounded-full border border-dashed border-[#554637]/70 flex items-center justify-center p-4 sm:p-5">
                                        <!-- CENTER ALBUM COVER LABEL -->
                                        <div class="w-full h-full rounded-full border-2 border-[#D9973E]/60 overflow-hidden flex items-center justify-center bg-[#140E0A] shadow-inner relative">
                                            <template x-if="nowPlaying && nowPlaying.thumbnail_url">
                                                <img :src="nowPlaying.thumbnail_url" alt="Cover" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!nowPlaying || !nowPlaying.thumbnail_url">
                                                <div class="text-3xl font-serif text-[#D9973E]">☕</div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SHINY LIGHT REFLECTION SWEEP -->
                            <div class="absolute inset-0 shine-reflection rounded-full pointer-events-none"></div>
                        </div>

                        <!-- CENTER METALLIC PIN -->
                        <div class="absolute inset-0 m-auto w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gradient-to-tr from-[#D9973E] via-white to-[#D9973E] border-2 border-[#140E0A] shadow-md pointer-events-none z-10"></div>

                        <!-- STYLISH TONEARM (METALLIC NEEDLE ARM RESTING ON RECORD) -->
                        <div class="absolute top-2 right-2 sm:top-3 sm:right-3 w-20 sm:w-24 h-36 sm:h-44 pointer-events-none z-20 transition-transform duration-700 ease-out origin-top-right"
                             :class="isPlaying ? 'rotate-[22deg]' : 'rotate-[0deg]'">
                            <!-- Pivot Base -->
                            <div class="absolute top-0 right-0 w-6 h-6 rounded-full bg-gradient-to-tr from-[#3A2D22] via-[#8A7B66] to-[#E4DCCC] border border-[#140E0A] shadow-md"></div>
                            <!-- Arm Bar -->
                            <div class="absolute top-4 right-2.5 w-1 h-28 sm:h-32 bg-gradient-to-b from-[#8A7B66] via-[#D9973E] to-[#A89A85] rounded-full shadow-sm"></div>
                            <!-- Cartridge Head & Stylus -->
                            <div class="absolute bottom-2 sm:bottom-4 right-0 w-4 h-7 bg-[#1F1812] border border-[#D9973E] rounded-xs shadow-md rotate-[-15deg] flex items-center justify-center">
                                <span class="w-1 h-2 bg-[#D9973E] rounded-full"></span>
                            </div>
                        </div>
                    </div>

                    <!-- NOW PLAYING METADATA -->
                    <div class="min-w-0 text-center sm:text-left flex-1 w-full max-w-xl xl:max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#D9973E]/15 border border-[#D9973E]/40 text-[#D9973E] font-mono text-xs uppercase tracking-[0.2em] mb-2.5 rounded-full font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#D9973E] animate-ping"></span>
                            <span x-text="isPlaying ? 'SEDANG MEMUTAR' : 'AUDIO TERJEDA'"></span>
                        </div>

                        <h2 class="text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-serif font-bold text-[#FAF7F2] leading-tight tracking-tight line-clamp-2 drop-shadow-md"
                            x-text="nowPlaying ? (nowPlaying.song_title || nowPlaying.title) : 'Playlist Kafe KopiKita'">
                        </h2>

                        <p class="text-base sm:text-lg lg:text-xl text-[#D9973E] mt-1.5 font-mono font-medium truncate"
                           x-text="nowPlaying ? (nowPlaying.artist || 'Artis Musik') : 'Chill Lo-Fi & Jazz Vibes'">
                        </p>

                        <!-- TIMELINE PROGRESS BAR -->
                        <div class="mt-4 w-full">
                            <div class="w-full bg-[#261D16] h-2.5 rounded-full overflow-hidden border border-[#3A2D22]">
                                <div class="bg-gradient-to-r from-[#D9973E] via-[#E5A955] to-[#5F7F42] h-full transition-all duration-300 rounded-full shadow-[0_0_12px_rgba(217,151,62,0.6)]"
                                     :style="'width: ' + playbackProgressPercent + '%'"></div>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between font-mono text-xs text-[#A89A85]">
                                <span class="text-[#D9973E] font-bold" x-text="playbackCurrentTimeFormatted">00:00</span>
                                <span class="text-[10px] text-[#8A7B66] uppercase tracking-wider font-semibold">// Live Sync Player</span>
                                <span class="text-[#FAF7F2] font-semibold" x-text="playbackDurationFormatted">00:00</span>
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
                            <div class="mt-3.5 inline-flex items-center gap-2 px-3.5 py-1.5 bg-[#261D16] border border-[#D9973E]/40 rounded-xl shadow-xs">
                                <span class="font-mono text-xs text-[#A89A85] uppercase tracking-wider">Direquest oleh:</span>
                                <span class="font-mono text-sm font-bold text-[#D9973E]" x-text="'★ Kak ' + nowPlaying.customer_name"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 2. MODE VIDEO: CINEMATIC YOUTUBE PLAYER SCREEN (STREAM-ONLY DI DALAM FRAME DENGAN 100% ZOOM) -->
                <div x-show="displayMode === 'video'" class="w-full h-full flex-1 min-h-0 flex flex-col justify-center select-none">
                    <div class="w-full h-full min-h-0 rounded-2xl overflow-hidden border-2 border-[#3A2D22] shadow-[0_20px_60px_rgba(0,0,0,0.9)] bg-black relative select-none cursor-default flex items-center justify-center">
                        <div class="w-full h-full relative select-none">
                            <div id="tv-player-wrap" class="w-full h-full pointer-events-none select-none" x-show="nowPlaying && nowPlaying.youtube_id">
                                <div id="tv-yt-player" class="w-full h-full pointer-events-none select-none"></div>
                            </div>

                            <template x-if="!nowPlaying || !nowPlaying.youtube_id">
                                <div class="w-full h-full flex flex-col items-center justify-center bg-[#140E0A] text-[#A89A85] select-none pointer-events-none">
                                    <span class="text-5xl mb-2 text-[#D9973E]">🎬</span>
                                    <span class="font-mono text-xs">Memuat tayangan video...</span>
                                </div>
                            </template>

                            <!-- OVERLAY BOTTOM INSIDE VIDEO FRAME (NOW PLAYING TITLE & LIVE SYNC PROGRESS BAR WITH 75% TRANSPARENCY & SUBTLE BLUR) -->
                            <div class="absolute bottom-0 inset-x-0 z-20 bg-[#120D09]/25 backdrop-blur-md border-t border-white/10 px-4 py-3 sm:px-5 sm:py-3.5 flex flex-col gap-1.5 pointer-events-none select-none">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0 flex-1 flex items-center gap-2.5">
                                        <span class="w-2 h-2 rounded-full bg-[#D9973E] shrink-0" :class="isPlaying ? 'animate-ping' : 'opacity-40'"></span>
                                        <div class="min-w-0 flex-1">
                                            <h2 class="text-sm sm:text-base font-serif font-bold text-[#FAF7F2] truncate drop-shadow-md leading-tight"
                                                x-text="nowPlaying ? (nowPlaying.song_title || nowPlaying.title) : 'Playlist Kafe KopiKita'"></h2>
                                            <p class="text-xs text-[#D9973E] font-mono truncate mt-0.5"
                                               x-text="nowPlaying ? (nowPlaying.artist || 'Artis Musik') : 'Chill Lo-Fi & Jazz Vibes'"></p>
                                        </div>
                                    </div>
                                    <template x-if="nowPlaying && nowPlaying.customer_name">
                                        <div class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-[#D9973E]/20 border border-[#D9973E]/40 rounded-lg shrink-0">
                                            <span class="text-[10px] font-mono text-[#D9973E] font-semibold" x-text="'★ Kak ' + nowPlaying.customer_name"></span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Timeline Progress Bar in Overlay -->
                                <div class="w-full flex items-center gap-2.5 pt-0.5">
                                    <span class="font-mono text-[11px] text-[#D9973E] font-bold shrink-0" x-text="playbackCurrentTimeFormatted">00:00</span>
                                    <div class="w-full bg-white/20 h-1.5 rounded-full overflow-hidden backdrop-blur-xs">
                                        <div class="bg-gradient-to-r from-[#D9973E] via-[#E5A955] to-[#5F7F42] h-full transition-all duration-300 rounded-full shadow-[0_0_10px_rgba(217,151,62,0.8)]"
                                             :style="'width: ' + playbackProgressPercent + '%'"></div>
                                    </div>
                                    <span class="font-mono text-[11px] text-[#FAF7F2] font-semibold shrink-0" x-text="playbackDurationFormatted">00:00</span>
                                </div>
                            </div>

                            <!-- OVERLAY INDIKATOR KETIKA MUSIK TERJEDA (PAUSED) DI DALAM FRAME VIDEO -->
                            <div x-show="!isPlaying && nowPlaying"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute inset-0 z-22 bg-black/60 backdrop-blur-xs flex flex-col items-center justify-center pointer-events-none select-none text-center p-4">
                                <div class="px-5 py-3.5 bg-[#1C1611]/95 border-2 border-[#D9973E]/60 rounded-2xl text-[#FAF7F2] shadow-[0_12px_40px_rgba(0,0,0,0.85)] backdrop-blur-md flex items-center gap-3.5 animate-pulse">
                                    <div class="w-10 h-10 rounded-xl bg-[#D9973E]/20 border border-[#D9973E]/50 flex items-center justify-center text-[#D9973E] text-lg shrink-0 shadow-inner">
                                        ⏸
                                    </div>
                                    <div class="text-left min-w-0">
                                        <div class="font-mono text-xs font-bold text-[#D9973E] uppercase tracking-wider flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-[#D9973E] animate-ping"></span>
                                            <span>Audio & Video Terjeda</span>
                                        </div>
                                        <div class="text-[11px] text-[#C4B6A3] font-mono mt-0.5 truncate">
                                            Pemutaran dijeda sementara di stasiun kasir
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- END-SCREEN CURTAIN: Menutup 100% kartu/grid rekomendasi YouTube di detik-detik akhir -->
                            <div x-show="isNearTrackEnd"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-700"
                                 x-transition:enter-start="opacity-0 scale-98"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-500"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 class="absolute inset-0 z-30 flex flex-col items-center justify-center bg-[#0E0906]/95 backdrop-blur-2xl text-center p-6 select-none pointer-events-none">
                                <div class="w-24 h-24 rounded-full bg-[#D9973E]/15 blur-2xl absolute animate-pulse"></div>
                                <div class="relative z-10 flex flex-col items-center max-w-md">
                                    <div class="w-12 h-12 rounded-xl bg-[#1C1611] border border-[#D9973E]/40 flex items-center justify-center shadow-xl mb-2.5">
                                        <span class="text-2xl animate-spin-slow">☕</span>
                                    </div>
                                    <div class="inline-flex items-center gap-1.5 px-3 py-0.5 bg-[#D9973E]/15 border border-[#D9973E]/40 rounded-full mb-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#D9973E] animate-ping"></span>
                                        <span class="font-mono text-[10px] font-bold text-[#D9973E] uppercase tracking-widest">Menyiapkan Lagu Berikutnya</span>
                                    </div>
                                    <template x-if="queue && queue.length > 0">
                                        <div class="mt-0.5">
                                            <div class="font-serif text-lg font-bold text-[#FAF7F2] truncate max-w-sm drop-shadow-md" x-text="queue[0].song_title || queue[0].title"></div>
                                            <div class="font-mono text-xs text-[#D9973E] mt-0.5" x-text="queue[0].artist || 'Artis Musik'"></div>
                                        </div>
                                    </template>
                                    <template x-if="!queue || queue.length === 0">
                                        <div class="mt-0.5 font-serif text-sm text-[#A89A85]">
                                            Playlist Santai Kafe KopiKita
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- SHIELD PELINDUNG TRANSPARAN: Memblokir 100% interaksi mouse/touch/klik agar murni stream pasif dari kasir -->
                            <div class="video-shield absolute inset-0 z-25 cursor-default select-none"
                                 @click.prevent.stop
                                 @dblclick.prevent.stop
                                 @mousedown.prevent.stop
                                 @mouseup.prevent.stop
                                 @contextmenu.prevent.stop
                                 @touchstart.prevent.stop
                                 @touchend.prevent.stop
                                 title=""></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COL: LIVE READY ORDERS & UP NEXT QUEUE (5 COLS) -->
            <div class="lg:col-span-5 xl:col-span-4 w-full bg-[#1C1611]/95 border border-[#32261C] rounded-2xl p-4 sm:p-5 shadow-2xl backdrop-blur-xl flex flex-col h-full min-h-0 max-h-full justify-between overflow-hidden">

                <!-- TOP SECTION: PESANAN SIAP (TAMPIL JIKA ADA PESANAN SIAP) -->
                <template x-if="readyOrders.length > 0">
                    <div class="mb-3 sm:mb-4 bg-gradient-to-r from-[#1E2E17] to-[#142010] border-2 border-[#5F7F42] rounded-xl p-3 sm:p-3.5 shadow-md shrink-0">
                        <div class="flex items-center justify-between mb-2 sm:mb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#85BF5C] animate-ping"></span>
                                <span class="font-mono text-xs font-bold text-[#85BF5C] uppercase tracking-wider">🔔 Pesanan Siap Di Meja</span>
                            </div>
                            <span class="font-mono text-[10px] font-bold text-[#FAF7F2] bg-[#5F7F42]/40 px-2.5 py-0.5 rounded-full" x-text="readyOrders.length + ' Pesanan'"></span>
                        </div>
                        <div class="flex flex-wrap gap-2 max-h-20 sm:max-h-24 overflow-y-auto no-scrollbar">
                            <template x-for="ro in readyOrders" :key="ro.id">
                                <div class="px-3 py-1.5 bg-[#25391C] border border-[#5F7F42]/80 rounded-lg text-xs font-mono text-white flex items-center gap-2 shadow-sm">
                                    <span class="font-bold text-[#D9973E] text-sm" x-text="ro.code"></span>
                                    <span class="text-white/90 font-medium" x-text="ro.customer_name ? ('(' + ro.customer_name + ')') : ''"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- QUEUE SECTION HEADER -->
                <div class="flex items-center justify-between border-b border-[#32261C] pb-2.5 sm:pb-3 mb-2.5 sm:mb-3 shrink-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#D9973E] animate-pulse shrink-0"></span>
                        <h3 class="font-mono text-xs sm:text-sm uppercase tracking-[0.15em] font-bold text-[#FAF7F2] truncate">Antrean Lagu Berikutnya</h3>
                    </div>

                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#D9973E]/15 border border-[#D9973E]/30 text-[#D9973E] shrink-0 ml-2"
                          x-text="queue.length + ' Lagu'"></span>
                </div>

                <!-- QUEUE LIST (EXPANDABLE SCROLLABLE AREA) -->
                <div class="space-y-1.5 sm:space-y-2 overflow-y-auto pr-0 flex-1 min-h-0 no-scrollbar">
                    <template x-if="queue.length === 0">
                        <div class="h-full flex flex-col items-center justify-center text-center py-8 text-[#A89A85] font-mono text-xs">
                            <span class="text-3xl mb-2 opacity-60">☕</span>
                            <span class="font-semibold text-[#FAF7F2]">Antrean request lagu sedang kosong.</span>
                            <span class="text-[11px] mt-1 text-[#8A7B66]">Scan QR di bawah untuk me-request lagu pertamamu!</span>
                        </div>
                    </template>

                    <template x-for="(item, index) in queue" :key="item.id + '_' + (item.type || 'req')">
                        <div class="flex items-center justify-between py-1.5 sm:py-2 px-2.5 sm:px-3 bg-[#261D16]/90 border border-[#3A2D22] rounded-xl hover:border-[#D9973E]/50 transition group">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <span class="font-mono font-bold text-[#D9973E] text-xs w-4 text-center shrink-0" x-text="'#' + (index + 1)"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-semibold text-[#FAF7F2] truncate leading-tight group-hover:text-[#D9973E] transition-colors" x-text="item.song_title || item.title"></div>
                                    <div class="text-[10px] text-[#A89A85] truncate leading-tight mt-0.5 flex items-center gap-1.5 font-mono">
                                        <span x-text="item.artist || 'Artis YouTube'"></span>
                                        <template x-if="item.customer_name">
                                            <span class="flex items-center gap-1">
                                                <span class="text-[#8A7B66]">&bull;</span>
                                                <span class="text-[#D9973E] font-semibold truncate" x-text="'Req: ' + item.customer_name"></span>
                                            </span>
                                        </template>
                                        <template x-if="!item.customer_name && (item.type === 'default' || !item.is_request)">
                                            <span class="flex items-center gap-1">
                                                <span class="text-[#8A7B66]">&bull;</span>
                                                <span class="text-[#85BF5C]">Playlist Kafe</span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <!-- BADGE: REQUEST vs BAWAAN -->
                            <template x-if="item.type === 'request' || item.is_request">
                                <span class="font-mono text-[9px] uppercase tracking-wider text-[#D9973E] bg-[#D9973E]/15 px-2 py-0.5 rounded-full border border-[#D9973E]/40 font-bold shrink-0 ml-2">Request</span>
                            </template>
                            <template x-if="item.type === 'default' || !item.is_request">
                                <span class="font-mono text-[9px] uppercase tracking-wider text-[#85BF5C] bg-[#5F7F42]/20 px-2 py-0.5 rounded-full border border-[#5F7F42]/40 font-bold shrink-0 ml-2">Bawaan</span>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- SUBTLE CARD FOOTNOTE -->
                <div class="mt-2.5 sm:mt-3.5 pt-2 sm:pt-2.5 border-t border-[#32261C] flex items-center justify-between text-[11px] font-mono text-[#8A7B66] shrink-0">
                    <span>* Putar bergilir otomatis</span>
                    <span class="text-[#D9973E] font-semibold">Auto-skip jika diblokir</span>
                </div>
            </div>
        </main>

        <!-- 3. FOOTER -->
        <footer class="w-full border-t border-[#32261C] pt-3.5 flex flex-col sm:flex-row items-center justify-between gap-4 sm:gap-6 shrink-0">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-white border border-[#E4DCCC] rounded-xl shrink-0 shadow-lg">
                    <img src="{{ \App\Support\QrCode::dataUri(route('music.request'), 150) }}" width="68" height="68" alt="QR Request Musik">
                </div>
                <div>
                    <div class="font-serif font-bold text-base sm:text-lg text-[#FAF7F2]">Punya Struk Belanja?</div>
                    <div class="text-xs text-[#A89A85] max-w-sm mt-0.5">
                        Scan QR di samping untuk me-request lagu favoritmu langsung dari meja. Lagu akan diputar otomatis setelah lagu saat ini selesai!
                    </div>
                </div>
            </div>

            <div class="font-mono text-xs text-[#A89A85] uppercase tracking-widest text-right">
                <div class="text-[#D9973E] font-bold">{{ config('cafe.name') }} AUDIO & ORDER STREAM</div>
                <div class="text-[10px] text-[#8A7B66] mt-0.5">Precision Jukebox & Kitchen Announcer Engine</div>
            </div>
        </footer>

    </div>
</body>
</html>
