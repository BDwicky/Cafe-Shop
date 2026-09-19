{{-- COMPONENT NAVBAR MUSIC WIDGET (SOUND STATION INTEGRATED PLAYER) --}}
{{-- Persistent audio player: Musik tidak mati saat berpindah menu/tab --}}
<div x-data="navbarMusicWidget()"
     x-init="initWidget()"
     class="border-t border-[#32261C] bg-[#140E0A] p-3 text-[#FAF7F2] select-none shrink-0 relative">

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
                <span class="px-1.5 py-0.2 font-mono text-[8px] bg-[#2A211A] text-[#8A7B66] border border-[#3A3026] rounded-md">
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
        <!-- 1. Tab Ini adalah Host di Halaman Dedicated Sound Station -->
        <template x-if="isMasterHost && isDedicatedPage">
            <div class="flex items-center justify-between text-[8px] font-mono text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/30 px-2 py-0.5 rounded-lg">
                <span class="flex items-center gap-1.5 font-bold truncate">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42] animate-pulse shrink-0"></span>
                    <span class="truncate" x-text="'Host: ' + deviceName + ' (Anti-Mati)'">Host Pemutar Kafe</span>
                </span>
                <span class="text-[#8A7B66] shrink-0">👑 Master</span>
            </div>
        </template>

        <!-- 2. Tab Ini adalah Host di Halaman POS Biasa (Kasir, KDS, Orders) -->
        <template x-if="isMasterHost && !isDedicatedPage">
            <div class="flex items-center justify-between text-[8px] font-mono text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/30 px-2 py-0.5 rounded-lg">
                <span class="flex items-center gap-1.5 font-bold truncate">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42] animate-pulse shrink-0"></span>
                    <span class="truncate" x-text="'Host: ' + deviceName">Pemutar Aktif di Tab Ini</span>
                </span>
                <span class="text-[#D9973E] font-bold shrink-0">🔊 Master</span>
            </div>
        </template>

        <!-- 3. Tab/Perangkat Lain adalah Host (Tab Ini adalah Remote Controller) -->
        <template x-if="!isMasterHost && hasActiveHost">
            <div class="flex items-center justify-between text-[8px] font-mono text-[#A89A85] bg-[#1C1611] border border-[#2A211A] px-2 py-0.5 rounded-lg">
                <span class="flex items-center gap-1.5 min-w-0 mr-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42] shrink-0 animate-pulse"></span>
                    <span class="truncate">Host: <strong class="text-[#FAF7F2]" x-text="activeHostPageTitle || 'Perangkat Lain'"></strong></span>
                </span>
                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="text-[#D9973E] font-semibold">📡 Remote</span>
                    <button type="button" @click.stop="claimMasterHost(true)"
                            class="text-[7.5px] px-1.5 py-0.2 bg-[#D9973E]/20 hover:bg-[#D9973E] text-[#D9973E] hover:text-[#1F1812] border border-[#D9973E]/40 rounded font-bold transition active:scale-95"
                            title="Ambil alih pemutar audio utama ke perangkat ini">
                        Ambil Alih
                    </button>
                </div>
            </div>
        </template>

        <!-- 4. Sedang Menghubungkan / Tidak Ada Host Aktif -->
        <template x-if="!isMasterHost && !hasActiveHost">
            <div class="flex items-center justify-between text-[8px] font-mono text-[#A89A85] bg-[#1C1611] border border-[#2A211A] px-2 py-0.5 rounded-lg">
                <span class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#D9973E] animate-ping"></span>
                    <span>Menghubungkan Pemutar...</span>
                </span>
                <button type="button" @click.stop="claimMasterHost(true)"
                        class="text-[7.5px] px-1.5 py-0.2 bg-[#D9973E] text-[#1F1812] font-bold rounded hover:bg-[#c4842e] transition">
                    Aktifkan Host
                </button>
            </div>
        </template>
    </div>

    <!-- NOW PLAYING TRACK INFO -->
    <div class="flex items-center gap-2.5 mb-2 bg-[#1C1611] border border-[#2A211A] p-2 rounded-xl">
        <!-- Thumbnail / Disc Icon -->
        <div class="w-8 h-8 rounded-lg shrink-0 overflow-hidden bg-[#261D16] border border-[#3A2D22] flex items-center justify-center relative">
            <template x-if="currentTrack && currentTrack.thumbnail_url">
                <img :src="currentTrack.thumbnail_url" alt="Thumb" class="w-full h-full object-cover">
            </template>
            <template x-if="!currentTrack || !currentTrack.thumbnail_url">
                <span class="text-xs text-[#D9973E]" :class="isPlaying ? 'animate-spin' : ''">♫</span>
            </template>
        </div>

        <!-- Title & Requester -->
        <div class="min-w-0 flex-1">
            <div class="text-[11px] font-bold text-[#FAF7F2] truncate leading-tight"
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
             :title="isLive ? 'Siaran Langsung Radio 24/7' : 'Klik untuk melompat ke durasi lagu'">
            <template x-if="!isLive">
                <div class="bg-[#D9973E] h-full transition-all duration-300"
                     :style="'width: ' + Math.min(100, Math.max(0, progressPercent)) + '%'"></div>
            </template>
            <template x-if="isLive">
                <div class="w-full h-full bg-gradient-to-r from-[#D9973E] via-red-500 to-[#D9973E] animate-pulse"></div>
            </template>
        </div>
        <div class="mt-0.5 flex items-center justify-between font-mono text-[8px] text-[#7A6A58]">
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
    <div class="flex items-center justify-between gap-1.5">
        <!-- Play / Pause & Skip -->
        <div class="flex items-center gap-1.5">
            <button type="button"
                    @click="togglePlayPause()"
                    :title="isPlaying ? 'Jeda Lagu' : 'Putar Lagu'"
                    class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition-all duration-150 shadow active:scale-90 cursor-pointer"
                    :class="isPlaying
                        ? 'bg-[#D9973E] text-[#140E0A] shadow-[0_0_10px_rgba(217,151,62,0.4)]'
                        : 'bg-[#2A211A] text-[#D9973E] border border-[#D9973E]/60 hover:bg-[#D9973E] hover:text-[#140E0A]'">
                <!-- Pause SVG -->
                <svg x-show="isPlaying" class="w-3 h-3 fill-current" viewBox="0 0 24 24">
                    <rect x="6" y="4" width="4" height="16" rx="1"/>
                    <rect x="14" y="4" width="4" height="16" rx="1"/>
                </svg>
                <!-- Play SVG -->
                <svg x-show="!isPlaying" class="w-3 h-3 fill-current ml-0.5" viewBox="0 0 24 24">
                    <path d="M8 5.14v14.72a1 1 0 001.5.86l11.5-7.36a1 1 0 000-1.72L9.5 4.28A1 1 0 008 5.14z"/>
                </svg>
            </button>

            <button type="button"
                    @click="skipTrackConfirm()"
                    title="Lewati Lagu Berikutnya"
                    class="w-7 h-7 rounded-full bg-[#2A211A] hover:bg-[#3A3026] text-[#A89A85] hover:text-[#D9973E] border border-[#3A3026] hover:border-[#D9973E]/40 flex items-center justify-center transition-all duration-150 active:scale-90 cursor-pointer">
                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                    <path d="M5.5 4.5v15a1 1 0 001.5.86l9-7.5a1 1 0 000-1.72l-9-7.5a1 1 0 00-1.5.86zM18 4.5a1 1 0 00-1 1v13a1 1 0 102 0v-13a1 1 0 00-1-1z"/>
                </svg>
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

    <!-- TOMBOL TRIGGER MODE ADZAN MANUAL (KASIR / BARISTA) -->
    <div class="mt-2 pt-2 border-t border-[#261D16]">
        <button type="button"
                @click="toggleManualAdzanMode()"
                :title="isAdzanMode ? 'Mode Adzan Sedang Aktif (Klik untuk Matikan)' : 'Aktifkan Mode Adzan Manual (Volume otomatis diturunkan ke 10%)'"
                class="w-full py-1.5 px-2.5 rounded-lg font-mono text-[9px] font-bold flex items-center justify-between transition-all cursor-pointer border shadow-sm"
                :class="isAdzanMode
                    ? 'bg-[#5F7F42] border-[#85BF5C] text-white animate-pulse shadow-[0_0_12px_rgba(95,127,66,0.6)]'
                    : 'bg-[#1C1611] hover:bg-[#2A211A] border-[#32261C] hover:border-[#D9973E]/50 text-[#D9973E]'">
            <span class="flex items-center gap-1.5 truncate">
                <span class="text-xs shrink-0">🕌</span>
                <span class="truncate" x-text="isAdzanMode ? ('Mode Adzan Aktif (' + (activePrayerName || 'Adzan') + ')') : 'Mode Adzan Manual'"></span>
            </span>
            <span class="text-[8px] uppercase tracking-wider font-semibold opacity-90 shrink-0 ml-1"
                  :class="isAdzanMode ? 'text-white underline' : 'text-[#A89A85]'"
                  x-text="isAdzanMode ? 'Matikan ✕' : 'Nyalakan ↗'"></span>
        </button>
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

            // Teruskan perintah secara cross-device ke server agar master host di perangkat lain (PC/Laptop) menerimanya
            try {
                fetch('{{ route('kasir.music.master.command') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        command: command,
                        data: data
                    })
                }).catch(() => {});
            } catch (e) {}
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

