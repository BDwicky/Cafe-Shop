@extends('kasir.app')

@section('title', 'Sound Station — Pemutar Musik Kafe')

@section('content')
<style>
    .title-marquee-wrap {
        overflow: hidden;
        white-space: nowrap;
        position: relative;
        max-width: 100%;
        display: block;
    }

    .title-marquee-text {
        display: inline-block;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
        transform: translateX(0);
        transition: transform 0.25s ease-out;
    }

    .title-marquee-text.animate-marquee-hover {
        overflow: visible !important;
        text-overflow: clip !important;
        max-width: none !important;
        animation: marqueeScrollText var(--marquee-dur, 4s) cubic-bezier(0.35, 0, 0.65, 1) infinite alternate;
    }

    @keyframes marqueeScrollText {
        0%, 18% {
            transform: translateX(0);
        }
        82%, 100% {
            transform: translateX(var(--marquee-dist, -50px));
        }
    }
</style>
<div class="h-full flex flex-col overflow-hidden bg-[#FAF7F2]"
     x-data="musicStationPage()"
     x-init="init()">

    <!-- TOPBAR SOUND STATION (WARM MODERN ESPRESSO) -->
    <header class="px-4 sm:px-6 py-3.5 border-b border-[#3A3026] bg-[#1A130D] text-[#F7F3EC] flex flex-wrap items-center justify-between gap-4 shrink-0 select-none shadow-md">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#D9973E] text-[#1F1812] flex items-center justify-center font-bold text-lg shadow-sm transition-all"
                 :class="isPlaying ? 'shadow-[0_0_12px_rgba(217,151,62,0.4)]' : ''">
                <span :class="isPlaying ? 'animate-spin' : ''">♫</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base sm:text-lg font-serif font-bold tracking-tight text-[#F7F3EC]">
                        Sound Station Kafe
                    </h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#5F7F42]/20 text-[#85BF5C] border border-[#5F7F42]/40 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#85BF5C] animate-pulse"></span>
                        <span>Tersinkronisasi Realtime</span>
                    </span>
                </div>
                <p class="text-[11px] text-[#A89A85] font-mono mt-0.5">
                    Terkoneksi langsung dengan pemutar audio di Navbar Widget & Display TV
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-2.5">
            <button type="button"
                    onclick="window.open('{{ route('kasir.music.mini') }}', 'SoundStationMini', 'width=380,height=520,resizable=yes')"
                    class="px-3.5 py-2 rounded-xl bg-[#D9973E] hover:bg-[#E5A44B] text-[#1F1812] text-xs font-mono font-bold tracking-wider uppercase transition-all shadow-sm active:scale-95 flex items-center gap-1.5 cursor-pointer">
                <span>⧉</span>
                <span>Mini Player</span>
            </button>
            <a href="{{ route('music.display') }}" target="_blank"
               class="px-3.5 py-2 rounded-xl bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#F7F3EC] text-xs font-mono tracking-wider uppercase transition-all flex items-center gap-1.5 active:scale-95 shadow-2xs">
                <span>Display TV</span>
                <span>↗</span>
            </a>
            <a href="{{ route('music.request') }}" target="_blank"
               class="px-3.5 py-2 rounded-xl bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#D9973E] text-xs font-mono tracking-wider uppercase transition-all flex items-center gap-1.5 active:scale-95 shadow-2xs font-bold">
                <span>Request Tamu</span>
                <span>↗</span>
            </a>
        </div>
    </header>

    <!-- BANNER VOICE ANNOUNCER & AUDIO DUCKING -->
    <div class="px-4 sm:px-6 py-2.5 bg-[#140E0A] border-b border-[#3A3026] flex flex-wrap items-center justify-between gap-3 text-xs font-mono shrink-0">
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 font-bold" :class="isAnnouncing ? 'text-[#E5A44B] animate-pulse' : (voiceAnnouncerEnabled ? 'text-[#85BF5C]' : 'text-[#7A6A58]')">
                <span class="w-2 h-2 rounded-full" :class="isAnnouncing ? 'bg-[#E5A44B] animate-ping' : (voiceAnnouncerEnabled ? 'bg-[#85BF5C]' : 'bg-gray-500')"></span>
                <span x-text="isAnnouncing ? '📢 SEDANG MEMANGGIL PESANAN (AUDIO DUCKED)' : (voiceAnnouncerEnabled ? '📢 Pemanggil Pesanan Otomatis: AKTIF' : '📢 Pemanggil Pesanan: NONAKTIF')"></span>
            </span>
            <span class="text-[#7A6A58] text-[11px] hidden md:inline">&bull; Volume musik mengecil otomatis saat nama pesanan dipanggil</span>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="testAnnouncer()"
                    class="px-3 py-1.5 rounded-xl bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[11px] text-[#D9973E] hover:text-[#F7F3EC] transition flex items-center gap-1 cursor-pointer active:scale-95 shadow-2xs">
                <span>▶</span>
                <span>Tes Suara</span>
            </button>
            <button type="button" @click="toggleVoiceAnnouncer()"
                    class="px-3 py-1.5 rounded-xl text-[11px] border transition font-mono cursor-pointer active:scale-95 shadow-2xs"
                    :class="voiceAnnouncerEnabled ? 'bg-[#5F7F42]/15 border-[#5F7F42]/60 text-[#85BF5C]' : 'bg-[#2A211A] border-[#3A3026] text-[#A89A85]'">
                <span x-text="voiceAnnouncerEnabled ? 'Matikan Suara' : 'Aktifkan Suara'"></span>
            </button>
        </div>
    </div>

    <!-- MAIN BODY GRID (SCROLLABLE CONTENT AREA) -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-[#FAF7F2]">
        <div class="max-w-7xl 2xl:max-w-[1520px] w-full mx-auto grid grid-cols-1 lg:grid-cols-12 gap-5 xl:gap-6 items-stretch">

            <!-- KIRI: PLAYER KAFE & KONTROL (5 COLS) - TINGGI PERSIS SAMA DENGAN GRID KANAN -->
            <div class="lg:col-span-5 flex flex-col gap-3.5 sm:gap-4 lg:h-[700px] xl:h-[750px]">

                <!-- KARTU NOW PLAYING & KONTROL PLAYER (DECK HI-FI ROUNDED-2XL) - FLEXIBLE HERO CONTAINER -->
                <div class="bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] rounded-2xl p-4 sm:p-4.5 shadow-xl relative overflow-hidden flex-1 flex flex-col justify-between min-h-0">
                    <div class="flex items-center justify-between border-b border-[#3A3026] pb-2.5 mb-2.5 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <!-- Equalizer Visualizer Bars (Warm Amber Theme) -->
                            <div class="flex items-end gap-1 h-3.5 w-4 shrink-0">
                                <span class="w-1 bg-[#D9973E] rounded-full transition-all duration-150"
                                      :class="isPlaying ? 'h-3.5 animate-pulse' : 'h-1'"></span>
                                <span class="w-1 bg-[#D9973E] rounded-full transition-all duration-150 delay-75"
                                      :class="isPlaying ? 'h-2.5 animate-pulse' : 'h-1.5'"></span>
                                <span class="w-1 bg-[#D9973E] rounded-full transition-all duration-150 delay-150"
                                      :class="isPlaying ? 'h-3.5 animate-pulse' : 'h-1'"></span>
                            </div>
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold"
                                  :class="isPlaying ? 'text-[#D9973E]' : 'text-amber-400'"
                                  x-text="isPlaying ? 'SEDANG MEMUTAR' : 'TERJEDA'"></span>
                        </div>
                        <template x-if="currentTrack">
                            <span class="font-mono text-[10px] font-bold px-2.5 py-0.5 rounded-full border shadow-2xs"
                                  :class="currentTrack.type === 'customer_request' ? 'border-[#D9973E]/50 text-[#E5A44B] bg-[#D9973E]/15' : 'border-[#3A3026] text-[#A89A85] bg-[#140E0A]'"
                                  x-text="currentTrack.type === 'customer_request' ? '★ Request Pelanggan' : 'Playlist Bawaan'"></span>
                        </template>
                    </div>

                    <!-- THUMBNAIL COVER & EQUALIZER OVERLAY (FLEX-1: MENYESUAIKAN TINGGI ALAMI) -->
                    <div class="w-full bg-black border border-[#3A3026] mb-2.5 flex items-center justify-center overflow-hidden flex-1 min-h-[130px] rounded-xl relative group shadow-inner">
                        <template x-if="currentTrack && currentTrack.thumbnail_url">
                            <img :src="currentTrack.thumbnail_url" alt="Thumb" class="w-full h-full object-cover opacity-85 group-hover:scale-105 transition-transform duration-500">
                        </template>
                        <template x-if="!currentTrack || !currentTrack.thumbnail_url">
                            <div class="w-full h-full flex flex-col items-center justify-center bg-[#140E0A] text-[#D9973E]">
                                <span class="text-4xl" :class="isPlaying ? 'animate-spin' : ''">♫</span>
                                <span class="text-[10px] font-mono uppercase tracking-widest text-[#A89A85] mt-2 font-bold">Sound Station Kafe</span>
                            </div>
                        </template>

                        <!-- Center Play/Pause Quick Action on Hover -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none group-hover:pointer-events-auto bg-black/40 backdrop-blur-xs transition-all opacity-0 group-hover:opacity-100">
                            <button type="button"
                                    @click.stop="togglePlayPause()"
                                    :title="isPlaying ? 'Jeda Lagu' : 'Putar Lagu'"
                                    class="w-13 h-13 rounded-full bg-black/75 hover:bg-[#D9973E] text-white hover:text-[#140E0A] border border-white/20 hover:border-[#D9973E] backdrop-blur-md flex items-center justify-center transition-all duration-200 transform scale-90 group-hover:scale-100 shadow-2xl active:scale-95 cursor-pointer">
                                <svg x-show="isPlaying" class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                                    <rect x="6" y="4" width="4" height="16" rx="1.5"/>
                                    <rect x="14" y="4" width="4" height="16" rx="1.5"/>
                                </svg>
                                <svg x-show="!isPlaying" class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                                    <path d="M7 5.14v13.72a1 1 0 001.5.86l10.5-6.86a1 1 0 000-1.72L8.5 4.28A1 1 0 007 5.14z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Bottom Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent flex flex-col justify-between p-3 pointer-events-none text-white">
                            <div class="flex items-center justify-between">
                                <template x-if="isLive">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-mono font-bold bg-red-600 text-white shadow-sm flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                        LIVE
                                    </span>
                                </template>
                                <span class="px-2 py-0.5 bg-black/70 border border-white/10 font-mono text-[9px] text-[#A89A85] rounded-md ml-auto">
                                    Audio Master: Navbar Widget
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" :class="isPlaying ? 'bg-[#85BF5C] animate-pulse' : 'bg-amber-400'"></span>
                                    <span class="font-mono text-xs text-[#F7F3EC] font-semibold" x-text="isPlaying ? 'Memutar di Suara Kafe' : 'Musik Terjeda'"></span>
                                </div>
                                <span class="font-mono text-[11px] font-bold text-[#E5A44B]" x-text="currentTimeFormatted + ' / ' + durationFormatted"></span>
                            </div>
                        </div>
                    </div>

                    <!-- TRACK INFO & MARQUEE -->
                    <div class="mb-2.5 shrink-0">
                        <div class="title-marquee-wrap track-row-marquee">
                            <h2 class="title-marquee-text text-base sm:text-lg font-serif font-bold text-[#F7F3EC]"
                                :title="currentTrack ? (currentTrack.song_title || currentTrack.title) : ''"
                                x-text="currentTrack ? (currentTrack.song_title || currentTrack.title) : 'Memuat lagu...'"></h2>
                        </div>
                        <div class="text-xs text-[#A89A85] font-mono truncate mt-0.5"
                             x-text="currentTrack ? (currentTrack.artist || 'Artis Kafe') : '-'"></div>

                        <!-- Badge Permintaan Pelanggan -->
                        <template x-if="currentTrack && currentTrack.customer_name">
                            <div class="mt-1.5 text-[11px] font-mono text-[#E5A44B] bg-[#D9973E]/10 border border-[#D9973E]/30 py-1 px-2.5 rounded-xl flex items-center justify-between">
                                <span>Permintaan dari: <b class="text-[#F7F3EC]" x-text="currentTrack.customer_name"></b></span>
                                <span class="font-bold text-[#D9973E]">★ Antrean #1</span>
                            </div>
                        </template>

                        <!-- INDIKATOR MUSIK KASIR TERJEDA OLEH REQUEST PELANGGAN -->
                        <template x-if="pausedCashierTrack">
                            <div class="track-row-marquee mt-1.5 text-[11px] font-mono text-[#E5A44B] bg-[#2A211A] border border-[#D9973E]/40 py-1 px-2.5 rounded-xl flex items-center justify-between gap-2 animate-pulse">
                                <div class="flex items-center gap-1.5 min-w-0 flex-1">
                                    <span class="font-bold shrink-0 text-[#D9973E]">⏸️ Musik Kasir:</span>
                                    <div class="title-marquee-wrap min-w-0 flex-1">
                                        <span class="title-marquee-text text-[#F7F3EC] font-semibold"
                                              :title="pausedCashierTrack.title"
                                              x-text="pausedCashierTrack.title"></span>
                                    </div>
                                    <span class="text-[#A89A85] shrink-0" x-text="'(' + ((pausedCashierTrack.position > 86400 || pausedCashierTrack.isLive) ? 'LIVE' : formatTime(pausedCashierTrack.position)) + ')'"></span>
                                </div>
                                <span class="text-[9px] bg-[#D9973E]/20 text-[#E5A44B] px-2 py-0.5 rounded-full shrink-0 font-bold border border-[#D9973E]/40">Auto-Resume</span>
                            </div>
                        </template>
                    </div>

                    <!-- REAL-TIME TIMELINE PROGRESS SCRUBBER -->
                    <div class="mb-2.5 shrink-0">
                        <div class="w-full bg-[#140E0A] h-2.5 rounded-full overflow-hidden cursor-pointer relative group/bar border border-[#3A3026]"
                             @click="seekFromBar($event)"
                             :title="isLive ? 'Siaran Langsung Radio 24/7' : 'Klik untuk melompat ke detik yang dipilih'">
                            <template x-if="!isLive">
                                <div class="bg-gradient-to-r from-[#D9973E] to-[#F59E0B] h-full transition-all duration-300 rounded-full"
                                     :style="'width: ' + Math.min(100, Math.max(0, progressPercent)) + '%'"></div>
                            </template>
                            <template x-if="isLive">
                                <div class="w-full h-full bg-gradient-to-r from-[#D9973E] via-red-500 to-[#D9973E] animate-pulse"></div>
                            </template>
                            <div class="absolute inset-0 bg-white/5 opacity-0 group-hover/bar:opacity-100 transition-opacity"></div>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between font-mono text-[11px] text-[#A89A85]">
                            <div class="flex items-center gap-1.5">
                                <template x-if="isLive">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-950 text-red-300 border border-red-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-ping"></span>
                                        LIVE
                                    </span>
                                </template>
                                <span class="font-bold text-[#F7F3EC]" x-text="currentTimeFormatted">00:00</span>
                            </div>
                            <span class="text-[10px] text-[#7A6A58]" x-text="isLive ? '// Radio Siaran Langsung 24/7' : '// Geser atau klik garis'"></span>
                            <span class="font-bold text-[#F7F3EC]" x-text="durationFormatted">00:00</span>
                        </div>
                    </div>

                    <!-- KONTROL PEMUTAR AUDIO SINKRON (HI-FI CONSOLE) -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-[#3A3026] shrink-0">
                        <!-- Cluster Tombol Playback -->
                        <div class="flex items-center gap-2">
                            <!-- REPLAY / DARI AWAL -->
                            <button type="button"
                                    @click="replayCurrentTrack()"
                                    title="Putar ulang lagu dari detik 0:00"
                                    class="w-9 h-9 rounded-full bg-[#2A211A] hover:bg-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC] border border-[#3A3026] flex items-center justify-center transition-all duration-150 active:scale-90 shadow-sm group/replay cursor-pointer">
                                <svg class="w-4 h-4 group-hover/replay:-rotate-45 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                                </svg>
                            </button>

                            <!-- PRIMARY PLAY / PAUSE (HERO AMBER GOLD) -->
                            <button type="button"
                                    @click="togglePlayPause()"
                                    :title="isPlaying ? 'Jeda Lagu' : 'Putar Lagu'"
                                    class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-200 transform active:scale-95 shadow-lg hover:shadow-[0_4px_16px_rgba(217,151,62,0.45)] cursor-pointer select-none bg-gradient-to-br from-[#D9973E] to-[#B87728] hover:from-[#E5A44B] hover:to-[#C2822B] text-[#1F1812] font-bold">
                                <!-- PAUSE ICON -->
                                <svg x-show="isPlaying" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                    <rect x="6" y="4" width="4" height="16" rx="1.5"/>
                                    <rect x="14" y="4" width="4" height="16" rx="1.5"/>
                                </svg>

                                <!-- PLAY ICON (PERFECTLY CENTERED) -->
                                <svg x-show="!isPlaying" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                    <path d="M7 5.14v13.72a1 1 0 001.5.86l10.5-6.86a1 1 0 000-1.72L8.5 4.28A1 1 0 007 5.14z"/>
                                </svg>
                            </button>

                            <!-- SKIP NEXT TRACK -->
                            <button type="button"
                                    @click="skipCurrentTrack()"
                                    :disabled="isSkipping"
                                    title="Lewati ke lagu berikutnya"
                                    class="h-9 px-3.5 rounded-full bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] hover:border-[#D9973E]/50 text-[#F7F3EC] text-xs font-mono uppercase tracking-wider transition-all duration-150 active:scale-95 flex items-center gap-1.5 shadow-sm group/skip disabled:opacity-50 cursor-pointer">
                                <span class="text-[11px] font-bold text-[#D5CCC0] group-hover/skip:text-white">Skip</span>
                                <svg class="w-4 h-4 text-[#D9973E] group-hover/skip:translate-x-0.5 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5.5 4.5v15a1 1 0 001.5.86l9-7.5a1 1 0 000-1.72l-9-7.5a1 1 0 00-1.5.86zM18 4.5a1 1 0 00-1 1v13a1 1 0 102 0v-13a1 1 0 00-1-1z"/>
                                </svg>
                            </button>

                            <!-- BAN CURRENT TRACK (AUTO-BAN & SKIP) -->
                            <button type="button"
                                    @click="autoBanCurrentTrack()"
                                    :disabled="isSkipping || !currentTrack"
                                    title="Ban lagu ini dari kafe dan lewati sekarang"
                                    class="h-9 px-3 rounded-full bg-rose-950/50 hover:bg-rose-900/80 border border-rose-800/70 hover:border-rose-500 text-rose-300 text-xs font-mono uppercase tracking-wider transition-all duration-150 active:scale-95 flex items-center gap-1 shadow-sm group/ban disabled:opacity-50 cursor-pointer">
                                <span class="text-xs">🚫</span>
                                <span class="text-[11px] font-bold">Ban</span>
                            </button>
                        </div>

                        <!-- VOLUME SLIDER SINKRON (SVG ICONS) -->
                        <div class="flex items-center gap-2 bg-[#140E0A] border border-[#2A211A] px-3 py-1.5 rounded-full shadow-inner">
                            <button type="button" @click="toggleMute()" class="text-[#A89A85] hover:text-[#D9973E] transition p-0.5 cursor-pointer flex items-center justify-center">
                                <svg x-show="!isMuted && volume > 30" class="w-4 h-4 fill-current text-[#D9973E]" viewBox="0 0 24 24">
                                    <path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.5A2.25 2.25 0 002.25 9.75v4.5A2.25 2.25 0 004.5 16.5h1.94l4.5 4.5c.944.945 2.56.276 2.56-1.06V4.06zM17.75 12c0-1.34-.54-2.56-1.42-3.44a1 1 0 10-1.42 1.42c.52.52.84 1.24.84 2.02s-.32 1.5-.84 2.02a1 1 0 101.42 1.42c.88-.88 1.42-2.1 1.42-3.44zM21.25 12c0-2.31-.94-4.41-2.46-5.93a1 1 0 10-1.42 1.42A6.38 6.38 0 0119.25 12c0 1.76-.72 3.36-1.88 4.51a1 1 0 101.42 1.42A8.38 8.38 0 0021.25 12z"/>
                                </svg>
                                <svg x-show="!isMuted && volume <= 30 && volume > 0" class="w-4 h-4 fill-current text-[#D9973E]" viewBox="0 0 24 24">
                                    <path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.5A2.25 2.25 0 002.25 9.75v4.5A2.25 2.25 0 004.5 16.5h1.94l4.5 4.5c.944.945 2.56.276 2.56-1.06V4.06zM17.75 12c0-1.34-.54-2.56-1.42-3.44a1 1 0 10-1.42 1.42c.52.52.84 1.24.84 2.02s-.32 1.5-.84 2.02a1 1 0 101.42 1.42c.88-.88 1.42-2.1 1.42-3.44z"/>
                                </svg>
                                <svg x-show="isMuted || volume === 0" class="w-4 h-4 fill-current text-rose-400" viewBox="0 0 24 24">
                                    <path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.5A2.25 2.25 0 002.25 9.75v4.5A2.25 2.25 0 004.5 16.5h1.94l4.5 4.5c.944.945 2.56.276 2.56-1.06V4.06zM17.78 9.22a.75.75 0 10-1.06 1.06L18.44 12l-1.72 1.72a.75.75 0 001.06 1.06l1.72-1.72 1.72 1.72a.75.75 0 101.06-1.06L20.56 12l1.72-1.72a.75.75 0 00-1.06-1.06l-1.72 1.72-1.72-1.72z"/>
                                </svg>
                            </button>
                            <input type="range" min="0" max="100"
                                   x-model="volume"
                                   @input="changeVolume($event.target.value)"
                                   class="w-18 sm:w-20 accent-[#D9973E] cursor-pointer h-1.5 bg-[#2A211A] rounded"
                                   title="Volume Musik (Tersinkronisasi)">
                            <span class="font-mono text-[10px] text-[#A89A85] w-7 text-right font-bold" x-text="volume + '%'"></span>
                        </div>
                    </div>
                </div>

                <!-- CARA KERJA SOUND STATION KAFE (LIGHT IVORY CARD) - COMPACT & NATURAL, TIDAK GENDUT -->
                <div class="bg-white border border-[#E4DCCC] rounded-2xl p-3.5 sm:p-4 text-xs shadow-xs shrink-0">
                    <div class="font-mono text-[10px] uppercase tracking-wider text-[#1F1812] font-bold flex items-center justify-between pb-2 mb-2.5 border-b border-[#E4DCCC]">
                        <span class="flex items-center gap-1.5">
                            <span class="text-[#D9973E]">ℹ</span>
                            <span>Aturan Pemutaran Musik Kafe</span>
                        </span>
                        <span class="text-[9px] text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/20 px-2 py-0.5 rounded-full font-mono font-bold">Auto-Sync</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] text-[#5C4D3C]">
                        <div class="p-2.5 sm:p-3 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl hover:border-[#D9973E]/60 hover:bg-[#FFFDF9] transition-all">
                            <div class="font-bold text-[#1F1812] flex items-center gap-1 text-[11px]">
                                <span>🎧</span> <span>Pengecualian Kasir</span>
                            </div>
                            <div class="text-[10px] text-[#7A6A58] mt-1 leading-snug">Bebas putar playlist panjang (lofi, ambient) tanpa batas durasi.</div>
                        </div>
                        <div class="p-2.5 sm:p-3 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl hover:border-[#D9973E]/60 hover:bg-[#FFFDF9] transition-all">
                            <div class="font-bold text-[#1F1812] flex items-center gap-1 text-[11px]">
                                <span>⏯️</span> <span>Fade-Out & Resume</span>
                            </div>
                            <div class="text-[10px] text-[#7A6A58] mt-1 leading-snug">Musik kasir fade-out 5s saat request masuk, dan resume saat selesai.</div>
                        </div>
                        <div class="p-2.5 sm:p-3 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl hover:border-[#D9973E]/60 hover:bg-[#FFFDF9] transition-all">
                            <div class="font-bold text-[#1F1812] flex items-center gap-1 text-[11px]">
                                <span>⏱️</span> <span>Batas Request Tamu</span>
                            </div>
                            <div class="text-[10px] text-[#7A6A58] mt-1 leading-snug">Maksimal 7 menit per lagu untuk request dari struk pelanggan.</div>
                        </div>
                        <div class="p-2.5 sm:p-3 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl hover:border-[#D9973E]/60 hover:bg-[#FFFDF9] transition-all">
                            <div class="font-bold text-[#1F1812] flex items-center gap-1 text-[11px]">
                                <span>📢</span> <span>Audio Ducking</span>
                            </div>
                            <div class="text-[10px] text-[#7A6A58] mt-1 leading-snug">Volume mengecil otomatis saat suara pemanggilan pesanan aktif.</div>
                        </div>
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-[#E4DCCC] flex items-center justify-between text-[10px] font-mono text-[#7A6A58]">
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42] animate-pulse"></span>
                            <span>Audio Engine: Terkoneksi</span>
                        </span>
                        <a href="{{ route('kasir.announcer.settings') }}" class="text-[#D9973E] hover:text-[#B87728] font-bold flex items-center gap-1 transition-colors">
                            <span>Pengaturan Suara</span>
                            <span>⚙️</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- KANAN: TABS (LIGHT CONTAINER DENGAN TINGGI KONSISTEN) (7 COLS) -->
            <div class="lg:col-span-7 bg-white border border-[#E4DCCC] shadow-xs flex flex-col lg:h-[700px] xl:h-[750px] rounded-2xl overflow-hidden">

                <!-- TAB HEADERS (ROUNDED PILL TABS) -->
                <div class="p-2.5 bg-[#FAF7F2] border-b border-[#E4DCCC] flex items-center justify-between gap-1.5 shrink-0 select-none overflow-x-auto">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <button type="button"
                                @click="activeTab = 'queue'"
                                class="px-3 sm:px-4 py-2 sm:py-2.5 font-mono text-xs uppercase tracking-wider transition-all rounded-xl flex items-center gap-2 cursor-pointer shrink-0"
                                :class="activeTab === 'queue' ? 'bg-white font-bold text-[#1F1812] shadow-xs border border-[#E4DCCC]' : 'text-[#7A6A58] hover:text-[#1F1812] hover:bg-[#F0EAE1]'">
                            <span>Antrean Request</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-extrabold"
                                  :class="activeTab === 'queue' ? 'bg-[#D9973E] text-[#1F1812]' : 'bg-[#E4DCCC] text-[#7A6A58]'"
                                  x-text="queue.length"></span>
                        </button>

                        <button type="button"
                                @click="activeTab = 'default_tracks'"
                                class="px-3 sm:px-4 py-2 sm:py-2.5 font-mono text-xs uppercase tracking-wider transition-all rounded-xl flex items-center gap-2 cursor-pointer shrink-0"
                                :class="activeTab === 'default_tracks' ? 'bg-white font-bold text-[#1F1812] shadow-xs border border-[#E4DCCC]' : 'text-[#7A6A58] hover:text-[#1F1812] hover:bg-[#F0EAE1]'">
                            <span>Playlist Bawaan</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-extrabold"
                                  :class="activeTab === 'default_tracks' ? 'bg-[#5F7F42] text-white' : 'bg-[#E4DCCC] text-[#7A6A58]'"
                                  x-text="defaultTracks.length"></span>
                        </button>

                        <button type="button"
                                @click="activeTab = 'history'"
                                class="px-3 sm:px-4 py-2 sm:py-2.5 font-mono text-xs uppercase tracking-wider transition-all rounded-xl cursor-pointer shrink-0"
                                :class="activeTab === 'history' ? 'bg-white font-bold text-[#1F1812] shadow-xs border border-[#E4DCCC]' : 'text-[#7A6A58] hover:text-[#1F1812] hover:bg-[#F0EAE1]'">
                            Riwayat
                        </button>

                        <button type="button"
                                @click="activeTab = 'ban_list'"
                                class="px-3 sm:px-4 py-2 sm:py-2.5 font-mono text-xs uppercase tracking-wider transition-all rounded-xl flex items-center gap-2 cursor-pointer shrink-0"
                                :class="activeTab === 'ban_list' ? 'bg-white font-bold text-rose-700 shadow-xs border border-rose-300' : 'text-[#7A6A58] hover:text-rose-700 hover:bg-rose-50/50'">
                            <span>🚫 Ban List</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-extrabold"
                                  :class="activeTab === 'ban_list' ? 'bg-rose-600 text-white' : 'bg-rose-100 text-rose-700 border border-rose-200'"
                                  x-text="bannedTracks.length"></span>
                        </button>
                    </div>

                    <!-- TOMBOL CEPAT BAN LAGU (TETAP DI TAB AKTIF SAAT INI TANPA PERLU GANTI TAB) -->
                    <button type="button"
                            @click="openBanModal()"
                            title="Masukkan lagu / kata kunci ke Ban List tanpa berpindah tab"
                            class="ml-auto px-3 py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 hover:border-rose-400 text-rose-700 rounded-xl font-mono text-xs uppercase tracking-wider font-bold transition-all shadow-2xs flex items-center gap-1.5 cursor-pointer active:scale-95 shrink-0">
                        <span>🚫</span>
                        <span class="hidden sm:inline">+ Ban Lagu</span>
                        <span class="sm:hidden">+ Ban</span>
                    </button>
                </div>

                <!-- TAB CONTENT WRAPPER -->
                <div class="flex-1 overflow-hidden p-4 sm:p-5 bg-white flex flex-col min-h-0">

                    <!-- TAB 1: ANTREAN REQUEST PELANGGAN -->
                    <div x-show="activeTab === 'queue'" class="h-full flex flex-col min-h-0">
                        <div class="flex items-center justify-between mb-3.5 pb-2 border-b border-[#E4DCCC] shrink-0">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs uppercase tracking-wider font-bold text-[#1F1812]">Daftar Antrean Aktif</span>
                                <span class="px-2.5 py-0.5 text-[11px] font-mono font-bold rounded-full"
                                      :class="queue.length > 0 ? 'bg-[#D9973E]/15 text-[#B87728] border border-[#D9973E]/30' : 'bg-[#FAF7F2] text-[#7A6A58] border border-[#E4DCCC]'"
                                      x-text="queue.length + ' Lagu Mengantre'"></span>
                            </div>
                            <button type="button"
                                    @click="refreshQueue()"
                                    class="text-[11px] font-mono text-[#7A6A58] hover:text-[#1F1812] transition flex items-center gap-1 cursor-pointer">
                                <span>🔄</span>
                                <span>Segarkan Antrean</span>
                            </button>
                        </div>

                        <!-- STATE KOSONG: BELUM ADA REQUEST TAMU -->
                        <template x-if="queue.length === 0">
                            <div class="flex-1 flex flex-col items-center justify-center text-center p-6 sm:p-8">
                                <div class="bg-[#FAF7F2] border border-[#E4DCCC] p-6 text-center rounded-2xl my-auto shadow-2xs max-w-sm w-full">
                                    <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-white text-[#D9973E] border border-[#E4DCCC] flex items-center justify-center text-2xl shadow-xs">
                                        ☕
                                    </div>
                                    <div class="font-serif font-bold text-base text-[#1F1812]">Antrean Request Kosong</div>
                                    <p class="font-sans text-xs text-[#7A6A58] mt-1.5 leading-relaxed">
                                        Saat ini tidak ada request lagu dari tamu. Sound station memutar <b>Playlist Bawaan Kafe</b> secara otomatis.
                                    </p>
                                    <div class="mt-4 pt-3 border-t border-[#E4DCCC] flex flex-wrap items-center justify-center gap-2">
                                        <button type="button"
                                                @click="activeTab = 'default_tracks'"
                                                class="px-4 py-2 bg-[#1F1812] hover:bg-[#3A3026] text-white font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all shadow-xs flex items-center gap-1.5 cursor-pointer active:scale-95">
                                            <span>🎵</span>
                                            <span>Kelola Playlist Bawaan</span>
                                        </button>
                                        <a href="{{ route('music.request') }}" target="_blank"
                                           class="px-4 py-2 bg-white border border-[#E4DCCC] text-[#5C4D3C] hover:bg-[#FAF7F2] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all flex items-center gap-1.5 shadow-2xs active:scale-95">
                                            <span>Buka Form Tamu</span>
                                            <span>↗</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- LIST KARTU ANTREAN (IMPROVED STYLING DENGAN NOMOR DAN HOVER) -->
                        <template x-if="queue.length > 0">
                            <div class="space-y-2.5 overflow-y-auto pr-1 flex-1 min-h-0">
                                <template x-for="(item, index) in queue" :key="item.id">
                                    <div class="track-row-marquee p-3 bg-white hover:bg-[#FAF7F2] border border-[#E4DCCC] hover:border-[#D9973E]/70 rounded-xl flex items-center justify-between gap-3 text-xs transition-all shadow-2xs group/track">
                                        
                                        <!-- NOMOR URUT & THUMBNAIL -->
                                        <div class="flex items-center gap-3 min-w-0 flex-1">
                                            <span class="font-mono text-xs font-bold text-[#8A7B66] w-6 text-center shrink-0"
                                                  x-text="'#' + (index + 1)"></span>
                                            
                                            <div class="w-13 h-10 bg-black border border-[#E4DCCC] rounded-lg overflow-hidden shrink-0 relative">
                                                <template x-if="item.thumbnail_url">
                                                    <img :src="item.thumbnail_url" alt="Thumb" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!item.thumbnail_url">
                                                    <div class="w-full h-full flex items-center justify-center bg-[#140E0A] text-[#D9973E] text-xs">
                                                        ♫
                                                    </div>
                                                </template>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <div class="title-marquee-wrap min-w-0 flex-1">
                                                        <span class="title-marquee-text font-bold text-xs text-[#1F1812] group-hover/track:text-[#B87728] transition-colors"
                                                              :title="item.title"
                                                              x-text="item.title"></span>
                                                    </div>
                                                    <template x-if="item.type === 'request' || item.is_request">
                                                        <span class="px-2 py-0.5 bg-[#D9973E]/15 text-[#B87728] border border-[#D9973E]/30 text-[9px] font-mono font-bold rounded-full shrink-0">★ Request</span>
                                                    </template>
                                                    <template x-if="item.type === 'default' || !item.is_request">
                                                        <span class="px-2 py-0.5 bg-[#5F7F42]/15 text-[#5F7F42] border border-[#5F7F42]/30 text-[9px] font-mono font-bold rounded-full shrink-0">🎵 Bawaan</span>
                                                    </template>
                                                </div>
                                                <div class="text-[11px] text-[#7A6A58] truncate mt-0.5" x-text="item.artist || 'YouTube'"></div>
                                                <template x-if="item.customer_name">
                                                    <div class="font-mono text-[10px] text-[#B87728] mt-0.5 truncate font-semibold" x-text="'Oleh: ' + item.customer_name"></div>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <!-- ACTIONS UNTUK REQUEST PELANGGAN -->
                                            <template x-if="item.type === 'request' || item.is_request">
                                                <div class="flex items-center gap-1.5">
                                                    <button type="button"
                                                            @click="skipQueueItem(item.id)"
                                                            class="px-2.5 py-1.5 bg-[#FAF7F2] hover:bg-[#F0EAE1] border border-[#E4DCCC] text-[10px] font-mono uppercase font-bold text-[#5C4D3C] hover:text-[#1F1812] rounded-lg transition cursor-pointer active:scale-95 shadow-2xs">
                                                        Lewati
                                                    </button>
                                                    <button type="button"
                                                            @click="rejectQueueItem(item.id)"
                                                            class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-[10px] font-mono uppercase font-bold text-rose-700 rounded-lg transition cursor-pointer active:scale-95 shadow-2xs">
                                                        Tolak
                                                    </button>
                                                    <button type="button"
                                                            @click="autoBanQueueItem(item)"
                                                            title="Tolak dan masukkan lagu ini ke Ban List (Blacklist)"
                                                            class="px-2.5 py-1.5 bg-rose-100/70 hover:bg-rose-200 border border-rose-300 text-[10px] font-mono uppercase font-bold text-rose-800 rounded-lg transition cursor-pointer active:scale-95 shadow-2xs flex items-center gap-1">
                                                        <span>🚫</span>
                                                        <span>Ban</span>
                                                    </button>
                                                </div>
                                            </template>

                                            <!-- ACTIONS UNTUK LAGU BAWAAN -->
                                            <template x-if="item.type === 'default' || !item.is_request">
                                                <div class="flex items-center gap-1.5">
                                                    <button type="button"
                                                            @click="playDefaultTrackDirect(item)"
                                                            class="px-3 py-1.5 bg-[#FAF7F2] hover:bg-[#D9973E] text-[#1F1812] hover:text-white border border-[#E4DCCC] hover:border-[#D9973E] text-[10px] font-mono uppercase font-bold rounded-lg transition cursor-pointer flex items-center gap-1 active:scale-95 shadow-2xs">
                                                        <span>▶</span>
                                                        <span>Putar</span>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- TAB 2: PLAYLIST BAWAAN KAFE -->
                    <div x-show="activeTab === 'default_tracks'" class="h-full flex flex-col min-h-0">
                        <div class="flex items-center justify-between pb-2 border-b border-[#E4DCCC] shrink-0 mb-3">
                            <div>
                                <span class="font-mono text-xs uppercase tracking-wider font-bold text-[#1F1812]">Playlist Bawaan Kasir / Kafe</span>
                                <p class="text-[11px] text-[#7A6A58] mt-0.5">Diputar otomatis berurutan saat tidak ada request tamu.</p>
                            </div>
                            <span class="text-[11px] font-mono font-bold text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/20 px-2.5 py-0.5 rounded-full"
                                  x-text="defaultTracks.length + ' Lagu Terdaftar'"></span>
                        </div>

                        <!-- FORM TAMBAH LAGU BAWAAN (AUTO METADATA DARI LINK) -->
                        <div class="p-3.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-2xl shadow-2xs text-[#1F1812] shrink-0 mb-3">
                            <div class="flex items-center justify-between mb-3 border-b border-[#E4DCCC] pb-2.5">
                                <div>
                                    <div class="font-mono text-xs uppercase tracking-wider text-[#1F1812] font-bold">
                                        + Tambah Lagu ke Playlist Bawaan
                                    </div>
                                    <p class="text-[11px] text-[#7A6A58] mt-0.5">
                                        Tempel link YouTube. Judul, artis, durasi, dan cover akan <b>otomatis terisi</b>.
                                    </p>
                                </div>
                                <!-- SWITCH MODE: 1 LINK ATAU BANYAK LINK (BATCH) -->
                                <div class="flex items-center gap-1 bg-white border border-[#E4DCCC] p-1 rounded-xl text-[10px] font-mono">
                                    <button type="button" @click="importMode = 'single'"
                                            class="px-2.5 py-1 rounded-lg transition cursor-pointer"
                                            :class="importMode === 'single' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-xs' : 'text-[#7A6A58] hover:text-[#1F1812]'">
                                        1 Link (Auto)
                                    </button>
                                    <button type="button" @click="importMode = 'batch'"
                                            class="px-2.5 py-1 rounded-lg transition cursor-pointer"
                                            :class="importMode === 'batch' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-xs' : 'text-[#7A6A58] hover:text-[#1F1812]'">
                                        Banyak (Batch)
                                    </button>
                                </div>
                            </div>

                            <!-- MODE 1: SINGLE LINK AUTO IMPORT (AJAX NO REFRESH) -->
                            <form x-show="importMode === 'single'" @submit.prevent="submitSingleTrack()" data-no-pjax class="space-y-3">
                                <div>
                                    <label class="block text-[11px] font-mono uppercase text-[#7A6A58] mb-1.5 font-bold">
                                        Link Video YouTube <span class="text-[#D9973E]">*</span>
                                    </label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1">
                                            <input type="text" name="youtube_url" required
                                                   x-model="importLink"
                                                   @input.debounce.400ms="inspectUrl()"
                                                   @paste="setTimeout(() => inspectUrl(), 50)"
                                                   placeholder="Tempel link YouTube (misal: https://youtu.be/...)"
                                                   class="w-full px-3.5 py-2 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-[#D9973E] focus:ring-1 focus:ring-[#D9973E] shadow-2xs font-medium">
                                            <div x-show="inspectingLink" class="absolute right-3 top-2 text-xs text-[#D9973E] font-mono animate-pulse flex items-center gap-1">
                                                <span>⏳</span>
                                                <span>Mendeteksi judul...</span>
                                            </div>
                                        </div>
                                        <button type="submit"
                                                :disabled="inspectingLink || !importLink || isSubmittingSingle"
                                                class="px-4 py-2 bg-gradient-to-r from-[#D9973E] to-[#B87728] hover:from-[#E5A44B] hover:to-[#C2822B] text-[#1F1812] font-mono text-xs uppercase tracking-wider border border-[#B87728] rounded-xl transition font-bold disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95">
                                            <span x-show="isSubmittingSingle" class="animate-spin text-xs">⟳</span>
                                            <span x-text="isSubmittingSingle ? 'Menyimpan...' : 'Simpan Lagu'"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- PRATINJAU OTOMATIS VIDEO YOUTUBE -->
                                <template x-if="inspectedVideo">
                                    <div class="p-3 border rounded-xl flex items-center gap-3 animate-fade-in bg-white border-[#D9973E]/40 shadow-2xs">
                                        <img :src="inspectedVideo.thumbnail_url" alt="Thumb" class="w-14 h-10 object-cover rounded-lg border border-[#E4DCCC] shrink-0">
                                        <div class="min-w-0 flex-1 text-xs">
                                            <div class="title-marquee-wrap track-row-marquee">
                                                <div class="title-marquee-text font-bold text-[#1F1812]"
                                                     :title="inspectedVideo.title"
                                                     x-text="inspectedVideo.title"></div>
                                            </div>
                                            <div class="text-[#7A6A58] text-[11px] truncate" x-text="inspectedVideo.artist || 'YouTube Channel'"></div>
                                            <div class="mt-0.5 flex items-center gap-2 font-mono text-[10px]">
                                                <span class="px-2 py-0.5 rounded-full bg-[#5F7F42]/10 text-[#5F7F42] border border-[#5F7F42]/20 font-bold"
                                                      x-text="'⏱️ ' + (inspectedVideo.duration_formatted || formatTime(inspectedVideo.duration_seconds || 0))"></span>
                                                <span class="text-[#7A6A58]">Judul otomatis terisi</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- PESAN ERROR INSPECT -->
                                <div x-show="inspectError" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-mono" x-text="inspectError"></div>

                                <!-- OPTIONAL EDITABLE TITLE & ARTIST -->
                                <div class="pt-2 border-t border-[#E4DCCC]">
                                    <details class="group">
                                        <summary class="cursor-pointer text-[11px] font-mono text-[#7A6A58] hover:text-[#1F1812] flex items-center justify-between select-none">
                                            <span>⚙️ Edit Judul / Nama Artis Kustom (Opsional)</span>
                                            <span class="group-open:rotate-180 transition-transform">▼</span>
                                        </summary>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-2.5">
                                            <div>
                                                <label class="block text-[10px] font-mono text-[#7A6A58] mb-1 font-bold">Judul Lagu</label>
                                                <input type="text" name="title" x-model="importTitle" placeholder="Gunakan judul YouTube"
                                                       class="w-full px-3 py-1.5 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:border-[#D9973E]">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-mono text-[#7A6A58] mb-1 font-bold">Nama Artis</label>
                                                <input type="text" name="artist" x-model="importArtist" placeholder="Gunakan channel YouTube"
                                                       class="w-full px-3 py-1.5 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:border-[#D9973E]">
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            </form>

                            <!-- MODE 2: BATCH IMPORT MULTIPLE LINKS (AJAX NO REFRESH) -->
                            <form x-show="importMode === 'batch'" @submit.prevent="submitBatchTracks()" data-no-pjax class="space-y-3">
                                <div>
                                    <label class="block text-[11px] font-mono uppercase text-[#7A6A58] mb-1.5 font-bold">
                                        Daftar Link Video YouTube (1 Link per Baris)
                                    </label>
                                    <textarea x-model="batchUrls" rows="3" required
                                              placeholder="Tempel beberapa link YouTube di sini, misal:&#10;https://youtu.be/RO75uUZiAw0&#10;https://youtu.be/GxldQ9GyXLA"
                                              class="w-full p-2.5 bg-white border border-[#D6CBB8] rounded-xl text-xs font-mono text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-[#D9973E] focus:ring-1 focus:ring-[#D9973E] shadow-2xs"></textarea>
                                    <p class="text-[10px] text-[#7A6A58] mt-1 font-mono">
                                        Sistem otomatis mengambil judul dan durasi untuk tiap lagu.
                                    </p>
                                </div>
                                <button type="submit"
                                        :disabled="isSubmittingBatch || !batchUrls"
                                        class="px-4 py-2 bg-gradient-to-r from-[#D9973E] to-[#B87728] hover:from-[#E5A44B] hover:to-[#C2822B] text-[#1F1812] font-mono text-xs uppercase tracking-wider border border-[#B87728] rounded-xl transition font-bold disabled:opacity-50 flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95">
                                    <span x-show="isSubmittingBatch" class="animate-spin text-xs">⟳</span>
                                    <span x-text="isSubmittingBatch ? 'Mengimpor...' : '📥 Import Semua Lagu Sekaligus'"></span>
                                </button>
                            </form>
                        </div>

                        <!-- LIST DAFTAR LAGU BAWAAN (REAKTIF REALTIME TANPA RELOAD) -->
                        <div class="flex-1 flex flex-col min-h-0">
                            <div class="flex items-center justify-between text-[11px] font-mono text-[#7A6A58] pb-1.5 border-b border-[#E4DCCC] shrink-0 mb-2">
                                <span>Tarik ⋮⋮ untuk ubah urutan &bull; Klik ▶ Putar langsung</span>
                                <span x-text="defaultTracks.length + ' Lagu'"></span>
                            </div>

                            <template x-if="defaultTracks.length === 0">
                                <div class="p-6 bg-[#FAF7F2] border border-[#E4DCCC] rounded-2xl text-center text-xs font-mono text-[#7A6A58]">
                                    Belum ada lagu di playlist bawaan. Tempel link YouTube di atas untuk menambahkan.
                                </div>
                            </template>

                            <div class="flex-1 overflow-y-auto pr-1.5 space-y-2 min-h-0">
                                <template x-for="(track, index) in defaultTracks" :key="track.id">
                                    <div draggable="true"
                                         @dragstart="onTrackDragStart($event, index)"
                                         @dragover.prevent="onTrackDragOver($event, index)"
                                         @dragenter.prevent="dragOverIndex = index"
                                         @dragleave="dragOverIndex = (dragOverIndex === index ? null : dragOverIndex)"
                                         @drop="onTrackDrop($event, index)"
                                         @dragend="onTrackDragEnd($event)"
                                         class="track-row-marquee p-3 bg-white hover:bg-[#FAF7F2] border rounded-xl flex items-center justify-between gap-2.5 text-xs transition-all select-none shadow-2xs text-[#1F1812]"
                                         :class="{
                                             'border-[#D9973E] bg-[#FFFBF5] shadow-md ring-2 ring-[#D9973E]/30': dragOverIndex === index,
                                             'opacity-40 border-dashed border-[#D9973E]': draggedIndex === index,
                                             'border-[#E4DCCC] hover:border-[#D9973E]/60': dragOverIndex !== index && draggedIndex !== index,
                                             'border-l-4 border-l-[#D9973E] bg-[#FFFBF5] border-[#E4DCCC] shadow-xs': currentTrack && currentTrack.id === track.id && currentTrack.type === 'default_track'
                                         }">
                                        
                                        <!-- DRAG HANDLE & NUMBER -->
                                        <div class="flex items-center gap-1.5 shrink-0 cursor-grab active:cursor-grabbing text-[#A89A85] hover:text-[#1F1812] px-1 py-1"
                                             title="Tahan dan geser untuk memindahkan urutan lagu">
                                            <span class="text-sm font-bold leading-none tracking-tighter select-none">⋮⋮</span>
                                            <span class="font-mono text-[11px] font-semibold text-[#8A7B66] w-6 h-6 rounded-lg bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center shadow-2xs" x-text="index + 1"></span>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <div class="title-marquee-wrap min-w-0 flex-1">
                                                    <span class="title-marquee-text font-bold text-[#1F1812]"
                                                          :title="track.title"
                                                          x-text="track.title"></span>
                                                </div>
                                                <template x-if="track.duration_seconds > 0 && track.duration_seconds < 86400 && !(track.title && (track.title.toLowerCase().includes('radio') || track.title.toLowerCase().includes('live 24/7') || track.title.toLowerCase().includes('[live]')))">
                                                    <span class="font-mono text-[10px] text-[#7A6A58] bg-[#FAF7F2] border border-[#E4DCCC] px-2 py-0.5 rounded-full font-bold shrink-0"
                                                          x-text="'⏱️ ' + formatTime(track.duration_seconds)"></span>
                                                </template>
                                                <template x-if="track.duration_seconds >= 86400 || (track.title && (track.title.toLowerCase().includes('radio') || track.title.toLowerCase().includes('live 24/7') || track.title.toLowerCase().includes('[live]')))">
                                                    <span class="font-mono text-[10px] text-red-700 bg-red-50 border border-red-200 px-2 py-0.5 rounded-full inline-flex items-center gap-1 font-bold shrink-0">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                                        <span>RADIO 24/7</span>
                                                    </span>
                                                </template>
                                            </div>
                                            <div class="text-[#7A6A58] text-[11px] truncate mt-0.5 font-mono">
                                                <span x-text="track.artist || 'Artis Kafe'"></span> &bull; <span class="text-[10px]" x-text="'ID: ' + track.youtube_id"></span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <!-- PLAY DIRECT -->
                                            <button type="button"
                                                    @click="playDefaultTrackDirect(track)"
                                                    title="Putar lagu ini sekarang"
                                                    class="px-2.5 py-1.5 rounded-lg font-mono text-[10px] uppercase font-bold border transition-all flex items-center gap-1 cursor-pointer active:scale-95 shadow-2xs"
                                                    :class="(currentTrack && currentTrack.id === track.id && isPlaying)
                                                        ? 'bg-gradient-to-r from-[#D9973E] to-[#B87728] text-[#1F1812] border-[#B87728] shadow-[0_0_8px_rgba(217,151,62,0.4)]'
                                                        : 'bg-[#FAF7F2] hover:bg-[#D9973E] text-[#1F1812] hover:text-white border-[#E4DCCC]'">
                                                <span x-text="(currentTrack && currentTrack.id === track.id && isPlaying) ? '▶ Diputar' : '▶ Putar'"></span>
                                            </button>

                                            <!-- EDIT -->
                                            <button type="button"
                                                    @click="openEditModal(track)"
                                                    class="px-2.5 py-1.5 rounded-lg font-mono text-[10px] uppercase border border-[#E4DCCC] text-[#5C4D3C] hover:bg-[#FAF7F2] hover:text-[#1F1812] transition flex items-center gap-1 cursor-pointer active:scale-95 shadow-2xs">
                                                <span>✏️</span>
                                                <span>Edit</span>
                                            </button>

                                            <!-- TOGGLE ACTIVE -->
                                            <button type="button"
                                                    @click="toggleTrack(track)"
                                                    class="px-2.5 py-1.5 rounded-lg font-mono text-[10px] uppercase font-bold border transition cursor-pointer active:scale-95 shadow-2xs"
                                                    :class="track.is_active ? 'bg-[#5F7F42]/10 text-[#5F7F42] border border-[#5F7F42]/30 hover:bg-[#5F7F42]/20' : 'bg-[#FAF7F2] text-[#A89A85] border border-[#E4DCCC] hover:bg-[#F0EAE1]'"
                                                    :title="track.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'">
                                                <span x-text="track.is_active ? '✓ Aktif' : 'Nonaktif'"></span>
                                            </button>

                                            <!-- BAN DARI PLAYLIST BAWAAN -->
                                            <button type="button"
                                                    @click="autoBanDefaultTrack(track)"
                                                    title="Ban lagu ini agar tidak dapat di-request dan hapus dari playlist bawaan"
                                                    class="px-2 py-1.5 rounded-lg font-mono text-[10px] uppercase font-bold text-rose-700 border border-rose-200 bg-rose-50 hover:bg-rose-100 transition cursor-pointer active:scale-95 shadow-2xs flex items-center gap-0.5">
                                                <span>🚫</span>
                                                <span>Ban</span>
                                            </button>

                                            <!-- DELETE -->
                                            <button type="button"
                                                    @click="deleteTrack(track)"
                                                    title="Hapus lagu ini dari playlist bawaan"
                                                    class="px-2.5 py-1.5 rounded-lg font-mono text-[10px] uppercase font-bold text-stone-600 border border-[#E4DCCC] bg-[#FAF7F2] hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 transition cursor-pointer active:scale-95 shadow-2xs">
                                                ✕ Hapus
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: RIWAYAT PEMUTARAN -->
                    <div x-show="activeTab === 'history'" class="h-full flex flex-col min-h-0">
                        <div class="flex items-center justify-between pb-2 border-b border-[#E4DCCC] shrink-0 mb-3">
                            <div>
                                <span class="font-mono text-xs uppercase tracking-wider font-bold text-[#1F1812]">Riwayat Lagu Request Terakhir</span>
                                <p class="text-[11px] text-[#7A6A58] mt-0.5">Daftar lagu yang pernah diminta pelanggan. Klik <b>+ Playlist Bawaan</b> untuk menyimpan lagu favorit ke koleksi kafe.</p>
                            </div>
                            <span class="text-[10px] font-mono font-bold text-[#7A6A58] bg-[#FAF7F2] border border-[#E4DCCC] px-2 py-0.5 rounded-full shrink-0">{{ count($recentHistory) }} Riwayat</span>
                        </div>
                        <div class="flex-1 overflow-y-auto pr-1 space-y-2 min-h-0">
                            @forelse ($recentHistory as $hist)
                                <div class="track-row-marquee p-3 bg-white hover:bg-[#FAF7F2] border border-[#E4DCCC] hover:border-[#D9973E]/60 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs rounded-xl shadow-2xs text-[#1F1812]">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="title-marquee-wrap min-w-0 flex-1">
                                                <span class="title-marquee-text font-bold text-xs text-[#1F1812]"
                                                      title="{{ $hist->song_title }}">{{ $hist->song_title }}</span>
                                            </div>
                                            @if ($hist->artist)
                                                <span class="text-[#7A6A58] text-[11px] font-normal truncate font-mono shrink-0">({{ $hist->artist }})</span>
                                            @endif
                                        </div>
                                        <div class="text-[#7A6A58] text-[11px] truncate mt-0.5 font-mono">
                                            Peminta: <span class="font-medium text-[#1F1812]">{{ $hist->customer_name ?: 'Pelanggan' }}</span> &bull; {{ $hist->updated_at->format('H:i') }}
                                        </div>
                                        @if ($hist->notes)
                                            <div class="text-[10px] text-amber-600 mt-0.5 truncate flex items-center gap-1 font-mono">
                                                <span>⚠️</span>
                                                <span>{{ $hist->notes }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                        <span class="font-mono text-[10px] uppercase font-bold px-2 py-0.5 rounded-full border
                                            @if($hist->status === 'played') bg-blue-50 text-blue-700 border-blue-200
                                            @elseif($hist->status === 'skipped') bg-yellow-50 text-yellow-700 border-yellow-200
                                            @else bg-rose-50 text-rose-700 border-rose-200 @endif">
                                            {{ strtoupper($hist->status) }}
                                        </span>

                                        @if ($hist->youtube_id)
                                            <!-- TOMBOL PUTAR LANGSUNG -->
                                            <button type="button"
                                                    @click="playHistoryTrackDirect({{ json_encode([
                                                        'id' => $hist->id,
                                                        'title' => $hist->song_title,
                                                        'artist' => $hist->artist ?: 'YouTube',
                                                        'youtube_id' => $hist->youtube_id,
                                                        'duration_seconds' => $hist->duration_seconds ?? 0,
                                                        'type' => 'history'
                                                    ]) }})"
                                                    title="Putar langsung lagu ini sekarang"
                                                    class="px-2.5 py-1 bg-[#FAF7F2] hover:bg-[#D9973E] text-[#1F1812] hover:text-white border border-[#E4DCCC] hover:border-[#D9973E] rounded-lg font-mono text-[11px] font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer active:scale-95">
                                                <span>▶</span>
                                                <span class="hidden md:inline">Putar</span>
                                            </button>

                                            <!-- TOMBOL MASUKKAN KE PLAYLIST BAWAAN -->
                                            <button type="button"
                                                    @click="addRequestToDefault({{ $hist->id }}, '{{ addslashes($hist->song_title) }}')"
                                                    :disabled="addingToDefaultId === {{ $hist->id }} || isInDefaultPlaylist('{{ $hist->youtube_id }}')"
                                                    class="px-3 py-1 font-mono text-[11px] rounded-lg transition flex items-center gap-1.5 shadow-2xs"
                                                    :class="isInDefaultPlaylist('{{ $hist->youtube_id }}')
                                                        ? 'bg-[#5F7F42]/15 text-[#5F7F42] border border-[#5F7F42]/30 cursor-default font-semibold'
                                                        : 'bg-white hover:bg-[#D9973E] text-[#5C4D3C] hover:text-[#1F1812] border border-[#E4DCCC] hover:border-[#D9973E] font-bold cursor-pointer active:scale-95'"
                                                    :title="isInDefaultPlaylist('{{ $hist->youtube_id }}') ? 'Lagu ini sudah ada di playlist bawaan' : 'Tambahkan lagu ini ke playlist bawaan kafe'">
                                                <span x-show="addingToDefaultId === {{ $hist->id }}" class="animate-spin text-xs">⟳</span>
                                                <span x-show="addingToDefaultId !== {{ $hist->id }} && isInDefaultPlaylist('{{ $hist->youtube_id }}')">✓</span>
                                                <span x-show="addingToDefaultId !== {{ $hist->id }} && !isInDefaultPlaylist('{{ $hist->youtube_id }}')">＋</span>
                                                <span x-text="isInDefaultPlaylist('{{ $hist->youtube_id }}') ? 'Di Playlist' : 'Playlist Bawaan'"></span>
                                            </button>

                                            <!-- TOMBOL BAN LAGU DARI RIWAYAT -->
                                            <button type="button"
                                                    @click="autoBanHistoryTrack({{ json_encode([
                                                        'youtube_id' => $hist->youtube_id,
                                                        'song_title' => $hist->song_title,
                                                        'artist' => $hist->artist ?: 'YouTube',
                                                    ]) }})"
                                                    title="Masukkan lagu ini ke Ban List agar tidak dapat di-request lagi"
                                                    class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 hover:border-rose-300 font-mono text-[11px] font-bold rounded-lg transition flex items-center gap-1 shadow-2xs cursor-pointer active:scale-95">
                                                <span>🚫</span>
                                                <span class="hidden md:inline">Ban</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-10 text-[#7A6A58] font-mono text-xs bg-[#FAF7F2] border border-[#E4DCCC] rounded-2xl">
                                    Belum ada riwayat pemutaran request.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- TAB 4: BAN LIST (BLACKLIST) LAGU -->
                    <div x-show="activeTab === 'ban_list'" class="h-full flex flex-col min-h-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 mb-3 border-b border-[#E4DCCC] gap-2 shrink-0">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs uppercase tracking-wider font-bold text-rose-700">🚫 Ban List / Blacklist Lagu</span>
                                    <span class="text-[10px] font-mono font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-full"
                                          x-text="bannedTracks.length + ' Lagu Dilarang'"></span>
                                </div>
                                <p class="text-[11px] text-[#7A6A58] mt-0.5">Lagu atau kata kunci yang dilarang diputar di kafe. Request dari pelanggan yang cocok akan otomatis ditolak oleh sistem.</p>
                            </div>

                            <!-- SEARCH BAR BAN LIST -->
                            <div class="relative w-full sm:w-56 shrink-0">
                                <input type="text"
                                       x-model="banSearchQuery"
                                       placeholder="Cari lagu di ban list..."
                                       class="w-full pl-8 pr-3 py-1.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-rose-500 font-mono shadow-2xs">
                                <span class="absolute left-2.5 top-2 text-xs text-[#A89A85]">🔍</span>
                            </div>
                        </div>

                        <!-- FORM INPUT MANUAL BAN LIST -->
                        <div class="p-3.5 bg-[#FFF8F8] border border-rose-200 rounded-2xl shadow-2xs text-[#1F1812] shrink-0 mb-3">
                            <div class="flex items-center justify-between mb-2.5 border-b border-rose-100 pb-2">
                                <div class="font-mono text-xs uppercase tracking-wider text-rose-900 font-bold flex items-center gap-1.5">
                                    <span>➕</span>
                                    <span>Tambah Lagu / Kata Kunci ke Ban List (Manual)</span>
                                </div>
                                <span class="text-[10px] font-mono text-rose-600 bg-rose-100/60 px-2 py-0.5 rounded-full font-semibold">Blokir Instan</span>
                            </div>

                            <form @submit.prevent="submitBanManual()" data-no-pjax class="space-y-2.5">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                                    <div>
                                        <label class="block text-[10px] font-mono uppercase text-[#7A6A58] mb-1 font-bold">Link / ID Video YouTube (Opsional)</label>
                                        <input type="text"
                                               x-model="banForm.youtube_url"
                                               placeholder="https://youtu.be/... atau ID 11 digit"
                                               class="w-full px-3 py-1.5 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-rose-500 font-mono shadow-2xs">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-mono uppercase text-[#7A6A58] mb-1 font-bold">Judul Lagu / Kata Kunci <span class="text-rose-600">*</span></label>
                                        <input type="text"
                                               x-model="banForm.title"
                                               placeholder="Contoh: DJ Remix, Despacito, dll."
                                               class="w-full px-3 py-1.5 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-rose-500 font-medium shadow-2xs"
                                               required>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-mono uppercase text-[#7A6A58] mb-1 font-bold">Nama Artis (Opsional)</label>
                                        <input type="text"
                                               x-model="banForm.artist"
                                               placeholder="Contoh: Artis / Penyanyi"
                                               class="w-full px-3 py-1.5 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-rose-500 font-medium shadow-2xs">
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row items-center justify-between gap-2.5 pt-1">
                                    <div class="w-full sm:flex-1">
                                        <input type="text"
                                               x-model="banForm.reason"
                                               placeholder="Alasan dilarang (Contoh: Terlalu bising / lirik tidak pantas)"
                                               class="w-full px-3 py-1.5 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-rose-500 font-medium shadow-2xs">
                                    </div>
                                    <button type="submit"
                                            :disabled="isSubmittingBan || (!banForm.title && !banForm.youtube_url)"
                                            class="w-full sm:w-auto px-4 py-1.5 bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-700 hover:to-rose-800 text-white font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-xs disabled:opacity-50 flex items-center justify-center gap-1.5 cursor-pointer active:scale-95 shrink-0">
                                        <span x-show="isSubmittingBan" class="animate-spin text-xs">⟳</span>
                                        <span>🚫 Masukkan ke Ban List</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- LIST LAGU YANG DI-BAN (SCROLLABLE AREA) -->
                        <div class="flex-1 overflow-y-auto pr-1 space-y-2 min-h-0">
                            <template x-if="filteredBannedTracks.length === 0">
                                <div class="h-full flex flex-col items-center justify-center text-center py-10 text-[#7A6A58] font-mono text-xs bg-[#FAF7F2] border border-[#E4DCCC] rounded-2xl">
                                    <span class="text-3xl mb-2 opacity-60">🛡️</span>
                                    <span class="font-bold text-[#1F1812]">Tidak ada lagu di Ban List</span>
                                    <span class="text-[11px] mt-1 text-[#7A6A58]" x-text="banSearchQuery ? 'Tidak ada lagu yang cocok dengan pencarian.' : 'Belum ada lagu yang diblokir. Tambahkan lagu manual di atas atau gunakan tombol Ban di antrean.'"></span>
                                </div>
                            </template>

                            <template x-for="(track, index) in filteredBannedTracks" :key="track.id">
                                <div class="p-3 bg-white hover:bg-rose-50/40 border border-[#E4DCCC] hover:border-rose-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs rounded-xl shadow-2xs text-[#1F1812]"
                                     :class="!track.is_active ? 'opacity-60 bg-gray-50' : ''">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <span class="font-mono font-bold text-rose-600 text-xs w-5 text-center shrink-0" x-text="'#' + (index + 1)"></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <div class="font-bold text-xs text-[#1F1812] truncate" x-text="track.title || 'Lagu Tanpa Judul'"></div>
                                                <template x-if="track.youtube_id">
                                                    <a :href="'https://www.youtube.com/watch?v=' + track.youtube_id" target="_blank" rel="noopener"
                                                       class="px-1.5 py-0.5 bg-red-100 text-red-700 hover:bg-red-200 rounded text-[9px] font-mono font-bold shrink-0 flex items-center gap-0.5">
                                                        <span>▶ YT</span>
                                                        <span x-text="track.youtube_id"></span>
                                                    </a>
                                                </template>
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-mono font-bold shrink-0"
                                                      :class="track.is_active ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-gray-200 text-gray-700'">
                                                    <span x-text="track.is_active ? '● AKTIF' : '○ NONAKTIF'"></span>
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-[#7A6A58] truncate mt-0.5 flex items-center gap-2 font-mono">
                                                <span x-text="track.artist ? ('Artis: ' + track.artist) : 'Semua Artis'"></span>
                                                <span>&bull;</span>
                                                <span class="text-rose-600 font-semibold" x-text="'Alasan: ' + (track.reason || 'Dilarang kasir')"></span>
                                                <template x-if="track.banned_by">
                                                    <span class="text-[#A89A85]" x-text="'(' + track.banned_by + ')'"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                        <!-- TOGGLE AKTIF/NONAKTIF -->
                                        <button type="button"
                                                @click="toggleBanStatus(track)"
                                                :title="track.is_active ? 'Nonaktifkan sementara (izinkan diputar)' : 'Aktifkan kembali larangan'"
                                                class="px-2.5 py-1 font-mono text-[11px] font-bold rounded-lg border transition cursor-pointer active:scale-95 shadow-2xs"
                                                :class="track.is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200'">
                                            <span x-text="track.is_active ? 'Nonaktifkan' : 'Aktifkan'"></span>
                                        </button>

                                        <!-- HAPUS DARI BAN LIST -->
                                        <button type="button"
                                                @click="deleteBanTrack(track)"
                                                title="Hapus permanen dari Ban List"
                                                class="px-2.5 py-1 bg-white hover:bg-rose-50 text-rose-700 hover:text-rose-800 border border-[#E4DCCC] hover:border-rose-300 font-mono text-[11px] font-bold rounded-lg transition cursor-pointer active:scale-95 shadow-2xs flex items-center gap-1">
                                            <span>🗑️</span>
                                            <span class="hidden md:inline">Hapus</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

            </div>

        </div>
    </main>

    <!-- MODAL EDIT LAGU BAWAAN KAFE (AJAX NO REFRESH) -->
    <div x-show="isEditingTrack"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-fade-in"
         @keydown.escape.window="closeEditModal()">
        <div class="bg-white border border-[#E4DCCC] rounded-2xl max-w-md w-full p-6 shadow-2xl relative text-[#1F1812] pt-6 overflow-hidden"
             @click.outside="closeEditModal()">

            <div class="flex items-center justify-between border-b border-[#E4DCCC] pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-[#FAF7F2] border border-[#E4DCCC] text-[#D9973E] inline-flex items-center justify-center text-sm shadow-2xs">✏️</span>
                    <h3 class="font-serif font-bold text-base text-[#1F1812]">Edit Lagu Bawaan Kafe</h3>
                </div>
                <button type="button" @click="closeEditModal()" class="text-[#7A6A58] hover:text-[#1F1812] text-lg font-bold leading-none cursor-pointer">
                    ✕
                </button>
            </div>

            <form @submit.prevent="submitEditTrack()" data-no-pjax class="space-y-4">
                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-bold">
                        Judul Lagu <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" required
                           x-model="editForm.title"
                           class="w-full px-3.5 py-2 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-[#D9973E] focus:ring-1 focus:ring-[#D9973E] font-medium shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-bold">
                        Nama Artis (Opsional)
                    </label>
                    <input type="text" name="artist"
                           x-model="editForm.artist"
                           placeholder="Kosongkan jika tidak ada"
                           class="w-full px-3.5 py-2 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-[#D9973E] focus:ring-1 focus:ring-[#D9973E] font-medium shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-bold">
                        Link atau ID Video YouTube
                    </label>
                    <input type="text" name="youtube_url"
                           x-model="editForm.youtube_url"
                           placeholder="Contoh: https://youtu.be/... atau ID YouTube 11 digit"
                           class="w-full px-3.5 py-2 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-[#D9973E] focus:ring-1 focus:ring-[#D9973E] font-medium shadow-2xs">
                    <p class="text-[10px] text-[#7A6A58] mt-1 font-mono">
                        Biarkan tautan tetap seperti ini jika hanya ingin mengubah nama lagu/artis.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-bold">
                        Urutan Putar (Sort Order)
                    </label>
                    <input type="number" name="sort_order" min="0"
                           x-model="editForm.sort_order"
                           class="w-28 px-3.5 py-1.5 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-[#D9973E] focus:ring-1 focus:ring-[#D9973E] font-mono font-bold shadow-2xs">
                </div>

                <div class="pt-3 border-t border-[#E4DCCC] flex justify-end gap-2">
                    <button type="button" @click="closeEditModal()"
                            class="px-4 py-2 bg-[#FAF7F2] border border-[#E4DCCC] text-[#7A6A58] font-mono text-xs uppercase tracking-wider rounded-xl hover:bg-[#F0EAE1] hover:text-[#1F1812] transition cursor-pointer shadow-2xs">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="isSavingEdit || !editForm.title"
                            class="px-5 py-2 bg-gradient-to-r from-[#D9973E] to-[#B87728] hover:from-[#E5A44B] hover:to-[#C2822B] text-[#1F1812] font-mono text-xs uppercase tracking-wider border border-[#B87728] rounded-xl transition font-bold shadow-xs disabled:opacity-50 flex items-center gap-1.5 cursor-pointer active:scale-95">
                        <span x-show="isSavingEdit" class="animate-spin text-xs">⟳</span>
                        <span x-text="isSavingEdit ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL CEPAT INPUT BAN LIST (BISA DIBUKA DARI TAB APAPUN TANPA GANTI TAB) -->
    <div x-show="showBanModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-fade-in"
         @keydown.escape.window="closeBanModal()">
        <div class="bg-white border border-rose-200 rounded-2xl max-w-md w-full p-6 shadow-2xl relative text-[#1F1812] overflow-hidden"
             @click.outside="closeBanModal()">

            <div class="flex items-center justify-between border-b border-[#E4DCCC] pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 inline-flex items-center justify-center text-sm shadow-2xs font-bold">🚫</span>
                    <div>
                        <h3 class="font-serif font-bold text-base text-[#1F1812]">Tambah ke Ban List</h3>
                        <p class="text-[10px] font-mono text-[#7A6A58]">Lagu dilarang di-request pelanggan</p>
                    </div>
                </div>
                <button type="button" @click="closeBanModal()" class="text-[#7A6A58] hover:text-[#1F1812] text-lg font-bold leading-none cursor-pointer">
                    ✕
                </button>
            </div>

            <form @submit.prevent="submitBanModal()" data-no-pjax class="space-y-3.5">
                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-bold">
                        Judul Lagu / Kata Kunci <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" id="banModalInput" required
                           x-model="banForm.title"
                           placeholder="Contoh: DJ Remix, Despacito, lagu tertentu..."
                           class="w-full px-3.5 py-2 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 font-medium shadow-2xs">
                    <p class="text-[10px] text-[#7A6A58] mt-1 font-mono">
                        Bisa berupa judul lengkap atau kata kunci larangan.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-bold">
                        Link / ID Video YouTube (Opsional)
                    </label>
                    <input type="text"
                           x-model="banForm.youtube_url"
                           placeholder="https://youtu.be/... atau ID YouTube 11 digit"
                           class="w-full px-3.5 py-2 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 font-mono shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-bold">
                        Nama Artis (Opsional)
                    </label>
                    <input type="text"
                           x-model="banForm.artist"
                           placeholder="Nama penyanyi / band"
                           class="w-full px-3.5 py-2 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 font-medium shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-bold">
                        Alasan Larangan (Opsional)
                    </label>
                    <input type="text"
                           x-model="banForm.reason"
                           placeholder="Contoh: Terlalu bising / lirik vulgar"
                           class="w-full px-3.5 py-2 bg-white border border-[#D6CBB8] rounded-xl text-xs text-[#1F1812] placeholder-[#A89A85] focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 font-medium shadow-2xs">
                </div>

                <div class="pt-3 border-t border-[#E4DCCC] flex justify-end gap-2">
                    <button type="button" @click="closeBanModal()"
                            class="px-4 py-2 bg-[#FAF7F2] border border-[#E4DCCC] text-[#7A6A58] font-mono text-xs uppercase tracking-wider rounded-xl hover:bg-[#F0EAE1] hover:text-[#1F1812] transition cursor-pointer shadow-2xs">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="isSubmittingBan || (!banForm.title && !banForm.youtube_url)"
                            class="px-5 py-2 bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-700 hover:to-rose-800 text-white font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-xs disabled:opacity-50 flex items-center gap-1.5 cursor-pointer active:scale-95">
                        <span x-show="isSubmittingBan" class="animate-spin text-xs">⟳</span>
                        <span>🚫 Masukkan ke Ban List</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function musicStationPage() {
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

        currentTrack: {!! json_encode($state['now_playing']) !!},
        currentTime: 0,
        duration: {{ $state['now_playing']['duration_seconds'] ?? 0 }},
        progressPercent: 0,
        currentTimeFormatted: '00:00',
        durationFormatted: '{{ isset($state['now_playing']['duration_seconds']) && $state['now_playing']['duration_seconds'] > 0 ? ($state['now_playing']['duration_seconds'] >= 3600 ? sprintf('%02d:%02d:%02d', floor($state['now_playing']['duration_seconds'] / 3600), floor(($state['now_playing']['duration_seconds'] % 3600) / 60), $state['now_playing']['duration_seconds'] % 60) : sprintf('%02d:%02d', floor($state['now_playing']['duration_seconds'] / 60), $state['now_playing']['duration_seconds'] % 60)) : '00:00' }}',
        isLive: false,

        queue: {!! json_encode($state['queue']) !!},
        queueCount: {{ $state['queue_count'] }},

        activeTab: (() => {
            try {
                const urlTab = new URLSearchParams(window.location.search).get('tab');
                const validTabs = ['queue', 'default_tracks', 'history', 'ban_list'];
                if (urlTab && validTabs.includes(urlTab)) return urlTab;
                const saved = localStorage.getItem('soundstation_active_tab');
                if (saved && validTabs.includes(saved)) return saved;
            } catch (e) {}
            return 'queue';
        })(),
        pausedCashierTrack: null,

        // DEFAULT TRACKS STATE (AJAX NO REFRESH)
        defaultTracks: {!! json_encode($defaultTracks) !!},
        batchUrls: '',
        draggedIndex: null,
        dragOverIndex: null,
        isSubmittingSingle: false,
        isSubmittingBatch: false,
        isSavingEdit: false,
        isSkipping: false,
        addingToDefaultId: null,

        // BAN LIST (BLACKLIST) STATE
        bannedTracks: {!! json_encode($bannedTracks ?? []) !!},
        banSearchQuery: '',
        showBanModal: false,
        banForm: {
            youtube_url: '',
            title: '',
            artist: '',
            reason: 'Dilarang oleh kasir'
        },
        isSubmittingBan: false,

        get filteredBannedTracks() {
            if (!this.banSearchQuery || !this.banSearchQuery.trim()) {
                return this.bannedTracks;
            }
            const q = this.banSearchQuery.toLowerCase().trim();
            return this.bannedTracks.filter(t => {
                return (t.title && t.title.toLowerCase().includes(q)) ||
                       (t.artist && t.artist.toLowerCase().includes(q)) ||
                       (t.youtube_id && t.youtube_id.toLowerCase().includes(q)) ||
                       (t.reason && t.reason.toLowerCase().includes(q));
            });
        },

        importMode: 'single', // 'single' atau 'batch'
        importLink: '',
        importTitle: '',
        importArtist: '',
        lastInspectedUrl: '',
        lastAutoTitle: '',
        lastAutoArtist: '',
        inspectingLink: false,
        inspectedVideo: null,
        inspectError: null,

        isEditingTrack: false,
        editForm: {
            id: null,
            title: '',
            artist: '',
            youtube_url: '',
            sort_order: 0
        },
        editUpdateUrl: '',

        openEditModal(data) {
            const id = data.id;
            const youtubeId = data.youtubeId || data.youtube_id || '';
            this.editForm = {
                id: Number(id),
                title: data.title || '',
                artist: data.artist || '',
                youtube_url: youtubeId ? ('https://youtu.be/' + youtubeId) : '',
                sort_order: Number(data.sortOrder ?? data.sort_order ?? 0)
            };
            this.editUpdateUrl = '{{ url('/kasir/music/default-tracks') }}/' + id;
            this.isEditingTrack = true;
        },

        closeEditModal() {
            this.isEditingTrack = false;
        },

        voiceAnnouncerEnabled: true,
        isAnnouncing: false,
        duckedVolume: 12,

        async inspectUrl() {
            const url = (this.importLink || '').trim();
            if (!url) {
                this.inspectedVideo = null;
                this.inspectError = null;
                this.lastInspectedUrl = '';
                return;
            }
            if (!url.includes('youtu') && url.length !== 11) {
                return;
            }
            if (url === this.lastInspectedUrl) {
                return;
            }

            this.inspectingLink = true;
            this.inspectError = null;
            this.lastInspectedUrl = url;

            try {
                const res = await fetch('{{ route('kasir.music.inspect') }}?url=' + encodeURIComponent(url));
                const data = await res.json();
                if (res.ok && data.valid) {
                    this.inspectedVideo = data;
                    if (!this.importTitle || this.importTitle === this.lastAutoTitle || this.importTitle === 'hh') {
                        this.importTitle = data.title || '';
                        this.lastAutoTitle = data.title || '';
                    }
                    if (!this.importArtist || this.importArtist === this.lastAutoArtist) {
                        this.importArtist = data.artist || '';
                        this.lastAutoArtist = data.artist || '';
                    }
                } else {
                    this.inspectedVideo = null;
                    this.inspectError = data.message || 'Tautan YouTube tidak valid.';
                }
            } catch (e) {
                this.inspectError = 'Gagal memuat info video YouTube secara otomatis. Anda tetap dapat menyimpan lagu.';
            } finally {
                this.inspectingLink = false;
            }
        },

        init() {
            // Sinkronisasi active tab ke URL dan localStorage
            try {
                const url = new URL(window.location.href);
                if (this.activeTab && url.searchParams.get('tab') !== this.activeTab) {
                    url.searchParams.set('tab', this.activeTab);
                    window.history.replaceState({}, '', url.href);
                }
            } catch (e) {}

            this.$watch('activeTab', (newTab) => {
                try {
                    localStorage.setItem('soundstation_active_tab', newTab);
                    const url = new URL(window.location.href);
                    if (url.searchParams.get('tab') !== newTab) {
                        url.searchParams.set('tab', newTab);
                        window.history.replaceState({}, '', url.href);
                    }
                } catch (e) {}
            });

            // Sambungkan ke SoundStationHub
            if (window.SoundStationHub) {
                this.applyState(window.SoundStationHub.state);

                window.SoundStationHub.onSync((state) => {
                    this.applyState(state);
                });

                window.SoundStationHub.onTimeSync((timeData) => {
                    this.applyTimeSync(timeData);
                });
            }

            if (window.SoundStation) {
                this.syncFromWindowMaster();
            }

            // Sync langsung jika di-host di tab yang sama
            window.addEventListener('soundstation:timesync', (e) => {
                if (e.detail) this.applyTimeSync(e.detail);
            });

            // Interpolasi visual 1 detik untuk pergerakan detik yang mulus
            const visualTickTimer = setInterval(() => {
                if (this.isPlaying && !this.isLive) {
                    if (this.duration > 0 && this.currentTime < this.duration) {
                        this.currentTime = Math.min(this.duration, this.currentTime + 1);
                        this.currentTimeFormatted = this.formatTime(this.currentTime);
                        this.progressPercent = Math.min(100, Math.max(0, (this.currentTime / this.duration) * 100));
                    }
                }
            }, 1000);

            const syncTimer = setInterval(() => {
                if (window.SoundStation) {
                    this.syncFromWindowMaster();
                } else if (window.SoundStationHub) {
                    this.applyState(window.SoundStationHub.state);
                }
            }, 1500);

            const cleanupMusicTimers = () => {
                clearInterval(visualTickTimer);
                clearInterval(syncTimer);
                window.removeEventListener('kasir:page-leave', cleanupMusicTimers);
            };

            window.addEventListener('kasir:page-leave', cleanupMusicTimers);
            if (typeof this.$cleanup === 'function') {
                this.$cleanup(cleanupMusicTimers);
            }
        },

        applyTimeSync(timeData) {
            if (!timeData) return;
            if (typeof timeData.currentTime !== 'undefined') this.currentTime = timeData.currentTime;
            if (typeof timeData.duration !== 'undefined') this.duration = timeData.duration;
            if (typeof timeData.progressPercent !== 'undefined') {
                this.progressPercent = Math.min(100, Math.max(0, timeData.progressPercent));
            }
            if (typeof timeData.isLive !== 'undefined') this.isLive = !!timeData.isLive;
            if (timeData.currentTimeFormatted) this.currentTimeFormatted = timeData.currentTimeFormatted;
            if (timeData.durationFormatted) this.durationFormatted = timeData.durationFormatted;
            if (typeof timeData.isPlaying !== 'undefined') this.isPlaying = timeData.isPlaying;
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

        applyState(state) {
            if (!state) return;
            this.isPlaying = !!state.isPlaying;
            if (state.currentTrack) this.currentTrack = state.currentTrack;
            if (typeof state.volume !== 'undefined') {
                this.volume = Number(state.volume);
                localStorage.setItem('pos_music_volume', this.volume);
            }
            if (typeof state.isMuted !== 'undefined') this.isMuted = !!state.isMuted;
            if (typeof state.currentTime !== 'undefined') this.currentTime = state.currentTime;
            if (typeof state.duration !== 'undefined') this.duration = state.duration;
            if (typeof state.progressPercent !== 'undefined') {
                this.progressPercent = Math.min(100, Math.max(0, state.progressPercent));
            }
            if (typeof state.isLive !== 'undefined') this.isLive = !!state.isLive;
            if (state.currentTimeFormatted) this.currentTimeFormatted = state.currentTimeFormatted;
            if (state.durationFormatted) this.durationFormatted = state.durationFormatted;
            if (typeof state.queueCount !== 'undefined') this.queueCount = state.queueCount;
            if (Array.isArray(state.queue)) this.queue = state.queue;
            if (typeof state.isAnnouncing !== 'undefined') this.isAnnouncing = !!state.isAnnouncing;
            if (typeof state.voiceAnnouncerEnabled !== 'undefined') this.voiceAnnouncerEnabled = !!state.voiceAnnouncerEnabled;
            if (typeof state.pausedCashierTrack !== 'undefined') this.pausedCashierTrack = state.pausedCashierTrack;
        },

        syncFromWindowMaster() {
            const m = window.SoundStation;
            if (!m) return;
            this.isPlaying = m.isPlaying;
            if (m.currentTrack) this.currentTrack = m.currentTrack;
            if (typeof m.volume !== 'undefined') {
                this.volume = Number(m.volume);
                localStorage.setItem('pos_music_volume', this.volume);
            }
            this.isMuted = m.isMuted;
            this.currentTime = m.currentTime || 0;
            this.duration = m.duration || 0;
            this.isLive = !!m.isLive;
            this.progressPercent = Math.min(100, Math.max(0, m.progressPercent || 0));
            this.currentTimeFormatted = m.currentTimeFormatted || '00:00';
            this.durationFormatted = m.durationFormatted || '00:00';
            this.queueCount = m.queueCount;
            if (Array.isArray(m.queue)) this.queue = m.queue;
            this.isAnnouncing = m.isAnnouncing;
            this.voiceAnnouncerEnabled = m.voiceAnnouncerEnabled;
            if (typeof m.pausedCashierTrack !== 'undefined') this.pausedCashierTrack = m.pausedCashierTrack;
        },

        togglePlayPause() {
            if (window.SoundStation) {
                window.SoundStation.togglePlayPause();
                return;
            }
            if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('TOGGLE_PLAY_PAUSE');
            }
        },

        replayCurrentTrack() {
            this.currentTime = 0;
            this.currentTimeFormatted = '00:00';
            this.progressPercent = 0;
            if (window.SoundStation && window.SoundStation.isMasterHost) {
                window.SoundStation.seekTo(0);
            } else if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('SEEK_TO', { seconds: 0 });
            }
        },

        skipCurrentTrack() {
            this.isSkipping = true;
            if (window.SoundStation && window.SoundStation.isMasterHost) {
                window.SoundStation.playNextTrack(this.currentTrack?.id || null);
            } else if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('SKIP');
            }
            setTimeout(() => {
                this.isSkipping = false;
            }, 1200);
        },

        async submitSingleTrack() {
            const url = (this.importLink || '').trim();
            if (!url) return;

            this.isSubmittingSingle = true;
            try {
                const res = await fetch('{{ route('kasir.music.default.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        youtube_url: url,
                        title: this.importTitle || null,
                        artist: this.importArtist || null
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (data.track) {
                        this.defaultTracks.unshift(data.track);
                    } else {
                        await this.fetchDefaultTracks();
                    }
                    this.importLink = '';
                    this.importTitle = '';
                    this.importArtist = '';
                    this.inspectedVideo = null;
                    this.inspectError = null;
                    this.lastInspectedUrl = '';
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Lagu berhasil ditambahkan ke playlist bawaan.', type: 'success' });
                    }
                } else {
                    const err = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Gagal menambahkan lagu.');
                    if (window.customToast) {
                        window.customToast({ message: err, type: 'danger' });
                    } else {
                        alert(err);
                    }
                }
            } catch (e) {
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan saat menambahkan lagu.', type: 'danger' });
                }
            } finally {
                this.isSubmittingSingle = false;
            }
        },

        async submitBatchTracks() {
            const links = (this.batchUrls || '').trim();
            if (!links) return;

            this.isSubmittingBatch = true;
            try {
                const res = await fetch('{{ route('kasir.music.default.store_batch') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        youtube_urls: links
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (Array.isArray(data.tracks)) {
                        this.defaultTracks = data.tracks;
                    } else {
                        await this.fetchDefaultTracks();
                    }
                    this.batchUrls = '';
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Lagu berhasil diimpor ke playlist bawaan.', type: 'success' });
                    }
                } else {
                    const err = data.message || 'Gagal mengimpor kumpulan lagu.';
                    if (window.customToast) {
                        window.customToast({ message: err, type: 'danger' });
                    }
                }
            } catch (e) {
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan saat mengimpor batch lagu.', type: 'danger' });
                }
            } finally {
                this.isSubmittingBatch = false;
            }
        },

        async toggleTrack(track) {
            const originalState = track.is_active;
            track.is_active = !track.is_active;

            try {
                const res = await fetch('{{ url('/kasir/music/default-tracks') }}/' + track.id + '/toggle', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (window.customToast) {
                        window.customToast({
                            message: track.is_active ? 'Lagu diaktifkan di playlist bawaan.' : 'Lagu dinonaktifkan dari playlist bawaan.',
                            type: 'info'
                        });
                    }
                } else {
                    track.is_active = originalState;
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Gagal mengubah status lagu.', type: 'danger' });
                    }
                }
            } catch (e) {
                track.is_active = originalState;
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan jaringan.', type: 'danger' });
                }
            }
        },

        async deleteTrack(track) {
            if (window.customConfirm) {
                const ok = await window.customConfirm({
                    title: 'Hapus Lagu Bawaan',
                    message: `Hapus lagu "${track.title}" dari playlist bawaan kafe?`,
                    type: 'danger',
                    confirmText: 'Hapus',
                    cancelText: 'Batal'
                });
                if (!ok) return;
            } else if (!confirm(`Hapus lagu "${track.title}" dari playlist bawaan kafe?`)) {
                return;
            }

            // OPTIMISTIC INSTANT UPDATE: Langsung hilangkan kartu dari UI (0 ms)
            const targetId = track.id;
            const targetIndex = this.defaultTracks.findIndex(t => t.id === targetId);
            const backupTrack = { ...track };
            this.defaultTracks = this.defaultTracks.filter(t => t.id !== targetId);

            if (window.customToast) {
                window.customToast({ message: 'Lagu dihapus dari playlist bawaan.', type: 'info' });
            }

            // Jalankan request ke server di latar belakang
            try {
                const res = await fetch('{{ url('/kasir/music/default-tracks') }}/' + targetId, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    // Rollback jika server mengembalikan error
                    if (targetIndex !== -1) {
                        this.defaultTracks.splice(targetIndex, 0, backupTrack);
                    }
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Gagal menghapus lagu di server.', type: 'danger' });
                    }
                }
            } catch (e) {
                // Rollback jika terjadi masalah jaringan
                if (targetIndex !== -1) {
                    this.defaultTracks.splice(targetIndex, 0, backupTrack);
                }
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan jaringan saat menghapus lagu.', type: 'danger' });
                }
            }
        },



        isInDefaultPlaylist(youtubeId) {
            if (!youtubeId || !Array.isArray(this.defaultTracks)) return false;
            return this.defaultTracks.some(t => t.youtube_id === youtubeId);
        },

        async addRequestToDefault(requestId, songTitle) {
            if (this.addingToDefaultId) return;
            this.addingToDefaultId = requestId;

            try {
                const res = await fetch('{{ url('/kasir/music/requests') }}/' + requestId + '/add-to-default', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (data.track) {
                        const existingIdx = this.defaultTracks.findIndex(t => t.youtube_id === data.track.youtube_id);
                        if (existingIdx !== -1) {
                            this.defaultTracks[existingIdx].is_active = true;
                        } else {
                            this.defaultTracks.push(data.track);
                        }
                    } else {
                        await this.fetchDefaultTracks();
                    }

                    if (window.customToast) {
                        window.customToast({
                            message: data.message || `Lagu "${songTitle}" berhasil ditambahkan ke playlist bawaan.`,
                            type: 'success'
                        });
                    }
                } else {
                    const err = data.message || 'Gagal menambahkan lagu ke playlist bawaan.';
                    if (window.customToast) {
                        window.customToast({ message: err, type: 'danger' });
                    }
                }
            } catch (e) {
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan jaringan.', type: 'danger' });
                }
            } finally {
                this.addingToDefaultId = null;
            }
        },

        playHistoryTrackDirect(track) {
            if (!track) return;
            this.playDefaultTrackDirect(track);
        },

        onTrackDragStart(event, index) {
            this.draggedIndex = index;
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', index);
            }
        },

        onTrackDragOver(event, index) {
            if (this.draggedIndex === null || this.draggedIndex === index) return;
            this.dragOverIndex = index;
        },

        async onTrackDrop(event, targetIndex) {
            event.preventDefault();
            if (this.draggedIndex === null || this.draggedIndex === targetIndex) {
                this.draggedIndex = null;
                this.dragOverIndex = null;
                return;
            }

            const item = this.defaultTracks.splice(this.draggedIndex, 1)[0];
            this.defaultTracks.splice(targetIndex, 0, item);

            this.draggedIndex = null;
            this.dragOverIndex = null;

            await this.saveTracksOrder();
        },

        onTrackDragEnd(event) {
            this.draggedIndex = null;
            this.dragOverIndex = null;
        },

        async saveTracksOrder() {
            const trackIds = this.defaultTracks.map(t => t.id);
            try {
                const res = await fetch('{{ route('kasir.music.default.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ track_ids: trackIds })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (window.customToast) {
                        window.customToast({
                            message: '✓ Urutan playlist berhasil diperbarui.',
                            type: 'success',
                            duration: 2000
                        });
                    }
                }
            } catch (e) {
                console.error('Gagal menyimpan urutan:', e);
            }
        },

        async submitEditTrack() {
            if (!this.editForm.title || !this.editForm.id) return;

            const targetId = this.editForm.id;
            const targetIndex = this.defaultTracks.findIndex(t => t.id === targetId);
            if (targetIndex === -1) return;

            // Simpan data lama untuk rollback jika error
            const oldTrack = { ...this.defaultTracks[targetIndex] };

            // OPTIMISTIC INSTANT UPDATE: Langsung ubah judul/artis/urutan di UI (0 ms)
            this.defaultTracks[targetIndex].title = this.editForm.title;
            this.defaultTracks[targetIndex].artist = this.editForm.artist || null;
            this.defaultTracks[targetIndex].sort_order = Number(this.editForm.sort_order || 0);

            // Langsung tutup modal dan beri toast instan
            this.closeEditModal();
            if (window.customToast) {
                window.customToast({ message: 'Perubahan lagu berhasil disimpan.', type: 'success' });
            }

            // Kirim request ke server di background
            try {
                const res = await fetch(this.editUpdateUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        title: this.editForm.title,
                        artist: this.editForm.artist || null,
                        youtube_url: this.editForm.youtube_url || null,
                        sort_order: this.editForm.sort_order
                    })
                });
                const data = await res.json();
                if (res.ok && data.success && data.track) {
                    this.defaultTracks[targetIndex] = data.track;
                } else if (!res.ok) {
                    // Rollback jika gagal
                    this.defaultTracks[targetIndex] = oldTrack;
                    const err = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Gagal menyimpan perubahan.');
                    if (window.customToast) {
                        window.customToast({ message: err, type: 'danger' });
                    }
                }
            } catch (e) {
                this.defaultTracks[targetIndex] = oldTrack;
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan jaringan saat menyimpan lagu.', type: 'danger' });
                }
            }
        },

        async fetchDefaultTracks() {
            try {
                const res = await fetch('{{ route('kasir.music.default.list') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (Array.isArray(data.tracks)) {
                        this.defaultTracks = data.tracks;
                    }
                }
            } catch (e) {}
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

            if (window.SoundStation && window.SoundStation.isMasterHost) {
                window.SoundStation.seekTo(targetTime);
            } else if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('SEEK_TO', { seconds: targetTime });
            }
        },

        toggleVoiceAnnouncer() {
            if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('TOGGLE_ANNOUNCER');
            }
        },

        testAnnouncer() {
            if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('TEST_ANNOUNCER');
            }
        },

        refreshQueue() {
            if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('REFRESH_QUEUE');
            }
        },

        async skipQueueItem(id) {
            if (window.customConfirm) {
                const ok = await window.customConfirm({
                    title: 'Lewati Request',
                    message: 'Lewati lagu request ini?',
                    type: 'warning',
                    confirmText: 'Lewati',
                    cancelText: 'Batal'
                });
                if (!ok) return;
            }

            try {
                await fetch('{{ url('/kasir/music/requests') }}/' + id + '/skip', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                this.refreshQueue();
                if (window.customToast) {
                    window.customToast({ message: 'Request lagu dilewati.', type: 'info' });
                }
            } catch (e) {}
        },

        async rejectQueueItem(id) {
            if (window.customConfirm) {
                const ok = await window.customConfirm({
                    title: 'Tolak Request',
                    message: 'Tolak lagu request ini?',
                    type: 'danger',
                    confirmText: 'Tolak',
                    cancelText: 'Batal'
                });
                if (!ok) return;
            }

            try {
                await fetch('{{ url('/kasir/music/requests') }}/' + id + '/reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                this.refreshQueue();
                if (window.customToast) {
                    window.customToast({ message: 'Request lagu ditolak.', type: 'warning' });
                }
            } catch (e) {}
        },

        playDefaultTrackDirect(track) {
            if (!track || !track.youtube_id) return;
            if (window.SoundStation && typeof window.SoundStation.playDirectTrack === 'function') {
                window.SoundStation.playDirectTrack(track);
            } else if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('PLAY_TRACK', { track: track });
            }
            if (window.customToast) {
                window.customToast({
                    message: '▶ Memutar: ' + (track.title || 'Lagu Kafe'),
                    type: 'success',
                    duration: 3000
                });
            }
        },

        // BAN LIST MANAGEMENT METHODS
        async submitBanManual() {
            if (!this.banForm.title && !this.banForm.youtube_url) return;
            this.isSubmittingBan = true;

            try {
                const res = await fetch('{{ route('kasir.music.ban.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        youtube_id: this.banForm.youtube_url,
                        title: this.banForm.title,
                        artist: this.banForm.artist,
                        reason: this.banForm.reason || 'Dilarang secara manual oleh kasir'
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (data.banned_track) {
                        const existingIdx = this.bannedTracks.findIndex(b => b.id === data.banned_track.id);
                        if (existingIdx !== -1) {
                            this.bannedTracks[existingIdx] = data.banned_track;
                        } else {
                            this.bannedTracks.unshift(data.banned_track);
                        }
                    }
                    this.banForm = {
                        youtube_url: '',
                        title: '',
                        artist: '',
                        reason: 'Dilarang oleh kasir'
                    };
                    this.showBanModal = false;
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Lagu berhasil dimasukkan ke Ban List.', type: 'success' });
                    }
                } else {
                    const err = data.message || 'Gagal menambahkan ke Ban List.';
                    if (window.customToast) {
                        window.customToast({ message: err, type: 'danger' });
                    }
                }
            } catch (e) {
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan saat menambahkan ke Ban List.', type: 'danger' });
                }
            } finally {
                this.isSubmittingBan = false;
            }
        },

        async toggleBanStatus(track) {
            const originalState = track.is_active;
            track.is_active = !track.is_active;

            try {
                const res = await fetch('{{ url('/kasir/music/ban-list') }}/' + track.id + '/toggle', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (window.customToast) {
                        window.customToast({
                            message: track.is_active ? 'Ban lagu diaktifkan kembali.' : 'Ban lagu dinonaktifkan sementara.',
                            type: 'info'
                        });
                    }
                } else {
                    track.is_active = originalState;
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Gagal mengubah status ban.', type: 'danger' });
                    }
                }
            } catch (e) {
                track.is_active = originalState;
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan jaringan.', type: 'danger' });
                }
            }
        },

        async deleteBanTrack(track) {
            if (window.customConfirm) {
                const ok = await window.customConfirm({
                    title: 'Hapus dari Ban List',
                    message: `Hapus "${track.title}" dari Ban List (lagu akan diizinkan kembali)?`,
                    type: 'danger',
                    confirmText: 'Hapus',
                    cancelText: 'Batal'
                });
                if (!ok) return;
            } else if (!confirm(`Hapus "${track.title}" dari Ban List?`)) {
                return;
            }

            const targetId = track.id;
            const targetIndex = this.bannedTracks.findIndex(b => b.id === targetId);
            const backup = { ...track };
            this.bannedTracks = this.bannedTracks.filter(b => b.id !== targetId);

            if (window.customToast) {
                window.customToast({ message: 'Lagu dihapus dari Ban List.', type: 'info' });
            }

            try {
                const res = await fetch('{{ url('/kasir/music/ban-list') }}/' + targetId, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    if (targetIndex !== -1) {
                        this.bannedTracks.splice(targetIndex, 0, backup);
                    }
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Gagal menghapus dari Ban List.', type: 'danger' });
                    }
                }
            } catch (e) {
                if (targetIndex !== -1) {
                    this.bannedTracks.splice(targetIndex, 0, backup);
                }
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan jaringan.', type: 'danger' });
                }
            }
        },

        async autoBanQueueItem(item) {
            if (window.customConfirm) {
                const ok = await window.customConfirm({
                    title: 'Ban & Tolak Request',
                    message: `Tolak request "${item.title}" dan masukkan ke Ban List agar tidak dapat di-request lagi?`,
                    type: 'danger',
                    confirmText: 'Ban & Tolak',
                    cancelText: 'Batal'
                });
                if (!ok) return;
            }

            try {
                const res = await fetch('{{ url('/kasir/music/requests') }}/' + item.id + '/ban', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        reason: 'Dilarang oleh kasir (Blacklist)'
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (data.banned_track) {
                        this.bannedTracks.unshift(data.banned_track);
                    }
                    this.refreshQueue();
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Lagu berhasil di-ban dan ditolak.', type: 'success' });
                    }
                } else {
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Gagal melakukan ban.', type: 'danger' });
                    }
                }
            } catch (e) {
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan jaringan.', type: 'danger' });
                }
            }
        },

        async autoBanCurrentTrack() {
            if (!this.currentTrack) return;
            const title = this.currentTrack.song_title || this.currentTrack.title || 'Lagu ini';

            if (window.customConfirm) {
                const ok = await window.customConfirm({
                    title: 'Ban Lagu Saat Ini',
                    message: `Ban lagu "${title}" dari kafe dan langsung lewati ke lagu berikutnya?`,
                    type: 'danger',
                    confirmText: 'Ban & Lewati',
                    cancelText: 'Batal'
                });
                if (!ok) return;
            } else if (!confirm(`Ban lagu "${title}" dan lewati sekarang?`)) {
                return;
            }

            try {
                const res = await fetch('{{ route('kasir.music.ban.current') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        youtube_id: this.currentTrack.youtube_id,
                        title: this.currentTrack.song_title || this.currentTrack.title,
                        artist: this.currentTrack.artist,
                        request_id: this.currentTrack.request_id || this.currentTrack.id,
                        reason: 'Dilarang oleh kasir saat diputar'
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (data.banned_track) {
                        this.bannedTracks.unshift(data.banned_track);
                    }
                    if (data.next_track) {
                        this.handleTrackTransition(data.next_track);
                    } else {
                        this.skipCurrentTrack();
                    }
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Lagu berhasil di-ban dan dilewati.', type: 'success' });
                    }
                } else {
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Gagal melakukan ban.', type: 'danger' });
                    }
                }
            } catch (e) {
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan jaringan.', type: 'danger' });
                }
            }
        },

        async autoBanHistoryTrack(hist) {
            if (!hist || (!hist.youtube_id && !hist.song_title)) return;
            const title = hist.song_title || 'Lagu ini';

            if (window.customConfirm) {
                const ok = await window.customConfirm({
                    title: 'Ban Lagu dari Riwayat',
                    message: `Masukkan "${title}" ke Ban List agar tidak dapat di-request lagi oleh tamu?`,
                    type: 'danger',
                    confirmText: 'Ban Lagu',
                    cancelText: 'Batal'
                });
                if (!ok) return;
            } else if (!confirm(`Masukkan "${title}" ke Ban List?`)) {
                return;
            }

            try {
                const res = await fetch('{{ route('kasir.music.ban.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        youtube_id: hist.youtube_id,
                        title: hist.song_title,
                        artist: hist.artist,
                        reason: 'Dilarang oleh kasir dari riwayat lagu'
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (data.banned_track) {
                        const existingIdx = this.bannedTracks.findIndex(b => b.id === data.banned_track.id);
                        if (existingIdx !== -1) {
                            this.bannedTracks[existingIdx] = data.banned_track;
                        } else {
                            this.bannedTracks.unshift(data.banned_track);
                        }
                    }
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Lagu berhasil dimasukkan ke Ban List.', type: 'success' });
                    }
                } else {
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Gagal menambahkan ke Ban List.', type: 'danger' });
                    }
                }
            } catch (e) {
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan jaringan.', type: 'danger' });
                }
            }
        },

        openBanModal() {
            this.showBanModal = true;
            this.$nextTick(() => {
                const el = document.getElementById('banModalInput');
                if (el) el.focus();
            });
        },

        closeBanModal() {
            this.showBanModal = false;
        },

        async submitBanModal() {
            await this.submitBanManual();
            this.activeTab = 'ban_list';
            this.showBanModal = false;
        },

        async autoBanDefaultTrack(track) {
            if (!track) return;
            const title = track.title || 'Lagu ini';

            if (window.customConfirm) {
                const ok = await window.customConfirm({
                    title: 'Ban Lagu Bawaan',
                    message: `Hapus "${title}" dari Playlist Bawaan dan masukkan ke Ban List agar tidak dapat diputar lagi?`,
                    type: 'danger',
                    confirmText: 'Ban & Hapus',
                    cancelText: 'Batal'
                });
                if (!ok) return;
            } else if (!confirm(`Ban lagu "${title}" dan hapus dari playlist bawaan?`)) {
                return;
            }

            const targetId = track.id;
            const targetIndex = this.defaultTracks.findIndex(t => t.id === targetId);
            const backupTrack = { ...track };
            this.defaultTracks = this.defaultTracks.filter(t => t.id !== targetId);

            try {
                // 1. Masukkan ke ban list
                const res = await fetch('{{ route('kasir.music.ban.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        youtube_id: track.youtube_id,
                        title: track.title,
                        artist: track.artist,
                        reason: 'Dilarang oleh kasir dari playlist bawaan'
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (data.banned_track) {
                        this.bannedTracks.unshift(data.banned_track);
                    }
                    // 2. Hapus dari database default tracks
                    fetch('{{ url('/kasir/music/default-tracks') }}/' + targetId, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    if (window.customToast) {
                        window.customToast({ message: `Lagu "${title}" berhasil di-ban dan dihapus dari playlist.`, type: 'success' });
                    }
                } else {
                    if (targetIndex !== -1) {
                        this.defaultTracks.splice(targetIndex, 0, backupTrack);
                    }
                    if (window.customToast) {
                        window.customToast({ message: data.message || 'Gagal melakukan ban.', type: 'danger' });
                    }
                }
            } catch (e) {
                if (targetIndex !== -1) {
                    this.defaultTracks.splice(targetIndex, 0, backupTrack);
                }
                if (window.customToast) {
                    window.customToast({ message: 'Terjadi kesalahan jaringan.', type: 'danger' });
                }
            }
        }
    };
}

