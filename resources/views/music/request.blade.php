@extends('layouts.public')

@section('title', 'Request Musik Kafe')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8 sm:py-12"
     x-data="{
        code: '{{ $code ?? '' }}',
        validating: false,
        isValid: {{ isset($initialValidation['valid']) && $initialValidation['valid'] ? 'true' : 'false' }},
        isOwner: {{ isset($initialValidation['is_owner']) && $initialValidation['is_owner'] ? 'true' : 'false' }},
        valMessage: '{{ addslashes($initialValidation['message'] ?? '') }}',
        alreadyUsed: {{ isset($initialValidation['already_used']) && $initialValidation['already_used'] ? 'true' : 'false' }},
        orderInfo: {{ isset($initialValidation['order']) ? json_encode($initialValidation['order']) : 'null' }},

        query: '',
        searching: false,
        selectedSong: null,
        customerName: '{{ $initialValidation['order']['customer_name'] ?? '' }}',

        submitting: false,
        requestSubmitted: false,
        myPosition: null,
        mySong: null,

        nowPlaying: {{ json_encode($playerState['now_playing']) }},
        queue: {{ json_encode($playerState['queue']) }},
        queueCount: {{ $playerState['queue_count'] }},
        initialPlayback: {{ json_encode($playback ?? null) }},

        playbackCurrentTime: 0,
        playbackDuration: 0,
        playbackProgressPercent: 0,
        playbackCurrentTimeFormatted: '00:00',
        playbackDurationFormatted: '00:00',
        isPlaying: false,

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

        init() {
            if (this.code && !this.isValid && !this.alreadyUsed) {
                this.checkCode();
            }

            // Sinkronisasi status playback awal jika tersedia dari cache server
            let initialDur = Number(this.initialPlayback?.duration || 0);
            if (initialDur <= 0 && this.nowPlaying && Number(this.nowPlaying.duration_seconds || 0) > 0) {
                initialDur = Number(this.nowPlaying.duration_seconds);
            }
            this.playbackDuration = initialDur;
            this.playbackDurationFormatted = this.formatSeconds(this.playbackDuration);

            if (this.initialPlayback) {
                this.isPlaying = typeof this.initialPlayback.is_playing !== 'undefined' ? !!this.initialPlayback.is_playing : true;
                let elapsed = 0;
                const serverUpdated = Number(this.initialPlayback.updated_at || 0);
                if (serverUpdated > 0) {
                    const diffSec = (Date.now() - serverUpdated) / 1000;
                    if (diffSec >= 0 && diffSec <= 15) {
                        elapsed = diffSec;
                    }
                }
                let cur = Number(this.initialPlayback.current_time || 0) + (this.isPlaying ? elapsed : 0);
                if (this.playbackDuration > 0) {
                    cur = Math.min(this.playbackDuration, Math.max(0, cur));
                }
                this.playbackCurrentTime = cur;
                this.playbackProgressPercent = this.playbackDuration > 0 ? (this.playbackCurrentTime / this.playbackDuration) * 100 : 0;
                this.playbackCurrentTimeFormatted = this.formatSeconds(this.playbackCurrentTime);
            } else if (this.nowPlaying) {
                this.isPlaying = true;
                this.playbackCurrentTime = 0;
                this.playbackProgressPercent = 0;
                this.playbackCurrentTimeFormatted = '00:00';
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
                        const dur = Number(t.duration || 0) > 0 ? Number(t.duration) : (this.nowPlaying?.duration_seconds || this.playbackDuration);
                        this.playbackDuration = dur;
                        this.playbackCurrentTime = Number(t.currentTime || 0);
                        this.playbackProgressPercent = Number(t.progressPercent || 0);
                        this.playbackCurrentTimeFormatted = t.currentTimeFormatted || this.formatSeconds(this.playbackCurrentTime);
                        this.playbackDurationFormatted = t.durationFormatted || this.formatSeconds(this.playbackDuration);
                        if (typeof t.isPlaying !== 'undefined') {
                            this.isPlaying = !!t.isPlaying;
                        }
                    }
                });
            }

            // Interpolasi visual 0.5 detik untuk timeline lagu berjalan secara halus
            setInterval(() => {
                if (this.isPlaying && this.playbackDuration > 0 && this.playbackCurrentTime < this.playbackDuration) {
                    this.playbackCurrentTime = Math.min(this.playbackDuration, this.playbackCurrentTime + 0.5);
                    this.playbackProgressPercent = Math.min(100, (this.playbackCurrentTime / this.playbackDuration) * 100);
                    this.playbackCurrentTimeFormatted = this.formatSeconds(Math.floor(this.playbackCurrentTime));
                }
            }, 500);

            // Polling status antrean & lagu kafe setiap 3 detik
            this.fetchStatus();
            setInterval(() => this.fetchStatus(), 3000);
        },

        async checkCode() {
            if (!this.code.trim()) {
                this.valMessage = 'Harap isi kode struk transaksi Anda.';
                this.isValid = false;
                return;
            }
            this.validating = true;
            this.valMessage = '';
            try {
                const res = await fetch('{{ route('music.validate_code') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ code: this.code.trim() })
                });

                let data = {};
                try {
                    data = await res.json();
                } catch (jsonErr) {
                    data = {};
                }

                if (res.ok) {
                    this.isValid = data.valid;
                    this.isOwner = !!data.is_owner;
                    this.valMessage = data.message;
                    this.alreadyUsed = !!data.already_used;
                    this.orderInfo = data.order || null;
                    if (data.is_owner && (!this.customerName || this.customerName === 'Pelanggan')) {
                        this.customerName = '👑 Owner';
                    } else if (data.order && data.order.customer_name) {
                        this.customerName = data.order.customer_name;
                    }
                } else {
                    this.isValid = false;
                    this.isOwner = false;
                    this.valMessage = data.message || ('Gagal memverifikasi kode (Status: ' + res.status + ').');
                    this.alreadyUsed = !!data.already_used;
                }
            } catch (e) {
                this.isValid = false;
                this.valMessage = 'Gagal memverifikasi kode. Periksa koneksi internet Anda.';
            } finally {
                this.validating = false;
            }
        },

        async searchOrExtract() {
            const q = this.query.trim();
            if (!q) return;

            this.searching = true;
            try {
                const res = await fetch('{{ route('music.search') }}?q=' + encodeURIComponent(q), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                if (data.results && data.results.length > 0) {
                    const first = data.results[0];
                    this.selectedSong = {
                        youtube_id: first.youtube_id,
                        title: first.title,
                        artist: first.artist,
                        thumbnail_url: first.thumbnail_url || 'https://img.youtube.com/vi/' + first.youtube_id + '/hqdefault.jpg',
                        duration_seconds: first.duration_seconds || null,
                        duration_formatted: first.duration_formatted || '--:--',
                        is_valid_duration: first.is_valid_duration !== false,
                        duration_error: first.duration_error || null
                    };

                    if (!this.selectedSong.is_valid_duration) {
                        window.customAlert({
                            title: 'Durasi Melebihi Batas Kafe',
                            message: this.selectedSong.duration_error || 'Lagu ini melebihi batas maksimal 7 menit. Silakan pilih lagu lain.',
                            type: 'warning',
                            btnText: 'Mengerti'
                        });
                    }
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.searching = false;
            }
        },

        selectPreset(title, artist, ytId, durationSeconds, durationFormatted) {
            this.selectedSong = {
                youtube_id: ytId,
                title: title,
                artist: artist,
                thumbnail_url: 'https://img.youtube.com/vi/' + ytId + '/hqdefault.jpg',
                duration_seconds: durationSeconds || null,
                duration_formatted: durationFormatted || '--:--',
                is_valid_duration: true,
                duration_error: null
            };
            this.query = title + ' - ' + artist;
        },

        async submitSong() {
            if (!this.selectedSong) return;

            if (this.selectedSong.is_valid_duration === false) {
                window.customAlert({
                    title: 'Durasi Melebihi Batas',
                    message: this.selectedSong.duration_error || 'Lagu ini melebihi batas maksimal 7 menit. Demi kenyamanan seluruh pengunjung, silakan pilih lagu lain.',
                    type: 'warning',
                    btnText: 'Paham'
                });
                return;
            }

            this.submitting = true;

            try {
                const res = await fetch('{{ route('music.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        code: this.code.trim(),
                        song_title: this.selectedSong.title,
                        artist: this.selectedSong.artist,
                        youtube_id: this.selectedSong.youtube_id,
                        thumbnail_url: this.selectedSong.thumbnail_url,
                        duration_seconds: this.selectedSong.duration_seconds,
                        customer_name: this.customerName.trim() || 'Pelanggan'
                    })
                });

                let data = {};
                try {
                    data = await res.json();
                } catch (jsonErr) {
                    data = {};
                }

                if (res.ok) {
                    this.requestSubmitted = true;
                    this.myPosition = data.queue_position;
                    this.mySong = data.request;
                    this.isOwner = !!data.is_owner;
                    this.fetchStatus();

                    // Beritahukan pemutar Master Host seketika bahwa request baru masuk
                    if (typeof BroadcastChannel !== 'undefined') {
                        try {
                            const syncChannel = new BroadcastChannel('cafe_soundstation_sync');
                            syncChannel.postMessage({ type: 'NEW_REQUEST_SUBMITTED' });
                        } catch (e) {}
                    }
                } else {
                    let warnMsg = data.message;
                    if (!warnMsg) {
                        if (res.status === 419) {
                            warnMsg = 'Sesi halaman telah kedaluwarsa. Silakan refresh halaman dan coba kembali.';
                        } else if (res.status >= 500) {
                            warnMsg = 'Server sedang mengalami gangguan sementara. Silakan coba beberapa saat lagi.';
                        } else {
                            warnMsg = 'Gagal mengirim request lagu (Kode status: ' + res.status + ').';
                        }
                    }
                    window.customAlert({
                        title: 'Perhatian',
                        message: warnMsg,
                        type: 'warning',
                        btnText: 'OK'
                    });
                }
            } catch (e) {
                console.error('Submit song error:', e);
                window.customAlert({
                    title: 'Kendala Koneksi',
                    message: 'Tidak dapat terhubung ke server kafe. Pastikan perangkat Anda terhubung ke internet/Wi-Fi kafe, lalu coba lagi.',
                    type: 'error',
                    btnText: 'OK'
                });
            } finally {
                this.submitting = false;
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
                if (res.ok) {
                    const data = await res.json();
                    this.nowPlaying = data.now_playing;
                    this.queue = data.queue || [];
                    this.queueCount = data.queue_count || 0;

                    // Sinkronisasi playback state dari server (untuk HP/perangkat pelanggan tanpa BroadcastChannel)
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

                    // Perbarui status pesanan jika orderInfo ada dalam ready_orders
                    if (this.orderInfo && data.ready_orders && Array.isArray(data.ready_orders)) {
                        const readyMatch = data.ready_orders.find(o => o.id === this.orderInfo.id);
                        if (readyMatch) {
                            this.orderInfo.prep_status = readyMatch.prep_status;
                        }
                    }
                }
            } catch (e) {}
        }
     }">

    <!-- HERO HEADER -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#D9973E]/15 border border-[#D9973E]/40 text-[#D9973E] text-[11px] font-mono uppercase tracking-[0.2em] mb-3">
            <span>♫</span>
            <span>Jukebox Digital Kafe</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-serif font-bold text-[#1F1812] tracking-tight">Request Musik Pilihanmu</h1>
        <p class="mt-2 text-sm text-[#5C4D3C] max-w-md mx-auto leading-relaxed">
            Punya struk belanja di {{ config('cafe.name') }}? Masukkan kodenya dan putar lagu favoritmu saat santai di kafe!
        </p>
    </div>

    <!-- KARTU STATUS PERSIAPAN PESANAN PELANGGAN (KITCHEN & BARISTA LIVE TRACKER) -->
    <template x-if="orderInfo">
        <div class="mb-8 p-4 sm:p-5 border-2 shadow-md transition"
             :class="{
                'bg-[#5F7F42]/10 border-[#5F7F42] text-[#1F1812]': orderInfo.prep_status === 'ready',
                'bg-yellow-50 border-[#D9973E] text-[#1F1812]': orderInfo.prep_status === 'preparing',
                'bg-blue-50 border-blue-300 text-[#1F1812]': orderInfo.prep_status === 'pending',
                'bg-gray-50 border-gray-300 text-gray-700': orderInfo.prep_status === 'completed'
             }">
            <div class="flex items-center justify-between border-b pb-2 mb-3"
                 :class="orderInfo.prep_status === 'ready' ? 'border-[#5F7F42]/40' : 'border-gray-200'">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold"
                      :class="{
                        'text-[#5F7F42]': orderInfo.prep_status === 'ready',
                        'text-[#D9973E]': orderInfo.prep_status === 'preparing',
                        'text-blue-600': orderInfo.prep_status === 'pending',
                        'text-gray-500': orderInfo.prep_status === 'completed'
                      }">
                    STATUS PESANAN MAKAN & MINUM ANDA
                </span>
                <span class="font-mono text-xs font-bold text-[#1F1812]" x-text="'No. ' + orderInfo.code"></span>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg shrink-0"
                     :class="{
                        'bg-[#5F7F42] text-white animate-bounce': orderInfo.prep_status === 'ready',
                        'bg-[#D9973E] text-white animate-spin-slow': orderInfo.prep_status === 'preparing',
                        'bg-blue-500 text-white': orderInfo.prep_status === 'pending',
                        'bg-gray-400 text-white': orderInfo.prep_status === 'completed'
                     }">
                    <template x-if="orderInfo.prep_status === 'ready'"><span>✓</span></template>
                    <template x-if="orderInfo.prep_status === 'preparing'"><span>☕</span></template>
                    <template x-if="orderInfo.prep_status === 'pending'"><span>⏳</span></template>
                    <template x-if="orderInfo.prep_status === 'completed'"><span>✓</span></template>
                </div>

                <div class="min-w-0 flex-1">
                    <template x-if="orderInfo.prep_status === 'ready'">
                        <div>
                            <div class="font-serif font-bold text-base text-[#5F7F42] leading-tight">
                                PESANAN SUDAH SIAP!
                            </div>
                            <div class="text-xs text-[#1F1812] mt-0.5 font-medium">
                                Silakan ambil pesanan Anda di <b>Meja Kasir</b> sekarang. Terima kasih!
                            </div>
                        </div>
                    </template>

                    <template x-if="orderInfo.prep_status === 'preparing'">
                        <div>
                            <div class="font-serif font-bold text-sm text-[#D9973E] leading-tight">
                                Sedang Diracik & Dimasak...
                            </div>
                            <div class="text-xs text-[#5C4D3C] mt-0.5">
                                Barista & tim kitchen sedang menyiapkan pesanan Anda dengan presisi.
                            </div>
                        </div>
                    </template>

                    <template x-if="orderInfo.prep_status === 'pending'">
                        <div>
                            <div class="font-serif font-bold text-sm text-blue-700 leading-tight">
                                Pesanan Diterima (Dalam Antrean)
                            </div>
                            <div class="text-xs text-[#5C4D3C] mt-0.5">
                                Pesanan Anda sudah masuk ke sistem barista & dapur.
                            </div>
                        </div>
                    </template>

                    <template x-if="orderInfo.prep_status === 'completed'">
                        <div>
                            <div class="font-serif font-bold text-sm text-gray-700 leading-tight">
                                Pesanan Selesai Diserahkan
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                Selamat menikmati kopi dan hidangan Anda di {{ config('cafe.name') }}!
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    <!-- WIDGET NOW PLAYING LIVE DI KAFE (TERSIKRONISASI) -->
    <div class="bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] p-4 sm:p-5 mb-8 relative overflow-hidden shadow-xl">
        <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-[#D9973E]/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="flex items-center justify-between border-b border-[#3A3026] pb-3 mb-3">
            <div class="flex items-center gap-2">
                <!-- Equalizer Animation -->
                <div class="flex items-end gap-0.5 h-3 w-3 shrink-0">
                    <span class="w-0.5 bg-[#5F7F42] rounded-full h-3 animate-pulse"></span>
                    <span class="w-0.5 bg-[#5F7F42] rounded-full h-2 animate-pulse delay-75"></span>
                    <span class="w-0.5 bg-[#5F7F42] rounded-full h-3 animate-pulse delay-150"></span>
                </div>
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#5F7F42] font-semibold">
                    Sedang Diputar di Kafe
                </span>
            </div>
            <div class="flex items-center gap-2">
                <template x-if="nowPlaying && nowPlaying.type === 'customer_request'">
                    <span class="font-mono text-[9px] uppercase tracking-wider px-2 py-0.5 bg-[#D9973E]/20 text-[#D9973E] border border-[#D9973E]/40 rounded-full">
                        ★ Request
                    </span>
                </template>
                <span class="font-mono text-[10px] text-[#A89A85]" x-text="queueCount + ' Lagu di Antrean'"></span>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <!-- Vinyl Icon / Thumbnail -->
            <div class="relative w-14 h-14 sm:w-16 sm:h-16 shrink-0 bg-[#2A211A] border border-[#3A3026] overflow-hidden flex items-center justify-center rounded-sm">
                <template x-if="nowPlaying && nowPlaying.thumbnail_url">
                    <img :src="nowPlaying.thumbnail_url" alt="Cover" class="w-full h-full object-cover">
                </template>
                <template x-if="!nowPlaying || !nowPlaying.thumbnail_url">
                    <div class="w-8 h-8 rounded-full border-2 border-dashed border-[#D9973E] flex items-center justify-center text-xs font-mono text-[#D9973E]">♫</div>
                </template>
            </div>

            <div class="min-w-0 flex-1">
                <div class="text-sm sm:text-base font-bold text-[#F7F3EC] truncate"
                     x-text="nowPlaying ? (nowPlaying.song_title || nowPlaying.title) : 'Memuat Lagu Kafe...'">
                </div>
                <div class="text-xs text-[#A89A85] truncate mt-0.5"
                     x-text="nowPlaying ? (nowPlaying.artist || 'Playlist Kafe') : '{{ config('cafe.name') }} Selections'">
                </div>
                <template x-if="nowPlaying && nowPlaying.customer_name">
                    <div class="mt-1 inline-flex items-center gap-1 font-mono text-[9px] uppercase tracking-wider text-[#D9973E]">
                        <span>Permintaan dari:</span>
                        <span class="font-bold" x-text="nowPlaying.customer_name"></span>
                    </div>
                </template>

                <!-- LIVE TIMELINE PROGRESS BAR -->
                <div class="mt-2.5">
                    <div class="w-full bg-[#140E0A] h-1.5 rounded-full overflow-hidden border border-[#3A3026]">
                        <div class="bg-[#D9973E] h-full transition-all duration-300 rounded-full"
                             :style="'width: ' + playbackProgressPercent + '%'"></div>
                    </div>
                    <div class="mt-1 flex items-center justify-between font-mono text-[9px] text-[#A89A85]">
                        <span class="text-[#D9973E] font-semibold" x-text="playbackCurrentTimeFormatted">00:00</span>
                        <span class="text-[8px] text-[#7A6A58] uppercase tracking-wider">Sync Live Kafe</span>
                        <span x-text="playbackDurationFormatted">00:00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KONDISI: SUDAH BERHASIL SUBMIT REQUEST -->
    <template x-if="requestSubmitted">
        <div class="bg-white border-2 border-[#5F7F42] p-6 sm:p-8 text-center shadow-lg animate-fade-in">
            <div class="w-16 h-16 bg-[#5F7F42]/15 text-[#5F7F42] rounded-full mx-auto flex items-center justify-center text-2xl mb-4 font-bold">
                ✓
            </div>
            <h2 class="text-2xl font-serif font-bold text-[#1F1812]">Lagu Berhasil Masuk Antrean!</h2>
            <p class="text-sm text-[#5C4D3C] mt-2 max-w-md mx-auto">
                Terima kasih, request musikmu sudah tercatat dan <b class="text-[#1F1812]">akan otomatis diputar tepat setelah lagu yang sedang berjalan selesai</b>.
            </p>

            <div class="mt-6 p-4 bg-[#F7F3EC] border border-[#E0D8CC] text-left flex items-center gap-4">
                <img :src="mySong.thumbnail_url" alt="Thumb" class="w-16 h-12 object-cover border border-[#3A3026]">
                <div class="min-w-0 flex-1">
                    <div class="font-medium text-sm text-[#1F1812] truncate" x-text="mySong.song_title"></div>
                    <div class="text-xs text-[#7A6A58] truncate" x-text="mySong.artist || 'Artis'"></div>
                    <div class="font-mono text-[10px] text-[#5F7F42] font-semibold mt-1">
                        Urutan Antrean: Ke-<span x-text="myPosition"></span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('music.display') }}" target="_blank" class="px-5 py-2.5 bg-[#1F1812] text-[#F7F3EC] font-mono text-xs uppercase tracking-wider hover:bg-[#2A211A] transition">
                    Lihat Layar Display Musik Kafe ›
                </a>
                <a href="{{ route('menu.public') }}" class="px-5 py-2.5 border border-[#1F1812] text-[#1F1812] font-mono text-xs uppercase tracking-wider hover:bg-[#F7F3EC] transition">
                    Lihat Menu Kafe
                </a>
            </div>

            <!-- Tombol Khusus Mode Owner: Request Lagu Lagi Tanpa Batas -->
            <template x-if="isOwner">
                <div class="mt-5 pt-5 border-t border-[#D5CCC0]/60">
                    <button type="button"
                            @click="requestSubmitted = false; selectedSong = null; query = '';"
                            class="w-full sm:w-auto px-6 py-3 bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] hover:text-white font-mono text-xs uppercase tracking-widest font-bold transition shadow-md flex items-center justify-center gap-2 mx-auto cursor-pointer">
                        <span>👑</span>
                        <span>+ Request Lagu Lainnya (Akses Owner Unlimited)</span>
                    </button>
                    <p class="text-[11px] text-[#7A6A58] mt-2 font-mono">
                        Mode Owner aktif: Anda dapat memasukkan lagu sebanyak yang diinginkan ke antrean kafe tanpa batas.
                    </p>
                </div>
            </template>
        </div>
    </template>

    <!-- FORM UTAMA REQUEST MUSIK -->
    <template x-if="!requestSubmitted">
        <div class="space-y-6">

            <!-- BANNER ATURAN & ETIKA REQUEST MUSIK KAFE (ANTI LAGU 10 MENIT / 1 JAM) -->
            <div class="bg-[#FDFBF7] border border-[#E0D8CC] p-4 sm:p-5 shadow-sm text-[#1F1812]">
                <div class="flex items-center gap-2 mb-2 pb-2 border-b border-[#EAE2D5]">
                    <span class="text-base">📜</span>
                    <span class="font-mono text-xs uppercase tracking-wider font-bold text-[#1F1812]">
                        Aturan & Etika Request Musik Kafe
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-[#5C4D3C]">
                    <div class="flex items-start gap-2">
                        <span class="text-[#D9973E] font-bold shrink-0">⏱️</span>
                        <div>
                            <b class="text-[#1F1812]">Maksimal Durasi 7 Menit</b>
                            <p class="text-[11px] text-[#7A6A58] mt-0.5">
                                Video 10 menit, kompilasi 1 jam, podcast, atau live stream otomatis ditolak sistem agar semua pengunjung kebagian giliran.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-[#5F7F42] font-bold shrink-0">☕</span>
                        <div>
                            <b class="text-[#1F1812]">Suasana Santai Kafe</b>
                            <p class="text-[11px] text-[#7A6A58] mt-0.5">
                                Disarankan lagu santai (Pop, Akustik, Jazz, Indie, Lo-fi) yang cocok menemani ngobrol & menikmati kopi.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-[#1F1812] font-bold shrink-0">🎟️</span>
                        <div>
                            <b class="text-[#1F1812]">1 Struk = 1 Lagu Favorit</b>
                            <p class="text-[11px] text-[#7A6A58] mt-0.5">
                                Setiap transaksi berhak atas 1x request. Untuk request berikutnya, silakan pesan menu kopi lagi di kasir.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-red-600 font-bold shrink-0">🛡️</span>
                        <div>
                            <b class="text-[#1F1812]">Bebas SARA & Konten Kasar</b>
                            <p class="text-[11px] text-[#7A6A58] mt-0.5">
                                Kasir berhak melewati (*skip*) lagu yang memuat lirik tidak pantas demi kenyamanan seluruh pengunjung.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LANGKAH 1: VALIDASI KODE STRUK -->
            <div class="bg-white border border-[#E0D8CC] p-5 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">LANGKAH 1 DARI 2</span>
                    <span class="text-xs font-mono font-semibold"
                          :class="isOwner ? 'text-[#D9973E] font-bold flex items-center gap-1' : 'text-[#D9973E]'">
                        <template x-if="isOwner">
                            <span>👑 AKSES OWNER: UNLIMITED REQUEST</span>
                        </template>
                        <template x-if="!isOwner">
                            <span>1 Transaksi = 1 Lagu</span>
                        </template>
                    </span>
                </div>
                <label class="block text-sm font-semibold text-[#1F1812] mb-1">
                    Kode Unik dari Struk Transaksi
                </label>
                <p class="text-xs text-[#7A6A58] mb-3">
                    Lihat kode 8 karakter bertuliskan <span class="font-mono font-bold text-[#1F1812]">KODE: MK-XXXXXX</span> di bagian bawah struk belanja Anda.
                </p>

                <div class="flex gap-2">
                    <input type="text"
                           x-model="code"
                           @keydown.enter.prevent="checkCode()"
                           placeholder="Contoh: MK-7A8B9C"
                           class="flex-1 px-3.5 py-2.5 bg-[#F7F3EC] border border-[#D5CCC0] text-sm font-mono uppercase tracking-widest text-[#1F1812] focus:outline-none focus:border-[#D9973E] focus:ring-1 focus:ring-[#D9973E]"
                           :disabled="isValid">

                    <button type="button"
                            @click="isValid ? (isValid = false, isOwner = false, code = '') : checkCode()"
                            class="px-5 py-2.5 font-mono text-xs uppercase tracking-wider transition font-bold"
                            :class="isValid ? 'bg-[#7A6A58] text-white hover:bg-[#5C4D3C]' : 'bg-[#1F1812] text-[#F7F3EC] hover:bg-[#D9973E] hover:text-[#1F1812]'">
                        <span x-show="validating">Cek...</span>
                        <span x-show="!validating && !isValid">Gunakan Kode</span>
                        <span x-show="!validating && isValid">Ganti Kode</span>
                    </button>
                </div>

                <!-- PESAN VALIDASI -->
                <div class="mt-3 text-xs" x-show="valMessage">
                    <!-- Banner VIP Mode Owner -->
                    <div x-show="isValid && isOwner" class="p-3.5 bg-[#D9973E]/15 border border-[#D9973E] text-[#1F1812] flex items-center gap-2.5">
                        <span class="text-xl">👑</span>
                        <div>
                            <div class="font-bold text-[#B5762A] uppercase tracking-wider text-[11px]">Mode Akses Owner Terverifikasi</div>
                            <div class="text-xs font-medium" x-text="valMessage"></div>
                        </div>
                    </div>
                    <!-- Banner Pelanggan Normal -->
                    <div x-show="isValid && !isOwner" class="p-3 bg-[#5F7F42]/10 border border-[#5F7F42]/30 text-[#5F7F42] flex items-center gap-2">
                        <span class="font-bold">✓</span>
                        <span x-text="valMessage"></span>
                    </div>
                    <div x-show="!isValid && valMessage" class="p-3 bg-red-50 border border-red-200 text-red-700 flex items-start gap-2">
                        <span class="font-bold">✕</span>
                        <div>
                            <span x-text="valMessage"></span>
                            <template x-if="alreadyUsed">
                                <div class="mt-1 text-[11px] text-red-600">
                                    Setiap transaksi kafe berhak atas 1 request lagu. Ingin request lagu lagi? Silakan nikmati pesanan kopi atau camilan berikutnya di kasir!
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LANGKAH 2: CARI & PILIH LAGU (AKTIF JIKA KODE VALID) -->
            <div class="bg-white border border-[#E0D8CC] p-5 sm:p-6 shadow-sm transition"
                 :class="{ 'opacity-50 pointer-events-none select-none': !isValid }">

                <div class="flex items-center justify-between mb-3">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">LANGKAH 2 DARI 2</span>
                    <span class="text-xs font-mono font-bold"
                          :class="isOwner ? 'text-[#D9973E]' : 'text-[#5F7F42]'"
                          x-show="isValid"
                          x-text="isOwner ? '👑 Owner VIP Active ✓' : 'Kode Terverifikasi ✓'">
                    </span>
                </div>

                <label class="block text-sm font-semibold text-[#1F1812] mb-1">
                    Cari Lagu atau Tempel Tautan YouTube
                </label>
                <p class="text-xs text-[#7A6A58] mb-3">
                    Tempel link video YouTube (contoh: <span class="font-mono text-[#1F1812]">https://youtu.be/...</span>) atau ketik judul lagu dan nama penyanyi:
                </p>

                <div class="flex gap-2 mb-4">
                    <input type="text"
                           x-model="query"
                           @keydown.enter.prevent="searchOrExtract()"
                           placeholder="Contoh: Nadin Amizah - Rayuan Perempuan Gila atau link YouTube"
                           class="flex-1 px-3.5 py-2.5 bg-[#F7F3EC] border border-[#D5CCC0] text-sm text-[#1F1812] focus:outline-none focus:border-[#D9973E]">

                    <button type="button"
                            @click="searchOrExtract()"
                            :disabled="searching || !query.trim()"
                            class="px-5 py-2.5 bg-[#D9973E] text-[#1F1812] font-mono text-xs font-bold uppercase tracking-wider hover:bg-[#c4842e] transition disabled:opacity-50">
                        <span x-show="searching">Mencari...</span>
                        <span x-show="!searching">Pilih Lagu</span>
                    </button>
                </div>

                <!-- PRESET POPULER KAFE (DENGAN INFORMASI DURASI) -->
                <div class="mb-5">
                    <span class="block font-mono text-[10px] uppercase tracking-wider text-[#A89A85] mb-2">Rekomendasi Cepat Suasana Kafe:</span>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="selectPreset('Until I Found You', 'Stephen Sanchez', 'GxldQ9GyXfk', 177, '02:57')"
                                class="px-2.5 py-1 bg-[#F7F3EC] hover:bg-[#EAE2D5] border border-[#D5CCC0] text-xs text-[#1F1812] transition flex items-center gap-1.5">
                            <span>☕ Until I Found You</span>
                            <span class="font-mono text-[10px] text-[#7A6A58]">(02:57)</span>
                        </button>
                        <button type="button" @click="selectPreset('Golden Hour', 'JVKE', 'PEM0Vs8jf1w', 209, '03:29')"
                                class="px-2.5 py-1 bg-[#F7F3EC] hover:bg-[#EAE2D5] border border-[#D5CCC0] text-xs text-[#1F1812] transition flex items-center gap-1.5">
                            <span>🌅 Golden Hour</span>
                            <span class="font-mono text-[10px] text-[#7A6A58]">(03:29)</span>
                        </button>
                        <button type="button" @click="selectPreset('Sialan', 'Adrian Khalif & Juicy Luicy', 'fG4-oP4e0pQ', 238, '03:58')"
                                class="px-2.5 py-1 bg-[#F7F3EC] hover:bg-[#EAE2D5] border border-[#D5CCC0] text-xs text-[#1F1812] transition flex items-center gap-1.5">
                            <span>📻 Sialan - Juicy Luicy</span>
                            <span class="font-mono text-[10px] text-[#7A6A58]">(03:58)</span>
                        </button>
                        <button type="button" @click="selectPreset('Fly Me to the Moon', 'Frank Sinatra', 'ZEcqHA7dbwM', 147, '02:27')"
                                class="px-2.5 py-1 bg-[#F7F3EC] hover:bg-[#EAE2D5] border border-[#D5CCC0] text-xs text-[#1F1812] transition flex items-center gap-1.5">
                            <span>🎷 Fly Me to the Moon</span>
                            <span class="font-mono text-[10px] text-[#7A6A58]">(02:27)</span>
                        </button>
                    </div>
                </div>

                <!-- PRATINJAU LAGU TERPILIH (DENGAN PENGECEKAN DURASI MAKSIMAL 7 MENIT) -->
                <template x-if="selectedSong">
                    <div class="p-4 bg-[#F7F3EC] border-2 mb-5 transition-all"
                         :class="selectedSong.is_valid_duration ? 'border-[#D9973E]' : 'border-red-500 bg-red-50/50'">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold"
                                  :class="selectedSong.is_valid_duration ? 'text-[#D9973E]' : 'text-red-600'">
                                Lagu Pilihan Kamu:
                            </span>
                            <!-- BADGE INDIKATOR DURASI -->
                            <template x-if="selectedSong.duration_formatted && selectedSong.duration_formatted !== '--:--'">
                                <span class="font-mono text-[10px] px-2 py-0.5 rounded border"
                                      :class="selectedSong.is_valid_duration
                                        ? 'bg-[#5F7F42]/15 text-[#5F7F42] border-[#5F7F42]/30'
                                        : 'bg-red-100 text-red-700 border-red-300 font-bold'">
                                    ⏱️ Durasi: <span x-text="selectedSong.duration_formatted"></span>
                                </span>
                            </template>
                        </div>

                        <div class="flex items-center gap-4">
                            <img :src="selectedSong.thumbnail_url" alt="Thumb" class="w-20 h-14 object-cover border border-[#1F1812] shrink-0">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-sm text-[#1F1812] truncate" x-text="selectedSong.title"></div>
                                <div class="text-xs text-[#7A6A58] truncate" x-text="selectedSong.artist || 'Artis YouTube'"></div>
                                <div class="font-mono text-[10px] text-[#A89A85] mt-1" x-text="'ID: ' + selectedSong.youtube_id"></div>
                            </div>
                        </div>

                        <!-- PERINGATAN JIKA MELEBIHI BATAS DURASI 7 MENIT -->
                        <template x-if="selectedSong.is_valid_duration === false">
                            <div class="mt-3 p-3 bg-red-50 border border-red-200 text-xs text-red-700 rounded flex items-start gap-2">
                                <span class="text-sm font-bold leading-none">⚠️</span>
                                <div class="flex-1">
                                    <div class="font-bold">Lagu Tidak Dapat Diputar (Durasi Melebihi Batas)</div>
                                    <div class="mt-0.5 text-[11px]" x-text="selectedSong.duration_error || 'Durasi lagu ini melebihi batas 7 menit. Silakan cari atau pilih lagu lain demi kenyamanan bersama seluruh pengunjung.'"></div>
                                </div>
                            </div>
                        </template>

                        <!-- INPUT NAMA / NOMOR MEJA -->
                        <div class="mt-4 pt-4 border-t border-[#D5CCC0]">
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] mb-1">
                                Nama Kamu atau Nomor Meja (Opsional):
                            </label>
                            <input type="text"
                                   x-model="customerName"
                                   placeholder="Contoh: Budi / Meja 03"
                                   maxlength="100"
                                   class="w-full px-3 py-2 bg-white border border-[#D5CCC0] text-sm text-[#1F1812] focus:outline-none focus:border-[#D9973E]">
                        </div>

                        <!-- TOMBOL SUBMIT -->
                        <div class="mt-4">
                            <button type="button"
                                    @click="submitSong()"
                                    :disabled="submitting || selectedSong.is_valid_duration === false"
                                    class="w-full py-3 bg-[#1F1812] text-[#F7F3EC] font-mono text-xs uppercase tracking-widest font-bold transition shadow disabled:opacity-50 disabled:cursor-not-allowed hover:enabled:bg-[#D9973E] hover:enabled:text-[#1F1812]">
                                <span x-show="submitting">Memproses Request...</span>
                                <span x-show="!submitting && selectedSong.is_valid_duration !== false && isOwner">👑 Masukkan ke Antrean (Akses Owner Unlimited) ›</span>
                                <span x-show="!submitting && selectedSong.is_valid_duration !== false && !isOwner">♫ Masukkan ke Antrean Pemutar Kafe ›</span>
                                <span x-show="!submitting && selectedSong.is_valid_duration === false">⛔ Durasi Melebihi Batas (Maks. 7 Menit)</span>
                            </button>
                            <div class="text-center font-mono text-[10px] text-[#7A6A58] mt-2">
                                * Lagu akan diputar segera setelah lagu saat ini selesai diputar.
                            </div>
                        </div>
                    </div>
                </template>

            </div>

            <!-- ANTREAN LAGU KAFE SAAT INI (PREVIEW) -->
            <div class="bg-white border border-[#E0D8CC] p-5 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-[#E0D8CC] pb-3 mb-3">
                    <span class="font-mono text-xs uppercase tracking-widest text-[#1F1812] font-bold">
                        Antrean Request Berikutnya
                    </span>
                    <span class="font-mono text-xs text-[#D9973E]" x-text="queue.length + ' lagu menunggu'"></span>
                </div>

                <template x-if="queue.length === 0">
                    <div class="text-center py-6 text-sm text-[#A89A85] font-mono">
                        Antrean sedang kosong. Request kamu akan langsung menjadi yang pertama berikutnya!
                    </div>
                </template>

                <template x-if="queue.length > 0">
                    <div class="space-y-2">
                        <template x-for="(item, index) in queue" :key="item.id">
                            <div class="flex items-center justify-between p-2.5 bg-[#F7F3EC] border border-[#EAE2D5] text-xs">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="font-mono font-bold text-[#A89A85] w-5 text-center" x-text="'#' + (index + 1)"></span>
                                    <div class="min-w-0">
                                        <div class="font-medium text-[#1F1812] truncate" x-text="item.song_title"></div>
                                        <div class="text-[10px] text-[#7A6A58]" x-text="'Dari: ' + (item.customer_name || 'Pelanggan')"></div>
                                    </div>
                                </div>
                                <span class="font-mono text-[9px] uppercase tracking-wider text-[#5F7F42] bg-[#5F7F42]/10 px-2 py-0.5 border border-[#5F7F42]/20 shrink-0">
                                    Menunggu
                                </span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

        </div>
    </template>
</div>
@endsection
