{{-- COMPONENT NAVBAR MUSIC WIDGET (SOUND STATION INTEGRATED PLAYER) --}}
{{-- Persistent audio player: Musik tidak mati saat berpindah menu/tab --}}
<div x-data="navbarMusicWidget()"
     x-init="initWidget()"
     class="border-t border-[#3A3026] bg-[#140E0A] p-3 text-[#F7F3EC] select-none shrink-0 relative">

    <!-- HEADER WIDGET: STATUS & EQUALIZER -->
    <div class="flex items-center justify-between mb-1.5">
        <div class="flex items-center gap-1.5">
            <!-- Animated Equalizer Bars -->
            <div class="flex items-end gap-0.5 h-3 w-3.5 shrink-0">
                <span class="w-0.5 bg-[#D9973E] rounded-full transition-all duration-150"
                      :class="isPlaying ? 'h-3 animate-pulse' : 'h-1'"></span>
                <span class="w-0.5 bg-[#D9973E] rounded-full transition-all duration-150 delay-75"
                      :class="isPlaying ? 'h-2 animate-pulse' : 'h-1.5'"></span>
                <span class="w-0.5 bg-[#D9973E] rounded-full transition-all duration-150 delay-150"
                      :class="isPlaying ? 'h-3 animate-pulse' : 'h-1'"></span>
            </div>
            <a href="{{ route('kasir.music.index') }}"
               class="font-mono text-[9px] uppercase tracking-[0.2em] font-bold text-[#D9973E] hover:underline flex items-center gap-1">
                <span>Sound Station</span>
                <span class="text-[8px] text-[#A89A85]">↗</span>
            </a>
        </div>

        <!-- QUEUE BADGE & MINI POP-UP BUTTON -->
        <div class="flex items-center gap-1">
            <template x-if="queueCount > 0">
                <span class="px-1.5 py-0.2 font-mono text-[9px] font-bold bg-[#D9973E] text-[#1F1812] rounded-full animate-pulse"
                      x-text="queueCount + ' Req'"></span>
            </template>
            <template x-if="queueCount === 0">
                <span class="px-1.5 py-0.2 font-mono text-[8px] bg-[#2A211A] text-[#8A7B66] border border-[#3A3026]">
                    Playlist
                </span>
            </template>
            <button type="button"
                    @click="openMiniPlayer()"
                    title="Buka di jendela mini terpisah"
                    class="p-0.5 text-[#A89A85] hover:text-[#D9973E] text-[10px] leading-none transition">
                ⧉
            </button>
        </div>
    </div>

    <!-- HOST STATUS BADGE (DEDICATED HOST VS REMOTE CONTROLLER) -->
    <div class="mb-2">
        <template x-if="isDedicatedPage">
            <div class="flex items-center justify-between text-[8px] font-mono text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/30 px-1.5 py-0.5 rounded">
                <span class="flex items-center gap-1 font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42] animate-pulse"></span>
                    <span>Host Pemutar Kafe (Anti-Mati)</span>
                </span>
                <span class="text-[#8A7B66]">👑 Master</span>
            </div>
        </template>
        <template x-if="!isDedicatedPage && hasDedicatedHost">
            <div class="flex items-center justify-between text-[8px] font-mono text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/30 px-1.5 py-0.5 rounded">
                <span class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42] animate-pulse"></span>
                    <span>Host Musik Aktif di Tab Lain</span>
                </span>
                <span class="text-[#D9973E] font-bold">Remote</span>
            </div>
        </template>
        <template x-if="!isDedicatedPage && !hasDedicatedHost">
            <div class="flex items-center justify-between text-[8px] font-mono text-[#A89A85] bg-[#1F1812] border border-[#2A211A] px-1.5 py-0.5 rounded">
                <span class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#D9973E]"></span>
                    <span>Player Standalone</span>
                </span>
                <a href="{{ route('kasir.music.index') }}" target="_blank" class="text-[#D9973E] hover:underline font-bold">
                    Jadikan Tab Host ↗
                </a>
            </div>
        </template>
    </div>

    <!-- NOW PLAYING TRACK INFO -->
    <div class="flex items-center gap-2.5 mb-2 bg-[#1F1812] border border-[#2A211A] p-2">
        <!-- Thumbnail / Disc Icon -->
        <div class="w-8 h-8 rounded shrink-0 overflow-hidden bg-[#2A211A] border border-[#3A3026] flex items-center justify-center relative">
            <template x-if="currentTrack && currentTrack.thumbnail_url">
                <img :src="currentTrack.thumbnail_url" alt="Thumb" class="w-full h-full object-cover">
            </template>
            <template x-if="!currentTrack || !currentTrack.thumbnail_url">
                <span class="text-xs text-[#D9973E]" :class="isPlaying ? 'animate-spin' : ''">♫</span>
            </template>
        </div>

        <!-- Title & Requester -->
        <div class="min-w-0 flex-1">
            <div class="text-[11px] font-bold text-[#F7F3EC] truncate leading-tight"
                 x-text="currentTrack ? currentTrack.title : 'Memuat Musik Kafe...'"></div>
            <div class="flex items-center gap-1 mt-0.5">
                <template x-if="currentTrack && currentTrack.type === 'customer_request'">
                    <span class="font-mono text-[9px] text-[#D9973E] font-semibold truncate"
                          x-text="'★ ' + (currentTrack.customer_name || 'Pelanggan')"></span>
                </template>
                <template x-if="!currentTrack || currentTrack.type !== 'customer_request'">
                    <span class="font-mono text-[9px] text-[#8A7B66] truncate"
                          x-text="currentTrack ? (currentTrack.artist || 'Playlist Kafe') : 'Standby'"></span>
                </template>
            </div>
        </div>
    </div>

    <!-- SLENDER REAL-TIME PROGRESS BAR & TIMESTAMPS -->
    <div class="mb-2">
        <div class="w-full bg-[#2A211A] h-1 rounded-full overflow-hidden cursor-pointer"
             @click="seekFromBar($event)"
             title="Klik untuk melompat ke durasi lagu">
            <div class="bg-[#D9973E] h-full transition-all duration-300"
                 :style="'width: ' + progressPercent + '%'"></div>
        </div>
        <div class="mt-0.5 flex items-center justify-between font-mono text-[8px] text-[#7A6A58]">
            <span x-text="currentTimeFormatted">00:00</span>
            <span x-text="durationFormatted">00:00</span>
        </div>
    </div>

    <!-- AUDIO CONTROLS ROW -->
    <div class="flex items-center justify-between gap-1.5">
        <!-- Play / Pause & Skip -->
        <div class="flex items-center gap-1.5">
            <button type="button"
                    @click="togglePlayPause()"
                    :title="isPlaying ? 'Jeda Lagu' : 'Putar Lagu'"
                    class="w-7 h-7 rounded-full bg-[#D9973E] hover:bg-[#c4842e] text-[#1F1812] flex items-center justify-center font-bold text-xs transition shadow active:scale-95">
                <span x-show="!isPlaying" class="ml-0.5">▶</span>
                <span x-show="isPlaying">⏸</span>
            </button>

            <button type="button"
                    @click="skipTrackConfirm()"
                    title="Lewati Lagu Berikutnya"
                    class="w-6 h-6 rounded bg-[#2A211A] hover:bg-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC] border border-[#3A3026] flex items-center justify-center text-xs transition active:scale-95">
                ⏭
            </button>
        </div>

        <!-- Volume & Mute Controls -->
        <div class="flex items-center gap-1.5">
            <button type="button"
                    @click="toggleMute()"
                    :title="isMuted ? 'Bunyikan' : 'Senyapkan'"
                    class="text-xs text-[#A89A85] hover:text-[#F7F3EC] transition p-0.5">
                <span x-show="!isMuted && volume > 30">🔊</span>
                <span x-show="!isMuted && volume <= 30 && volume > 0">🔉</span>
                <span x-show="isMuted || volume === 0">🔇</span>
            </button>
            <input type="range" min="0" max="100"
                   x-model="volume"
                   @input="changeVolume($event.target.value)"
                   class="w-14 h-1 bg-[#2A211A] accent-[#D9973E] rounded cursor-pointer"
                   title="Volume Musik">
            <span class="font-mono text-[8px] text-[#A89A85] w-4 text-right" x-text="volume + '%'"></span>
        </div>
    </div>

    <!-- ANNOUNCER STATUS ALERT (TAMPIL SAAT VOICE ANNOUNCER SEDANG BERBICARA) -->
    <div x-show="isAnnouncing"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="mt-2 py-1 px-2 bg-[#D9973E]/15 border border-[#D9973E]/40 text-[#D9973E] text-[10px] font-mono flex items-center gap-1.5 animate-pulse"
         style="display: none;">
        <span>📢</span>
        <span class="truncate">Memanggil pesanan siap di kasir...</span>
    </div>

    <!-- HIDDEN YOUTUBE IFRAME EMBED (AKTIF DALAM DOM AGAR TIDAK DI-THROTTLE SAAT SWITCH TAB) -->
    <div style="position: absolute; width: 140px; height: 90px; opacity: 0.01; pointer-events: none; overflow: hidden; z-index: -9999; bottom: 0; left: 0;">
        <div id="navbar-yt-player"></div>
    </div>