// AUTO-RUNNING TITLE ON HOVER (MARQUEE UNTUK JUDUL LAGU PANJANG)
(function initMarqueeHover() {
    function handleTitleMarqueeEnter(trigger) {
        const wraps = trigger.classList.contains('title-marquee-wrap')
            ? [trigger]
            : trigger.querySelectorAll('.title-marquee-wrap');

        wraps.forEach(wrap => {
            const text = wrap.querySelector('.title-marquee-text') || wrap;
            if (!text || text.classList.contains('animate-marquee-hover')) return;

            const originalMaxWidth = text.style.maxWidth;
            const originalOverflow = text.style.overflow;
            text.style.maxWidth = 'none';
            text.style.overflow = 'visible';

            const fullWidth = text.scrollWidth;
            const visibleWidth = wrap.clientWidth;
            const overflow = fullWidth - visibleWidth;

            text.style.maxWidth = originalMaxWidth;
            text.style.overflow = originalOverflow;

            if (overflow > 4) {
                const speed = 36; // px per second
                const duration = Math.max(2.4, Math.min(14, (overflow + 16) / speed));
                text.style.setProperty('--marquee-dist', `-${overflow + 14}px`);
                text.style.setProperty('--marquee-dur', `${duration.toFixed(2)}s`);
                text.classList.add('animate-marquee-hover');
            }
        });
    }

    function handleTitleMarqueeLeave(trigger) {
        const wraps = trigger.classList.contains('title-marquee-wrap')
            ? [trigger]
            : trigger.querySelectorAll('.title-marquee-wrap');

        wraps.forEach(wrap => {
            const text = wrap.querySelector('.title-marquee-text') || wrap;
            if (!text) return;
            text.classList.remove('animate-marquee-hover');
            text.style.removeProperty('--marquee-dist');
            text.style.removeProperty('--marquee-dur');
        });
    }

    document.addEventListener('mouseover', (e) => {
        const trigger = e.target.closest('.track-row-marquee, .title-marquee-wrap');
        if (trigger && (!e.relatedTarget || !trigger.contains(e.relatedTarget))) {
            handleTitleMarqueeEnter(trigger);
        }
    });

    document.addEventListener('mouseout', (e) => {
        const trigger = e.target.closest('.track-row-marquee, .title-marquee-wrap');
        if (trigger && (!e.relatedTarget || !trigger.contains(e.relatedTarget))) {
            handleTitleMarqueeLeave(trigger);
        }
    });
})();
</script>
@endsection
