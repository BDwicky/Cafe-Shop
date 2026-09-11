@extends('kasir.app')

@section('title', 'Sound Station — Pemutar Musik Kafe')

@section('content')
<div class="h-full flex flex-col overflow-hidden"
     x-data="musicStationPage()"
     x-init="init()">

    <!-- TOPBAR SOUND STATION -->
    <div class="px-6 py-4 border-b border-[#3A3026] bg-[#1F1812] text-[#F7F3EC] flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[#D9973E] text-[#1F1812] flex items-center justify-center font-bold text-lg shadow">
                ♫
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base font-serif font-bold tracking-tight">Sound Station Kafe</h1>
                    <span class="font-mono text-[9px] uppercase px-2 py-0.5 bg-[#5F7F42]/15 text-[#5F7F42] border border-[#5F7F42]/30 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42] animate-pulse"></span>
                        Tersinkronisasi Realtime
                    </span>
                </div>
                <p class="text-xs text-[#A89A85] font-mono">Terkoneksi langsung dengan pemutar audio di Navbar Widget & Display</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="button"
                    onclick="window.open('{{ route('kasir.music.mini') }}', 'SoundStationMini', 'width=380,height=520,resizable=yes')"
                    class="px-3.5 py-2 bg-[#D9973E] hover:bg-[#c4842e] text-[#1F1812] text-xs font-mono font-bold tracking-wider uppercase transition flex items-center gap-2 shadow">
                <span>⧉ Mini Player (Pop-up)</span>
            </button>
            <a href="{{ route('music.display') }}" target="_blank"
               class="px-3.5 py-2 bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#F7F3EC] text-xs font-mono tracking-wider uppercase transition flex items-center gap-2">
                <span>Display TV Kafe</span>
                <span>↗</span>
            </a>
            <a href="{{ route('music.request') }}" target="_blank"
               class="px-3.5 py-2 bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#D9973E] text-xs font-mono tracking-wider uppercase transition flex items-center gap-2">
                <span>Form Request Pelanggan</span>
                <span>↗</span>
            </a>
        </div>
    </div>

    <!-- BANNER VOICE ANNOUNCER & AUDIO DUCKING (SINKRON DENGAN MASTER NAVBAR) -->
    <div class="px-6 py-2.5 bg-[#140E0A] border-b border-[#3A3026] flex flex-wrap items-center justify-between gap-3 text-xs font-mono">
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5" :class="isAnnouncing ? 'text-[#D9973E] animate-pulse font-bold' : (voiceAnnouncerEnabled ? 'text-[#5F7F42]' : 'text-[#7A6A58]')">
                <span class="w-2 h-2 rounded-full" :class="isAnnouncing ? 'bg-[#D9973E] animate-ping' : (voiceAnnouncerEnabled ? 'bg-[#5F7F42]' : 'bg-gray-500')"></span>
                <span x-text="isAnnouncing ? '📢 SEDANG MEMANGGIL PESANAN (AUDIO DUCKED)' : (voiceAnnouncerEnabled ? '📢 Pemanggil Pesanan Otomatis: AKTIF' : '📢 Pemanggil Pesanan: NONAKTIF')"></span>
            </span>
            <span class="text-[#7A6A58] hidden sm:inline">&bull; Volume musik otomatis mengecil saat memanggil pesanan siap</span>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="testAnnouncer()"
                    class="px-2.5 py-1 bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[11px] text-[#D9973E] transition flex items-center gap-1">
                <span>▶</span>
                <span>Tes Suara Panggilan</span>
            </button>
            <button type="button" @click="toggleVoiceAnnouncer()"
                    class="px-2.5 py-1 text-[11px] border transition font-mono"
                    :class="voiceAnnouncerEnabled ? 'bg-[#5F7F42]/10 border-[#5F7F42] text-[#5F7F42]' : 'bg-[#2A211A] border-[#3A3026] text-[#A89A85]'">
                <span x-text="voiceAnnouncerEnabled ? 'Matikan Suara' : 'Aktifkan Suara'"></span>
            </button>
        </div>
    </div>

    <!-- MAIN BODY GRID -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-[#F7F3EC]">
        <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- KIRI: PLAYER KAFE & KONTROL (5 COLS) -->
            <div class="lg:col-span-5 space-y-6">

                <!-- KARTU NOW PLAYING & KONTROL PLAYER -->
                <div class="bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] p-5 shadow-lg relative overflow-hidden">
                    <div class="flex items-center justify-between border-b border-[#3A3026] pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <!-- Equalizer Visualizer -->
                            <div class="flex items-end gap-0.5 h-3.5 w-4 shrink-0">
                                <span class="w-1 bg-[#5F7F42] rounded-full transition-all duration-150"
                                      :class="isPlaying ? 'h-3.5 animate-pulse' : 'h-1'"></span>
                                <span class="w-1 bg-[#5F7F42] rounded-full transition-all duration-150 delay-75"
                                      :class="isPlaying ? 'h-2.5 animate-pulse' : 'h-1.5'"></span>
                                <span class="w-1 bg-[#5F7F42] rounded-full transition-all duration-150 delay-150"
                                      :class="isPlaying ? 'h-3.5 animate-pulse' : 'h-1'"></span>
                            </div>
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold"
                                  :class="isPlaying ? 'text-[#5F7F42]' : 'text-yellow-500'"
                                  x-text="isPlaying ? 'SEDANG MEMUTAR' : 'TERJEDA'"></span>
                        </div>
                        <template x-if="currentTrack">
                            <span class="font-mono text-[10px] px-2 py-0.5 border"
                                  :class="currentTrack.type === 'customer_request' ? 'border-[#D9973E] text-[#D9973E] bg-[#D9973E]/10' : 'border-[#3A3026] text-[#A89A85]'"
                                  x-text="currentTrack.type === 'customer_request' ? '★ Request Pelanggan' : 'Playlist Bawaan'"></span>
                        </template>
                    </div>

                    <!-- THUMBNAIL COVER & EQUALIZER OVERLAY -->
                    <div class="w-full bg-black border border-[#3A3026] mb-4 flex items-center justify-center overflow-hidden h-48 rounded-sm relative group">
                        <template x-if="currentTrack && currentTrack.thumbnail_url">
                            <img :src="currentTrack.thumbnail_url" alt="Thumb" class="w-full h-full object-cover opacity-85 group-hover:scale-105 transition-transform duration-500">
                        </template>
                        <template x-if="!currentTrack || !currentTrack.thumbnail_url">
                            <div class="w-full h-full flex items-center justify-center bg-[#140E0A]">
                                <span class="text-4xl text-[#D9973E]" :class="isPlaying ? 'animate-spin' : ''">♫</span>
                            </div>
                        </template>

                        <!-- Bottom Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent flex flex-col justify-between p-3.5">
                            <div class="flex justify-end">
                                <span class="px-2 py-0.5 bg-black/60 border border-white/10 font-mono text-[9px] text-[#A89A85] rounded">
                                    Audio Master: Navbar Widget
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" :class="isPlaying ? 'bg-[#5F7F42] animate-pulse' : 'bg-yellow-500'"></span>
                                    <span class="font-mono text-xs text-[#F7F3EC]" x-text="isPlaying ? 'Memutar di Suara Kafe' : 'Musik Terjeda'"></span>
                                </div>
                                <span class="font-mono text-[10px] text-[#D9973E]" x-text="currentTimeFormatted + ' / ' + durationFormatted"></span>
                            </div>
                        </div>
                    </div>

                    <!-- TRACK INFO -->
                    <div class="mb-4">
                        <h2 class="text-base sm:text-lg font-bold text-[#F7F3EC] truncate"
                            x-text="currentTrack ? currentTrack.title : 'Memuat lagu...'"></h2>
                        <div class="text-xs text-[#A89A85] truncate mt-0.5"
                             x-text="currentTrack ? (currentTrack.artist || 'Artis') : '-'"></div>

                        <template x-if="currentTrack && currentTrack.customer_name">
                            <div class="mt-2 text-xs font-mono text-[#D9973E] bg-[#D9973E]/10 border border-[#D9973E]/30 p-2 flex items-center justify-between">
                                <span>Permintaan dari: <b x-text="currentTrack.customer_name"></b></span>
                                <span>★ Antrean #1</span>
                            </div>
                        </template>

                        <!-- INDIKATOR MUSIK KASIR TERJEDA OLEH REQUEST PELANGGAN -->
                        <template x-if="pausedCashierTrack">
                            <div class="mt-2.5 text-xs font-mono text-[#D9973E] bg-[#D9973E]/15 border border-[#D9973E]/40 p-2.5 rounded flex items-center justify-between gap-2 animate-pulse">
                                <div class="truncate">
                                    <span class="font-bold">⏸️ Musik Kasir Terjeda:</span>
                                    <span class="text-[#F7F3EC] font-medium" x-text="pausedCashierTrack.title"></span>
                                    <span class="text-[#A89A85]" x-text="'(' + formatTime(pausedCashierTrack.position) + ')'"></span>
                                </div>
                                <span class="text-[9px] bg-[#D9973E]/20 text-[#D9973E] px-1.5 py-0.5 rounded shrink-0 font-bold border border-[#D9973E]/30">Auto-Resume</span>
                            </div>
                        </template>
                    </div>

                    <!-- REAL-TIME TIMELINE PROGRESS SCRUBBER -->
                    <div class="mb-4">
                        <div class="w-full bg-[#2A211A] h-2 rounded-full overflow-hidden cursor-pointer relative group/bar"
                             @click="seekFromBar($event)"
                             title="Klik untuk melompat ke detik yang dipilih">
                            <div class="bg-[#D9973E] h-full transition-all duration-300 rounded-full"
                                 :style="'width: ' + progressPercent + '%'"></div>
                            <div class="absolute inset-0 bg-white/10 opacity-0 group-hover/bar:opacity-100 transition-opacity"></div>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between font-mono text-[10px] text-[#A89A85]">
                            <span x-text="currentTimeFormatted">00:00</span>
                            <span class="text-[9px] text-[#7A6A58]">// Geser atau klik garis</span>
                            <span x-text="durationFormatted">00:00</span>
                        </div>
                    </div>

                    <!-- KONTROL PEMUTAR AUDIO SINKRON -->
                    <div class="pt-4 border-t border-[#3A3026] flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2.5">
                            <!-- PLAY/PAUSE -->
                            <button type="button"
                                    @click="togglePlayPause()"
                                    :title="isPlaying ? 'Jeda Lagu' : 'Putar Lagu'"
                                    class="w-11 h-11 bg-[#D9973E] hover:bg-[#c4842e] text-[#1F1812] flex items-center justify-center font-bold text-sm transition shadow active:scale-95">
                                <span x-show="!isPlaying" class="ml-0.5">▶</span>
                                <span x-show="isPlaying">❚❚</span>
                            </button>

                            <!-- SKIP NEXT -->
                            <button type="button"
                                    @click="skipCurrentTrack()"
                                    title="Lewati ke lagu berikutnya"
                                    class="px-3.5 py-2.5 bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-xs font-mono uppercase tracking-wider text-[#F7F3EC] transition active:scale-95 flex items-center gap-1.5">
                                <span>Skip</span>
                                <span>⏭</span>
                            </button>
                        </div>

                        <!-- VOLUME SLIDER SINKRON -->
                        <div class="flex items-center gap-2 bg-[#140E0A] border border-[#2A211A] px-2.5 py-1.5 rounded">
                            <button type="button" @click="toggleMute()" class="text-xs text-[#A89A85] hover:text-[#F7F3EC] p-0.5">
                                <span x-show="!isMuted && volume > 30">🔊</span>
                                <span x-show="!isMuted && volume <= 30 && volume > 0">🔉</span>
                                <span x-show="isMuted || volume === 0">🔇</span>
                            </button>
                            <input type="range" min="0" max="100"
                                   x-model="volume"
                                   @input="changeVolume($event.target.value)"
                                   class="w-20 accent-[#D9973E] cursor-pointer"
                                   title="Volume Musik (Tersinkronisasi)">
                            <span class="font-mono text-[10px] text-[#A89A85] w-6 text-right" x-text="volume + '%'"></span>
                        </div>
                    </div>
                </div>

                <!-- CARA KERJA SOUND STATION KAFE -->
                <div class="bg-white border border-[#E0D8CC] p-4 text-xs space-y-2 shadow-sm text-[#5C4D3C]">
                    <div class="font-mono text-[10px] uppercase tracking-wider text-[#1F1812] font-bold flex items-center gap-1.5">
                        <span class="text-[#D9973E]">ℹ</span>
                        <span>Aturan Pemutaran Otomatis Kafe:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-[11px] leading-relaxed">
                        <li>Lagu bawaan kafe diputar terus menerus jika antrean request kosong.</li>
                        <li><b>Pengecualian Durasi Kasir</b>: Kasir bebas memutar lagu/playlist panjang (1 jam, lofi, ambient) tanpa batasan durasi 7 menit.</li>
                        <li><b>Fade-Out 5 Detik & Auto-Resume</b>: Saat request pelanggan masuk, musik kasir memudar halus (fade-out 5s) lalu ter-pause. Setelah seluruh antrean request selesai, musik panjang kasir otomatis berlanjut (*resume*) dari detik terakhir.</li>
                        <li><b>Batas Durasi Tamu</b>: Batas maksimal 7 menit hanya diberlakukan untuk request dari struk pelanggan demi keadilan bersama.</li>
                        <li>Seluruh kontrol di halaman ini <b>tersinkronisasi langsung</b> dengan pemutar di Navbar Widget kasir.</li>
                    </ul>
                </div>

            </div>

            <!-- KANAN: TABS (ANTREAN REQUEST / KELOLA PLAYLIST BAWAAN / RIWAYAT) (7 COLS) -->
            <div class="lg:col-span-7 bg-white border border-[#E0D8CC] shadow-sm flex flex-col">

                <!-- TAB HEADERS -->
                <div class="flex border-b border-[#E0D8CC] bg-[#F7F3EC]">
                    <button type="button"
                            @click="activeTab = 'queue'"
                            class="px-4 py-3 font-mono text-xs uppercase tracking-wider transition border-r border-[#E0D8CC] flex items-center gap-2"
                            :class="activeTab === 'queue' ? 'bg-white font-bold text-[#1F1812] border-b-2 border-b-[#D9973E]' : 'text-[#7A6A58] hover:text-[#1F1812]'">
                        <span>Antrean Request</span>
                        <span class="px-1.5 py-0.2 bg-[#D9973E] text-[#1F1812] text-[10px] font-bold" x-text="queue.length"></span>
                    </button>

                    <button type="button"
                            @click="activeTab = 'default_tracks'"
                            class="px-4 py-3 font-mono text-xs uppercase tracking-wider transition border-r border-[#E0D8CC]"
                            :class="activeTab === 'default_tracks' ? 'bg-white font-bold text-[#1F1812] border-b-2 border-b-[#D9973E]' : 'text-[#7A6A58] hover:text-[#1F1812]'">
                        Playlist Bawaan ({{ $defaultTracks->count() }})
                    </button>

                    <button type="button"
                            @click="activeTab = 'history'"
                            class="px-4 py-3 font-mono text-xs uppercase tracking-wider transition"
                            :class="activeTab === 'history' ? 'bg-white font-bold text-[#1F1812] border-b-2 border-b-[#D9973E]' : 'text-[#7A6A58] hover:text-[#1F1812]'">
                        Riwayat
                    </button>
                </div>

                <!-- TAB 1: ANTREAN REQUEST PELANGGAN -->
                <div x-show="activeTab === 'queue'" class="p-5 flex-1">
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-mono text-xs uppercase tracking-wider text-[#A89A85]">Daftar Antrean Aktif</span>
                        <button type="button" @click="refreshQueue()" class="text-xs font-mono text-[#D9973E] hover:underline flex items-center gap-1">
                            <span>⟳</span>
                            <span>Segarkan Antrean</span>
                        </button>
                    </div>

                    <template x-if="queue.length === 0">
                        <div class="text-center py-12 text-[#A89A85] font-mono text-xs">
                            Tidak ada lagu yang sedang mengantre.<br>
                            Musik saat ini memainkan playlist bawaan kafe.
                        </div>
                    </template>

                    <template x-if="queue.length > 0">
                        <div class="space-y-3">
                            <template x-for="(item, index) in queue" :key="item.id">
                                <div class="p-3.5 bg-[#F7F3EC] border border-[#E0D8CC] flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="font-mono font-bold text-base text-[#D9973E] w-6" x-text="'#' + (index + 1)"></span>
                                        <img :src="item.thumbnail_url" alt="Thumb" class="w-14 h-10 object-cover border border-[#D5CCC0] shrink-0">
                                        <div class="min-w-0">
                                            <div class="font-bold text-sm text-[#1F1812] truncate" x-text="item.song_title"></div>
                                            <div class="text-xs text-[#7A6A58] truncate" x-text="item.artist || 'YouTube'"></div>
                                            <div class="font-mono text-[10px] text-[#D9973E] mt-0.5" x-text="'Oleh: ' + (item.customer_name || 'Pelanggan')"></div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <!-- SKIP BUTTON (AJAX) -->
                                        <button type="button"
                                                @click="skipQueueItem(item.id)"
                                                class="px-2.5 py-1 bg-white hover:bg-gray-100 border border-[#D5CCC0] text-[11px] font-mono uppercase text-[#1F1812] transition">
                                            Lewati
                                        </button>

                                        <!-- REJECT BUTTON (AJAX) -->
                                        <button type="button"
                                                @click="rejectQueueItem(item.id)"
                                                class="px-2.5 py-1 bg-red-50 hover:bg-red-100 border border-red-200 text-[11px] font-mono uppercase text-red-700 transition">
                                            Tolak
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- TAB 2: PLAYLIST BAWAAN KAFE -->
                <div x-show="activeTab === 'default_tracks'" class="p-5 flex-1">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <span class="font-mono text-xs uppercase tracking-wider text-[#A89A85]">Playlist Bawaan Kasir/Pemilik</span>
                            <p class="text-xs text-[#7A6A58] mt-0.5">Lagu-lagu ini diputar otomatis berurutan saat tidak ada request.</p>
                        </div>
                    </div>

                    <!-- FORM TAMBAH LAGU BAWAAN (AUTO METADATA DARI LINK) -->
                    <div class="p-4 bg-[#F7F3EC] border border-[#E0D8CC] mb-6">
                        <div class="flex items-center justify-between mb-3 border-b border-[#E0D8CC] pb-2">
                            <div>
                                <div class="font-mono text-xs uppercase tracking-wider text-[#1F1812] font-bold">
                                    + Tambah Lagu ke Playlist Bawaan
                                </div>
                                <p class="text-[11px] text-[#7A6A58] mt-0.5">
                                    Cukup tempel link YouTube. Judul, artis, durasi, dan cover akan <b>otomatis terisi</b> tanpa perlu ketik manual!
                                </p>
                            </div>
                            <!-- SWITCH MODE: 1 LINK ATAU BANYAK LINK (BATCH) -->
                            <div class="flex items-center gap-1 bg-[#E8DFD3] p-0.5 rounded text-[10px] font-mono">
                                <button type="button" @click="importMode = 'single'"
                                        class="px-2 py-1 rounded transition"
                                        :class="importMode === 'single' ? 'bg-[#1F1812] text-[#F7F3EC] font-bold shadow-sm' : 'text-[#7A6A58] hover:text-[#1F1812]'">
                                    1 Link (Auto)
                                </button>
                                <button type="button" @click="importMode = 'batch'"
                                        class="px-2 py-1 rounded transition"
                                        :class="importMode === 'batch' ? 'bg-[#1F1812] text-[#F7F3EC] font-bold shadow-sm' : 'text-[#7A6A58] hover:text-[#1F1812]'">
                                    Banyak Sekaligus (Batch)
                                </button>
                            </div>
                        </div>

                        <!-- MODE 1: SINGLE LINK AUTO IMPORT -->
                        <form x-show="importMode === 'single'" method="POST" action="{{ route('kasir.music.default.store') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-[11px] font-mono uppercase text-[#7A6A58] mb-1 font-semibold">
                                    Link Video YouTube <span class="text-[#D9973E]">*</span>
                                </label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <input type="text" name="youtube_url" required
                                               x-model="importLink"
                                               @input.debounce.400ms="inspectUrl()"
                                               @paste="setTimeout(() => inspectUrl(), 50)"
                                               placeholder="Tempel link YouTube (misal: https://youtu.be/...)"
                                               class="w-full px-3 py-2 bg-white border border-[#D5CCC0] text-xs text-[#1F1812] focus:outline-none focus:border-[#D9973E]">
                                        <div x-show="inspectingLink" class="absolute right-2.5 top-2 text-xs text-[#D9973E] font-mono animate-pulse flex items-center gap-1">
                                            <span>⏳</span>
                                            <span>Mendeteksi judul...</span>
                                        </div>
                                    </div>
                                    <button type="submit"
                                            :disabled="inspectingLink || !importLink"
                                            class="px-5 py-2 bg-[#1F1812] text-[#F7F3EC] font-mono text-xs uppercase tracking-wider hover:bg-[#D9973E] hover:text-[#1F1812] transition font-bold disabled:opacity-50 disabled:cursor-not-allowed">
                                        Simpan Lagu
                                    </button>
                                </div>
                            </div>

                            <!-- PRATINJAU OTOMATIS VIDEO YOUTUBE -->
                            <template x-if="inspectedVideo">
                                <div class="p-3 border flex items-center gap-3 animate-fade-in bg-white border-[#5F7F42]/30">
                                    <img :src="inspectedVideo.thumbnail_url" alt="Thumb" class="w-16 h-12 object-cover border border-[#3A3026] shrink-0">
                                    <div class="min-w-0 flex-1 text-xs">
                                        <div class="font-bold text-[#1F1812] truncate" x-text="inspectedVideo.title"></div>
                                        <div class="text-[#7A6A58] text-[11px] truncate mt-0.5" x-text="inspectedVideo.artist || 'YouTube Channel'"></div>
                                        <div class="mt-1 flex items-center gap-2 font-mono text-[10px]">
                                            <span class="px-1.5 py-0.5 rounded bg-[#5F7F42]/10 text-[#5F7F42] border border-[#5F7F42]/30">
                                                <span x-text="'⏱️ ' + inspectedVideo.duration_formatted"></span>
                                                <span x-text="inspectedVideo.is_valid_duration ? '✓ Sesuai Aturan' : '✓ Pengecualian Kasir (Bebas Durasi)'"></span>
                                            </span>
                                            <span class="text-[#7A6A58]">Judul & artis otomatis terisi</span>
                                        </div>
                                        <template x-if="!inspectedVideo.is_valid_duration">
                                            <div class="text-[#5F7F42] text-[10px] mt-1 font-mono">
                                                ✓ Lagu panjang diizinkan untuk playlist kasir. Otomatis fade-out jika ada request tamu, dan auto-resume setelahnya.
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- PESAN ERROR INSPECT -->
                            <div x-show="inspectError" class="p-2.5 bg-red-50 border border-red-200 text-red-700 text-xs font-mono" x-text="inspectError"></div>

                            <!-- OPTIONAL EDITABLE TITLE & ARTIST -->
                            <div class="pt-2 border-t border-[#E0D8CC]">
                                <details class="group">
                                    <summary class="cursor-pointer text-[11px] font-mono text-[#7A6A58] hover:text-[#1F1812] flex items-center justify-between select-none">
                                        <span>⚙️ Edit Judul / Nama Artis Kustom (Opsional — default otomatis dari YouTube)</span>
                                        <span class="group-open:rotate-180 transition-transform">▼</span>
                                    </summary>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2.5">
                                        <div>
                                            <label class="block text-[10px] font-mono text-[#7A6A58] mb-1">Judul Lagu (Bisa Disesuaikan)</label>
                                            <input type="text" name="title" x-model="importTitle" placeholder="Kosongkan untuk gunakan judul YouTube"
                                                   class="w-full px-3 py-1.5 bg-white border border-[#D5CCC0] text-xs text-[#1F1812]">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-mono text-[#7A6A58] mb-1">Nama Artis (Bisa Disesuaikan)</label>
                                            <input type="text" name="artist" x-model="importArtist" placeholder="Kosongkan untuk gunakan nama channel"
                                                   class="w-full px-3 py-1.5 bg-white border border-[#D5CCC0] text-xs text-[#1F1812]">
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </form>

                        <!-- MODE 2: BATCH IMPORT MULTIPLE LINKS -->
                        <form x-show="importMode === 'batch'" method="POST" action="{{ route('kasir.music.default.store_batch') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-[11px] font-mono uppercase text-[#7A6A58] mb-1 font-semibold">
                                    Daftar Link Video YouTube (1 Link per Baris)
                                </label>
                                <textarea name="youtube_urls" rows="4" required
                                          placeholder="Tempel beberapa link YouTube di sini, misal:&#10;https://youtu.be/RO75uUZiAw0&#10;https://youtu.be/GxldQ9GyXLA&#10;https://youtu.be/viimfQi_pUw"
                                          class="w-full p-2.5 bg-white border border-[#D5CCC0] text-xs font-mono text-[#1F1812] focus:outline-none focus:border-[#D9973E]"></textarea>
                                <p class="text-[10px] text-[#7A6A58] mt-1 font-mono">
                                    Sistem akan secara otomatis mengambil judul, artis, dan durasi untuk tiap lagu, serta menyaring lagu yang melebihi batas 7 menit.
                                </p>
                            </div>
                            <button type="submit"
                                    class="px-5 py-2 bg-[#1F1812] text-[#F7F3EC] font-mono text-xs uppercase tracking-wider hover:bg-[#D9973E] hover:text-[#1F1812] transition font-bold">
                                📥 Import Semua Lagu Sekaligus
                            </button>
                        </form>
                    </div>

                    <!-- LIST DAFTAR LAGU BAWAAN -->
                    <div class="space-y-2">
                        @foreach ($defaultTracks as $track)
                            <div class="p-3 bg-white border border-[#E0D8CC] flex items-center justify-between gap-3 text-xs">
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-[#1F1812] truncate flex items-center gap-2">
                                        <span>{{ $track->title }}</span>
                                        @if($track->duration_seconds > 0)
                                            <span class="font-mono text-[10px] text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/20 px-1.5 py-0.2 rounded">
                                                ⏱️ {{ sprintf('%02d:%02d', floor($track->duration_seconds / 60), $track->duration_seconds % 60) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[#7A6A58] text-[11px] truncate">{{ $track->artist ?? 'Artis Kafe' }} &bull; ID: {{ $track->youtube_id }}</div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <!-- EDIT -->
                                    <button type="button"
                                            @click="openEditModal($el.dataset)"
                                            data-id="{{ $track->id }}"
                                            data-title="{{ e($track->title) }}"
                                            data-artist="{{ e($track->artist ?? '') }}"
                                            data-youtube-id="{{ $track->youtube_id }}"
                                            data-sort-order="{{ $track->sort_order ?? 0 }}"
                                            class="px-2 py-1 font-mono text-[10px] uppercase border border-[#D5CCC0] text-[#1F1812] hover:bg-[#E8DFD3] transition flex items-center gap-1 cursor-pointer">
                                        <span>✏️</span>
                                        <span>Edit</span>
                                    </button>

                                    <!-- TOGGLE ACTIVE -->
                                    <form method="POST" action="{{ route('kasir.music.default.toggle', $track) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-2 py-1 font-mono text-[10px] uppercase border {{ $track->is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-500 border-gray-300' }}">
                                            {{ $track->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>

                                    <!-- DELETE -->
                                    <form method="POST" action="{{ route('kasir.music.default.destroy', $track) }}"
                                          data-confirm="Hapus lagu ini?"
                                          data-confirm-title="Hapus Lagu"
                                          data-confirm-type="danger"
                                          data-confirm-btn="Hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-1 font-mono text-[10px] uppercase text-red-600 hover:text-red-800">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- TAB 3: RIWAYAT PEMUTARAN -->
                <div x-show="activeTab === 'history'" class="p-5 flex-1">
                    <span class="font-mono text-xs uppercase tracking-wider text-[#A89A85] block mb-4">Riwayat Lagu Request Terakhir</span>
                    <div class="space-y-2">
                        @forelse ($recentHistory as $hist)
                            <div class="p-3 bg-[#F7F3EC] border border-[#E0D8CC] flex items-center justify-between gap-3 text-xs">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-[#1F1812] truncate">{{ $hist->song_title }}</div>
                                    <div class="text-[#7A6A58] text-[11px] truncate">
                                        Peminta: {{ $hist->customer_name ?: 'Pelanggan' }} &bull; {{ $hist->updated_at->format('H:i') }}
                                    </div>
                                    @if ($hist->notes)
                                        <div class="text-[10px] text-amber-700 mt-0.5 truncate flex items-center gap-1 font-mono">
                                            <span>⚠️</span>
                                            <span>{{ $hist->notes }}</span>
                                        </div>
                                    @endif
                                </div>
                                <span class="font-mono text-[10px] uppercase px-2 py-0.5 border
                                    @if($hist->status === 'played') bg-blue-50 text-blue-700 border-blue-200
                                    @elseif($hist->status === 'skipped') bg-yellow-50 text-yellow-700 border-yellow-200
                                    @else bg-red-50 text-red-700 border-red-200 @endif">
                                    {{ strtoupper($hist->status) }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-8 text-[#A89A85] font-mono text-xs">
                                Belum ada riwayat pemutaran request.
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- MODAL EDIT LAGU BAWAAN KAFE -->
    <div x-show="isEditingTrack"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in"
         @keydown.escape.window="closeEditModal()">
        <div class="bg-white border border-[#3A3026] max-w-md w-full p-6 shadow-2xl relative"
             @click.outside="closeEditModal()">
            <div class="flex items-center justify-between border-b border-[#E0D8CC] pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-base text-[#D9973E]">✏️</span>
                    <h3 class="font-serif font-bold text-base text-[#1F1812]">Edit Lagu Bawaan Kafe</h3>
                </div>
                <button type="button" @click="closeEditModal()" class="text-[#7A6A58] hover:text-[#1F1812] text-lg font-bold leading-none cursor-pointer">
                    ✕
                </button>
            </div>

            <form method="POST" :action="editUpdateUrl" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-semibold">
                        Judul Lagu <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" required
                           x-model="editForm.title"
                           class="w-full px-3 py-2 bg-[#F7F3EC] border border-[#D5CCC0] text-xs text-[#1F1812] focus:outline-none focus:border-[#D9973E]">
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-semibold">
                        Nama Artis (Opsional)
                    </label>
                    <input type="text" name="artist"
                           x-model="editForm.artist"
                           placeholder="Kosongkan jika tidak ada"
                           class="w-full px-3 py-2 bg-[#F7F3EC] border border-[#D5CCC0] text-xs text-[#1F1812] focus:outline-none focus:border-[#D9973E]">
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-semibold">
                        Link atau ID Video YouTube
                    </label>
                    <input type="text" name="youtube_url"
                           x-model="editForm.youtube_url"
                           placeholder="Contoh: https://youtu.be/... atau ID YouTube 11 digit"
                           class="w-full px-3 py-2 bg-[#F7F3EC] border border-[#D5CCC0] text-xs text-[#1F1812] focus:outline-none focus:border-[#D9973E]">
                    <p class="text-[10px] text-[#7A6A58] mt-1 font-mono">
                        Biarkan tautan tetap seperti ini jika hanya ingin mengubah nama lagu/artis.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase text-[#7A6A58] mb-1 font-semibold">
                        Urutan Putar (Sort Order)
                    </label>
                    <input type="number" name="sort_order" min="0"
                           x-model="editForm.sort_order"
                           class="w-28 px-3 py-1.5 bg-[#F7F3EC] border border-[#D5CCC0] text-xs text-[#1F1812] focus:outline-none focus:border-[#D9973E]">
                </div>

                <div class="pt-3 border-t border-[#E0D8CC] flex justify-end gap-2">
                    <button type="button" @click="closeEditModal()"
                            class="px-4 py-2 border border-[#D5CCC0] text-[#1F1812] font-mono text-xs uppercase tracking-wider hover:bg-[#F7F3EC] transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-[#1F1812] text-[#F7F3EC] font-mono text-xs uppercase tracking-wider hover:bg-[#D9973E] hover:text-[#1F1812] transition font-bold shadow cursor-pointer">
                        Simpan Perubahan
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

        currentTrack: {{ json_encode($state['now_playing']) }},
        currentTime: 0,
        duration: {{ $state['now_playing']['duration_seconds'] ?? 0 }},
        progressPercent: 0,
        currentTimeFormatted: '00:00',
        durationFormatted: '{{ isset($state['now_playing']['duration_seconds']) && $state['now_playing']['duration_seconds'] > 0 ? sprintf('%02d:%02d', floor($state['now_playing']['duration_seconds'] / 60), $state['now_playing']['duration_seconds'] % 60) : '00:00' }}',

        queue: {{ json_encode($state['queue']) }},
        queueCount: {{ $state['queue_count'] }},

        activeTab: 'queue', // 'queue', 'default_tracks', 'history'
        pausedCashierTrack: null,

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
                if (this.isPlaying && this.duration > 0 && this.currentTime < this.duration) {
                    this.currentTime = Math.min(this.duration, this.currentTime + 1);
                    this.currentTimeFormatted = this.formatTime(this.currentTime);
                    this.progressPercent = (this.currentTime / this.duration) * 100;
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
            if (typeof timeData.progressPercent !== 'undefined') this.progressPercent = timeData.progressPercent;
            if (timeData.currentTimeFormatted) this.currentTimeFormatted = timeData.currentTimeFormatted;
            if (timeData.durationFormatted) this.durationFormatted = timeData.durationFormatted;
            if (typeof timeData.isPlaying !== 'undefined') this.isPlaying = timeData.isPlaying;
        },

        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '00:00';
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
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
            if (typeof state.progressPercent !== 'undefined') this.progressPercent = state.progressPercent;
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
            this.progressPercent = m.progressPercent || 0;
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

        skipCurrentTrack() {
            if (window.SoundStationHub) {
                window.SoundStationHub.sendCommand('SKIP');
            }
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
            if (!this.duration) return;
            const rect = event.currentTarget.getBoundingClientRect();
            const clickRatio = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
            const targetTime = Math.round(clickRatio * this.duration);

            this.currentTime = targetTime;
            this.currentTimeFormatted = this.formatTime(targetTime);
            this.progressPercent = clickRatio * 100;

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
        }
    };
}
</script>
@endsection