</div>

<script>
// GLOBAL SOUNDSTATION SYNCHRONIZATION HUB (DEDICATED HOST & TIME SYNC)
(function() {
    if (window.SoundStationHub) return;

    const channel = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('cafe_soundstation_sync') : null;
    const tabId = 'tab_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    const isDedicatedPage = (window.location.pathname.endsWith('/kasir/music') || window.location.pathname.endsWith('/kasir/music/')) && !window.location.pathname.includes('/mini');

    window.SoundStationHub = {
        channel: channel,
        tabId: tabId,
        isDedicatedPage: isDedicatedPage,
        hasDedicatedHost: false,
        activeHostTabId: null,
        lastHostHeartbeat: 0,

        state: {
            isPlaying: false,
            currentTrack: null,
            currentTime: 0,
            duration: 0,
            progressPercent: 0,
            currentTimeFormatted: '00:00',
            durationFormatted: '00:00',
            volume: parseInt(localStorage.getItem('pos_music_volume') || '75'),
            isMuted: false,
            queueCount: 0,
            queue: [],
            isAnnouncing: false,
            voiceAnnouncerEnabled: true,
            hasMaster: true
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

        broadcastState(partialState) {
            Object.assign(this.state, partialState);
            try {
                localStorage.setItem('pos_soundstation_state', JSON.stringify({
                    isPlaying: this.state.isPlaying,
                    currentTrack: this.state.currentTrack,
                    volume: this.state.volume,
                    isMuted: this.state.isMuted,
                    queueCount: this.state.queueCount,
                    voiceAnnouncerEnabled: this.state.voiceAnnouncerEnabled
                }));
            } catch (e) {}

            if (this.channel) {
                try {
                    this.channel.postMessage({
                        type: 'STATE_UPDATE',
                        state: this.state,
                        senderTabId: this.tabId
                    });
                } catch (e) {}
            }

            window.dispatchEvent(new CustomEvent('soundstation:sync', { detail: this.state }));
        },

        broadcastTimeSync(timeData) {
            Object.assign(this.timeState, timeData);
            if (this.channel) {
                try {
                    this.channel.postMessage({
                        type: 'TIME_SYNC',
                        data: timeData,
                        senderTabId: this.tabId
                    });
                } catch (e) {}
            }
            window.dispatchEvent(new CustomEvent('soundstation:timesync', { detail: timeData }));
        },

        sendCommand(command, data = {}) {
            if (window.SoundStation && typeof window.SoundStation.handleCommand === 'function' && window.SoundStation.isMasterHost) {
                window.SoundStation.handleCommand(command, data);
                return;
            }

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
        },

        onCommand(handler) {
            if (this.channel) {
                this.channel.addEventListener('message', (e) => {
                    if (e.data && e.data.type === 'COMMAND') {
                        handler(e.data.command, e.data.data);
                    }
                });
            }
        }
    };
})();

function navbarMusicWidget() {
    return {
        player: null,
        playerReady: false,
        isPlaying: false,
        volume: parseInt(localStorage.getItem('pos_music_volume') || '75'),
        isMuted: false,

        currentTrack: null,
        currentRequestId: null,
        lastDefaultTrackId: parseInt(localStorage.getItem('pos_soundstation_last_default_id') || '0') || null,
        isTransitioningTrack: false,
        playbackWatchdog: null,
        isAutoSkippingBlocked: false,

        pausedCashierTrack: (() => {
            try {
                return JSON.parse(localStorage.getItem('pos_soundstation_paused_cashier_track') || 'null');
            } catch (e) {
                return null;
            }
        })(),
        isFadingAudio: false,

        currentTime: 0,
        duration: 0,
        progressPercent: 0,
        currentTimeFormatted: '00:00',
        durationFormatted: '00:00',

        queueCount: 0,
        queue: [],

        voiceAnnouncerEnabled: true,
        isAnnouncing: false,
        duckedVolume: 12,

        isDedicatedPage: window.location.pathname.includes('/kasir/music') && !window.location.pathname.includes('/mini'),
        isMasterHost: false,
        hasDedicatedHost: false,
        lastHostHeartbeatTime: 0,

        initWidget() {
            window.SoundStation = this;

            // 1. Tentukan status awal Master Host
            if (this.isDedicatedPage) {
                this.isMasterHost = true;
                this.broadcastHostHeartbeat();
                setInterval(() => this.broadcastHostHeartbeat(), 1500);
            } else {
                // Periksa apakah ada host dedicated yang aktif
                try {
                    const savedHost = JSON.parse(localStorage.getItem('pos_soundstation_active_host') || '{}');
                    if (savedHost.isDedicated && Date.now() - (savedHost.timestamp || 0) < 3500) {
                        this.hasDedicatedHost = true;
                        this.isMasterHost = false;
                    } else {
                        this.isMasterHost = true; // Fallback jika tab music belum dibuka
                    }
                } catch (e) {
                    this.isMasterHost = true;
                }
            }

            // Dengarkan pesan heartbeat, time sync, dan state
            if (window.SoundStationHub && window.SoundStationHub.channel) {
                window.SoundStationHub.channel.addEventListener('message', (e) => {
                    if (!e.data) return;

                    if (e.data.type === 'HOST_HEARTBEAT') {
                        if (e.data.isDedicated && !this.isDedicatedPage) {
                            this.hasDedicatedHost = true;
                            this.lastHostHeartbeatTime = Date.now();
                            if (this.isMasterHost) {
                                // Handover ke dedicated host
                                this.isMasterHost = false;
                                if (this.player && typeof this.player.pauseVideo === 'function') {
                                    this.player.pauseVideo();
                                }
                            }
                        }
                    } else if (e.data.type === 'HOST_CLOSED') {
                        if (!this.isDedicatedPage) {
                            this.hasDedicatedHost = false;
                            this.isMasterHost = true;
                            this.initPlayer();
                        }
                    } else if (e.data.type === 'NEW_REQUEST_SUBMITTED') {
                        if (this.isMasterHost) {
                            this.refreshQueue();
                        }
                    }
                });

                // Dengarkan sync detik/menit live (TIME_SYNC)
                window.SoundStationHub.onTimeSync((timeData) => {
                    if (!this.isMasterHost) {
                        this.currentTime = timeData.currentTime;
                        this.duration = timeData.duration;
                        this.progressPercent = timeData.progressPercent;
                        this.currentTimeFormatted = timeData.currentTimeFormatted;
                        this.durationFormatted = timeData.durationFormatted;
                        if (typeof timeData.isPlaying !== 'undefined') {
                            this.isPlaying = timeData.isPlaying;
                        }
                    }
                });

                // Dengarkan sync state lagu, volume, queue
                window.SoundStationHub.onSync((state) => {
                    if (!this.isMasterHost) {
                        this.isPlaying = !!state.isPlaying;
                        if (state.currentTrack) this.currentTrack = state.currentTrack;
                        if (typeof state.volume !== 'undefined') this.volume = state.volume;
                        if (typeof state.isMuted !== 'undefined') this.isMuted = !!state.isMuted;
                        if (typeof state.queueCount !== 'undefined') this.queueCount = state.queueCount;
                        if (Array.isArray(state.queue)) this.queue = state.queue;
                        if (typeof state.isAnnouncing !== 'undefined') this.isAnnouncing = !!state.isAnnouncing;
                        if (typeof state.pausedCashierTrack !== 'undefined') this.pausedCashierTrack = state.pausedCashierTrack;
                    }
                });

                // Dengarkan perintah remote jika tab ini adalah Master Host
                window.SoundStationHub.onCommand((cmd, data) => {
                    if (this.isMasterHost) {
                        this.handleCommand(cmd, data);
                    }
                });
            }

            // Watchdog check apakah dedicated host masih hidup
            setInterval(() => {
                if (!this.isDedicatedPage && this.hasDedicatedHost) {
                    if (Date.now() - this.lastHostHeartbeatTime > 4000) {
                        this.hasDedicatedHost = false;
                        this.isMasterHost = true;
                        this.initPlayer();
                    }
                }
            }, 2500);

            // Inisialisasi YouTube player jika tab ini adalah master host
            if (this.isMasterHost) {
                this.loadYouTubeApi();
            }

            // Polling KDS Announcer & Antrean
            setInterval(() => {
                if (this.isMasterHost) {
                    this.checkReadyOrders();
                }
            }, 3500);

            setInterval(() => this.refreshQueue(), 3500);
            this.refreshQueue();

            // Progress ticker setiap 300ms (High-frequency timeline ticker)
            setInterval(() => this.tickPlayback(), 300);

            // Interpolasi lokal 1 detik untuk jendela remote agar visual detik bergerak mulus
            setInterval(() => {
                if (!this.isMasterHost && this.isPlaying && this.duration > 0) {
                    if (this.currentTime < this.duration) {
                        this.currentTime = Math.min(this.duration, this.currentTime + 1);
                        this.currentTimeFormatted = this.formatTime(this.currentTime);
                        this.progressPercent = (this.currentTime / this.duration) * 100;
                    }
                }
            }, 1000);

            // Informasikan jika tab host ditutup
            if (this.isDedicatedPage) {
                window.addEventListener('beforeunload', () => {
                    try {
                        localStorage.removeItem('pos_soundstation_active_host');
                        if (window.SoundStationHub && window.SoundStationHub.channel) {
                            window.SoundStationHub.channel.postMessage({ type: 'HOST_CLOSED' });
                        }
                    } catch (e) {}
                });
            }
        },

        broadcastHostHeartbeat() {
            if (window.SoundStationHub && window.SoundStationHub.channel) {
                try {
                    window.SoundStationHub.channel.postMessage({
                        type: 'HOST_HEARTBEAT',
                        isDedicated: true,
                        tabId: window.SoundStationHub.tabId,
                        timestamp: Date.now()
                    });
                    localStorage.setItem('pos_soundstation_active_host', JSON.stringify({
                        isDedicated: true,
                        tabId: window.SoundStationHub.tabId,
                        timestamp: Date.now()
                    }));
                } catch (e) {}
            }
        },

        loadYouTubeApi() {
            if (!window.YT) {
                const tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                const firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            }

            window.onYouTubeIframeAPIReady = () => {
                this.initPlayer();
            };

            if (window.YT && window.YT.Player) {
                this.initPlayer();
            }
        },

        initPlayer() {
            if (this.player) {
                if (this.isPlaying && typeof this.player.playVideo === 'function') {
                    this.player.playVideo();
                }
                return;
            }

            this.player = new YT.Player('navbar-yt-player', {
                height: '90',
                width: '140',
                playerVars: {
                    'playsinline': 1,
                    'controls': 0,
                    'rel': 0,
                    'origin': window.location.origin
                },
                events: {
                    'onReady': () => {
                        this.playerReady = true;
                        this.player.setVolume(this.volume);
                        this.playNextTrack();
                    },
                    'onError': (event) => {
                        this.handlePlayerError(event.data);
                    },
                    'onStateChange': (event) => {
                        // 1 = PLAYING, 2 = PAUSED, 0 = ENDED, 3 = BUFFERING
                        if (event.data === 1) {
                            this.clearPlaybackWatchdog();
                            this.isPlaying = true;
                            this.isTransitioningTrack = false;
                            this.isAutoSkippingBlocked = false;
                            if (this.currentTrack) {
                                document.title = '♫ ' + this.currentTrack.title + ' — POS';
                            }

                            // GUARDRAIL DURASI MAKSIMAL KAFE (7 MENIT = 420 DETIK) HANYA UNTUK REQUEST PELANGGAN
                            // KASIR MEMILIKI PENGECUALIAN DURASI (BEBAS PUTAR PLAYLIST PANJANG/1 JAM)
                            try {
                                const trackDuration = (typeof this.player.getDuration === 'function') ? this.player.getDuration() : 0;
                                const isCustomerRequest = this.currentTrack && (this.currentTrack.type === 'customer_request' || this.currentRequestId);
                                if (isCustomerRequest && trackDuration > 420 && !this.isTransitioningTrack) {
                                    console.warn('[SoundStation] Lagu request pelanggan berdurasi ' + Math.round(trackDuration) + 's melebihi batas 7 menit. Auto-skip guardrail triggered.');
                                    if (window.customAlert) {
                                        window.customAlert({
                                            title: 'Batas Durasi Kafe (Maks. 7 Menit)',
                                            message: 'Lagu "' + (this.currentTrack ? this.currentTrack.title : 'Sedang Diputar') + '" berdurasi ' + this.formatTime(trackDuration) + ' (melebihi batas maksimal 7 menit). Pemutar otomatis beralih ke lagu berikutnya demi kenyamanan seluruh pengunjung.',
                                            type: 'info',
                                            btnText: 'Lanjut'
                                        });
                                    }
                                    setTimeout(() => {
                                        if (!this.isTransitioningTrack) {
                                            this.playNextTrack(this.currentRequestId);
                                        }
                                    }, 1200);
                                    return;
                                }
                            } catch (e) {}
                        } else if (event.data === 2) {
                            this.isPlaying = false;
                        } else if (event.data === 0) {
                            this.clearPlaybackWatchdog();
                            this.isPlaying = false;
                            if (!this.isTransitioningTrack) {
                                this.playNextTrack(this.currentRequestId);
                            }
                        }
                        this.broadcastSync();
                        this.broadcastTimeSync();
                    }
                }
            });
        },

        clearPlaybackWatchdog() {
            if (this.playbackWatchdog) {
                clearTimeout(this.playbackWatchdog);
                this.playbackWatchdog = null;
            }
        },

        handlePlayerError(errorCode) {
            this.clearPlaybackWatchdog();
            if (this.isTransitioningTrack || this.isAutoSkippingBlocked) return;
            this.isAutoSkippingBlocked = true;

            let errorReason = 'Video tidak dapat diputar di pemutar kafe.';
            if (errorCode === 101 || errorCode === 150) {
                errorReason = 'Video diblokir oleh pemilik hak cipta / melarang pemutaran di luar situs YouTube.';
            } else if (errorCode === 100) {
                errorReason = 'Video telah dihapus atau disetel privat oleh pemiliknya.';
            } else if (errorCode === 2 || errorCode === 5) {
                errorReason = 'Format video tidak didukung atau tautan tidak valid.';
            } else if (errorCode === 'TIMEOUT') {
                errorReason = 'Waktu muat habis (Timeout) / pemutaran macet.';
            }

            const trackTitle = this.currentTrack ? (this.currentTrack.song_title || this.currentTrack.title) : 'Lagu request';
            console.warn('[SoundStation Auto-Skip] Error (' + errorCode + '): ' + errorReason + ' pada "' + trackTitle + '". Melewati otomatis...');

            // Beri notifikasi toast visual agar kasir / staff tahu lagu di-skip karena diblokir
            if (window.customToast) {
                window.customToast({
                    message: '⚠️ Lagu "' + trackTitle + '" diblokir di YouTube. Otomatis beralih ke lagu berikutnya...',
                    type: 'warning',
                    duration: 4000
                });
            }

            setTimeout(() => {
                this.playNextTrack(this.currentRequestId, true, errorReason);
            }, 600);
        },

        tickPlayback() {
            if (!this.isMasterHost) return;
            if (!this.player || !this.playerReady) return;

            try {
                if (typeof this.player.getCurrentTime === 'function' && typeof this.player.getDuration === 'function') {
                    const ct = this.player.getCurrentTime() || 0;
                    const dur = this.player.getDuration() || 0;

                    // HARD CAP 7 MENIT (420s) HANYA UNTUK REQUEST PELANGGAN (KASIR BEBAS DURASI)
                    const isCustomerRequest = this.currentTrack && (this.currentTrack.type === 'customer_request' || this.currentRequestId);
                    if (isCustomerRequest && this.isPlaying && ct > 420 && !this.isTransitioningTrack) {
                        console.warn('[SoundStation] Lagu request pelanggan telah berputar 7 menit (420s). Melakukan transisi ke lagu berikutnya...');
                        this.playNextTrack(this.currentRequestId);
                        return;
                    }

                    this.currentTime = ct;
                    this.duration = dur;
                    this.progressPercent = dur > 0 ? Math.min(100, Math.max(0, (ct / dur) * 100)) : 0;
                    this.currentTimeFormatted = this.formatTime(ct);
                    this.durationFormatted = this.formatTime(dur);

                    // SIARKAN DETIK & MENIT SECARA REAL-TIME KE SELURUH JENDELA (TIME_SYNC)
                    this.broadcastTimeSync();
                }
            } catch (e) {}
        },

        broadcastTimeSync() {
            if (window.SoundStationHub) {
                window.SoundStationHub.broadcastTimeSync({
                    currentTime: this.currentTime,
                    duration: this.duration,
                    progressPercent: this.progressPercent,
                    currentTimeFormatted: this.currentTimeFormatted,
                    durationFormatted: this.durationFormatted,
                    isPlaying: this.isPlaying,
                    timestamp: Date.now()
                });
            }

            // Laporkan status detik & durasi ke server setiap 3 detik untuk Smart TV / display external
            const now = Date.now();
            if (this.isMasterHost && (!this._lastServerSync || now - this._lastServerSync > 3000)) {
                this._lastServerSync = now;
                try {
                    fetch('{{ route('kasir.music.playback.sync') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            current_time: this.currentTime,
                            duration: this.duration,
                            is_playing: this.isPlaying
                        })
                    }).catch(() => {});
                } catch (e) {}
            }
        },

        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '00:00';
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        },

        seekFromBar(event) {
            if (!this.duration) return;
            const rect = event.currentTarget.getBoundingClientRect();
            const clickRatio = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
            const targetTime = Math.round(clickRatio * this.duration);

            this.currentTime = targetTime;
            this.currentTimeFormatted = this.formatTime(targetTime);
            this.progressPercent = clickRatio * 100;

            if (this.isMasterHost) {
                this.seekTo(targetTime);
            } else if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('SEEK_TO', { seconds: targetTime });
            }
        },

        seekTo(seconds) {
            if (!this.player || !this.playerReady) return;
            try {
                this.player.seekTo(seconds, true);
                this.currentTime = seconds;
                this.progressPercent = this.duration > 0 ? (seconds / this.duration) * 100 : 0;
                this.currentTimeFormatted = this.formatTime(seconds);
                this.broadcastTimeSync();
                this.broadcastSync();
            } catch (e) {}
        },

        broadcastSync() {
            if (window.SoundStationHub) {
                window.SoundStationHub.broadcastState({
                    isPlaying: this.isPlaying,
                    currentTrack: this.currentTrack,
                    currentTime: this.currentTime,
                    duration: this.duration,
                    progressPercent: this.progressPercent,
                    currentTimeFormatted: this.currentTimeFormatted,
                    durationFormatted: this.durationFormatted,
                    volume: this.volume,
                    isMuted: this.isMuted,
                    queueCount: this.queueCount,
                    queue: this.queue,
                    isAnnouncing: this.isAnnouncing,
                    voiceAnnouncerEnabled: this.voiceAnnouncerEnabled,
                    pausedCashierTrack: this.pausedCashierTrack
                });
            }
        },

        handleCommand(command, data = {}) {
            switch (command) {
                case 'TOGGLE_PLAY_PAUSE':
                    this.togglePlayPause();
                    break;
                case 'PLAY':
                    if (this.player && this.playerReady && !this.isPlaying) {
                        this.player.playVideo();
                    }
                    break;
                case 'PAUSE':
                    if (this.player && this.playerReady && this.isPlaying) {
                        this.player.pauseVideo();
                    }
                    break;
                case 'SKIP':
                    this.playNextTrack(this.currentRequestId);
                    break;
                case 'SET_VOLUME':
                    if (typeof data.volume !== 'undefined') {
                        this.changeVolume(data.volume);
                    }
                    break;
                case 'TOGGLE_MUTE':
                    this.toggleMute();
                    break;
                case 'SEEK_TO':
                    if (typeof data.seconds !== 'undefined') {
                        this.seekTo(data.seconds);
                    }
                    break;
                case 'TOGGLE_ANNOUNCER':
                    this.voiceAnnouncerEnabled = !this.voiceAnnouncerEnabled;
                    this.broadcastSync();
                    break;
                case 'TEST_ANNOUNCER':
                    this.announceOrder({ id: 0, code: 'TEST-01', customer_name: 'Budi Santoso' }, true);
                    break;
                case 'REFRESH_QUEUE':
                    this.refreshQueue();
                    break;
            }
        },

        fadeAudio(fromVol, toVol, durationMs = 5000) {
            return new Promise((resolve) => {
                if (!this.player || !this.playerReady) {
                    resolve();
                    return;
                }

                const steps = 20;
                const stepTime = Math.max(50, Math.floor(durationMs / steps));
                const volDiff = toVol - fromVol;
                let currentStep = 0;

                this.isFadingAudio = true;

                const interval = setInterval(() => {
                    currentStep++;
                    const progress = currentStep / steps;
                    const newVol = Math.round(fromVol + (volDiff * progress));

                    try {
                        if (this.player && typeof this.player.setVolume === 'function') {
                            this.player.setVolume(Math.max(0, Math.min(100, newVol)));
                        }
                    } catch (e) {}

                    if (currentStep >= steps) {
                        clearInterval(interval);
                        this.isFadingAudio = false;
                        resolve();
                    }
                }, stepTime);
            });
        },

        async interruptAndPlayRequest() {
            if (!this.isMasterHost || !this.isPlaying) return;
            if (!this.currentTrack || this.currentTrack.type !== 'default_track') return;
            if (this.queueCount <= 0 || this.isFadingAudio || this.isTransitioningTrack) return;

            this.isTransitioningTrack = true;

            // 1. Simpan posisi track kasir yang ter-pause
            const currentPos = (this.player && typeof this.player.getCurrentTime === 'function')
                ? Math.floor(this.player.getCurrentTime())
                : (this.currentTime || 0);

            const pausedData = {
                id: this.currentTrack.id,
                title: this.currentTrack.title,
                artist: this.currentTrack.artist,
                youtube_id: this.currentTrack.youtube_id,
                position: currentPos,
                type: 'default_track'
            };

            this.pausedCashierTrack = pausedData;
            try {
                localStorage.setItem('pos_soundstation_paused_cashier_track', JSON.stringify(pausedData));
            } catch (e) {}

            this.broadcastSync();

            // 2. Notifikasi kasir
            if (window.customToast) {
                window.customToast({
                    message: '🎵 Request lagu baru masuk! Memudarkan musik kasir (fade-out 5s) untuk memutar lagu request...',
                    type: 'info',
                    duration: 5000
                });
            }

            // 3. Fade-out audio selama 5 detik
            await this.fadeAudio(this.volume, 0, 5000);

            // 4. Putar lagu request
            this.isTransitioningTrack = false;
            await this.playNextTrack();

            // 5. Fade-in audio ke volume normal dalam 1.5 detik
            if (this.player && this.playerReady) {
                await this.fadeAudio(0, this.volume, 1500);
            }
        },

        async playNextTrack(finishId = null, wasBlocked = false, blockedReason = null) {
            if (this.isTransitioningTrack) return;
            this.isTransitioningTrack = true;
            this.clearPlaybackWatchdog();

            // Cek apakah ada track kasir yang terpause untuk di-resume
            const hasResumeTrack = this.pausedCashierTrack && this.pausedCashierTrack.id;

            try {
                const res = await fetch('{{ route('kasir.music.next') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        finish_request_id: finishId || null,
                        last_default_track_id: this.lastDefaultTrackId,
                        was_blocked: !!wasBlocked,
                        blocked_reason: blockedReason || null,
                        resume_default_track_id: hasResumeTrack ? this.pausedCashierTrack.id : null,
                        resume_position: hasResumeTrack ? this.pausedCashierTrack.position : null
                    })
                });

                const track = await res.json();
                if (track && track.youtube_id) {
                    const isResume = track.type === 'default_track' && typeof track.resume_position !== 'undefined' && track.resume_position !== null;
                    const startSec = isResume ? track.resume_position : 0;

                    this.currentTrack = track;
                    this.currentRequestId = track.request_id || null;
                    if (track.type === 'default_track') {
                        this.lastDefaultTrackId = track.id;
                        try {
                            localStorage.setItem('pos_soundstation_last_default_id', track.id);
                        } catch (e) {}
                    }

                    this.currentTime = startSec;
                    this.progressPercent = 0;
                    this.currentTimeFormatted = this.formatTime(startSec);

                    if (this.player && this.playerReady) {
                        if (isResume && startSec > 0) {
                            this.player.loadVideoById({
                                videoId: track.youtube_id,
                                startSeconds: startSec
                            });
                        } else {
                            this.player.loadVideoById(track.youtube_id);
                        }
                        this.isPlaying = true;
                        document.title = '♫ ' + track.title + ' — POS';

                        // Watchdog: jika dalam 7 detik player tidak masuk ke state PLAYING (1), video mungkin diblokir diam-diam
                        this.playbackWatchdog = setTimeout(() => {
                            if (!this.isPlaying && this.currentTrack && !this.isTransitioningTrack) {
                                console.warn('[SoundStation Watchdog] Lagu "' + (this.currentTrack.title || '') + '" tidak berputar dalam 7 detik (terblokir / freeze). Melakukan auto-skip...');
                                this.handlePlayerError('TIMEOUT');
                            }
                        }, 7000);

                        // Jika ini adalah resume lagu kasir, lakukan fade-in halus 3 detik dan notifikasi
                        if (isResume) {
                            this.fadeAudio(0, this.volume, 3000);
                            if (window.customToast) {
                                window.customToast({
                                    message: '✓ Antrean request selesai. Melanjutkan musik kasir dari ' + this.formatTime(startSec) + '...',
                                    type: 'success',
                                    duration: 4000
                                });
                            }
                            this.pausedCashierTrack = null;
                            try {
                                localStorage.removeItem('pos_soundstation_paused_cashier_track');
                            } catch (e) {}
                        }
                    }

                    this.refreshQueue();
                    this.broadcastSync();
                    this.broadcastTimeSync();
                }
            } catch (e) {
                console.error('Next track fetch error:', e);
            } finally {
                setTimeout(() => {
                    this.isTransitioningTrack = false;
                    this.isAutoSkippingBlocked = false;
                }, 1500);
            }
        },

        togglePlayPause() {
            if (!this.isMasterHost) {
                if (window.SoundStationHub) {
                    window.SoundStationHub.sendCommand('TOGGLE_PLAY_PAUSE');
                }
                return;
            }

            if (!this.player || !this.playerReady) return;
            if (this.isPlaying) {
                this.player.pauseVideo();
            } else {
                this.player.playVideo();
            }
        },

        async skipTrackConfirm() {
            if (window.customConfirm) {
                const ok = await window.customConfirm({
                    title: 'Lewati Lagu',
                    message: 'Lewati lagu ini?',
                    type: 'warning',
                    confirmText: 'Lewati',
                    cancelText: 'Batal'
                });
                if (!ok) return;
            }

            if (!this.isMasterHost) {
                if (window.SoundStationHub) {
                    window.SoundStationHub.sendCommand('SKIP');
                }
                return;
            }

            this.playNextTrack(this.currentRequestId);
        },

        changeVolume(val) {
            const v = parseInt(val);
            this.volume = v;
            localStorage.setItem('pos_music_volume', v);

            if (!this.isMasterHost) {
                if (window.SoundStationHub) {
                    window.SoundStationHub.sendCommand('SET_VOLUME', { volume: v });
                }
                return;
            }

            if (this.player && this.playerReady) {
                this.player.setVolume(this.volume);
                if (this.volume > 0 && this.isMuted) {
                    this.toggleMute();
                }
            }
            this.broadcastSync();
        },

        toggleMute() {
            if (!this.isMasterHost) {
                if (window.SoundStationHub) {
                    window.SoundStationHub.sendCommand('TOGGLE_MUTE');
                }
                return;
            }

            if (!this.player || !this.playerReady) return;
            this.isMuted = !this.isMuted;
            if (this.isMuted) {
                this.player.mute();
            } else {
                this.player.unMute();
            }
            this.broadcastSync();
        },

        async refreshQueue() {
            try {
                const res = await fetch('{{ route('music.status') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) return;
                const data = await res.json();
                this.queueCount = data.queue_count || 0;
                this.queue = data.queue || [];
                this.broadcastSync();

                // Deteksi interupsi jika musik kasir sedang berputar dan ada request pelanggan masuk
                if (this.isMasterHost && this.isPlaying && this.currentTrack && this.currentTrack.type === 'default_track' && this.queueCount > 0 && !this.isFadingAudio && !this.isTransitioningTrack) {
                    this.interruptAndPlayRequest();
                }
            } catch (e) {}
        },

        async checkReadyOrders() {
            if (!this.voiceAnnouncerEnabled || this.isAnnouncing) return;

            try {
                const res = await fetch('{{ route('kasir.music.announcements.pending') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.orders && data.orders.length > 0) {
                    this.announceOrder(data.orders[0]);
                }
            } catch (e) {}
        },

        announceOrder(order, isTest = false) {
            if (this.isAnnouncing) return;
            this.isAnnouncing = true;
            this.broadcastSync();

            // 1. AUDIO DUCKING: Turunkan volume musik YouTube secara otomatis
            if (this.player && this.playerReady) {
                this.player.setVolume(this.duckedVolume);
            }

            // 2. NADA CHIME (E5 -> C5) via Web Audio API
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const now = ctx.currentTime;
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(659.25, now);
                gain1.gain.setValueAtTime(0.4, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.55);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.6);

                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(523.25, now + 0.22);
                gain2.gain.setValueAtTime(0.4, now + 0.22);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.85);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.22);
                osc2.stop(now + 0.9);
            } catch (e) {}

            // 3. TEXT-TO-SPEECH ANNOUNCER
            setTimeout(() => {
                const custName = order.customer_name ? order.customer_name.trim() : '';
                const text = custName
                    ? `Panggilan untuk Kak ${custName}, pesanan nomor ${order.code}, pesanan Anda sudah siap. Silakan ambil di meja kasir. Terima kasih.`
                    : `Panggilan pesanan nomor ${order.code}, pesanan Anda sudah siap. Silakan ambil di meja kasir. Terima kasih.`;

                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                    const utter = new SpeechSynthesisUtterance(text);
                    utter.lang = 'id-ID';
                    utter.rate = 0.92;
                    utter.pitch = 1.05;

                    const finish = async () => {
                        setTimeout(() => {
                            if (this.player && this.playerReady) {
                                this.player.setVolume(this.volume);
                            }
                            this.isAnnouncing = false;
                            this.broadcastSync();
                        }, 500);

                        if (!isTest && order.id) {
                            try {
                                await fetch('{{ url('/kasir/music/orders') }}/' + order.id + '/announced', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    }
                                });
                            } catch (err) {}
                        }
                    };

                    utter.onend = finish;
                    utter.onerror = finish;
                    window.speechSynthesis.speak(utter);
                } else {
                    setTimeout(() => {
                        if (this.player && this.playerReady) {
                            this.player.setVolume(this.volume);
                        }
                        this.isAnnouncing = false;
                        this.broadcastSync();
                    }, 2500);
                }
            }, 650);
        },

        openMiniPlayer() {
            window.open('{{ route('kasir.music.mini') }}', 'SoundStationMini', 'width=380,height=520,resizable=yes');
        }
    };
}
</script>