// Warm up Web Speech voices for immediate female Indonesian TTS
if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    window.speechSynthesis.getVoices();
    if (window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = () => {
            window.speechSynthesis.getVoices();
        };
    }
}

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
        isLive: false,
        _trackStartedAt: 0,
        liveSessionSeconds: 0,

        queueCount: 0,
        queue: [],
        _isSmartFadingOut: false,

        // PRAYER TIMES & ADZAN RESPECT MODE (SURABAYA & SIDOARJO)
        prayerSchedule: null,
        isAdzanMode: false,
        isManualAdzan: false,
        _isTestAdzan: false,
        manualAdzanTimer: null,
        activePrayerName: '',
        preAdzanVolume: null,
        adzanTargetVolume: (() => {
            try {
                const saved = JSON.parse(localStorage.getItem('pos_soundstation_announcer_settings') || 'null');
                if (saved && typeof saved.adzan_target_volume !== 'undefined') return Number(saved.adzan_target_volume);
            } catch (e) {}
            return {{ (int) (\App\Http\Controllers\KasirMusicController::getActiveAnnouncerSettings()['adzan_target_volume'] ?? 10) }};
        })(),
        adzanDurationMinutes: (() => {
            try {
                const saved = JSON.parse(localStorage.getItem('pos_soundstation_announcer_settings') || 'null');
                if (saved && typeof saved.adzan_duration_minutes !== 'undefined') return Number(saved.adzan_duration_minutes);
            } catch (e) {}
            return {{ (int) (\App\Http\Controllers\KasirMusicController::getActiveAnnouncerSettings()['adzan_duration_minutes'] ?? 5) }};
        })(),
        adzanModeEnabled: (() => {
            try {
                const saved = JSON.parse(localStorage.getItem('pos_soundstation_announcer_settings') || 'null');
                if (saved && typeof saved.adzan_mode_enabled !== 'undefined') return !!saved.adzan_mode_enabled;
            } catch (e) {}
            return {{ \App\Http\Controllers\KasirMusicController::getActiveAnnouncerSettings()['adzan_mode_enabled'] ? 'true' : 'false' }};
        })(),

        voiceAnnouncerEnabled: true,
        isAnnouncing: false,
        duckedVolume: (() => {
            try {
                const saved = JSON.parse(localStorage.getItem('pos_soundstation_announcer_settings') || 'null');
                if (saved && typeof saved.duck_volume !== 'undefined') return Number(saved.duck_volume);
            } catch (e) {}
            return {{ (int) (\App\Http\Controllers\KasirMusicController::getActiveAnnouncerSettings()['duck_volume'] ?? 12) }};
        })(),
        announcerSettings: (() => {
            try {
                return JSON.parse(localStorage.getItem('pos_soundstation_announcer_settings') || 'null') || @json(\App\Http\Controllers\KasirMusicController::getActiveAnnouncerSettings());
            } catch (e) {
                return @json(\App\Http\Controllers\KasirMusicController::getActiveAnnouncerSettings());
            }
        })(),

        isDedicatedPage: (window.location.pathname.replace(/\/$/, '') === '/kasir/music') && !window.location.pathname.includes('/mini'),
        isMasterHost: false,
        hasActiveHost: false,
        activeHostTabId: null,
        activeHostPageTitle: '',
        lastHostHeartbeatTime: 0,
        myTabId: (window.SoundStationHub && window.SoundStationHub.tabId) ? window.SoundStationHub.tabId : ('tab_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now()),
        deviceId: (() => {
            try {
                let id = localStorage.getItem('pos_soundstation_device_id');
                if (!id) {
                    id = 'dev_' + Math.random().toString(36).substr(2, 8) + '_' + Date.now().toString(36);
                    localStorage.setItem('pos_soundstation_device_id', id);
                }
                return id;
            } catch (e) {
                return 'dev_pos';
            }
        })(),
        deviceName: (() => {
            const ua = navigator.userAgent || '';
            if (/android/i.test(ua)) return 'Tablet Android';
            if (/ipad/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) return 'iPad Kasir';
            if (/iphone/i.test(ua)) return 'iPhone Kasir';
            if (/macintosh|mac os x/i.test(ua)) return 'Mac Kasir';
            if (/windows/i.test(ua)) return 'PC Kasir';
            if (/linux/i.test(ua)) return 'Linux POS';
            return 'Perangkat POS';
        })(),
        heartbeatTimer: null,
        remotePollTimer: null,
        watchdogTimer: null,

        getPageLabel() {
            const p = window.location.pathname;
            if (p.includes('/kasir/music')) return 'Sound Station';
            if (p.includes('/kasir/kitchen')) return 'Dapur KDS';
            if (p.includes('/kasir/orders')) return 'Riwayat Pesanan';
            if (p.includes('/kasir/menu')) return 'Kelola Menu';
            if (p.includes('/kasir/laporan')) return 'Laporan';
            return 'Kasir POS';
        },

        applyServerState(data) {
            if (!data) return;
            if (data.now_playing) {
                this.currentTrack = data.now_playing;
            }
            if (data.playback_state) {
                const pb = data.playback_state;
                if (pb.current_track) {
                    this.currentTrack = pb.current_track;
                }
                this.currentTime = Number(pb.current_time || 0);
                this.duration = Number(pb.duration || (this.currentTrack ? (this.currentTrack.duration_seconds || 0) : 0));
                this.isPlaying = !!pb.is_playing;
                this.isLive = !!pb.is_live || this.currentTime > 86400 || (this.currentTrack && /live|radio|24\/7/i.test(this.currentTrack.title || ''));
                this.currentTimeFormatted = this.formatTime(this.currentTime);
                this.durationFormatted = this.isLive ? 'RADIO 24/7' : this.formatTime(this.duration);
                this.progressPercent = this.isLive ? 100 : (this.duration > 0 ? Math.min(100, Math.max(0, (this.currentTime / this.duration) * 100)) : 0);
            }
            if (typeof data.queue_count !== 'undefined') {
                this.queueCount = data.queue_count;
            }
            if (Array.isArray(data.queue)) {
                this.queue = data.queue;
            }
        },

        async checkInitialMasterState() {
            // 1. Cek dulu apakah di browser lokal ada tab master yang sedang aktif
            try {
                const savedHost = JSON.parse(localStorage.getItem('pos_soundstation_active_host') || '{}');
                const isLocalHostActive = savedHost.tabId && (Date.now() - (savedHost.timestamp || 0) < 2500);

                if (isLocalHostActive && savedHost.tabId !== this.myTabId) {
                    this.isMasterHost = false;
                    this.hasActiveHost = true;
                    this.activeHostTabId = savedHost.tabId;
                    this.activeHostPageTitle = savedHost.pageTitle || 'Tab Lain';
                    this.lastHostHeartbeatTime = savedHost.timestamp;
                    this.pollServerStatus();
                    return;
                }
            } catch (e) {}

            // 2. Hubungi server: apakah ada Master Host aktif di kafe saat ini (perangkat lain)?
            try {
                const res = await fetch('{{ route('kasir.music.master.status') }}');
                if (res.ok) {
                    const data = await res.json();
                    if (data.has_master && data.master && data.master.client_id !== this.myTabId) {
                        // Perangkat lain sudah menjadi Master Host! Tab ini mulai sebagai REMOTE.
                        this.isMasterHost = false;
                        this.hasActiveHost = true;
                        const dev = data.master.device_name || 'Perangkat Lain';
                        const pg = data.master.page_title || 'Sound Station';
                        this.activeHostPageTitle = dev + ' (' + pg + ')';
                        this.applyServerState(data);
                        return;
                    }
                }
            } catch (e) {
                console.warn('[SoundStation] Gagal cek status master:', e);
            }

            // 3. Jika BELUM ada master aktif di seluruh kafe:
            // Tab ini mengklaim master
            this.claimMasterHost(false);
        },

        async pollServerStatus() {
            if (this.isMasterHost || document.hidden || window._isNavigatingKasirPage) return;
            try {
                const res = await fetch('{{ route('kasir.music.master.status') }}');
                if (!res.ok) return;
                const data = await res.json();
                if (data.has_master && data.master) {
                    this.hasActiveHost = true;
                    const dev = data.master.device_name || 'Perangkat Lain';
                    const pg = data.master.page_title || 'Sound Station';
                    this.activeHostPageTitle = dev + ' (' + pg + ')';
                    this.applyServerState(data);
                } else if (!data.has_master) {
                    if (this.isDedicatedPage) {
                        this.claimMasterHost(false);
                    } else {
                        this.hasActiveHost = false;
                    }
                }
            } catch (e) {}
        },

        async claimMasterHost(force = false) {
            const myPriority = this.isDedicatedPage ? 100 : 10;

            try {
                const res = await fetch('{{ route('kasir.music.master.claim') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        client_id: this.myTabId,
                        device_id: this.deviceId,
                        device_name: this.deviceName,
                        page_title: this.getPageLabel(),
                        priority: myPriority,
                        force: !!force
                    })
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.status === 'rejected') {
                        this.isMasterHost = false;
                        this.hasActiveHost = true;
                        const m = data.current_master;
                        this.activeHostPageTitle = m ? ((m.device_name || 'Perangkat Lain') + ' (' + (m.page_title || 'Sound Station') + ')') : 'Perangkat Lain';
                        this.applyServerState(data);
                        if (this.player && typeof this.player.stopVideo === 'function') {
                            try { this.player.stopVideo(); } catch (e) {}
                        }
                        return;
                    }

                    if (data.status === 'granted') {
                        this.applyServerState(data);
                    }
                } else {
                    if (!force && !this.isDedicatedPage) return;
                }
            } catch (e) {
                console.warn('[SoundStation] Gagal klaim master ke server:', e);
                if (!force && !this.isDedicatedPage) return;
            }

            this.isMasterHost = true;
            this.hasActiveHost = true;
            this.activeHostTabId = this.myTabId;
            this.activeHostPageTitle = this.deviceName + ' (' + this.getPageLabel() + ')';

            const hostData = {
                tabId: this.myTabId,
                deviceId: this.deviceId,
                deviceName: this.deviceName,
                priority: myPriority,
                isDedicated: this.isDedicatedPage,
                pageTitle: this.getPageLabel(),
                timestamp: Date.now()
            };

            try {
                localStorage.setItem('pos_soundstation_active_host', JSON.stringify(hostData));
            } catch (e) {}

            if (window.SoundStationHub && window.SoundStationHub.channel) {
                try {
                    window.SoundStationHub.channel.postMessage({
                        type: 'CLAIM_HOST',
                        force: force,
                        tabId: this.myTabId,
                        deviceId: this.deviceId,
                        deviceName: this.deviceName,
                        priority: myPriority,
                        isDedicated: this.isDedicatedPage,
                        pageTitle: this.getPageLabel(),
                        timestamp: Date.now()
                    });
                } catch (e) {}
            }

            if (this.heartbeatTimer) clearInterval(this.heartbeatTimer);
            this.broadcastHostHeartbeat();
            this.heartbeatTimer = setInterval(() => this.broadcastHostHeartbeat(), 7000);

            this.loadYouTubeApi();

            if (this.player && this.playerReady) {
                if (this.currentTrack && this.currentTrack.youtube_id) {
                    const sec = Math.max(0, Math.floor(this.currentTime || 0));
                    this.player.loadVideoById({
                        videoId: this.currentTrack.youtube_id,
                        startSeconds: sec
                    });
                    if (this.isPlaying) {
                        this.player.playVideo();
                    } else {
                        this.player.pauseVideo();
                    }
                }
            }

            if (force && window.customToast) {
                window.customToast({
                    message: '👑 Pemutar audio berhasil diambil alih ke ' + this.deviceName + '!',
                    type: 'success',
                    duration: 3500
                });
            }
        },

        stepDownToRemote(newHostTitle = 'Perangkat Lain', newHostTabId = null) {
            if (this.heartbeatTimer) {
                clearInterval(this.heartbeatTimer);
                this.heartbeatTimer = null;
            }

            this.isMasterHost = false;
            this.hasActiveHost = true;
            this.activeHostPageTitle = newHostTitle;
            this.activeHostTabId = newHostTabId;

            // HENTIKAN SUARA SEGERA DI PERANGKAT INI AGAR TIDAK BENTROK/DOUBLE AUDIO!
            if (this.player && typeof this.player.stopVideo === 'function') {
                try {
                    this.player.stopVideo();
                } catch (e) {}
            }
        },

        scheduleTakeoverElection() {
            if (this.isMasterHost) return;

            const baseDelay = this.isDedicatedPage ? 20 : 120;
            const jitter = Math.abs(this.myTabId.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0) % 250);
            const delay = baseDelay + (this.isDedicatedPage ? 0 : jitter);

            setTimeout(() => {
                if (this.isMasterHost) return;
                try {
                    const saved = JSON.parse(localStorage.getItem('pos_soundstation_active_host') || '{}');
                    if (saved.tabId && saved.tabId !== this.myTabId && (Date.now() - (saved.timestamp || 0) < 2200)) {
                        this.hasActiveHost = true;
                        this.activeHostTabId = saved.tabId;
                        this.activeHostPageTitle = saved.pageTitle || 'Perangkat Lain';
                        return;
                    }
                } catch (e) {}

                if (this.isDedicatedPage) {
                    this.claimMasterHost(false);
                }
            }, delay);
        },

        broadcastHostHeartbeat() {
            if (!this.isMasterHost) return;

            const hostData = {
                type: 'HOST_HEARTBEAT',
                tabId: this.myTabId,
                deviceId: this.deviceId,
                deviceName: this.deviceName,
                priority: this.isDedicatedPage ? 100 : 10,
                isDedicated: this.isDedicatedPage,
                pageTitle: this.getPageLabel(),
                timestamp: Date.now()
            };

            try {
                localStorage.setItem('pos_soundstation_active_host', JSON.stringify(hostData));
            } catch (e) {}

            if (window.SoundStationHub && window.SoundStationHub.channel) {
                try {
                    window.SoundStationHub.channel.postMessage(hostData);
                } catch (e) {}
            }

            // Jangan kirim HTTP request ke server jika tab sedang disembunyikan atau sedang navigasi halaman
            if (document.hidden || window._isNavigatingKasirPage) return;

            // Batasi request HTTP heartbeat ke server minimal berselang 6 detik
            const now = Date.now();
            if (this._lastHttpHeartbeat && (now - this._lastHttpHeartbeat < 6000)) return;
            this._lastHttpHeartbeat = now;

            // Kirim heartbeat ke server untuk sinkronisasi multi-device & deteksi preemption
            fetch('{{ route('kasir.music.master.heartbeat') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    client_id: this.myTabId,
                    device_id: this.deviceId,
                    device_name: this.deviceName,
                    page_title: this.getPageLabel(),
                    priority: this.isDedicatedPage ? 100 : 10,
                    current_time: this.currentTime,
                    duration: this.duration,
                    is_playing: this.isPlaying,
                    current_track: this.currentTrack
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data) return;

                // Jika server menyatakan hak master diambil alih oleh perangkat lain:
                if (data.status === 'preempted') {
                    const newHost = data.current_master
                        ? ((data.current_master.device_name || 'Perangkat Lain') + ' (' + (data.current_master.page_title || 'Sound Station') + ')')
                        : 'Perangkat Lain';

                    this.stepDownToRemote(newHost, data.current_master ? data.current_master.client_id : null);
                    if (data.playback_state) {
                        this.applyServerState({ playback_state: data.playback_state });
                    }

                    if (window.customToast) {
                        window.customToast({
                            message: '🔊 Pemutar audio diambil alih oleh ' + (data.current_master ? data.current_master.device_name : 'perangkat lain') + '. Tab ini beralih ke Remote.',
                            type: 'info',
                            duration: 5000
                        });
                    }

                    this.broadcastSync();
                    return;
                }

                // Eksekusi remote commands yang dikirim dari perangkat remote
                if (data.status === 'ok' && Array.isArray(data.commands) && data.commands.length > 0) {
                    data.commands.forEach(cmd => {
                        this.handleCommand(cmd.command, cmd.data);
                    });
                }
            })
            .catch(() => {});
        },

        initWidget() {
            window.SoundStation = this;

            // Muat cached playback state segera untuk visual instan
            try {
                const cachedState = JSON.parse(localStorage.getItem('pos_soundstation_state') || '{}');
                if (cachedState.currentTrack) this.currentTrack = cachedState.currentTrack;
                if (typeof cachedState.currentTime !== 'undefined') this.currentTime = cachedState.currentTime;
                if (typeof cachedState.duration !== 'undefined') this.duration = cachedState.duration;
                if (typeof cachedState.progressPercent !== 'undefined') this.progressPercent = cachedState.progressPercent;
                if (cachedState.currentTimeFormatted) this.currentTimeFormatted = cachedState.currentTimeFormatted;
                if (cachedState.durationFormatted) this.durationFormatted = cachedState.durationFormatted;
                if (typeof cachedState.isPlaying !== 'undefined') this.isPlaying = cachedState.isPlaying;
            } catch (e) {}

            // Inisialisasi status master secara cerdas tanpa berebut
            this.checkInitialMasterState();

            // Dengarkan pesan heartbeat, time sync, dan state dari BroadcastChannel (antar-tab di perangkat yang sama)
            if (window.SoundStationHub && window.SoundStationHub.channel) {
                window.SoundStationHub.channel.addEventListener('message', (e) => {
                    if (!e.data) return;

                    if (e.data.type === 'HOST_HEARTBEAT') {
                        if (e.data.tabId !== this.myTabId) {
                            this.hasActiveHost = true;
                            this.activeHostTabId = e.data.tabId;
                            this.activeHostPageTitle = e.data.pageTitle || 'Tab Lain';
                            this.lastHostHeartbeatTime = Date.now();

                            if (this.isMasterHost) {
                                const myPriority = this.isDedicatedPage ? 100 : 10;
                                const otherPriority = e.data.priority || 0;
                                if (otherPriority > myPriority || (otherPriority === myPriority && e.data.tabId < this.myTabId)) {
                                    this.stepDownToRemote(e.data.pageTitle, e.data.tabId);
                                }
                            }
                        }
                    } else if (e.data.type === 'CLAIM_HOST') {
                        if (e.data.tabId !== this.myTabId) {
                            const myPriority = this.isDedicatedPage ? 100 : 10;
                            const otherPriority = e.data.priority || 0;
                            if (e.data.force || otherPriority > myPriority || (otherPriority === myPriority && e.data.tabId < this.myTabId)) {
                                this.stepDownToRemote(e.data.pageTitle, e.data.tabId);
                            }
                        }
                    } else if (e.data.type === 'HOST_CLOSED') {
                        if (e.data.tabId === this.activeHostTabId || !this.isMasterHost) {
                            this.hasActiveHost = false;
                            this.scheduleTakeoverElection();
                        }
                    } else if (e.data.type === 'NEW_REQUEST_SUBMITTED') {
                        if (this.isMasterHost) {
                            this.refreshQueue();
                        }
                    } else if (e.data.type === 'ADZAN_MODE_STARTED') {
                        this.isAdzanMode = true;
                        this.isManualAdzan = !!e.data.isManual;
                        this.activePrayerName = e.data.prayer || 'Adzan';
                        if (!this.isMasterHost && typeof e.data.targetVolume !== 'undefined') {
                            this.volume = e.data.targetVolume;
                        }
                    } else if (e.data.type === 'ADZAN_MODE_ENDED') {
                        this.isAdzanMode = false;
                        this.isManualAdzan = false;
                        this.activePrayerName = '';
                        if (!this.isMasterHost && typeof e.data.restoreVolume !== 'undefined') {
                            this.volume = e.data.restoreVolume;
                        }
                    } else if (e.data.type === 'ANNOUNCER_SETTINGS_UPDATED' && e.data.settings) {
                        this.applyAnnouncerSettings(e.data.settings);
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

            // Watchdog check hanya untuk tab di browser yang sama (localStorage)
            this.watchdogTimer = setInterval(() => {
                if (!this.isMasterHost) {
                    try {
                        const saved = JSON.parse(localStorage.getItem('pos_soundstation_active_host') || '{}');
                        const now = Date.now();
                        if (saved.tabId && (now - (saved.timestamp || 0) < 2500)) {
                            this.hasActiveHost = true;
                            this.activeHostTabId = saved.tabId;
                            this.activeHostPageTitle = saved.pageTitle || 'Tab Lain';
                            this.lastHostHeartbeatTime = saved.timestamp;
                        }
                    } catch (e) {}
                }
            }, 2000);

            // Remote server polling setiap 8 detik untuk sinkronisasi antar-device (PC Kasir vs Tablet)
            this.remotePollTimer = setInterval(() => this.pollServerStatus(), 8000);

            // Inisialisasi YouTube player jika tab ini adalah master host
            if (this.isMasterHost) {
                this.loadYouTubeApi();
            }

            // Polling KDS Announcer & Antrean (hanya saat tab aktif & tidak sedang navigasi)
            setInterval(() => {
                if (this.isMasterHost && !document.hidden && !window._isNavigatingKasirPage) {
                    this.checkReadyOrders();
                }
            }, 8000);

            setInterval(() => {
                if (!document.hidden && !window._isNavigatingKasirPage) {
                    this.refreshQueue();
                }
            }, 12000);
            this.refreshQueue();

            // Inisialisasi Jadwal Sholat & Mode Hormat Adzan (Surabaya & Sidoarjo)
            this.fetchPrayerSchedule();
            setInterval(() => this.fetchPrayerSchedule(), 1800000);
            setInterval(() => this.checkPrayerTimeAdzan(), 10000);

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

            // Simpan state dan informasikan jika tab host ditutup
            window.addEventListener('beforeunload', () => {
                if (this.isMasterHost) {
                    try {
                        localStorage.setItem('pos_soundstation_state', JSON.stringify({
                            isPlaying: this.isPlaying,
                            currentTrack: this.currentTrack,
                            currentTime: this.currentTime,
                            duration: this.duration,
                            volume: this.volume,
                            isMuted: this.isMuted,
                            queueCount: this.queueCount,
                            voiceAnnouncerEnabled: this.voiceAnnouncerEnabled
                        }));
                        localStorage.removeItem('pos_soundstation_active_host');

                        if (window.SoundStationHub && window.SoundStationHub.channel) {
                            window.SoundStationHub.channel.postMessage({
                                type: 'HOST_CLOSED',
                                tabId: this.myTabId,
                                lastTrack: this.currentTrack,
                                lastTime: this.currentTime,
                                wasPlaying: this.isPlaying
                            });
                        }

                        // Beritahu server untuk melepaskan master lock secara asynchronous tanpa menunda navigasi
                        fetch('{{ route('kasir.music.master.release') }}', {
                            method: 'POST',
                            keepalive: true,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ client_id: this.myTabId })
                        }).catch(() => {});
                    } catch (e) {}
                }
            });
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
                        const initialVol = this.isAdzanMode ? Math.max(0, Math.min(100, this.adzanTargetVolume ?? 10)) : this.volume;
                        this.player.setVolume(initialVol);
                        if (this.isMuted) this.player.mute();

                        if (this.currentTrack && this.currentTrack.youtube_id) {
                            const startSec = Math.max(0, Math.floor(this.currentTime || 0));
                            if (startSec > 0 && startSec < 86400) {
                                this.player.loadVideoById({
                                    videoId: this.currentTrack.youtube_id,
                                    startSeconds: startSec
                                });
                            } else {
                                this.player.loadVideoById(this.currentTrack.youtube_id);
                            }
                            if (this.isPlaying) {
                                this.player.playVideo();
                            } else {
                                this.player.pauseVideo();
                            }
                        } else {
                            this.playNextTrack();
                        }
                    },
                    'onError': (event) => {
                        if (!this.isMasterHost) return;
                        this.handlePlayerError(event.data);
                    },
                    'onStateChange': (event) => {
                        if (!this.isMasterHost) return;

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
                        this.broadcastTimeSync(true);
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
                    let ct = this.player.getCurrentTime() || 0;
                    let dur = this.player.getDuration() || 0;

                    // Deteksi siaran langsung (Live Stream 24/7):
                    // 1. YouTube player getVideoData()?.isLive
                    // 2. ct > 86400 (melebihi 24 jam)
                    // 3. Judul track memuat Radio / Live 24/7 dan durasi YouTube tidak sinkron
                    const videoData = (typeof this.player.getVideoData === 'function') ? this.player.getVideoData() : null;
                    const isYouTubeLive = !!(videoData && (videoData.isLive || videoData.is_live));
                    const isExcessiveTime = ct > 86400;
                    const isTrackLive = isYouTubeLive || isExcessiveTime || (this.currentTrack && (
                        (this.currentTrack.title && /live|radio|24\/7/i.test(this.currentTrack.title) && (dur <= 0 || ct > 7200))
                    ));

                    if (isTrackLive) {
                        this.isLive = true;
                        if (!this._trackStartedAt) {
                            this._trackStartedAt = Date.now();
                        }
                        const sessionSec = Math.max(0, Math.floor((Date.now() - this._trackStartedAt) / 1000));
                        this.liveSessionSeconds = sessionSec;
                        this.currentTime = sessionSec;
                        this.duration = 0;
                        this.currentTimeFormatted = this.formatTime(sessionSec);
                        this.durationFormatted = 'RADIO 24/7';
                        this.progressPercent = 100;

                        this.broadcastTimeSync();
                        return;
                    }

                    this.isLive = false;

                    // HARD CAP 7 MENIT (420s) HANYA UNTUK REQUEST PELANGGAN (KASIR BEBAS DURASI)
                    const isCustomerRequest = this.currentTrack && (this.currentTrack.type === 'customer_request' || this.currentRequestId);
                    if (isCustomerRequest && this.isPlaying && ct > 420 && !this.isTransitioningTrack) {
                        console.warn('[SoundStation] Lagu request pelanggan telah berputar 7 menit (420s). Melakukan transisi ke lagu berikutnya...');
                        this.playNextTrack(this.currentRequestId);
                        return;
                    }

                    // Pastikan jika durasi YouTube belum terbaca (0), gunakan durasi dari track metadata
                    if (dur <= 0 && this.currentTrack && this.currentTrack.duration_seconds) {
                        dur = Number(this.currentTrack.duration_seconds);
                    }

                    // Batas aman: ct tidak boleh melebihi durasi video
                    if (dur > 0 && ct > dur) {
                        ct = dur;
                    }

                    // SMART TRANSISI ANTAR LAGU (DJ CROSSFADE & SEAMLESS RECO-BLOCKER)
                    const remainingSec = dur - ct;

                    // 1. Smart Fade-Out Audio: 3.5 detik sebelum lagu berakhir, pudarkan audio secara halus (DJ Crossfade)
                    if (dur > 15 && remainingSec <= 3.5 && remainingSec > 1.0 && this.isPlaying && !this.isTransitioningTrack && !this._isSmartFadingOut && !this.isFadingAudio && !this.isAdzanMode && !this.isAnnouncing) {
                        this._isSmartFadingOut = true;
                        this.fadeAudio(this.volume, 0, 3000);
                        if (window.SoundStationHub && window.SoundStationHub.channel) {
                            try {
                                window.SoundStationHub.channel.postMessage({
                                    type: 'TRACK_TRANSITION',
                                    action: 'pre_fade',
                                    remaining: remainingSec
                                });
                            } catch (e) {}
                        }
                    }

                    // 2. Transisi ganti track pada 1.0 detik sebelum durasi habis agar rekomendasi YouTube 100% terblokir
                    if (dur > 15 && (remainingSec <= 1.0) && this.isPlaying && !this.isTransitioningTrack) {
                        this.playNextTrack(this.currentRequestId);
                        return;
                    }

                    this.currentTime = Math.max(0, ct);
                    this.duration = Math.max(0, dur);
                    this.progressPercent = dur > 0 ? Math.min(100, Math.max(0, (ct / dur) * 100)) : 0;
                    this.currentTimeFormatted = this.formatTime(this.currentTime);
                    this.durationFormatted = this.formatTime(this.duration);

                    // SIARKAN DETIK & MENIT SECARA REAL-TIME KE SELURUH JENDELA (TIME_SYNC)
                    this.broadcastTimeSync();
                }
            } catch (e) {}
        },

        broadcastTimeSync(forceServerSync = false) {
            if (window.SoundStationHub) {
                window.SoundStationHub.broadcastTimeSync({
                    currentTime: this.currentTime,
                    duration: this.duration,
                    progressPercent: this.progressPercent,
                    currentTimeFormatted: this.currentTimeFormatted,
                    durationFormatted: this.durationFormatted,
                    isPlaying: this.isPlaying,
                    isLive: this.isLive,
                    timestamp: Date.now()
                });
            }

            // Laporkan status detik & durasi ke server setiap 1.8 detik untuk Smart TV / display external
            const now = Date.now();
            if (this.isMasterHost && (forceServerSync || !this._lastServerSync || now - this._lastServerSync > 1800)) {
                this._lastServerSync = now;
                try {
                    fetch('{{ route('kasir.music.playback.sync') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            client_id: this.myTabId,
                            current_time: this.currentTime,
                            duration: this.duration > 0 ? this.duration : (this.currentTrack?.duration_seconds || 0),
                            is_playing: this.isPlaying,
                            is_live: this.isLive,
                            current_track: this.currentTrack
                        })
                    }).catch(() => {});
                } catch (e) {}
            }
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

        seekFromBar(event) {
            if (this.isLive) {
                if (window.customToast) {
                    window.customToast({
                        message: '📻 Siaran Langsung Radio 24/7 memutar siaran realtime terkini.',
                        type: 'info',
                        duration: 2500
                    });
                }
                return;
            }
            if (!this.duration || this.duration <= 0) return;
            const rect = event.currentTarget.getBoundingClientRect();
            const clickRatio = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
            const targetTime = Math.round(clickRatio * this.duration);

            this.currentTime = targetTime;
            this.currentTimeFormatted = this.formatTime(targetTime);
            this.progressPercent = Math.min(100, Math.max(0, clickRatio * 100));

            if (this.isMasterHost) {
                this.seekTo(targetTime);
            } else if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('SEEK_TO', { seconds: targetTime });
            }
        },

        seekTo(seconds) {
            if (this.isLive || !this.player || !this.playerReady) return;
            try {
                this.player.seekTo(seconds, true);
                this.currentTime = seconds;
                this.progressPercent = this.duration > 0 ? Math.min(100, Math.max(0, (seconds / this.duration) * 100)) : 0;
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
                    isLive: this.isLive,
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
                    (async () => {
                        if (this.isPlaying && this.player && this.playerReady && !this.isTransitioningTrack && !this.isFadingAudio && !this.isAdzanMode) {
                            await this.fadeAudio(this.volume, 0, 500);
                        }
                        this.playNextTrack(this.currentRequestId);
                    })();
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
                case 'TEST_ADZAN_MODE':
                    this.triggerTestAdzanMode(data ? data.target_volume : null);
                    break;
                case 'UPDATE_ANNOUNCER_SETTINGS':
                    this.applyAnnouncerSettings(data ? data.settings : null);
                    break;
                case 'TOGGLE_MANUAL_ADZAN':
                    this.toggleManualAdzanMode(data ? data.action : null);
                    break;
                case 'REFRESH_QUEUE':
                    this.refreshQueue();
                    break;
                case 'PLAY_TRACK':
                    if (data.track) {
                        this.playDirectTrack(data.track);
                    }
                    break;
            }
        },

        playDirectTrack(track) {
            if (!track || !track.youtube_id) return;
            const isLiveTrack = (track.duration_seconds >= 86400) || (track.title && /live|radio|24\/7/i.test(track.title));
            this.currentTrack = {
                id: track.id,
                title: track.title,
                song_title: track.title,
                artist: track.artist || 'Playlist Kafe',
                youtube_id: track.youtube_id,
                thumbnail_url: 'https://img.youtube.com/vi/' + track.youtube_id + '/hqdefault.jpg',
                duration_seconds: track.duration_seconds || 0,
                type: 'default_track'
            };
            this.currentRequestId = null;
            this.lastDefaultTrackId = track.id;
            try {
                localStorage.setItem('pos_soundstation_last_default_id', track.id);
            } catch (e) {}

            this.currentTime = 0;
            this.duration = track.duration_seconds || 0;
            this.progressPercent = 0;
            this.isLive = isLiveTrack;
            this._trackStartedAt = Date.now();
            this.currentTimeFormatted = '00:00';
            this.durationFormatted = isLiveTrack ? 'RADIO 24/7' : this.formatTime(track.duration_seconds || 0);

            if (!this.isMasterHost) {
                this.claimMasterHost(true);
            }

            this._isSmartFadingOut = false;

            if (this.player && this.playerReady) {
                // SMART CROSSFADE: Mulai dari 0 untuk mencegah letupan audio
                try {
                    this.player.setVolume(0);
                } catch (e) {}

                this.player.loadVideoById(track.youtube_id);
                this.player.playVideo();
                this.isPlaying = true;
                document.title = '♫ ' + track.title + ' — POS';

                const targetReturnVol = this.isAdzanMode
                    ? Math.max(0, Math.min(100, this.adzanTargetVolume ?? 10))
                    : (this.isMuted ? 0 : this.volume);
                if (targetReturnVol > 0) {
                    this.fadeAudio(0, targetReturnVol, 1800);
                }
            } else {
                this.loadYouTubeApi();
            }

            this.broadcastSync();
            this.broadcastTimeSync();

            if (window.SoundStationHub && window.SoundStationHub.channel) {
                try {
                    window.SoundStationHub.channel.postMessage({
                        type: 'TRACK_CHANGED',
                        track: this.currentTrack,
                        senderTabId: this.myTabId
                    });
                } catch (e) {}
            }

            // SINKRONKAN KE BACKEND SERVER (AGAR /music/request & /music/display LANGSUNG TERBARUKAN)
            fetch('{{ route('kasir.music.playback.sync') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    client_id: this.myTabId,
                    current_time: 0,
                    duration: track.duration_seconds || 0,
                    is_playing: true,
                    current_track: this.currentTrack
                })
            }).catch(() => {});
        },

        fadeAudio(fromVol, toVol, durationMs = 5000) {
            return new Promise((resolve) => {
                if (this._fadeInterval) {
                    clearInterval(this._fadeInterval);
                    this._fadeInterval = null;
                }
                if (!this.player || !this.playerReady) {
                    resolve();
                    return;
                }

                const targetFrom = Math.max(0, Math.min(100, Math.round(Number(fromVol))));
                const targetTo = Math.max(0, Math.min(100, Math.round(Number(toVol))));

                // Jika targetTo > 0 dan player sempat mute, unMute terlebih dahulu
                try {
                    if (targetTo > 0 && typeof this.player.unMute === 'function') {
                        this.player.unMute();
                    }
                } catch (e) {}

                const steps = 20;
                const stepTime = Math.max(35, Math.floor(durationMs / steps));
                const volDiff = targetTo - targetFrom;
                let currentStep = 0;

                this.isFadingAudio = true;

                this._fadeInterval = setInterval(() => {
                    currentStep++;
                    const progress = currentStep / steps;
                    const newVol = Math.round(targetFrom + (volDiff * progress));

                    try {
                        if (this.player && typeof this.player.setVolume === 'function') {
                            this.player.setVolume(Math.max(0, Math.min(100, newVol)));
                        }
                    } catch (e) {}

                    if (currentStep >= steps) {
                        clearInterval(this._fadeInterval);
                        this._fadeInterval = null;
                        this.isFadingAudio = false;

                        // Pastikan volume akhir tepat targetTo dan jika 0% panggil mute()
                        try {
                            if (this.player && typeof this.player.setVolume === 'function') {
                                this.player.setVolume(targetTo);
                                if (targetTo === 0 && typeof this.player.mute === 'function') {
                                    this.player.mute();
                                } else if (targetTo > 0 && typeof this.player.unMute === 'function') {
                                    this.player.unMute();
                                }
                            }
                        } catch (e) {}

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
                position: (this.isLive || currentPos > 86400) ? 0 : currentPos,
                isLive: !!this.isLive,
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

            // 5. Fade-in audio ke volume target dalam 1.5 detik (menghormati mode adzan jika aktif)
            if (this.player && this.playerReady) {
                const returnVol = this.isAdzanMode ? Math.max(0, Math.min(100, this.adzanTargetVolume ?? 10)) : this.volume;
                await this.fadeAudio(0, returnVol, 1500);
            }
        },

        async playNextTrack(finishId = null, wasBlocked = false, blockedReason = null) {
            if (this.isTransitioningTrack) return;
            this.isTransitioningTrack = true;
            this._isSmartFadingOut = false;
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
                    this.isLive = false;
                    this._trackStartedAt = Date.now();
                    this.currentTimeFormatted = this.formatTime(startSec);

                    if (this.player && this.playerReady) {
                        // SMART CROSSFADE: Mulai dari volume 0 sebelum memuat video baru
                        try {
                            this.player.setVolume(0);
                        } catch (e) {}

                        if (isResume && startSec > 0 && startSec < 86400) {
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

                        // SMART CROSSFADE FADE-IN:
                        // Tentukan volume target (menghormati mode adzan jika aktif)
                        const targetReturnVol = this.isAdzanMode
                            ? Math.max(0, Math.min(100, this.adzanTargetVolume ?? 10))
                            : (this.isMuted ? 0 : this.volume);

                        if (isResume) {
                            this.fadeAudio(0, targetReturnVol, 2500);
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
                        } else if (!wasBlocked && targetReturnVol > 0) {
                            // Fade-in halus 1.8 detik untuk lagu baru
                            this.fadeAudio(0, targetReturnVol, 1800);
                        } else if (targetReturnVol === 0) {
                            try {
                                this.player.setVolume(0);
                            } catch (e) {}
                        }
                    }

                    this.refreshQueue();
                    this.broadcastSync();
                    this.broadcastTimeSync();

                    // SINKRONKAN STATE PEMUTAR KE SERVER & DISPLAY TV SECARA INSTAN
                    fetch('{{ route('kasir.music.playback.sync') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            client_id: this.myTabId,
                            current_time: startSec,
                            duration: track.duration_seconds || 0,
                            is_playing: true,
                            current_track: this.currentTrack
                        })
                    }).catch(() => {});

                    if (window.SoundStationHub && window.SoundStationHub.channel) {
                        try {
                            window.SoundStationHub.channel.postMessage({
                                type: 'TRACK_CHANGED',
                                track: this.currentTrack,
                                senderTabId: this.myTabId
                            });
                        } catch (e) {}
                    }
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

            if (!this.player || !this.playerReady) {
                this.loadYouTubeApi();
                return;
            }

            if (!this.currentTrack || !this.currentTrack.youtube_id) {
                this.playNextTrack();
                return;
            }

            if (this.isPlaying) {
                this.isPlaying = false;
                this.player.pauseVideo();
            } else {
                this.isPlaying = true;
                this.player.playVideo();
            }
            this.broadcastSync();
            this.broadcastTimeSync(true);
        },

        async skipTrackConfirm() {
            if (window.customConfirm) {
                const ok = await window.customConfirm({
                    title: 'Lewati Lagu',
                    message: 'Lewati lagu ini ke antrean berikutnya?',
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

            // SMART CROSSFADE: Fade-out cepat (500ms) jika sedang memutar sebelum beralih ke lagu berikutnya
            if (this.isPlaying && this.player && this.playerReady && !this.isTransitioningTrack && !this.isFadingAudio && !this.isAdzanMode) {
                await this.fadeAudio(this.volume, 0, 500);
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
            if (document.hidden || window._isNavigatingKasirPage) return;
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

                // Jika perangkat ini adalah Remote, sinkronkan data lagu yang sedang diputar dari server
                if (!this.isMasterHost && data.now_playing) {
                    this.currentTrack = data.now_playing;
                }

                this.broadcastSync();

                // Deteksi interupsi jika musik kasir sedang berputar dan ada request pelanggan masuk
                if (this.isMasterHost && this.isPlaying && this.currentTrack && this.currentTrack.type === 'default_track' && this.queueCount > 0 && !this.isFadingAudio && !this.isTransitioningTrack) {
                    this.interruptAndPlayRequest();
                }
            } catch (e) {}
        },

        async checkReadyOrders() {
            if (!this.voiceAnnouncerEnabled || this.isAnnouncing || document.hidden || window._isNavigatingKasirPage) return;

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

            // Load pengaturan suara aktif (localStorage atau default)
            const cfg = (() => {
                try {
                    return JSON.parse(localStorage.getItem('pos_soundstation_announcer_settings') || 'null') || this.announcerSettings || {};
                } catch (e) {
                    return this.announcerSettings || {};
                }
            })();

            // 1. AUDIO DUCKING (FADE-OUT HALUS): Turunkan volume musik YouTube secara bertahap saat pemanggilan dimulai
            const duckVol = parseInt(cfg.duck_volume || this.duckedVolume || 12);
            const currentVol = (this.player && typeof this.player.getVolume === 'function')
                ? this.player.getVolume()
                : this.volume;
            this.fadeAudio(currentVol, duckVol, 800);

            // 2. NADA CHIME
            const chimeStyle = cfg.chime_style || 'ding_dong';
            this.playWidgetChime(chimeStyle);

            const chimeDelay = chimeStyle === 'none' ? 100 : 1050;

            // 3. TEXT-TO-SPEECH ANNOUNCER
            setTimeout(() => {
                const custName = order.customer_name ? order.customer_name.trim() : '';
                let orderNum = '';
                if (order.code) {
                    const parts = order.code.split('-');
                    const lastPart = parts[parts.length - 1];
                    const parsed = parseInt(lastPart, 10);
                    orderNum = (!isNaN(parsed) && parsed > 0) ? parsed : lastPart;
                }

                // Buat teks panggilan berdasarkan template pengaturan
                let text = '';
                const tType = cfg.template_type || 'concise';
                if (tType === 'concise') {
                    text = custName
                        ? `Pesanan Kak ${custName}, siap diambil di kasir.`
                        : `Pesanan nomor ${orderNum || 'Anda'}, siap diambil di kasir.`;
                } else if (tType === 'formal') {
                    text = custName
                        ? `Panggilan untuk Kak ${custName}, pesanan nomor ${orderNum || order.code} sudah siap. Silakan ambil di kasir.`
                        : `Panggilan pesanan nomor ${orderNum || order.code}, pesanan Anda sudah siap. Silakan ambil di meja kasir.`;
                } else if (tType === 'airport') {
                    text = `Perhatian, pesanan nomor ${orderNum || order.code} atas nama ${custName ? 'Kak ' + custName : 'Pelanggan'}, siap diambil di meja kasir. Terima kasih.`;
                } else if (tType === 'english') {
                    text = `Order for ${custName || 'customer'}, your order number ${orderNum || order.code} is ready at the counter.`;
                } else if (tType === 'custom' && cfg.custom_template) {
                    text = cfg.custom_template
                        .replace(/\{name\}/gi, custName ? 'Kak ' + custName : 'Pelanggan')
                        .replace(/\{code\}/gi, orderNum || order.code);
                } else {
                    text = custName
                        ? `Pesanan Kak ${custName}, siap diambil di kasir.`
                        : `Pesanan nomor ${orderNum || 'Anda'}, siap diambil di kasir.`;
                }

                const finish = async () => {
                    setTimeout(async () => {
                        // 2. FADE-IN HALUS SAAT ANNOUNCER SELESAI: Kembalikan volume musik YouTube secara bertahap
                        const nowVol = (this.player && typeof this.player.getVolume === 'function')
                            ? this.player.getVolume()
                            : duckVol;
                        const returnVol = this.isAdzanMode ? Math.max(0, Math.min(100, this.adzanTargetVolume ?? 10)) : this.volume;
                        await this.fadeAudio(nowVol, returnVol, 2000);
                        this.isAnnouncing = false;
                        this.broadcastSync();
                    }, 400);

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

                let hasFinished = false;
                const safeFinish = () => {
                    if (hasFinished) return;
                    hasFinished = true;
                    finish();
                };

                // Putar suara announcer sesuai model yang dikonfigurasi
                this.playConfiguredAnnouncer({
                    text: text,
                    cfg: cfg,
                    onEnd: safeFinish
                });
            }, chimeDelay);
        },

        playWidgetChime(style) {
            if (style === 'none') return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const now = ctx.currentTime;

                if (style === 'airport') {
                    // 3-Tone Airport Chime: F4 (349Hz) -> A4 (440Hz) -> C5 (523Hz)
                    const tones = [349.23, 440.00, 523.25];
                    tones.forEach((freq, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        const t = now + (i * 0.18);
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, t);
                        gain.gain.setValueAtTime(0.3, t);
                        gain.gain.exponentialRampToValueAtTime(0.001, t + 0.45);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(t);
                        osc.stop(t + 0.5);
                    });
                } else if (style === 'bell') {
                    // Soft Bell D5
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, now);
                    gain.gain.setValueAtTime(0.4, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.85);
                } else {
                    // Default Ding-Dong (E5 -> C5)
                    const osc1 = ctx.createOscillator();
                    const gain1 = ctx.createGain();
                    osc1.type = 'sine';
                    osc1.frequency.setValueAtTime(659.25, now);
                    gain1.gain.setValueAtTime(0.35, now);
                    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                    osc1.connect(gain1);
                    gain1.connect(ctx.destination);
                    osc1.start(now);
                    osc1.stop(now + 0.55);

                    const osc2 = ctx.createOscillator();
                    const gain2 = ctx.createGain();
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(523.25, now + 0.22);
                    gain2.gain.setValueAtTime(0.35, now + 0.22);
                    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.85);
                    osc2.connect(gain2);
                    gain2.connect(ctx.destination);
                    osc2.start(now + 0.22);
                    osc2.stop(now + 0.9);
                }
            } catch (e) {}
        },

        playConfiguredAnnouncer({ text, cfg, onEnd }) {
            const model = cfg.voice_model || 'mbak_google';
            const rate = parseFloat(cfg.rate) || 1.0;
            const pitch = parseFloat(cfg.pitch) || 1.0;

            // Opsi 6: Suara spesifik perangkat (Web Speech API)
            if (model === 'device_voice') {
                this.speakDeviceSpeech(text, cfg.device_voice_name, rate, pitch, onEnd);
                return;
            }

            // Tentukan parameter bahasa Google TTS & kecepatan
            let lang = 'id';
            let sampleText = text;
            let playRate = rate;

            if (model === 'english_cafe') {
                lang = 'en';
                if (!/order for|ready at the counter/i.test(sampleText)) {
                    sampleText = 'Order for customer, ready for pickup at the counter.';
                }
            } else if (model === 'google_local') {
                lang = 'jv'; // Aksen medok lokal nusantara
            } else if (model === 'ms_gadis') {
                lang = 'id';
                playRate = 0.9; // Tempo santai
            } else if (model === 'ms_ardi') {
                lang = 'id';
                playRate = 1.15; // Tempo cepat
            } else {
                lang = 'id'; // Mbak Google Asli
            }

            // Gunakan URL relatif terhadap window.location.origin agar tidak ada masalah domain/port
            const ttsUrl = window.location.origin + '/music/tts?lang=' + lang + '&text=' + encodeURIComponent(sampleText);

            try {
                const audio = new Audio(ttsUrl);
                audio.playbackRate = playRate;
                audio.onended = () => {
                    if (onEnd) onEnd();
                };
                audio.onerror = (e) => {
                    console.warn('[Announcer Widget] Audio stream error:', e);
                    if (onEnd) onEnd();
                };

                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.catch(err => {
                        console.warn('[Announcer Widget] Audio play catch:', err);
                        if (onEnd) onEnd();
                    });
                }
            } catch (err) {
                console.warn('[Announcer Widget] Audio instantiation error:', err);
                if (onEnd) onEnd();
            }
        },

        speakDeviceSpeech(text, deviceVoiceName, rate, pitch, callback) {
            if (!('speechSynthesis' in window)) {
                if (callback) callback();
                return;
            }

            try {
                window.speechSynthesis.cancel();
                const utter = new SpeechSynthesisUtterance(text);
                utter.rate = parseFloat(rate) || 1.0;
                utter.pitch = parseFloat(pitch) || 1.0;

                const voices = window.speechSynthesis.getVoices() || [];
                if (deviceVoiceName) {
                    const v = voices.find(x => x.name === deviceVoiceName);
                    if (v) utter.voice = v;
                }

                utter.onend = () => { if (callback) callback(); };
                utter.onerror = () => { if (callback) callback(); };
                window.speechSynthesis.speak(utter);
            } catch (e) {
                if (callback) callback();
            }
        },

        async fetchPrayerSchedule() {
            try {
                const res = await fetch('{{ route('kasir.music.prayer-times') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.success && data.data) {
                    this.prayerSchedule = data.data.schedule;
                    if (data.settings) {
                        this.adzanModeEnabled = !!data.settings.enabled;
                        this.adzanTargetVolume = Number(data.settings.target_volume ?? 10);
                        this.adzanDurationMinutes = Number(data.settings.duration_minutes ?? 5);
                    }
                    this.checkPrayerTimeAdzan();
                }
            } catch (e) {}
        },

        checkPrayerTimeAdzan() {
            if (!this.adzanModeEnabled || !this.prayerSchedule) return;

            const now = new Date();
            const wibStr = now.toLocaleTimeString('en-GB', { timeZone: 'Asia/Jakarta', hour12: false });
            const timeParts = wibStr.split(':');
            if (timeParts.length < 2) return;
            const currentTotalMinutes = parseInt(timeParts[0], 10) * 60 + parseInt(timeParts[1], 10) + (parseInt(timeParts[2] || 0, 10) / 60);

            const prayers = [
                { key: 'subuh', name: 'Subuh' },
                { key: 'dzuhur', name: (now.getDay() === 5 ? "Jum'at" : 'Dzuhur') },
                { key: 'ashar', name: 'Ashar' },
                { key: 'maghrib', name: 'Maghrib' },
                { key: 'isya', name: 'Isya' }
            ];

            let activePrayer = null;
            for (const p of prayers) {
                const timeStr = this.prayerSchedule[p.key];
                if (!timeStr) continue;
                const [ph, pm] = timeStr.split(':').map(Number);
                const startMin = ph * 60 + pm;
                const endMin = startMin + (this.adzanDurationMinutes || 5);

                if (currentTotalMinutes >= startMin && currentTotalMinutes < endMin) {
                    activePrayer = p;
                    break;
                }
            }

            if (activePrayer) {
                if (!this.isAdzanMode) {
                    this.isAdzanMode = true;
                    this.activePrayerName = activePrayer.name;
                    if (this.preAdzanVolume === null || this.preAdzanVolume <= (this.adzanTargetVolume ?? 10)) {
                        this.preAdzanVolume = (this.volume && this.volume > 15) ? this.volume : (parseInt(localStorage.getItem('pos_music_volume') || '75'));
                    }

                    const targetVol = Math.max(0, Math.min(100, this.adzanTargetVolume ?? 10));

                    // Tampilkan Toast Notifikasi di Kasir
                    if (window.customToast) {
                        window.customToast({
                            message: '🕌 Memasuki Waktu Adzan ' + this.activePrayerName + ' (Wilayah Surabaya & Sidoarjo). Volume musik otomatis diturunkan ke ' + targetVol + '% selama adzan berkumandang.',
                            type: 'info',
                            duration: 8000
                        });
                    }

                    // Master Host memudarkan audio ke target volume adzan secara halus
                    if (this.isMasterHost) {
                        this.fadeAudio(this.volume, targetVol, 3000);
                    }

                    // Broadcast ke tab lain & TV Display
                    if (window.SoundStationHub && window.SoundStationHub.channel) {
                        window.SoundStationHub.channel.postMessage({
                            type: 'ADZAN_MODE_STARTED',
                            prayer: this.activePrayerName,
                            targetVolume: targetVol,
                            durationMinutes: this.adzanDurationMinutes || 5
                        });
                    }
                }
            } else {
                if (this.isAdzanMode && !this.isManualAdzan && !this._isTestAdzan) {
                    const finishedPrayer = this.activePrayerName || 'Adzan';
                    this.isAdzanMode = false;
                    this.activePrayerName = '';
                    const restoreVol = (this.preAdzanVolume !== null && this.preAdzanVolume > 15) ? this.preAdzanVolume : 75;

                    // Tampilkan Toast Notifikasi Selesai
                    if (window.customToast) {
                        window.customToast({
                            message: '✓ Waktu adzan ' + finishedPrayer + ' telah selesai. Volume musik dikembalikan normal (' + restoreVol + '%).',
                            type: 'success',
                            duration: 5000
                        });
                    }

                    // Master Host mengembalikan audio ke volume semula
                    if (this.isMasterHost) {
                        this.fadeAudio(this.adzanTargetVolume ?? 10, restoreVol, 3000);
                    }

                    // Broadcast ke tab lain & TV Display
                    if (window.SoundStationHub && window.SoundStationHub.channel) {
                        window.SoundStationHub.channel.postMessage({
                            type: 'ADZAN_MODE_ENDED',
                            prayer: finishedPrayer,
                            restoreVolume: restoreVol
                        });
                    }

                    this.preAdzanVolume = null;
                }
            }
        },

        toggleManualAdzanMode(forceAction = null) {
            // Idempotency: jika sudah aktif dan disuruh start lagi, abaikan
            if (forceAction === 'start' && this.isAdzanMode && this.isManualAdzan) {
                return;
            }
            // Idempotency: jika sudah mati dan disuruh stop lagi, abaikan
            if (forceAction === 'stop' && !this.isAdzanMode) {
                return;
            }

            const shouldStart = (forceAction === 'start') || (forceAction === null && !this.isAdzanMode);

            if (shouldStart) {
                if (this.manualAdzanTimer) {
                    clearTimeout(this.manualAdzanTimer);
                    this.manualAdzanTimer = null;
                }

                let prayerName = 'Adzan';
                if (this.prayerSchedule) {
                    const now = new Date();
                    const wibStr = now.toLocaleTimeString('en-GB', { timeZone: 'Asia/Jakarta', hour12: false });
                    const [h, m] = wibStr.split(':').map(Number);
                    const curMin = h * 60 + m;
                    const prayers = [
                        { key: 'subuh', name: 'Subuh' },
                        { key: 'dzuhur', name: (now.getDay() === 5 ? "Jum'at" : 'Dzuhur') },
                        { key: 'ashar', name: 'Ashar' },
                        { key: 'maghrib', name: 'Maghrib' },
                        { key: 'isya', name: 'Isya' }
                    ];
                    let closestDiff = 9999;
                    for (const p of prayers) {
                        if (this.prayerSchedule[p.key]) {
                            const [ph, pm] = this.prayerSchedule[p.key].split(':').map(Number);
                            const diff = Math.abs((ph * 60 + pm) - curMin);
                            if (diff < closestDiff && diff < 60) {
                                closestDiff = diff;
                                prayerName = p.name;
                            }
                        }
                    }
                }

                this.isAdzanMode = true;
                this.isManualAdzan = true;
                this.activePrayerName = prayerName;
                if (this.preAdzanVolume === null || this.preAdzanVolume <= (this.adzanTargetVolume ?? 10)) {
                    this.preAdzanVolume = (this.volume && this.volume > 15) ? this.volume : (parseInt(localStorage.getItem('pos_music_volume') || '75'));
                }
                const targetVol = Math.max(0, Math.min(100, this.adzanTargetVolume ?? 10));

                if (window.customToast) {
                    window.customToast({
                        message: '🕌 Mode Adzan Diaktifkan (Manual). Volume musik diturunkan ke ' + targetVol + '% selama adzan berkumandang.',
                        type: 'info',
                        duration: 6000
                    });
                }

                if (this.isMasterHost) {
                    this.fadeAudio(this.volume, targetVol, 3000);
                } else {
                    window.SoundStationHub.sendCommand('TOGGLE_MANUAL_ADZAN', { action: 'start' });
                }

                if (window.SoundStationHub && window.SoundStationHub.channel) {
                    window.SoundStationHub.channel.postMessage({
                        type: 'ADZAN_MODE_STARTED',
                        prayer: this.activePrayerName,
                        targetVolume: targetVol,
                        durationMinutes: this.adzanDurationMinutes || 5,
                        isManual: true
                    });
                }

                // Sync ke server cache agar TV Display dan semua client langsung otomatis mendeteksi (sertakan client_id agar master tidak merefleksikan perintahnya sendiri)
                try {
                    fetch('{{ route('kasir.music.master.command') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            client_id: this.myTabId,
                            command: 'TOGGLE_MANUAL_ADZAN',
                            data: {
                                action: 'start',
                                prayer: this.activePrayerName
                            }
                        })
                    }).catch(() => {});
                } catch (e) {}

                // Auto-expiry safety timer (sesuai durasi adzan, default 5 menit)
                const durationMs = (this.adzanDurationMinutes || 5) * 60 * 1000;
                this.manualAdzanTimer = setTimeout(() => {
                    if (this.isAdzanMode && this.isManualAdzan) {
                        this.toggleManualAdzanMode('stop');
                    }
                }, durationMs);

            } else {
                if (this.manualAdzanTimer) {
                    clearTimeout(this.manualAdzanTimer);
                    this.manualAdzanTimer = null;
                }

                const finishedPrayer = this.activePrayerName || 'Adzan';
                this.isAdzanMode = false;
                this.isManualAdzan = false;
                this.activePrayerName = '';
                const restoreVol = (this.preAdzanVolume !== null && this.preAdzanVolume > 15) ? this.preAdzanVolume : 75;

                if (window.customToast) {
                    window.customToast({
                        message: '✓ Mode Adzan dimatikan. Volume musik dikembalikan normal (' + restoreVol + '%).',
                        type: 'success',
                        duration: 5000
                    });
                }

                if (this.isMasterHost) {
                    this.fadeAudio(this.adzanTargetVolume ?? 10, restoreVol, 3000);
                } else {
                    window.SoundStationHub.sendCommand('TOGGLE_MANUAL_ADZAN', { action: 'stop' });
                }

                if (window.SoundStationHub && window.SoundStationHub.channel) {
                    window.SoundStationHub.channel.postMessage({
                        type: 'ADZAN_MODE_ENDED',
                        prayer: finishedPrayer,
                        restoreVolume: restoreVol
                    });
                }

                // Sync ke server cache agar status adzan dibersihkan di server
                try {
                    fetch('{{ route('kasir.music.master.command') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            client_id: this.myTabId,
                            command: 'TOGGLE_MANUAL_ADZAN',
                            data: {
                                action: 'stop'
                            }
                        })
                    }).catch(() => {});
                } catch (e) {}

                this.preAdzanVolume = null;
            }
        },

        applyAnnouncerSettings(settings) {
            if (!settings || typeof settings !== 'object') return;
            this.announcerSettings = { ...this.announcerSettings, ...settings };
            if (typeof settings.adzan_target_volume !== 'undefined') {
                this.adzanTargetVolume = Number(settings.adzan_target_volume);
            }
            if (typeof settings.duck_volume !== 'undefined') {
                this.duckedVolume = Number(settings.duck_volume);
            }
            if (typeof settings.adzan_duration_minutes !== 'undefined') {
                this.adzanDurationMinutes = Number(settings.adzan_duration_minutes);
            }
            if (typeof settings.adzan_mode_enabled !== 'undefined') {
                this.adzanModeEnabled = !!settings.adzan_mode_enabled;
            }
            try {
                localStorage.setItem('pos_soundstation_announcer_settings', JSON.stringify(this.announcerSettings));
            } catch (e) {}
        },

        triggerTestAdzanMode(customTargetVol = null) {
            const samplePrayer = 'Maghrib';
            this.isAdzanMode = true;
            this._isTestAdzan = true;
            this.activePrayerName = samplePrayer;
            const effectiveTarget = (customTargetVol !== null && !isNaN(customTargetVol))
                ? Number(customTargetVol)
                : (this.adzanTargetVolume ?? 10);
            const targetVol = Math.max(0, Math.min(100, effectiveTarget));

            if (this.preAdzanVolume === null || this.preAdzanVolume <= targetVol) {
                this.preAdzanVolume = (this.volume && this.volume > 15) ? this.volume : (parseInt(localStorage.getItem('pos_music_volume') || '75'));
            }

            if (window.customToast) {
                window.customToast({
                    message: '🕌 [UJI COBA] Memasuki Waktu Adzan ' + samplePrayer + ' (Surabaya & Sidoarjo). Volume musik otomatis diturunkan ke ' + targetVol + '% selama adzan.',
                    type: 'info',
                    duration: 7000
                });
            }

            if (this.isMasterHost) {
                this.fadeAudio(this.volume, targetVol, 2000);
            }

            if (window.SoundStationHub && window.SoundStationHub.channel) {
                window.SoundStationHub.channel.postMessage({
                    type: 'ADZAN_MODE_STARTED',
                    prayer: samplePrayer,
                    targetVolume: targetVol,
                    durationMinutes: 5,
                    isTest: true
                });
            }

            setTimeout(() => {
                this.isAdzanMode = false;
                this._isTestAdzan = false;
                const restoreVol = (this.preAdzanVolume !== null && this.preAdzanVolume > 15) ? this.preAdzanVolume : 75;
                if (window.customToast) {
                    window.customToast({
                        message: '✓ [UJI COBA] Waktu adzan selesai. Volume musik dikembalikan ke ' + restoreVol + '%.',
                        type: 'success',
                        duration: 4000
                    });
                }
                if (this.isMasterHost) {
                    this.fadeAudio(targetVol, restoreVol, 2000);
                }
                if (window.SoundStationHub && window.SoundStationHub.channel) {
                    window.SoundStationHub.channel.postMessage({
                        type: 'ADZAN_MODE_ENDED',
                        prayer: samplePrayer,
                        restoreVolume: restoreVol
                    });
                }
                this.preAdzanVolume = null;
            }, 8000);
        },

        openMiniPlayer() {
            window.open('{{ route('kasir.music.mini') }}', 'SoundStationMini', 'width=380,height=520,resizable=yes');
        }
    };
}
</script>
