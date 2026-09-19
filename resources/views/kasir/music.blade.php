@extends('kasir.app')

@section('title', 'Sound Station — Pemutar Musik Kafe')

@section('content')
<div class="h-full flex flex-col overflow-hidden"
     x-data="musicStationPage()"
     x-init="init()">

    <!-- TOPBAR SOUND STATION (WARM MODERN ESPRESSO) -->
    <header class="px-4 sm:px-6 py-3.5 border-b border-[#3A3026] bg-[#1A130D] text-[#F7F3EC] flex flex-wrap items-center justify-between gap-4 shrink-0 select-none shadow-md">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#D9973E] text-[#1F1812] flex items-center justify-center font-bold text-lg shadow-sm">
                ♫
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base sm:text-lg font-serif font-bold tracking-tight text-[#F7F3EC]">
                        Sound Station Kafe
                    </h1>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#5F7F42]/20 text-[#85BF5C] border border-[#5F7F42]/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#85BF5C] animate-pulse"></span>
                        Tersinkronisasi Realtime
                    </span>
                </div>
                <p class="text-[11px] text-[#A89A85] font-mono">
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
               class="px-3.5 py-2 rounded-xl bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#F7F3EC] text-xs font-mono tracking-wider uppercase transition-all flex items-center gap-1.5 active:scale-95">
                <span>Display TV</span>
                <span>↗</span>
            </a>
            <a href="{{ route('music.request') }}" target="_blank"
               class="px-3.5 py-2 rounded-xl bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#D9973E] text-xs font-mono tracking-wider uppercase transition-all flex items-center gap-1.5 active:scale-95">
                <span>Request Tamu</span>
                <span>↗</span>
            </a>
        </div>
    </header>

    <!-- BANNER VOICE ANNOUNCER & AUDIO DUCKING -->
    <div class="px-4 sm:px-6 py-2.5 bg-[#140E0A] border-b border-[#3A3026] flex flex-wrap items-center justify-between gap-3 text-xs font-mono">
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 font-bold" :class="isAnnouncing ? 'text-[#E5A44B] animate-pulse' : (voiceAnnouncerEnabled ? 'text-[#85BF5C]' : 'text-[#7A6A58]')">
                <span class="w-2 h-2 rounded-full" :class="isAnnouncing ? 'bg-[#E5A44B] animate-ping' : (voiceAnnouncerEnabled ? 'bg-[#85BF5C]' : 'bg-gray-500')"></span>
                <span x-text="isAnnouncing ? '📢 SEDANG MEMANGGIL PESANAN (AUDIO DUCKED)' : (voiceAnnouncerEnabled ? '📢 Pemanggil Pesanan Otomatis: AKTIF' : '📢 Pemanggil Pesanan: NONAKTIF')"></span>
            </span>
            <span class="text-[#7A6A58] text-[11px] hidden md:inline">&bull; Volume musik mengecil otomatis saat nama pesanan dipanggil</span>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="testAnnouncer()"
                    class="px-3 py-1.5 rounded-xl bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[11px] text-[#D9973E] hover:text-[#F7F3EC] transition flex items-center gap-1 cursor-pointer active:scale-95">
                <span>▶</span>
                <span>Tes Suara</span>
            </button>
            <button type="button" @click="toggleVoiceAnnouncer()"
                    class="px-3 py-1.5 rounded-xl text-[11px] border transition font-mono cursor-pointer active:scale-95"
                    :class="voiceAnnouncerEnabled ? 'bg-[#5F7F42]/15 border-[#5F7F42]/60 text-[#85BF5C]' : 'bg-[#2A211A] border-[#3A3026] text-[#A89A85]'">
                <span x-text="voiceAnnouncerEnabled ? 'Matikan Suara' : 'Aktifkan Suara'"></span>
            </button>
        </div>
    </div>

    <!-- MAIN BODY GRID -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-[#FAF7F2]">
        <div class="max-w-7xl 2xl:max-w-[1520px] w-full mx-auto grid grid-cols-1 lg:grid-cols-12 gap-5 xl:gap-6 items-start">

            <!-- KIRI: PLAYER KAFE & KONTROL (5 COLS) -->
            <div class="lg:col-span-5 space-y-4">

                <!-- KARTU NOW PLAYING & KONTROL PLAYER (DECK HI-FI ROUNDED-2XL) -->
                <div class="bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] rounded-2xl p-5 shadow-xl relative overflow-hidden">
                    <div class="flex items-center justify-between border-b border-[#3A3026] pb-3 mb-3.5">
                        <div class="flex items-center gap-2.5">
                            <!-- Equalizer Visualizer -->
                            <div class="flex items-end gap-1 h-3.5 w-4 shrink-0">
                                <span class="w-1 bg-[#85BF5C] rounded-full transition-all duration-150"
                                      :class="isPlaying ? 'h-3.5 animate-pulse' : 'h-1'"></span>
                                <span class="w-1 bg-[#85BF5C] rounded-full transition-all duration-150 delay-75"
                                      :class="isPlaying ? 'h-2.5 animate-pulse' : 'h-1.5'"></span>
                                <span class="w-1 bg-[#85BF5C] rounded-full transition-all duration-150 delay-150"
                                      :class="isPlaying ? 'h-3.5 animate-pulse' : 'h-1'"></span>
                            </div>
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold"
                                  :class="isPlaying ? 'text-[#85BF5C]' : 'text-amber-400'"
                                  x-text="isPlaying ? 'SEDANG MEMUTAR' : 'TERJEDA'"></span>
                        </div>
                        <template x-if="currentTrack">
                            <span class="font-mono text-[10px] font-bold px-2.5 py-0.5 rounded-full border shadow-2xs"
                                  :class="currentTrack.type === 'customer_request' ? 'border-[#D9973E]/50 text-[#E5A44B] bg-[#D9973E]/15' : 'border-[#3A3026] text-[#A89A85] bg-[#140E0A]'"
                                  x-text="currentTrack.type === 'customer_request' ? '★ Request Pelanggan' : 'Playlist Bawaan'"></span>
                        </template>
                    </div>

                    <!-- THUMBNAIL COVER & EQUALIZER OVERLAY -->
                    <div class="w-full bg-black border border-[#3A3026] mb-3.5 flex items-center justify-center overflow-hidden h-44 sm:h-48 rounded-xl relative group shadow-inner">
                        <template x-if="currentTrack && currentTrack.thumbnail_url">
                            <img :src="currentTrack.thumbnail_url" alt="Thumb" class="w-full h-full object-cover opacity-85 group-hover:scale-105 transition-transform duration-500">
                        </template>
                        <template x-if="!currentTrack || !currentTrack.thumbnail_url">
                            <div class="w-full h-full flex items-center justify-center bg-[#140E0A]">
                                <span class="text-4xl text-[#D9973E]" :class="isPlaying ? 'animate-spin' : ''">♫</span>
                            </div>
                        </template>

                        <!-- Center Play/Pause Quick Action on Hover -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none group-hover:pointer-events-auto">
                            <button type="button"
                                    @click.stop="togglePlayPause()"
                                    :title="isPlaying ? 'Jeda Lagu' : 'Putar Lagu'"
                                    class="w-13 h-13 rounded-full bg-black/75 hover:bg-[#D9973E] text-white hover:text-[#140E0A] border border-white/20 hover:border-[#D9973E] backdrop-blur-md flex items-center justify-center transition-all duration-200 transform scale-90 opacity-0 group-hover:scale-100 group-hover:opacity-100 shadow-2xl active:scale-95 cursor-pointer">
                                <svg x-show="isPlaying" class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                                    <rect x="6" y="4" width="4" height="16" rx="1.5"/>
                                    <rect x="14" y="4" width="4" height="16" rx="1.5"/>
                                </svg>
                                <svg x-show="!isPlaying" class="w-6 h-6 fill-current ml-1" viewBox="0 0 24 24">
                                    <path d="M8 5.14v14.72a1 1 0 001.5.86l11.5-7.36a1 1 0 000-1.72L9.5 4.28A1 1 0 008 5.14z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Bottom Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent flex flex-col justify-between p-3 pointer-events-none">
                            <div class="flex justify-end">
                                <span class="px-2 py-0.5 bg-black/70 border border-white/10 font-mono text-[9px] text-[#A89A85] rounded-md">
                                    Audio Master: Navbar Widget
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" :class="isPlaying ? 'bg-[#85BF5C] animate-pulse' : 'bg-amber-400'"></span>
                                    <span class="font-mono text-xs text-[#F7F3EC]" x-text="isPlaying ? 'Memutar di Suara Kafe' : 'Musik Terjeda'"></span>
                                </div>
                                <span class="font-mono text-[11px] font-bold text-[#E5A44B]" x-text="currentTimeFormatted + ' / ' + durationFormatted"></span>
                            </div>
                        </div>
                    </div>

                    <!-- TRACK INFO -->
                    <div class="mb-3.5">
                        <h2 class="text-base sm:text-lg font-serif font-bold text-[#F7F3EC] truncate"
                            x-text="currentTrack ? currentTrack.title : 'Memuat lagu...'"></h2>
                        <div class="text-xs text-[#A89A85] font-mono truncate mt-0.5"
                             x-text="currentTrack ? (currentTrack.artist || 'Artis Kafe') : '-'"></div>

                        <template x-if="currentTrack && currentTrack.customer_name">
                            <div class="mt-2.5 text-xs font-mono text-[#E5A44B] bg-[#D9973E]/10 border border-[#D9973E]/30 p-2.5 rounded-xl flex items-center justify-between">
                                <span>Permintaan dari: <b x-text="currentTrack.customer_name"></b></span>
                                <span class="font-bold">★ Antrean #1</span>
                            </div>
                        </template>

                        <!-- INDIKATOR MUSIK KASIR TERJEDA OLEH REQUEST PELANGGAN -->
                        <template x-if="pausedCashierTrack">
                            <div class="mt-2.5 text-xs font-mono text-[#E5A44B] bg-[#D9973E]/15 border border-[#D9973E]/40 p-2.5 rounded-xl flex items-center justify-between gap-2 animate-pulse">
                                <div class="truncate">
                                    <span class="font-bold">⏸️ Musik Kasir Terjeda:</span>
                                    <span class="text-[#F7F3EC] font-medium" x-text="pausedCashierTrack.title"></span>
                                    <span class="text-[#A89A85]" x-text="'(' + ((pausedCashierTrack.position > 86400 || pausedCashierTrack.isLive) ? 'LIVE' : formatTime(pausedCashierTrack.position)) + ')'"></span>
                                </div>
                                <span class="text-[9px] bg-[#D9973E]/20 text-[#E5A44B] px-2 py-0.5 rounded-full shrink-0 font-bold border border-[#D9973E]/30">Auto-Resume</span>
                            </div>
                        </template>
                    </div>

                    <!-- REAL-TIME TIMELINE PROGRESS SCRUBBER -->
                    <div class="mb-3.5">
                        <div class="w-full bg-[#2A211A] h-2 rounded-full overflow-hidden cursor-pointer relative group/bar"
                             @click="seekFromBar($event)"
                             :title="isLive ? 'Siaran Langsung Radio 24/7' : 'Klik untuk melompat ke detik yang dipilih'">
                            <template x-if="!isLive">
                                <div class="bg-[#D9973E] h-full transition-all duration-300 rounded-full"
                                     :style="'width: ' + Math.min(100, Math.max(0, progressPercent)) + '%'"></div>
                            </template>
                            <template x-if="isLive">
                                <div class="w-full h-full bg-gradient-to-r from-[#D9973E] via-red-500 to-[#D9973E] animate-pulse"></div>
                            </template>
                            <div class="absolute inset-0 bg-white/10 opacity-0 group-hover/bar:opacity-100 transition-opacity"></div>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between font-mono text-[10px] text-[#A89A85]">
                            <div class="flex items-center gap-1.5">
                                <template x-if="isLive">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-500/20 text-red-300 border border-red-500/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-ping"></span>
                                        LIVE
                                    </span>
                                </template>
                                <span x-text="currentTimeFormatted">00:00</span>
                            </div>
                            <span class="text-[9px] text-[#7A6A58]" x-text="isLive ? '// Radio Siaran Langsung 24/7' : '// Geser atau klik garis'"></span>
                            <span x-text="durationFormatted">00:00</span>
                        </div>
                    </div>

                    <!-- KONTROL PEMUTAR AUDIO SINKRON (DECK HI-FI KAFE) -->
                    <div class="pt-3.5 border-t border-[#3A3026] flex flex-wrap items-center justify-between gap-3">
                        <!-- Cluster Tombol Playback -->
                        <div class="flex items-center gap-2.5">
                            <!-- REPLAY / DARI AWAL -->
                            <button type="button"
                                    @click="replayCurrentTrack()"
                                    title="Putar ulang lagu dari detik 0:00"
                                    class="w-9 h-9 rounded-full bg-[#2A211A] hover:bg-[#3A3026] text-[#A89A85] hover:text-[#D9973E] border border-[#3A3026] hover:border-[#D9973E]/40 flex items-center justify-center transition-all duration-150 active:scale-90 group/replay cursor-pointer">
                                <svg class="w-4 h-4 group-hover/replay:-rotate-45 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                                </svg>
                            </button>

                            <!-- PRIMARY PLAY / PAUSE (AMBER GLOW & TACTILE) -->
                            <button type="button"
                                    @click="togglePlayPause()"
                                    :title="isPlaying ? 'Jeda Lagu' : 'Putar Lagu'"
                                    class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-200 transform active:scale-95 shadow-xl relative group/play cursor-pointer select-none"
                                    :class="isPlaying
                                        ? 'bg-gradient-to-br from-[#D9973E] to-[#B37829] text-[#140E0A] shadow-[0_0_20px_rgba(217,151,62,0.45)] ring-2 ring-[#D9973E]/60'
                                        : 'bg-[#2A211A] hover:bg-[#D9973E] text-[#D9973E] hover:text-[#140E0A] border-2 border-[#D9973E]/70 hover:border-[#D9973E] hover:shadow-[0_0_18px_rgba(217,151,62,0.35)]'">
                                <span x-show="isPlaying" class="absolute -inset-1 rounded-full border border-[#D9973E]/40 animate-ping pointer-events-none opacity-40"></span>

                                <!-- PAUSE ICON -->
                                <svg x-show="isPlaying" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                    <rect x="6" y="4" width="4" height="16" rx="1.5"/>
                                    <rect x="14" y="4" width="4" height="16" rx="1.5"/>
                                </svg>

                                <!-- PLAY ICON -->
                                <svg x-show="!isPlaying" class="w-5 h-5 fill-current ml-0.5" viewBox="0 0 24 24">
                                    <path d="M8 5.14v14.72a1 1 0 001.5.86l11.5-7.36a1 1 0 000-1.72L9.5 4.28A1 1 0 008 5.14z"/>
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
                        </div>

                        <!-- VOLUME SLIDER SINKRON -->
                        <div class="flex items-center gap-2 bg-[#140E0A] border border-[#2A211A] px-3 py-1.5 rounded-full shadow-inner">
                            <button type="button" @click="toggleMute()" class="text-xs text-[#A89A85] hover:text-[#D9973E] transition p-0.5 cursor-pointer">
                                <span x-show="!isMuted && volume > 30">🔊</span>
                                <span x-show="!isMuted && volume <= 30 && volume > 0">🔉</span>
                                <span x-show="isMuted || volume === 0">🔇</span>
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

                <!-- CARA KERJA SOUND STATION KAFE (ROUNDED-2XL) -->
                <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4.5 text-xs shadow-xs">
                    <div class="font-mono text-[10px] uppercase tracking-wider text-[#1F1812] font-bold flex items-center justify-between pb-2 mb-2.5 border-b border-[#E4DCCC]">
                        <span class="flex items-center gap-1.5">
                            <span class="text-[#D9973E]">ℹ</span>
                            <span>Aturan Pemutaran Musik Kafe</span>
                        </span>
                        <span class="text-[9px] text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/20 px-2 py-0.5 rounded-full font-mono font-bold">Auto-Sync</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] text-[#5C4D3C]">
                        <div class="p-2.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl">
                            <div class="font-bold text-[#1F1812] flex items-center gap-1">
                                <span>🎧</span> Pengecualian Kasir
                            </div>
                            <div class="text-[10px] text-[#7A6A58] mt-0.5">Bebas putar playlist panjang (lofi, ambient) tanpa batas durasi.</div>
                        </div>
                        <div class="p-2.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl">
                            <div class="font-bold text-[#1F1812] flex items-center gap-1">
                                <span>⏯️</span> Fade-Out & Resume
                            </div>
                            <div class="text-[10px] text-[#7A6A58] mt-0.5">Musik kasir fade-out 5s saat request masuk, dan resume saat selesai.</div>
                        </div>
                        <div class="p-2.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl">
                            <div class="font-bold text-[#1F1812] flex items-center gap-1">
                                <span>⏱️</span> Batas Request Tamu
                            </div>
                            <div class="text-[10px] text-[#7A6A58] mt-0.5">Maksimal 7 menit per lagu untuk request dari struk pelanggan.</div>
                        </div>
                        <div class="p-2.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl">
                            <div class="font-bold text-[#1F1812] flex items-center gap-1">
                                <span>📢</span> Audio Ducking
                            </div>
                            <div class="text-[10px] text-[#7A6A58] mt-0.5">Volume mengecil otomatis saat suara pemanggilan pesanan aktif.</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- KANAN: TABS (ROUNDED-2XL DENGAN TINGGI KONSISTEN) (7 COLS) -->
            <div class="lg:col-span-7 bg-white border border-[#E4DCCC] shadow-xs flex flex-col h-[700px] xl:h-[750px] rounded-2xl overflow-hidden">

                <!-- TAB HEADERS (ROUNDED PILL TABS) -->
                <div class="p-2.5 bg-[#FAF7F2] border-b border-[#E4DCCC] flex gap-1.5 shrink-0 select-none">
                    <button type="button"
                            @click="activeTab = 'queue'"
                            class="px-4 py-2.5 font-mono text-xs uppercase tracking-wider transition-all rounded-xl flex items-center gap-2 cursor-pointer"
                            :class="activeTab === 'queue' ? 'bg-white font-bold text-[#1F1812] shadow-xs border border-[#E4DCCC]' : 'text-[#7A6A58] hover:text-[#1F1812] hover:bg-[#F0EAE1]'">
                        <span>Antrean Request</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-extrabold"
                              :class="activeTab === 'queue' ? 'bg-[#D9973E] text-[#1F1812]' : 'bg-[#E4DCCC] text-[#7A6A58]'"
                              x-text="queue.length"></span>
                    </button>

                    <button type="button"
                            @click="activeTab = 'default_tracks'"
                            class="px-4 py-2.5 font-mono text-xs uppercase tracking-wider transition-all rounded-xl flex items-center gap-2 cursor-pointer"
                            :class="activeTab === 'default_tracks' ? 'bg-white font-bold text-[#1F1812] shadow-xs border border-[#E4DCCC]' : 'text-[#7A6A58] hover:text-[#1F1812] hover:bg-[#F0EAE1]'">
                        <span>Playlist Bawaan</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-extrabold"
                              :class="activeTab === 'default_tracks' ? 'bg-[#5F7F42] text-white' : 'bg-[#E4DCCC] text-[#7A6A58]'"
                              x-text="defaultTracks.length"></span>
                    </button>

                    <button type="button"
                            @click="activeTab = 'history'"
                            class="px-4 py-2.5 font-mono text-xs uppercase tracking-wider transition-all rounded-xl cursor-pointer"
                            :class="activeTab === 'history' ? 'bg-white font-bold text-[#1F1812] shadow-xs border border-[#E4DCCC]' : 'text-[#7A6A58] hover:text-[#1F1812] hover:bg-[#F0EAE1]'">
                        Riwayat
                    </button>
                </div>

                <!-- TAB CONTENT WRAPPER -->
                <div class="flex-1 overflow-y-auto p-4 sm:p-5">

                    <!-- TAB 1: ANTREAN REQUEST PELANGGAN -->
                    <div x-show="activeTab === 'queue'" class="h-full flex flex-col">
                        <div class="flex items-center justify-between mb-3.5 pb-2 border-b border-[#E4DCCC] shrink-0">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs uppercase tracking-wider font-bold text-[#1F1812]">Daftar Antrean Aktif</span>
                                <span class="text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full"
                                      :class="queue.length > 0 ? 'bg-[#D9973E]/20 text-[#8F5E1D]' : 'bg-gray-100 text-gray-600'"
                                      x-text="queue.length > 0 ? (queue.length + ' Lagu Mengantre') : 'Antrean Bersih'"></span>
                            </div>
                            <button type="button" @click="refreshQueue()" class="text-xs font-mono font-bold text-[#D9973E] hover:underline flex items-center gap-1 cursor-pointer">
                                <span>⟳</span>
                                <span>Segarkan Antrean</span>
                            </button>
                        </div>

                        <!-- KONDISI KOSONG -->
                        <template x-if="queue.length === 0">
                            <div class="flex-1 flex flex-col justify-between py-2">
                                <div class="bg-[#FAF7F2] border border-[#E4DCCC] p-6 text-center rounded-2xl my-auto">
                                    <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-[#EFE9DF] text-[#D9973E] flex items-center justify-center text-2xl shadow-inner">
                                        ♫
                                    </div>
                                    <h4 class="font-serif font-bold text-base text-[#1F1812]">Tidak Ada Lagu yang Sedang Mengantre</h4>
                                    <p class="text-xs text-[#7A6A58] mt-1 max-w-md mx-auto leading-relaxed">
                                        Sound Station saat ini memainkan <b>playlist bawaan kafe</b> secara otomatis. Pelanggan dapat menambahkan request lagu melalui barcode pada struk transaksi.
                                    </p>
                                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                                        <button type="button"
                                                @click="activeTab = 'default_tracks'"
                                                class="px-4 py-2 bg-[#1F1812] text-[#F7F3EC] hover:bg-[#D9973E] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all shadow-xs flex items-center gap-1.5 cursor-pointer active:scale-95">
                                            <span>📂</span>
                                            <span>Kelola Playlist Bawaan</span>
                                        </button>
                                        <a href="{{ route('music.request') }}" target="_blank"
                                           class="px-4 py-2 bg-white border border-[#E4DCCC] text-[#1F1812] hover:bg-[#FAF7F2] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all flex items-center gap-1.5 shadow-2xs active:scale-95">
                                            <span>Form Request Pelanggan</span>
                                            <span>↗</span>
                                        </a>
                                    </div>
                                </div>

                                <!-- MINI SUMMARY RINGKASAN STATUS KAFE -->
                                <div class="grid grid-cols-3 gap-2.5 pt-4 border-t border-[#E4DCCC] shrink-0 text-center">
                                    <div class="p-3 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl">
                                        <div class="text-[10px] font-mono uppercase text-[#7A6A58]">Durasi Maks.</div>
                                        <div class="text-xs font-bold font-mono text-[#1F1812] mt-0.5">7 Menit</div>
                                    </div>
                                    <div class="p-3 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl">
                                        <div class="text-[10px] font-mono uppercase text-[#7A6A58]">Struk Kasir</div>
                                        <div class="text-xs font-bold font-mono text-[#5F7F42] mt-0.5">1 Request / Struk</div>
                                    </div>
                                    <div class="p-3 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl">
                                        <div class="text-[10px] font-mono uppercase text-[#7A6A58]">Prioritas Lagu</div>
                                        <div class="text-xs font-bold font-mono text-[#D9973E] mt-0.5">Request > Bawaan</div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- JIKA ADA LAGU DI DALAM ANTREAN -->
                        <template x-if="queue.length > 0">
                            <div class="space-y-2.5">
                                <template x-for="(item, index) in queue" :key="item.id + '_' + (item.type || 'req')">
                                    <div class="p-3 bg-[#FAF7F2] border border-[#E4DCCC] hover:border-[#D9973E]/60 transition-all flex items-center justify-between gap-3 text-xs rounded-xl shadow-2xs">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="font-mono font-bold text-sm text-[#D9973E] w-5 shrink-0" x-text="'#' + (index + 1)"></span>
                                            <img :src="item.thumbnail_url" alt="Thumb" class="w-12 h-9 object-cover rounded-lg border border-[#E4DCCC] shrink-0">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <div class="font-bold text-xs text-[#1F1812] truncate" x-text="item.song_title || item.title"></div>
                                                    <!-- BADGE INDIKATOR: REQUEST vs BAWAAN -->
                                                    <template x-if="item.type === 'request' || item.is_request">
                                                        <span class="px-2 py-0.5 bg-[#D9973E]/15 text-[#8F5E1D] border border-[#D9973E]/30 text-[9px] font-mono font-bold rounded-full shrink-0">★ Request</span>
                                                    </template>
                                                    <template x-if="item.type === 'default' || !item.is_request">
                                                        <span class="px-2 py-0.5 bg-[#5F7F42]/15 text-[#5F7F42] border border-[#5F7F42]/30 text-[9px] font-mono font-bold rounded-full shrink-0">🎵 Bawaan</span>
                                                    </template>
                                                </div>
                                                <div class="text-[11px] text-[#7A6A58] truncate" x-text="item.artist || 'YouTube'"></div>
                                                <template x-if="item.customer_name">
                                                    <div class="font-mono text-[10px] text-[#D9973E] mt-0.5" x-text="'Oleh: ' + item.customer_name"></div>
                                                </template>
                                                <template x-if="!item.customer_name && (item.type === 'default' || !item.is_request)">
                                                    <div class="font-mono text-[10px] text-[#5F7F42] mt-0.5">Playlist Bawaan Kafe</div>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <!-- ACTIONS UNTUK REQUEST PELANGGAN -->
                                            <template x-if="item.type === 'request' || item.is_request">
                                                <div class="flex items-center gap-1.5">
                                                    <button type="button"
                                                            @click="skipQueueItem(item.id)"
                                                            class="px-2.5 py-1.5 bg-white hover:bg-gray-100 border border-[#E4DCCC] text-[10px] font-mono uppercase font-bold text-[#1F1812] rounded-lg transition cursor-pointer active:scale-95">
                                                        Lewati
                                                    </button>
                                                    <button type="button"
                                                            @click="rejectQueueItem(item.id)"
                                                            class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 border border-red-200 text-[10px] font-mono uppercase font-bold text-red-700 rounded-lg transition cursor-pointer active:scale-95">
                                                        Tolak
                                                    </button>
                                                </div>
                                            </template>

                                            <!-- ACTIONS UNTUK LAGU BAWAAN -->
                                            <template x-if="item.type === 'default' || !item.is_request">
                                                <div class="flex items-center gap-1.5">
                                                    <button type="button"
                                                            @click="playDefaultTrackDirect(item)"
                                                            class="px-2.5 py-1.5 bg-white hover:bg-[#5F7F42] hover:text-white border border-[#E4DCCC] text-[10px] font-mono uppercase font-bold text-[#5F7F42] rounded-lg transition cursor-pointer flex items-center gap-1 active:scale-95">
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
                    <div x-show="activeTab === 'default_tracks'" class="space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-[#E4DCCC]">
                            <div>
                                <span class="font-mono text-xs uppercase tracking-wider font-bold text-[#1F1812]">Playlist Bawaan Kasir / Kafe</span>
                                <p class="text-[11px] text-[#7A6A58] mt-0.5">Diputar otomatis berurutan saat tidak ada request tamu.</p>
                            </div>
                            <span class="text-[11px] font-mono font-bold text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/20 px-2.5 py-0.5 rounded-full"
                                  x-text="defaultTracks.length + ' Lagu Terdaftar'"></span>
                        </div>

                        <!-- FORM TAMBAH LAGU BAWAAN (AUTO METADATA DARI LINK) -->
                        <div class="p-4 bg-[#FAF7F2] border border-[#E4DCCC] rounded-2xl shadow-2xs">
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
                                <div class="flex items-center gap-1 bg-[#EFE9DF] p-1 rounded-xl text-[10px] font-mono">
                                    <button type="button" @click="importMode = 'single'"
                                            class="px-2.5 py-1 rounded-lg transition cursor-pointer"
                                            :class="importMode === 'single' ? 'bg-[#1F1812] text-[#F7F3EC] font-bold shadow-xs' : 'text-[#7A6A58] hover:text-[#1F1812]'">
                                        1 Link (Auto)
                                    </button>
                                    <button type="button" @click="importMode = 'batch'"
                                            class="px-2.5 py-1 rounded-lg transition cursor-pointer"
                                            :class="importMode === 'batch' ? 'bg-[#1F1812] text-[#F7F3EC] font-bold shadow-xs' : 'text-[#7A6A58] hover:text-[#1F1812]'">
                                        Banyak (Batch)
                                    </button>
                                </div>
                            </div>

                            <!-- MODE 1: SINGLE LINK AUTO IMPORT (AJAX NO REFRESH) -->
                            <form x-show="importMode === 'single'" @submit.prevent="submitSingleTrack()" class="space-y-3">
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
                                                   class="w-full px-3.5 py-2 bg-white border border-[#E4DCCC] rounded-xl text-xs text-[#1F1812] focus:outline-none focus:border-[#D9973E] shadow-2xs font-medium">
                                            <div x-show="inspectingLink" class="absolute right-3 top-2 text-xs text-[#D9973E] font-mono animate-pulse flex items-center gap-1">
                                                <span>⏳</span>
                                                <span>Mendeteksi judul...</span>
                                            </div>
                                        </div>
                                        <button type="submit"
                                                :disabled="inspectingLink || !importLink || isSubmittingSingle"
                                                class="px-4 py-2 bg-[#1F1812] text-[#F7F3EC] font-mono text-xs uppercase tracking-wider hover:bg-[#D9973E] hover:text-[#1F1812] rounded-xl transition font-bold disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95">
                                            <span x-show="isSubmittingSingle" class="animate-spin text-xs">⟳</span>
                                            <span x-text="isSubmittingSingle ? 'Menyimpan...' : 'Simpan Lagu'"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- PRATINJAU OTOMATIS VIDEO YOUTUBE -->
                                <template x-if="inspectedVideo">
                                    <div class="p-3 border rounded-xl flex items-center gap-3 animate-fade-in bg-white border-[#5F7F42]/40 shadow-2xs">
                                        <img :src="inspectedVideo.thumbnail_url" alt="Thumb" class="w-14 h-10 object-cover rounded-lg border border-[#3A3026] shrink-0">
                                        <div class="min-w-0 flex-1 text-xs">
                                            <div class="font-bold text-[#1F1812] truncate" x-text="inspectedVideo.title"></div>
                                            <div class="text-[#7A6A58] text-[11px] truncate" x-text="inspectedVideo.artist || 'YouTube Channel'"></div>
                                            <div class="mt-0.5 flex items-center gap-2 font-mono text-[10px]">
                                                <span class="px-2 py-0.5 rounded-full bg-[#5F7F42]/10 text-[#5F7F42] border border-[#5F7F42]/30 font-bold"
                                                      x-text="'⏱️ ' + inspectedVideo.duration_formatted"></span>
                                                <span class="text-[#7A6A58]">Judul otomatis terisi</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- PESAN ERROR INSPECT -->
                                <div x-show="inspectError" class="p-2.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-mono" x-text="inspectError"></div>

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
                                                       class="w-full px-3 py-1.5 bg-white border border-[#E4DCCC] rounded-xl text-xs text-[#1F1812]">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-mono text-[#7A6A58] mb-1 font-bold">Nama Artis</label>
                                                <input type="text" name="artist" x-model="importArtist" placeholder="Gunakan channel YouTube"
                                                       class="w-full px-3 py-1.5 bg-white border border-[#E4DCCC] rounded-xl text-xs text-[#1F1812]">
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            </form>

                            <!-- MODE 2: BATCH IMPORT MULTIPLE LINKS (AJAX NO REFRESH) -->
                            <form x-show="importMode === 'batch'" @submit.prevent="submitBatchTracks()" class="space-y-3">
                                <div>
                                    <label class="block text-[11px] font-mono uppercase text-[#7A6A58] mb-1.5 font-bold">
                                        Daftar Link Video YouTube (1 Link per Baris)
                                    </label>
                                    <textarea x-model="batchUrls" rows="3" required
                                              placeholder="Tempel beberapa link YouTube di sini, misal:&#10;https://youtu.be/RO75uUZiAw0&#10;https://youtu.be/GxldQ9GyXLA"
                                              class="w-full p-2.5 bg-white border border-[#E4DCCC] rounded-xl text-xs font-mono text-[#1F1812] focus:outline-none focus:border-[#D9973E] shadow-2xs"></textarea>
                                    <p class="text-[10px] text-[#7A6A58] mt-1 font-mono">
                                        Sistem otomatis mengambil judul dan durasi untuk tiap lagu.
                                    </p>
                                </div>
                                <button type="submit"
                                        :disabled="isSubmittingBatch || !batchUrls"
                                        class="px-4 py-2 bg-[#1F1812] text-[#F7F3EC] font-mono text-xs uppercase tracking-wider hover:bg-[#D9973E] hover:text-[#1F1812] rounded-xl transition font-bold disabled:opacity-50 flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95">
                                    <span x-show="isSubmittingBatch" class="animate-spin text-xs">⟳</span>
                                    <span x-text="isSubmittingBatch ? 'Mengimpor...' : '📥 Import Semua Lagu Sekaligus'"></span>
                                </button>
                            </form>
                        </div>

                        <!-- LIST DAFTAR LAGU BAWAAN (REAKTIF REALTIME TANPA RELOAD) -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-[11px] font-mono text-[#7A6A58] pb-1 border-b border-[#E4DCCC]">
                                <span>Tarik ⋮⋮ untuk ubah urutan &bull; Klik ▶ Putar langsung</span>
                                <span x-text="defaultTracks.length + ' Lagu'"></span>
                            </div>

                            <template x-if="defaultTracks.length === 0">
                                <div class="p-6 bg-[#FAF7F2] border border-[#E4DCCC] rounded-2xl text-center text-xs font-mono text-[#7A6A58]">
                                    Belum ada lagu di playlist bawaan. Tempel link YouTube di atas untuk menambahkan.
                                </div>
                            </template>

                            <div class="max-h-[380px] xl:max-h-[430px] overflow-y-auto pr-1.5 space-y-2">
                                <template x-for="(track, index) in defaultTracks" :key="track.id">
                                    <div draggable="true"
                                         @dragstart="onTrackDragStart($event, index)"
                                         @dragover.prevent="onTrackDragOver($event, index)"
                                         @dragenter.prevent="dragOverIndex = index"
                                         @dragleave="dragOverIndex = (dragOverIndex === index ? null : dragOverIndex)"
                                         @drop="onTrackDrop($event, index)"
                                         @dragend="onTrackDragEnd($event)"
                                         class="p-3 bg-white border rounded-xl flex items-center justify-between gap-2.5 text-xs transition-all select-none shadow-2xs"
                                         :class="{
                                             'border-[#D9973E] bg-[#D9973E]/10 shadow-md ring-2 ring-[#D9973E]/30': dragOverIndex === index,
                                             'opacity-40 border-dashed border-[#D9973E]': draggedIndex === index,
                                             'border-[#E4DCCC] hover:border-[#D9973E]/60': dragOverIndex !== index && draggedIndex !== index,
                                             'border-l-4 border-l-[#D9973E] bg-[#D9973E]/5': currentTrack && currentTrack.id === track.id && currentTrack.type === 'default_track'
                                         }">
                                        
                                        <!-- DRAG HANDLE & NUMBER -->
                                        <div class="flex items-center gap-1.5 shrink-0 cursor-grab active:cursor-grabbing text-[#A89A85] hover:text-[#1F1812] px-1 py-1"
                                             title="Tahan dan geser untuk memindahkan urutan lagu">
                                            <span class="text-sm font-bold leading-none tracking-tighter select-none">⋮⋮</span>
                                            <span class="font-mono text-[11px] font-semibold text-[#7A6A58] w-4 text-center" x-text="index + 1"></span>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold text-[#1F1812] truncate flex items-center gap-2">
                                                <span x-text="track.title"></span>
                                                <template x-if="track.duration_seconds > 0 && track.duration_seconds < 86400 && !(track.title && (track.title.toLowerCase().includes('radio') || track.title.toLowerCase().includes('live 24/7') || track.title.toLowerCase().includes('[live]')))">
                                                    <span class="font-mono text-[10px] text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/20 px-2 py-0.5 rounded-full font-bold"
                                                          x-text="'⏱️ ' + formatTime(track.duration_seconds)"></span>
                                                </template>
                                                <template x-if="track.duration_seconds >= 86400 || (track.title && (track.title.toLowerCase().includes('radio') || track.title.toLowerCase().includes('live 24/7') || track.title.toLowerCase().includes('[live]')))">
                                                    <span class="font-mono text-[10px] text-[#D9973E] bg-[#D9973E]/15 border border-[#D9973E]/30 px-2 py-0.5 rounded-full inline-flex items-center gap-1 font-bold">
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
                                                    class="px-2.5 py-1.5 rounded-lg font-mono text-[10px] uppercase font-bold border transition-all flex items-center gap-1 cursor-pointer active:scale-95"
                                                    :class="(currentTrack && currentTrack.id === track.id && isPlaying)
                                                        ? 'bg-[#D9973E] text-[#1F1812] border-[#D9973E] shadow-[0_0_8px_rgba(217,151,62,0.35)]'
                                                        : 'bg-[#1F1812] text-[#F7F3EC] border-[#1F1812] hover:bg-[#D9973E] hover:text-[#1F1812]'">
                                                <span x-text="(currentTrack && currentTrack.id === track.id && isPlaying) ? '▶ Diputar' : '▶ Putar'"></span>
                                            </button>

                                            <!-- EDIT -->
                                            <button type="button"
                                                    @click="openEditModal(track)"
                                                    class="px-2.5 py-1.5 rounded-lg font-mono text-[10px] uppercase border border-[#E4DCCC] text-[#1F1812] hover:bg-[#FAF7F2] transition flex items-center gap-1 cursor-pointer active:scale-95">
                                                <span>✏️</span>
                                                <span>Edit</span>
                                            </button>

                                            <!-- TOGGLE ACTIVE -->
                                            <button type="button"
                                                    @click="toggleTrack(track)"
                                                    class="px-2.5 py-1.5 rounded-lg font-mono text-[10px] uppercase font-bold border transition cursor-pointer active:scale-95"
                                                    :class="track.is_active ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100' : 'bg-gray-100 text-gray-500 border-gray-300 hover:bg-gray-200'"
                                                    :title="track.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'">
                                                <span x-text="track.is_active ? '✓ Aktif' : 'Nonaktif'"></span>
                                            </button>

                                            <!-- DELETE -->
                                            <button type="button"
                                                    @click="deleteTrack(track)"
                                                    title="Hapus lagu ini dari playlist bawaan"
                                                    class="px-2.5 py-1.5 rounded-lg font-mono text-[10px] uppercase font-bold text-red-600 border border-red-200 hover:bg-red-50 transition cursor-pointer active:scale-95">
                                                ✕ Hapus
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: RIWAYAT PEMUTARAN -->
                    <div x-show="activeTab === 'history'" class="space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-[#E4DCCC]">
                            <div>
                                <span class="font-mono text-xs uppercase tracking-wider font-bold text-[#1F1812]">Riwayat Lagu Request Terakhir</span>
                                <p class="text-[11px] text-[#7A6A58] mt-0.5">Daftar lagu yang pernah diminta pelanggan. Klik <b>+ Playlist Bawaan</b> untuk menyimpan lagu favorit ke koleksi kafe.</p>
                            </div>
                            <span class="text-[10px] font-mono font-bold text-[#7A6A58] bg-[#FAF7F2] border border-[#E4DCCC] px-2 py-0.5 rounded-full shrink-0">{{ count($recentHistory) }} Riwayat</span>
                        </div>
                        <div class="space-y-2">
                            @forelse ($recentHistory as $hist)
                                <div class="p-3 bg-[#FAF7F2] border border-[#E4DCCC] hover:border-[#D9973E]/60 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs rounded-xl shadow-2xs">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-[#1F1812] truncate flex items-center gap-2">
                                            <span class="font-bold text-xs truncate">{{ $hist->song_title }}</span>
                                            @if ($hist->artist)
                                                <span class="text-[#7A6A58] text-[11px] font-normal truncate font-mono">({{ $hist->artist }})</span>
                                            @endif
                                        </div>
                                        <div class="text-[#7A6A58] text-[11px] truncate mt-0.5 font-mono">
                                            Peminta: <span class="font-medium text-[#1F1812]">{{ $hist->customer_name ?: 'Pelanggan' }}</span> &bull; {{ $hist->updated_at->format('H:i') }}
                                        </div>
                                        @if ($hist->notes)
                                            <div class="text-[10px] text-amber-700 mt-0.5 truncate flex items-center gap-1 font-mono">
                                                <span>⚠️</span>
                                                <span>{{ $hist->notes }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                        <span class="font-mono text-[10px] uppercase font-bold px-2 py-0.5 rounded-full border
                                            @if($hist->status === 'played') bg-blue-50 text-blue-700 border-blue-200
                                            @elseif($hist->status === 'skipped') bg-yellow-50 text-yellow-700 border-yellow-200
                                            @else bg-red-50 text-red-700 border-red-200 @endif">
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
                                                    class="px-2.5 py-1 bg-white hover:bg-[#1F1812] text-[#1F1812] hover:text-[#F7F3EC] border border-[#E4DCCC] rounded-lg font-mono text-[11px] font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer active:scale-95">
                                                <span>▶</span>
                                                <span class="hidden md:inline">Putar</span>
                                            </button>

                                            <!-- TOMBOL MASUKKAN KE PLAYLIST BAWAAN -->
                                            <button type="button"
                                                    @click="addRequestToDefault({{ $hist->id }}, '{{ addslashes($hist->song_title) }}')"
                                                    :disabled="addingToDefaultId === {{ $hist->id }} || isInDefaultPlaylist('{{ $hist->youtube_id }}')"
                                                    class="px-3 py-1 font-mono text-[11px] rounded-lg transition flex items-center gap-1.5 shadow-2xs"
                                                    :class="isInDefaultPlaylist('{{ $hist->youtube_id }}')
                                                        ? 'bg-[#5F7F42]/10 text-[#5F7F42] border border-[#5F7F42]/30 cursor-default font-semibold'
                                                        : 'bg-white hover:bg-[#D9973E] text-[#1F1812] hover:text-[#1F1812] border border-[#E4DCCC] hover:border-[#D9973E] font-bold cursor-pointer active:scale-95'"
                                                    :title="isInDefaultPlaylist('{{ $hist->youtube_id }}') ? 'Lagu ini sudah ada di playlist bawaan' : 'Tambahkan lagu ini ke playlist bawaan kafe'">
                                                <span x-show="addingToDefaultId === {{ $hist->id }}" class="animate-spin text-xs">⟳</span>
                                                <span x-show="addingToDefaultId !== {{ $hist->id }} && isInDefaultPlaylist('{{ $hist->youtube_id }}')">✓</span>
                                                <span x-show="addingToDefaultId !== {{ $hist->id }} && !isInDefaultPlaylist('{{ $hist->youtube_id }}')">＋</span>
                                                <span x-text="isInDefaultPlaylist('{{ $hist->youtube_id }}') ? 'Di Playlist' : 'Playlist Bawaan'"></span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-10 text-[#A89A85] font-mono text-xs bg-[#FAF7F2] border border-[#E4DCCC] rounded-2xl">
                                    Belum ada riwayat pemutaran request.
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </main>

    <!-- MODAL EDIT LAGU BAWAAN KAFE (AJAX NO REFRESH) -->
    <div x-show="isEditingTrack"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-fade-in"
         @keydown.escape.window="closeEditModal()">
        <div class="bg-white border border-[#E4DCCC] rounded-2xl max-w-md w-full p-6 shadow-2xl relative"
             @click.outside="closeEditModal()">
            <div class="flex items-center justify-between border-b border-[#E4DCCC] pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-base text-[#D9973E]">✏️</span>
                    <h3 class="font-serif font-bold text-base text-[#1F1812]">Edit Lagu Bawaan Kafe</h3>
                </div>
                <button type="button" @click="closeEditModal()" class="text-[#7A6A58] hover:text-[#1F1812] text-lg font-bold leading-none cursor-pointer">
                    ✕
                </button>
            </div>

            <form @submit.prevent="submitEditTrack()" class="space-y-4">
                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-bold">
                        Judul Lagu <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" required
                           x-model="editForm.title"
                           class="w-full px-3.5 py-2 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl text-xs text-[#1F1812] focus:outline-none focus:border-[#D9973E] font-medium">
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-bold">
                        Nama Artis (Opsional)
                    </label>
                    <input type="text" name="artist"
                           x-model="editForm.artist"
                           placeholder="Kosongkan jika tidak ada"
                           class="w-full px-3.5 py-2 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl text-xs text-[#1F1812] focus:outline-none focus:border-[#D9973E] font-medium">
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-bold">
                        Link atau ID Video YouTube
                    </label>
                    <input type="text" name="youtube_url"
                           x-model="editForm.youtube_url"
                           placeholder="Contoh: https://youtu.be/... atau ID YouTube 11 digit"
                           class="w-full px-3.5 py-2 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl text-xs text-[#1F1812] focus:outline-none focus:border-[#D9973E] font-medium">
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
                           class="w-28 px-3.5 py-1.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl text-xs text-[#1F1812] focus:outline-none focus:border-[#D9973E] font-mono font-bold">
                </div>

                <div class="pt-3 border-t border-[#E4DCCC] flex justify-end gap-2">
                    <button type="button" @click="closeEditModal()"
                            class="px-4 py-2 border border-[#E4DCCC] text-[#1F1812] font-mono text-xs uppercase tracking-wider rounded-xl hover:bg-[#FAF7F2] transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="isSavingEdit || !editForm.title"
                            class="px-5 py-2 bg-[#1F1812] text-[#F7F3EC] font-mono text-xs uppercase tracking-wider hover:bg-[#D9973E] hover:text-[#1F1812] rounded-xl transition font-bold shadow-xs disabled:opacity-50 flex items-center gap-1.5 cursor-pointer active:scale-95">
                        <span x-show="isSavingEdit" class="animate-spin text-xs">⟳</span>
                        <span x-text="isSavingEdit ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
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
        volume: parseInt(localStorage.getItem('pos_music_volume') || '75'),
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

        activeTab: 'queue', // 'queue', 'default_tracks', 'history'
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
            setInterval(() => {
                if (this.isPlaying && !this.isLive) {
                    if (this.duration > 0 && this.currentTime < this.duration) {
                        this.currentTime = Math.min(this.duration, this.currentTime + 1);
                        this.currentTimeFormatted = this.formatTime(this.currentTime);
                        this.progressPercent = Math.min(100, Math.max(0, (this.currentTime / this.duration) * 100));
                    }
                }
            }, 1000);

            setInterval(() => {
                if (window.SoundStation) {
                    this.syncFromWindowMaster();
                } else if (window.SoundStationHub) {
                    this.applyState(window.SoundStationHub.state);
                }
            }, 1500);
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
            if (typeof state.volume !== 'undefined') this.volume = state.volume;
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
            this.volume = m.volume;
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
            if (window.SoundStationHub) {
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

        playDefaultTrackDirect(track) {
            if (!track) return;
            if (window.SoundStation && window.SoundStation.isMasterHost) {
                window.SoundStation.playDirectTrack(track);
            } else if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('PLAY_TRACK', { track: track });
            }
            if (window.customToast) {
                window.customToast({
                    message: '▶ Memutar "' + track.title + '" sekarang...',
                    type: 'success',
                    duration: 2500
                });
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
            const v = parseInt(val);
            this.volume = v;
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
        }
    };
}
</script>
@endsection
