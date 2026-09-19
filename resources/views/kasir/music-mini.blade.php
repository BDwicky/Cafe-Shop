<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sound Station Mini — {{ config('cafe.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#140E0A] text-[#F7F3EC] h-full overflow-hidden antialiased font-sans select-none flex flex-col justify-between p-4"
      x-data="soundStationMini()"
      x-init="init()">

    <!-- HEADER MINI -->
    <div class="flex items-center justify-between border-b border-[#3A3026] pb-3 shrink-0">
        <div class="flex items-center gap-2">
            <!-- Animated Equalizer Bars -->
            <div class="flex items-end gap-0.5 h-3 w-3 shrink-0">
                <span class="w-0.5 bg-[#D9973E] rounded-full transition-all duration-150"
                      :class="isPlaying ? 'h-3 animate-pulse' : 'h-1'"></span>
                <span class="w-0.5 bg-[#D9973E] rounded-full transition-all duration-150 delay-75"
                      :class="isPlaying ? 'h-2 animate-pulse' : 'h-1.5'"></span>
                <span class="w-0.5 bg-[#D9973E] rounded-full transition-all duration-150 delay-150"
                      :class="isPlaying ? 'h-3 animate-pulse' : 'h-1'"></span>
            </div>
            <span class="font-serif font-bold text-sm tracking-tight text-[#F7F3EC]">Sound Station Mini</span>
            <span class="font-mono text-[9px] px-1.5 py-0.2 bg-[#5F7F42]/20 text-[#5F7F42] border border-[#5F7F42]/30 rounded">
                Sync Live
            </span>
        </div>
        <span class="font-mono text-[10px] text-[#A89A85]" x-text="queueCount + ' Req'"></span>
    </div>

    <!-- CURRENT TRACK CARD -->
    <div class="my-3 p-3 bg-[#1F1812] border border-[#3A3026] shadow flex items-center gap-3">
        <!-- Thumbnail / Disc -->
        <div class="w-12 h-12 bg-black border border-[#3A3026] shrink-0 overflow-hidden flex items-center justify-center relative">
            <template x-if="currentTrack && currentTrack.thumbnail_url">
                <img :src="currentTrack.thumbnail_url" alt="Thumb" class="w-full h-full object-cover">
            </template>
            <template x-if="!currentTrack || !currentTrack.thumbnail_url">
                <span class="text-xl text-[#D9973E]" :class="isPlaying ? 'animate-spin' : ''">♫</span>
            </template>
        </div>

        <div class="min-w-0 flex-1">
            <div class="text-xs font-bold text-[#F7F3EC] truncate"
                 x-text="currentTrack ? currentTrack.title : 'Memuat Musik Kafe...'"></div>
            <div class="text-[10px] text-[#A89A85] truncate mt-0.5"
                 x-text="currentTrack ? (currentTrack.artist || 'Playlist Kafe') : '-'"></div>
            <template x-if="currentTrack && currentTrack.customer_name">
                <div class="text-[9px] font-mono text-[#D9973E] truncate mt-0.5"
                     x-text="'★ Request: ' + currentTrack.customer_name"></div>
            </template>
        </div>
    </div>

    <!-- TIMELINE PROGRESS BAR -->
    <div class="mb-3">
        <div class="w-full bg-[#2A211A] h-1.5 rounded-full overflow-hidden cursor-pointer"
             @click="seekFromBar($event)"
             :title="isLive ? 'Siaran Langsung Radio 24/7' : 'Klik untuk melompat ke durasi lagu'">
            <template x-if="!isLive">
                <div class="bg-[#D9973E] h-full transition-all duration-300"
                     :style="'width: ' + Math.min(100, Math.max(0, progressPercent)) + '%'"></div>
            </template>
            <template x-if="isLive">
                <div class="w-full h-full bg-gradient-to-r from-[#D9973E] via-red-500 to-[#D9973E] animate-pulse"></div>
            </template>
        </div>
        <div class="mt-1 flex items-center justify-between font-mono text-[9px] text-[#7A6A58]">
            <div class="flex items-center gap-1">
                <template x-if="isLive">
                    <span class="inline-flex items-center gap-0.5 text-red-400 font-bold">
                        <span class="w-1 h-1 rounded-full bg-red-500 animate-ping"></span> LIVE
                    </span>
                </template>
                <span x-text="currentTimeFormatted">00:00</span>
            </div>
            <span x-text="durationFormatted">00:00</span>
        </div>
    </div>

    <!-- AUDIO CONTROLS ROW -->
    <div class="flex items-center justify-between gap-3 bg-[#1F1812] border border-[#2A211A] p-3 shrink-0">
        <div class="flex items-center gap-2">
            <!-- PLAY/PAUSE -->
            <button type="button"
                    @click="togglePlayPause()"
                    :title="isPlaying ? 'Jeda Lagu' : 'Putar Lagu'"
                    class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs transition-all duration-150 shadow active:scale-90 cursor-pointer"
                    :class="isPlaying
                        ? 'bg-[#D9973E] text-[#140E0A] shadow-[0_0_12px_rgba(217,151,62,0.45)]'
                        : 'bg-[#2A211A] text-[#D9973E] border border-[#D9973E]/60 hover:bg-[#D9973E] hover:text-[#140E0A]'">
                <svg x-show="isPlaying" class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                    <rect x="6" y="4" width="4" height="16" rx="1"/>
                    <rect x="14" y="4" width="4" height="16" rx="1"/>
                </svg>
                <svg x-show="!isPlaying" class="w-3.5 h-3.5 fill-current ml-0.5" viewBox="0 0 24 24">
                    <path d="M8 5.14v14.72a1 1 0 001.5.86l11.5-7.36a1 1 0 000-1.72L9.5 4.28A1 1 0 008 5.14z"/>
                </svg>
            </button>

            <!-- SKIP -->
            <button type="button"
                    @click="skipTrack()"
                    title="Lewati ke lagu berikutnya"
                    class="w-8 h-8 rounded-full bg-[#2A211A] hover:bg-[#3A3026] text-[#A89A85] hover:text-[#D9973E] border border-[#3A3026] hover:border-[#D9973E]/40 flex items-center justify-center transition-all duration-150 active:scale-90 cursor-pointer">
                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                    <path d="M5.5 4.5v15a1 1 0 001.5.86l9-7.5a1 1 0 000-1.72l-9-7.5a1 1 0 00-1.5.86zM18 4.5a1 1 0 00-1 1v13a1 1 0 102 0v-13a1 1 0 00-1-1z"/>
                </svg>
            </button>
        </div>

        <!-- VOLUME & MUTE -->
        <div class="flex items-center gap-1.5">
            <button type="button" @click="toggleMute()" class="text-xs text-[#A89A85] hover:text-[#F7F3EC] p-0.5">
                <span x-show="!isMuted && volume > 30">🔊</span>
                <span x-show="!isMuted && volume <= 30 && volume > 0">🔉</span>
                <span x-show="isMuted || volume === 0">🔇</span>
            </button>
            <input type="range" min="0" max="100"
                   x-model="volume"
                   @input="changeVolume($event.target.value)"
                   class="w-16 h-1 bg-[#2A211A] accent-[#D9973E] cursor-pointer">
            <span class="font-mono text-[9px] text-[#A89A85] w-5 text-right" x-text="volume + '%'"></span>
        </div>
    </div>

    <!-- ANNOUNCER STATUS ALERT -->
    <div x-show="isAnnouncing"
         class="my-2 py-1 px-2 bg-[#D9973E]/15 border border-[#D9973E]/40 text-[#D9973E] text-[10px] font-mono flex items-center gap-1.5 animate-pulse">
        <span>📢</span>
        <span class="truncate">Memanggil pesanan siap di kasir...</span>
    </div>

    <!-- FOOTER INFO -->
    <div class="border-t border-[#3A3026] pt-2 flex items-center justify-between text-[9px] font-mono text-[#7A6A58] shrink-0">
        <span>Sinkron dengan Layar POS & Navbar</span>
        <a href="{{ route('kasir.music.index') }}" target="_blank" class="text-[#D9973E] hover:underline">Buka Lengkap ↗</a>
    </div>

    <!-- HIDDEN STANDALONE FALLBACK PLAYER -->
    <div style="position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; overflow: hidden; z-index: -9999;">
        <div id="yt-mini-player"></div>
    </div>

    <script>
    // GLOBAL SOUNDSTATION HUB (CLIENT LISTENER & REMOTE CONTROLLER)
    (function() {
        if (window.SoundStationHub) return;
        const channel = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('cafe_soundstation_sync') : null;
        window.SoundStationHub = {
            channel: channel,
            tabId: 'mini_' + Math.random().toString(36).substring(2, 9) + '_' + Date.now(),
            state: {
                isPlaying: false,
                currentTrack: null,
                currentTime: 0,
                duration: 0,
                progressPercent: 0,
                currentTimeFormatted: '00:00',
                durationFormatted: '00:00',
                volume: (() => {
                    const local = localStorage.getItem('pos_music_volume');
                    if (local !== null && !isNaN(parseInt(local))) {
                        return Math.max(0, Math.min(100, parseInt(local)));
                    }
                    return {{ (int) (\Illuminate\Support\Facades\Cache::get('soundstation_playback_volume', 50)) }};
                })(),
                isMuted: false,
                queueCount: 0,
                isAnnouncing: false,
                voiceAnnouncerEnabled: true
            },
            timeState: {
                currentTime: 0,
                duration: 0,
                progressPercent: 0,
                currentTimeFormatted: '00:00',
                durationFormatted: '00:00',
                isPlaying: false,
                timestamp: Date.now()
            },
            sendCommand(command, data = {}) {
                if (this.channel) {
                    try {
                        this.channel.postMessage({
                            type: 'COMMAND',
                            command: command,
                            data: data,
                            senderTabId: this.tabId
                        });
                    } catch (e) {}
                }
                window.dispatchEvent(new CustomEvent('soundstation:cmd', { detail: { command, data } }));
            },
            onSync(callback) {
                window.addEventListener('soundstation:sync', (e) => callback(e.detail));
                if (this.channel) {
                    this.channel.addEventListener('message', (e) => {
                        if (e.data && e.data.type === 'STATE_UPDATE' && e.data.state) {
                            Object.assign(this.state, e.data.state);
                            callback(this.state);
                        }
                    });
                }
            },
            onTimeSync(callback) {
                window.addEventListener('soundstation:timesync', (e) => callback(e.detail));
                if (this.channel) {
                    this.channel.addEventListener('message', (e) => {
                        if (e.data && e.data.type === 'TIME_SYNC' && e.data.data) {
                            Object.assign(this.timeState, e.data.data);
                            callback(e.data.data);
                        }
                    });
                }
            }
        };
    })();

    function soundStationMini() {
        return {
            isPlaying: false,
            volume: (() => {
                const local = localStorage.getItem('pos_music_volume');
                if (local !== null && !isNaN(parseInt(local))) {
                    return Math.max(0, Math.min(100, parseInt(local)));
                }
                return {{ (int) (\Illuminate\Support\Facades\Cache::get('soundstation_playback_volume', 50)) }};
            })(),
            isMuted: false,

            currentTrack: null,
            currentTime: 0,
            duration: 0,
            progressPercent: 0,
            currentTimeFormatted: '00:00',
            durationFormatted: '00:00',
            isLive: false,

            queueCount: {{ $state['queue_count'] }},
            isAnnouncing: false,

            init() {
                // Terapkan data cache jika ada
                try {
                    const cached = JSON.parse(localStorage.getItem('pos_soundstation_state') || '{}');
                    if (cached.currentTrack) this.currentTrack = cached.currentTrack;
                    if (typeof cached.isPlaying !== 'undefined') this.isPlaying = cached.isPlaying;
                    if (typeof cached.volume !== 'undefined') this.volume = cached.volume;
                } catch (e) {}

                // Dengarkan sync live state dari Main POS Window / Dedicated Host
                if (window.SoundStationHub) {
                    window.SoundStationHub.onSync((state) => {
                        if (!state) return;
                        this.isPlaying = !!state.isPlaying;
                        if (state.currentTrack) this.currentTrack = state.currentTrack;
                        if (typeof state.volume !== 'undefined') {
                            this.volume = Number(state.volume);
                            localStorage.setItem('pos_music_volume', this.volume);
                        }
                        if (typeof state.isMuted !== 'undefined') this.isMuted = !!state.isMuted;
                        if (typeof state.isLive !== 'undefined') this.isLive = !!state.isLive;
                        if (typeof state.queueCount !== 'undefined') this.queueCount = state.queueCount;
                        if (typeof state.isAnnouncing !== 'undefined') this.isAnnouncing = !!state.isAnnouncing;
                    });

                    // SINKRONISASI REALTIME DETIK & MENIT PLAYBACK (300ms)
                    window.SoundStationHub.onTimeSync((timeData) => {
                        if (!timeData) return;
                        if (typeof timeData.currentTime !== 'undefined') this.currentTime = timeData.currentTime;
                        if (typeof timeData.duration !== 'undefined') this.duration = timeData.duration;
                        if (typeof timeData.progressPercent !== 'undefined') this.progressPercent = Math.min(100, Math.max(0, timeData.progressPercent));
                        if (typeof timeData.isLive !== 'undefined') this.isLive = !!timeData.isLive;
                        if (timeData.currentTimeFormatted) this.currentTimeFormatted = timeData.currentTimeFormatted;
                        if (timeData.durationFormatted) this.durationFormatted = timeData.durationFormatted;
                        if (typeof timeData.isPlaying !== 'undefined') this.isPlaying = timeData.isPlaying;
                    });
                }

                // Interpolasi visual 1 detik agar pergerakan detik terlihat mulus di mini popup
                setInterval(() => {
                    if (this.isPlaying && !this.isLive && this.duration > 0 && this.currentTime < this.duration) {
                        this.currentTime = Math.min(this.duration, this.currentTime + 1);
                        this.currentTimeFormatted = this.formatTime(this.currentTime);
                        this.progressPercent = Math.min(100, Math.max(0, (this.currentTime / this.duration) * 100));
                    }
                }, 1000);
            },

            formatTime(seconds) {
                if (!seconds || isNaN(seconds) || seconds < 0) return '00:00';
                if (seconds > 86400 * 7) return 'LIVE';
                const totalSec = Math.floor(seconds);
                const h = Math.floor(totalSec / 3600);
                const m = Math.floor((totalSec % 3600) / 60);
                const s = totalSec % 60;
                if (h > 0) {
                    return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                }
                return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            },

            togglePlayPause() {
                if (window.SoundStationHub) {
                    window.SoundStationHub.sendCommand('TOGGLE_PLAY_PAUSE');
                }
            },

            skipTrack() {
                if (window.SoundStationHub) {
                    window.SoundStationHub.sendCommand('SKIP');
                }
            },

            changeVolume(val) {
                const v = Math.max(0, Math.min(100, parseInt(val) || 0));
                this.volume = v;
                localStorage.setItem('pos_music_volume', v);
                if (window.SoundStationHub) {
                    window.SoundStationHub.sendCommand('SET_VOLUME', { volume: v });
                }
            },

            toggleMute() {
                if (window.SoundStationHub) {
                    window.SoundStationHub.sendCommand('TOGGLE_MUTE');
                }
            },

            seekFromBar(event) {
                if (this.isLive) return;
                if (!this.duration || this.duration <= 0) return;
                const rect = event.currentTarget.getBoundingClientRect();
                const clickRatio = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
                const targetTime = Math.round(clickRatio * this.duration);

                this.currentTime = targetTime;
                this.currentTimeFormatted = this.formatTime(targetTime);
                this.progressPercent = Math.min(100, Math.max(0, clickRatio * 100));

                if (window.SoundStationHub) {
                    window.SoundStationHub.sendCommand('SEEK_TO', { seconds: targetTime });
                }
            }
        };
    }
    </script>
</body>
</html>
