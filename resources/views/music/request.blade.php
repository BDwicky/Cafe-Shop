@extends('layouts.public')

@section('title', 'Request Musik Kafe — ' . config('cafe.name'))

@section('content')
<style>
    @keyframes spinVinyl {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin-vinyl {
        animation: spinVinyl 16s linear infinite;
    }
    @keyframes eqBarPulse {
        0%, 100% { height: 20%; }
        50% { height: 100%; }
    }
    .animate-eq-1 { animation: eqBarPulse 0.9s ease-in-out infinite alternate; }
    .animate-eq-2 { animation: eqBarPulse 1.2s ease-in-out infinite alternate; animation-delay: 0.15s; }
    .animate-eq-3 { animation: eqBarPulse 0.8s ease-in-out infinite alternate; animation-delay: 0.3s; }
    .animate-eq-4 { animation: eqBarPulse 1.1s ease-in-out infinite alternate; animation-delay: 0.45s; }
</style>

<div class="min-h-screen bg-gradient-to-b from-[#FAF7F2] via-[#F5EFE6] to-[#EDE4D6] text-[#1F1812] py-8 sm:py-12 px-4 sm:px-6"
     x-data="{
        code: '{{ $code ?? '' }}',
        validating: false,
        isValid: {{ isset($initialValidation['valid']) && $initialValidation['valid'] ? 'true' : 'false' }},
        isOwner: {{ isset($initialValidation['is_owner']) && $initialValidation['is_owner'] ? 'true' : 'false' }},
        isTest: {{ isset($initialValidation['is_test']) && $initialValidation['is_test'] ? 'true' : 'false' }},
        valMessage: '{{ addslashes($initialValidation['message'] ?? '') }}',
        alreadyUsed: {{ isset($initialValidation['already_used']) && $initialValidation['already_used'] ? 'true' : 'false' }},
        orderInfo: {{ isset($initialValidation['order']) ? json_encode($initialValidation['order']) : 'null' }},

        query: '',
        searching: false,
        searchError: null,
        selectedSong: null,
        customerName: '{{ $initialValidation['order']['customer_name'] ?? '' }}',

        submitting: false,
        requestSubmitted: false,
        myPosition: null,
        mySong: null,

        nowPlaying: {{ json_encode($playerState['now_playing']) }},
        queue: {{ json_encode($playerState['queue']) }},
        queueCount: {{ (int) ($playerState['queue_count'] ?? 0) }},
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
                            if (s.currentTrack.duration_seconds) {
                                this.playbackDuration = Number(s.currentTrack.duration_seconds);
                                this.playbackDurationFormatted = this.formatSeconds(this.playbackDuration);
                            }
                        }
                        if (typeof s.queueCount !== 'undefined') {
                            this.queueCount = s.queueCount;
                        }
                        if (Array.isArray(s.queue)) {
                            this.queue = s.queue;
                        }
                        if (typeof s.isPlaying !== 'undefined') {
                            this.isPlaying = !!s.isPlaying;
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

            // Polling status antrean & lagu kafe setiap 3.5 detik
            this.fetchStatus();
            setInterval(() => this.fetchStatus(), 3500);
        },

        async checkCode() {
            const raw = this.code.trim();
            if (!raw) {
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
                    body: JSON.stringify({ code: raw })
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
                    this.isTest = !!data.is_test;
                    this.valMessage = data.message;
                    this.alreadyUsed = !!data.already_used;
                    this.orderInfo = data.order || null;
                    if (data.is_owner && (!this.customerName || this.customerName === 'Pelanggan')) {
                        this.customerName = '👑 Owner';
                    } else if (data.is_test && (!this.customerName || this.customerName === 'Pelanggan')) {
                        this.customerName = 'Pelanggan (Testing)';
                    } else if (data.order && data.order.customer_name) {
                        this.customerName = data.order.customer_name;
                    }
                } else {
                    this.isValid = false;
                    this.isOwner = false;
                    this.isTest = false;
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

        onQueryInput() {
            const q = (this.query || '').trim();
            if (!q) {
                this.selectedSong = null;
                this.searchError = null;
                return;
            }
            // Jika user mengetik/menempel link YouTube atau ID YouTube 11 karakter
            if (q.includes('youtube.com') || q.includes('youtu.be') || (q.length === 11 && !q.includes(' '))) {
                this.searchOrExtract();
            }
        },

        async searchOrExtract() {
            const q = (this.query || '').trim();
            if (!q) return;

            this.searching = true;
            this.searchError = null;
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
                        thumbnail_url: first.thumbnail_url || ('https://img.youtube.com/vi/' + first.youtube_id + '/hqdefault.jpg'),
                        duration_seconds: first.duration_seconds || null,
                        duration_formatted: first.duration_formatted || '--:--',
                        is_live: !!first.is_live,
                        is_nsfw: !!first.is_nsfw,
                        is_valid_duration: first.is_valid_duration !== false,
                        is_valid: first.is_valid !== false && !first.is_live && !first.is_nsfw && first.is_valid_duration !== false,
                        error_message: first.error_message || first.duration_error || null
                    };

                    if (!this.isOwner && !this.selectedSong.is_valid) {
                        let alertTitle = 'Lagu Tidak Memenuhi Aturan Kafe';
                        if (this.selectedSong.is_live) {
                            alertTitle = '🔴 Siaran Langsung (Live Stream) Ditolak';
                        } else if (this.selectedSong.is_nsfw) {
                            alertTitle = '🔞 Konten Dewasa / NSFW Ditolak';
                        } else if (!this.selectedSong.is_valid_duration) {
                            alertTitle = '⏱️ Durasi Melebihi Batas Kafe';
                        }

                        if (window.customToast) {
                            window.customToast({
                                message: '⚠️ Tautan video melanggar aturan kafe dan tidak dapat di-request.',
                                type: 'error',
                                duration: 3500
                            });
                        }

                        if (window.customAlert) {
                            window.customAlert({
                                title: alertTitle,
                                message: this.selectedSong.error_message || 'Lagu ini tidak dapat diputar di kafe. Silakan pilih lagu lain.',
                                type: 'error',
                                btnText: 'Paham, Ganti Lagu'
                            });
                        }
                    }
                } else {
                    this.searchError = 'Lagu atau video YouTube tidak ditemukan. Periksa kembali tautan Anda.';
                    if (window.customToast) {
                        window.customToast({
                            message: '❌ Tautan video YouTube tidak ditemukan.',
                            type: 'error',
                            duration: 3000
                        });
                    }
                }
            } catch (e) {
                this.searchError = 'Gagal mencari detail lagu dari YouTube. Pastikan koneksi internet stabil.';
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
                is_live: false,
                is_nsfw: false,
                is_valid_duration: true,
                is_valid: true,
                error_message: null
            };
            this.query = 'https://youtu.be/' + ytId;
            this.searchError = null;
        },

        async submitSong() {
            // 1. Pastikan kode sudah divalidasi jika belum
            if (!this.isValid) {
                await this.checkCode();
                if (!this.isValid) {
                    if (window.customAlert) {
                        window.customAlert({
                            title: 'Kode Struk Belum Valid',
                            message: this.valMessage || 'Harap masukkan kode struk belanja yang valid terlebih dahulu.',
                            type: 'warning',
                            btnText: 'Mengerti'
                        });
                    }
                    return;
                }
            }

            // 2. Jika user langsung klik submit padahal baru mengetik/menempel link dan belum sempat auto-search
            if (!this.selectedSong && this.query.trim()) {
                await this.searchOrExtract();
            }

            if (!this.selectedSong) {
                if (window.customAlert) {
                    window.customAlert({
                        title: 'Pilih Lagu Terlebih Dahulu',
                        message: 'Tempel link video YouTube atau klik salah satu rekomendasi lagu.',
                        type: 'warning',
                        btnText: 'Mengerti'
                    });
                }
                return;
            }

            if (!this.isOwner && (this.selectedSong.is_valid === false || this.selectedSong.is_valid_duration === false || this.selectedSong.is_live || this.selectedSong.is_nsfw)) {
                let alertTitle = 'Lagu Tidak Memenuhi Aturan';
                if (this.selectedSong.is_live) alertTitle = '🔴 Live Stream Ditolak';
                else if (this.selectedSong.is_nsfw) alertTitle = '🔞 Konten Dewasa / NSFW Ditolak';
                else if (!this.selectedSong.is_valid_duration) alertTitle = '⏱️ Durasi Melebihi Batas';

                if (window.customToast) {
                    window.customToast({
                        message: '❌ Lagu melanggar aturan kafe. Silakan pilih lagu lain.',
                        type: 'error',
                        duration: 3500
                    });
                }

                if (window.customAlert) {
                    window.customAlert({
                        title: alertTitle,
                        message: this.selectedSong.error_message || 'Lagu ini tidak memenuhi aturan pemutar musik kafe. Silakan pilih lagu lain.',
                        type: 'error',
                        btnText: 'Paham, Ganti Lagu'
                    });
                }
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
                        song_title: this.selectedSong.title || null,
                        artist: this.selectedSong.artist || null,
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
                    this.isTest = !!data.is_test;
                    this.query = '';
                    this.selectedSong = null;
                    this.searchError = null;
                    if (!this.isOwner && !this.isTest) {
                        this.alreadyUsed = true;
                    }
                    this.fetchStatus();

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
                    if (window.customAlert) {
                        window.customAlert({
                            title: 'Perhatian',
                            message: warnMsg,
                            type: 'warning',
                            btnText: 'OK'
                        });
                    } else {
                        alert(warnMsg);
                    }
                }
            } catch (e) {
                if (window.customAlert) {
                    window.customAlert({
                        title: 'Kendala Koneksi',
                        message: 'Tidak dapat terhubung ke server kafe. Pastikan perangkat Anda terhubung ke internet/Wi-Fi kafe, lalu coba lagi.',
                        type: 'error',
                        btnText: 'OK'
                    });
                } else {
                    alert('Tidak dapat terhubung ke server kafe.');
                }
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

    <div class="max-w-6xl mx-auto space-y-6 sm:space-y-8">

        <!-- HERO BRANDING HEADER -->
        <header class="text-center space-y-2">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-[#D9973E]/15 border border-[#D9973E]/40 text-[#D9973E] text-xs font-mono uppercase tracking-[0.2em] rounded-full shadow-xs">
                <span class="w-2 h-2 rounded-full bg-[#D9973E] animate-pulse"></span>
                <span>Jukebox Digital Kafe &bull; {{ config('cafe.name') }}</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-serif font-extrabold text-[#1F1812] tracking-tight">
                Request Musik Favoritmu
            </h1>
            <p class="text-xs sm:text-sm text-[#7A6A58] max-w-xl mx-auto leading-relaxed">
                Scan struk belanja kafe, tempel link video YouTube lagumu, dan dengarkan lagumu berputar di speaker kafe!
            </p>
        </header>

        <!-- 2-GRID LAYOUT (KIRI: FORM & ATURAN, KANAN: LIVE STATION & ANTREAN) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">

            <!-- GRID KIRI: FORM REQUEST & PANDUAN (lg:col-span-7) -->
            <div class="lg:col-span-7 space-y-6">

                <!-- 1. NOTIFIKASI SUKSES SETELAH REQUEST BERHASIL (BANNER ELEGAN & RINGKAS) -->
                <template x-if="requestSubmitted && mySong">
                    <div class="p-4 sm:p-5 bg-[#5F7F42]/10 border-2 border-[#5F7F42] rounded-2xl shadow-md animate-fade-in space-y-3">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5 min-w-0 flex-1">
                                <div class="w-11 h-11 rounded-full bg-[#5F7F42] text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-sm">
                                    ✓
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-serif font-bold text-base text-[#1F1812] leading-tight flex items-center gap-2">
                                        <span>Lagu Berhasil Masuk Antrean Prioritas!</span>
                                        <span class="px-2 py-0.5 bg-[#5F7F42] text-white font-mono text-[10px] rounded font-bold"
                                              x-text="'Urutan #' + myPosition"></span>
                                    </div>
                                    <div class="text-xs text-[#5C4D3C] mt-1 truncate">
                                        <b class="text-[#1F1812]" x-text="mySong.song_title"></b>
                                        <span class="text-[#8C7D6B]" x-text="' &bull; ' + (mySong.artist || 'YouTube')"></span>
                                    </div>
                                </div>
                            </div>
                            <template x-if="isOwner">
                                <span class="font-mono text-[10px] font-bold text-[#D9973E] bg-[#D9973E]/15 border border-[#D9973E]/30 px-2.5 py-1 rounded-lg shrink-0">
                                    👑 Mode Owner (Bebas Tambah)
                                </span>
                            </template>
                            <template x-if="isTest">
                                <span class="font-mono text-[10px] font-bold text-[#5F7F42] bg-[#5F7F42]/15 border border-[#5F7F42]/30 px-2.5 py-1 rounded-lg shrink-0">
                                    🧪 Mode Testing (Bisa Request Lagi)
                                </span>
                            </template>
                        </div>
                        <!-- Keterangan instant play & pause lagu bawaan -->
                        <div class="pt-2.5 border-t border-[#5F7F42]/25 flex items-center gap-2 text-xs font-mono text-[#3D5A24]">
                            <span class="text-base">⚡</span>
                            <span x-show="myPosition === 1">
                                <b>Prioritas #1:</b> Antrean request kosong! Lagumu akan <b>langsung diputar sekarang</b> dan lagu bawaan kafe otomatis di-pause.
                            </span>
                            <span x-show="myPosition > 1" x-text="'Lagu kamu berada di urutan #' + myPosition + ' antrean request dan diprioritaskan sebelum lagu bawaan kafe diputar kembali.'"></span>
                        </div>
                    </div>
                </template>

                <!-- 2. KONDISI PELANGGAN BIASA: STRUK SUDAH TERPAKAI (1 STRUK = 1 LAGU) -->
                <template x-if="alreadyUsed && !isOwner && !isTest">
                    <div class="bg-white border border-[#E5DDD0] rounded-2xl p-6 sm:p-8 text-center shadow-md space-y-4">
                        <div class="w-14 h-14 bg-[#D9973E]/15 text-[#D9973E] rounded-full mx-auto flex items-center justify-center text-2xl font-bold">
                            🎟️
                        </div>
                        <div>
                            <h2 class="text-xl font-serif font-bold text-[#1F1812]">Struk Transaksi Sudah Digunakan</h2>
                            <p class="text-xs text-[#7A6A58] mt-1.5 max-w-md mx-auto leading-relaxed">
                                Setiap 1 transaksi kafe berhak atas 1 request lagu. Request lagu Anda sedang mengantre dan akan otomatis diputar di speaker kafe!
                            </p>
                        </div>

                        <div class="pt-2 flex flex-col sm:flex-row gap-2.5 justify-center">
                            <a href="{{ route('music.display') }}" target="_blank"
                                class="px-5 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-xs">
                                Lihat Layar TV Musik ›
                            </a>
                            <button type="button"
                                    @click="code = ''; isValid = false; isOwner = false; isTest = false; alreadyUsed = false; valMessage = ''; requestSubmitted = false;"
                                    class="px-5 py-2.5 border border-[#D5CCC0] hover:bg-[#FAF7F2] text-[#1F1812] font-mono text-xs uppercase tracking-wider font-semibold rounded-xl transition cursor-pointer">
                                Punya Struk Lain? Masukkan Kode
                            </button>
                        </div>
                    </div>
                </template>

                <!-- 3. FORM UTAMA REQUEST MUSIK: 1 KARTU TUNGGAL TANPA TAHAP BERBELIT (TAMPIL JIKA BELUM TERPAKAI ATAU OWNER ATAU TEST) -->
                <template x-if="!alreadyUsed || isOwner || isTest">
                    <div class="bg-white border border-[#E5DDD0] rounded-2xl p-5 sm:p-7 shadow-md space-y-5">
                        <div class="flex items-center justify-between border-b border-[#E5DDD0] pb-3.5">
                            <div>
                                <h2 class="text-lg sm:text-xl font-serif font-bold text-[#1F1812]">
                                    Form Request Musik Kafe
                                </h2>
                                <p class="text-xs text-[#7A6A58] mt-0.5">
                                    Masukkan kode struk dan tempel link YouTube. Lagu otomatis masuk antrean tanpa perlu ketik judul.
                                </p>
                            </div>
                            <template x-if="isOwner">
                                <span class="font-mono text-[10px] uppercase font-bold text-[#D9973E] bg-[#D9973E]/10 border border-[#D9973E]/30 px-2.5 py-1 rounded-full shrink-0">
                                    👑 AKSES OWNER: UNLIMITED REQUEST
                                </span>
                            </template>
                            <template x-if="isTest">
                                <span class="font-mono text-[10px] uppercase font-bold text-[#5F7F42] bg-[#5F7F42]/10 border border-[#5F7F42]/30 px-2.5 py-1 rounded-full shrink-0">
                                    🧪 TESTING: MULTI-REQUEST
                                </span>
                            </template>
                        </div>

                        <!-- BANNER PRIORITAS & PUTAR LANGSUNG (INSTANT PLAY) -->
                        <div class="p-3 sm:p-3.5 bg-gradient-to-r from-[#D9973E]/15 via-[#FAF7F2] to-[#5F7F42]/15 border border-[#D9973E]/40 rounded-xl flex items-start gap-3 shadow-xs animate-fade-in">
                            <div class="w-8 h-8 rounded-lg bg-[#D9973E] text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-xs mt-0.5">
                                ⚡
                            </div>
                            <div class="min-w-0 flex-1 text-xs">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-serif font-bold text-[#1F1812] text-xs sm:text-sm">
                                        Prioritas Utama & Putar Langsung (Instant Play)
                                    </span>
                                    <span class="px-2 py-0.5 bg-[#D9973E]/20 text-[#9E641B] font-mono text-[9px] uppercase tracking-wider rounded font-bold">
                                        Antrean Prioritas
                                    </span>
                                </div>
                                <p class="text-[#5C4D3C] mt-1 leading-relaxed text-[11px] sm:text-xs font-sans">
                                    Lagu request pengunjung <b>selalu diprioritaskan</b> di atas playlist bawaan kafe. Jika antrean request sedang kosong, lagu pilihanmu akan <b>langsung diputar seketika</b> dan lagu bawaan kafe otomatis <b>di-pause</b>!
                                </p>
                            </div>
                        </div>

                        <!-- INPUT 1: KODE STRUK BELANJA -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-semibold">
                                    Kode Struk Belanja Kafe:
                                </label>
                                <template x-if="isValid">
                                    <span class="text-xs text-[#5F7F42] font-mono font-bold flex items-center gap-1">
                                        <span>✓</span>
                                        <span x-text="isOwner ? '👑 Owner' : (isTest ? '🧪 Testing (Multi-Req)' : 'Terverifikasi')"></span>
                                    </span>
                                </template>
                            </div>
                            <div class="relative">
                                <input type="text"
                                       x-model="code"
                                       @input.debounce.500ms="checkCode()"
                                       placeholder="Contoh: MK-7A8B9C (lihat di bagian bawah struk belanja)"
                                       class="w-full px-4 py-3 bg-[#FAF7F2] border rounded-xl text-sm font-mono uppercase tracking-widest text-[#1F1812] focus:outline-none focus:ring-2 transition shadow-inner font-semibold"
                                       :class="isValid ? 'border-[#5F7F42] focus:border-[#5F7F42] focus:ring-[#5F7F42]/20' : 'border-[#D5CCC0] focus:border-[#D9973E] focus:ring-[#D9973E]/20'">
                                <template x-if="code && !validating">
                                    <button type="button"
                                            @click="code = ''; isValid = false; isOwner = false; isTest = false; valMessage = '';"
                                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 text-sm cursor-pointer">
                                        ✕
                                    </button>
                                </template>
                                <template x-if="validating">
                                    <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs text-[#D9973E] font-mono animate-spin">
                                        ⟳
                                    </span>
                                </template>
                            </div>
                            <div x-show="valMessage && !isValid" class="text-xs text-red-600 font-mono mt-1" x-text="valMessage"></div>
                        </div>

                        <!-- INPUT 2: LINK YOUTUBE (DETEKSI OTOMATIS TANPA TOMBOL "CEK LAGU") -->
                        <div class="space-y-1.5 pt-1">
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-semibold">
                                Tautan Link Video YouTube:
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-red-600 font-bold">
                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                </div>
                                <input type="text"
                                       x-model="query"
                                       @input.debounce.400ms="onQueryInput()"
                                       @paste="setTimeout(() => onQueryInput(), 50)"
                                       placeholder="Tempel link YouTube (contoh: https://youtu.be/...)"
                                       class="w-full pl-11 pr-10 py-3 rounded-xl text-sm transition shadow-inner focus:outline-none focus:ring-2"
                                       :class="selectedSong && (!selectedSong.is_valid || selectedSong.is_live || selectedSong.is_nsfw || !selectedSong.is_valid_duration)
                                           ? '!bg-red-50/50 !border-red-500 !text-red-950 focus:!border-red-600 focus:!ring-red-200'
                                           : 'bg-[#FAF7F2] border border-[#D5CCC0] text-[#1F1812] focus:border-[#D9973E] focus:ring-[#D9973E]/20'">
                                <button type="button"
                                        x-show="query"
                                        @click="query = ''; selectedSong = null; searchError = null;"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 text-sm cursor-pointer">
                                    ✕
                                </button>
                            </div>

                            <!-- Live scanning indicator -->
                            <div x-show="searching" class="p-2.5 bg-[#D9973E]/10 border border-[#D9973E]/30 rounded-xl text-xs font-mono text-[#D9973E] flex items-center gap-2 animate-pulse">
                                <span class="animate-spin text-sm">⟳</span>
                                <span>Mendeteksi judul, artis & durasi lagu YouTube...</span>
                            </div>
                            <div x-show="searchError" class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs font-mono text-red-700" x-text="searchError"></div>

                            <!-- NOTIFIKASI / ALERT INLINE JIKA LINK MELANGGAR ATURAN KAFE -->
                            <template x-if="selectedSong && (!selectedSong.is_valid || selectedSong.is_live || selectedSong.is_nsfw || !selectedSong.is_valid_duration)">
                                <div class="p-3.5 sm:p-4 bg-gradient-to-r from-red-50 via-rose-50 to-red-50 border-2 border-red-500 rounded-xl text-red-900 shadow-md animate-fade-in space-y-2.5">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-red-600 text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-xs">
                                            <template x-if="selectedSong.is_live"><span>🔴</span></template>
                                            <template x-if="selectedSong.is_nsfw"><span>🔞</span></template>
                                            <template x-if="!selectedSong.is_live && !selectedSong.is_nsfw"><span>⚠️</span></template>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-serif font-bold text-sm text-red-950"
                                                    x-text="selectedSong.is_live ? 'Tautan Siaran Langsung (Live Stream) Ditolak' : (selectedSong.is_nsfw ? 'Konten Dewasa / NSFW (18+) Ditolak' : 'Durasi Video Melebihi Batas Kafe')"></h4>
                                                <span class="px-2 py-0.5 bg-red-600 text-white font-mono text-[9px] uppercase tracking-wider rounded font-bold shrink-0">
                                                    Ditolak Sistem
                                                </span>
                                            </div>
                                            <p class="text-xs text-red-800 mt-1 leading-relaxed"
                                               x-text="selectedSong.error_message || 'Video ini tidak diperkenankan diputar di area publik kafe demi kenyamanan bersama.'"></p>
                                        </div>
                                    </div>
                                    <div class="pt-2 flex items-center justify-between border-t border-red-200/80 text-xs font-mono">
                                        <span class="text-red-700 text-[11px] flex items-center gap-1.5">
                                            <span>💡</span>
                                            <span>Pilih rekomendasi santai di bawah atau tempel link lain.</span>
                                        </span>
                                        <button type="button"
                                                @click="query = ''; selectedSong = null; searchError = null;"
                                                class="px-2.5 py-1 bg-white hover:bg-red-100 text-red-700 hover:text-red-950 border border-red-300 rounded-lg text-[11px] font-bold transition cursor-pointer active:scale-95">
                                            Hapus & Ganti Lagu ✕
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- REKOMENDASI CEPAT 1-KLIK -->
                            <div class="pt-2">
                                <span class="block font-mono text-[10px] uppercase tracking-wider text-[#A89A85] mb-1.5 font-bold">
                                    Atau pilih lagu santai kafe sekali klik:
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" @click="selectPreset('Until I Found You', 'Stephen Sanchez', 'GxldQ9eX2wo', 177, '02:57')"
                                            class="px-2.5 py-1 bg-[#FAF7F2] hover:bg-[#D9973E]/15 hover:border-[#D9973E] border border-[#D5CCC0] rounded-lg text-xs text-[#1F1812] transition flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95">
                                        <span>☕ Until I Found You</span>
                                        <span class="font-mono text-[10px] text-[#7A6A58]">(02:57)</span>
                                    </button>
                                    <button type="button" @click="selectPreset('Golden Hour', 'JVKE', 'PEM0Vs8jf1w', 209, '03:29')"
                                            class="px-2.5 py-1 bg-[#FAF7F2] hover:bg-[#D9973E]/15 hover:border-[#D9973E] border border-[#D5CCC0] rounded-lg text-xs text-[#1F1812] transition flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95">
                                        <span>🌅 Golden Hour</span>
                                        <span class="font-mono text-[10px] text-[#7A6A58]">(03:29)</span>
                                    </button>
                                    <button type="button" @click="selectPreset('Sialan', 'Adrian Khalif & Juicy Luicy', '0i-D1eBVKUM', 238, '03:58')"
                                            class="px-2.5 py-1 bg-[#FAF7F2] hover:bg-[#D9973E]/15 hover:border-[#D9973E] border border-[#D5CCC0] rounded-lg text-xs text-[#1F1812] transition flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95">
                                        <span>📻 Sialan</span>
                                        <span class="font-mono text-[10px] text-[#7A6A58]">(03:58)</span>
                                    </button>
                                    <button type="button" @click="selectPreset('Fly Me to the Moon', 'Frank Sinatra', 'ZEcqHA7dbwM', 147, '02:27')"
                                            class="px-2.5 py-1 bg-[#FAF7F2] hover:bg-[#D9973E]/15 hover:border-[#D9973E] border border-[#D5CCC0] rounded-lg text-xs text-[#1F1812] transition flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95">
                                        <span>🎷 Fly Me to the Moon</span>
                                        <span class="font-mono text-[10px] text-[#7A6A58]">(02:27)</span>
                                    </button>
                                </div>
                            </div>

                            <!-- PREVIEW LAGU OTOMATIS -->
                            <template x-if="selectedSong">
                                <div class="mt-3 p-3.5 bg-gradient-to-br from-[#FAF7F2] to-[#F2EDE4] border rounded-xl animate-fade-in"
                                     :class="(isOwner || (selectedSong.is_valid && selectedSong.is_valid_duration && !selectedSong.is_live && !selectedSong.is_nsfw)) ? 'border-[#D9973E]' : 'border-red-500 bg-red-50/50'">
                                    <div class="flex items-center gap-3">
                                        <div class="relative w-20 h-14 rounded-lg overflow-hidden border border-[#3A3026] shrink-0 shadow-xs bg-black">
                                            <img :src="selectedSong.thumbnail_url" alt="Thumb" class="w-full h-full object-cover">
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-serif font-bold text-sm text-[#1F1812] truncate leading-tight"
                                                 x-text="selectedSong.title"></div>
                                            <div class="text-xs text-[#7A6A58] truncate font-mono mt-0.5"
                                                 x-text="selectedSong.artist || 'YouTube Video'"></div>
                                            <div class="mt-1 flex flex-wrap items-center gap-1.5 font-mono text-[10px]">
                                                <span class="font-bold"
                                                      :class="(isOwner || (selectedSong.is_valid && selectedSong.is_valid_duration && !selectedSong.is_live && !selectedSong.is_nsfw)) ? 'text-[#5F7F42]' : 'text-red-600'"
                                                      x-text="'⏱️ ' + selectedSong.duration_formatted"></span>
                                                <template x-if="isOwner">
                                                    <span class="px-2 py-0.5 bg-[#D9973E]/20 text-[#D9973E] border border-[#D9973E]/40 rounded font-bold">👑 Akses VIP Owner</span>
                                                </template>
                                                <template x-if="!isOwner && selectedSong.is_live">
                                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 border border-red-300 rounded font-bold">🔴 Live Stream (Ditolak)</span>
                                                </template>
                                                <template x-if="!isOwner && selectedSong.is_nsfw">
                                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 border border-red-300 rounded font-bold">🔞 Konten Dewasa / NSFW (Ditolak)</span>
                                                </template>
                                                <template x-if="!isOwner && !selectedSong.is_valid_duration && !selectedSong.is_live && !selectedSong.is_nsfw">
                                                    <span class="text-red-600 font-bold">⚠️ Melebihi batas 7 menit</span>
                                                </template>
                                            </div>
                                            <!-- Peringatan ringkas di kartu pratinjau (hanya untuk pelanggan biasa) -->
                                            <template x-if="!isOwner && (!selectedSong.is_valid || selectedSong.is_live || selectedSong.is_nsfw || !selectedSong.is_valid_duration)">
                                                <div class="mt-2 p-2 bg-red-100/90 border border-red-300 rounded-lg text-[11px] text-red-800 font-mono flex items-center gap-2">
                                                    <span class="text-sm shrink-0">🚫</span>
                                                    <span class="truncate font-semibold" x-text="selectedSong.error_message || 'Lagu ini melanggar ketentuan pemutar musik kafe.'"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- INPUT 3: NAMA / NOMOR MEJA (OPSIONAL) -->
                        <div class="space-y-1.5 pt-1">
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-semibold">
                                Nama Kamu atau Nomor Meja (Opsional):
                            </label>
                            <input type="text"
                                   x-model="customerName"
                                   placeholder="Contoh: Budi (Meja 04)"
                                   maxlength="100"
                                   class="w-full px-4 py-2.5 bg-[#FAF7F2] border border-[#D5CCC0] rounded-xl text-sm text-[#1F1812] focus:outline-none focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/20 transition shadow-inner">
                        </div>

                        <!-- TOMBOL SUBMIT UTAMA (1 KLIK LANGSUNG MASUK ANTREAN) -->
                        <div class="pt-2">
                            <button type="button"
                                    @click="submitSong()"
                                    :disabled="submitting || (!isOwner && selectedSong && (selectedSong.is_valid === false || selectedSong.is_valid_duration === false || selectedSong.is_live || selectedSong.is_nsfw))"
                                    class="w-full py-3.5 font-mono text-xs uppercase tracking-widest font-bold rounded-xl transition shadow-md flex items-center justify-center gap-2 cursor-pointer active:scale-98"
                                    :class="!isOwner && selectedSong && (selectedSong.is_valid === false || selectedSong.is_valid_duration === false || selectedSong.is_live || selectedSong.is_nsfw)
                                        ? '!bg-red-600 !text-white hover:!bg-red-700 cursor-not-allowed opacity-90'
                                        : 'bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] disabled:opacity-50 disabled:cursor-not-allowed'">
                                <span x-show="submitting" class="animate-spin text-sm">⟳</span>
                                <span x-show="submitting">Memproses Request...</span>
                                <span x-show="!submitting && !isOwner && selectedSong && (selectedSong.is_valid === false || selectedSong.is_valid_duration === false || selectedSong.is_live || selectedSong.is_nsfw)">
                                    🚫 Tautan Melanggar Aturan Kafe (Pilih Lagu Lain)
                                </span>
                                <template x-if="!submitting && (isOwner || !selectedSong || (selectedSong.is_valid && selectedSong.is_valid_duration && !selectedSong.is_live && !selectedSong.is_nsfw))">
                                    <span>
                                        <span x-show="isOwner">👑 Masukkan ke Antrean (Akses VIP Owner) ›</span>
                                        <span x-show="isTest">🧪 Kirim Request Musik (Mode Testing) ›</span>
                                        <span x-show="!isOwner && !isTest">♫ Kirim Request ke Pemutar Kafe ›</span>
                                    </span>
                                </template>
                            </button>
                            <div class="text-center font-mono text-[10px] mt-2 transition"
                                 :class="!isOwner && selectedSong && (selectedSong.is_valid === false || selectedSong.is_valid_duration === false || selectedSong.is_live || selectedSong.is_nsfw) ? 'text-red-600 font-bold' : 'text-[#7A6A58]'"
                                 x-text="!isOwner && selectedSong && (selectedSong.is_valid === false || selectedSong.is_valid_duration === false || selectedSong.is_live || selectedSong.is_nsfw) ? '⚠️ Tombol dinonaktifkan karena tautan melanggar aturan kafe. Silakan tempel tautan lagu lain.' : (isOwner ? '👑 Akses VIP Owner aktif: Request lagu tanpa batas antrean.' : '* Lagu berputar otomatis bergiliran di speaker kafe segera setelah lagu saat ini selesai.')">
                            </div>
                        </div>
                    </div>
                </template>

                <!-- BANNER ATURAN & ETIKA REQUEST MUSIK KAFE -->
                <section class="bg-[#FAF7F2] border border-[#E5DDD0] rounded-2xl p-4 sm:p-5 shadow-xs text-[#1F1812]">
                    <div class="flex items-center gap-2 mb-2.5 pb-2 border-b border-[#EAE2D5]">
                        <span class="text-base">📜</span>
                        <span class="font-mono text-xs uppercase tracking-wider font-bold text-[#1F1812]">
                            Aturan & Etika Request Musik Kafe
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-[#5C4D3C]">
                        <div class="flex items-start gap-2">
                            <span class="text-[#D9973E] font-bold shrink-0">⏱️</span>
                            <div>
                                <b class="text-[#1F1812]">Maksimal Durasi 7 Menit & Anti Live Stream</b>
                                <p class="text-[11px] text-[#7A6A58] mt-0.5">
                                    Video panjang, kompilasi 1 jam, podcast, atau siaran langsung (live stream) otomatis ditolak sistem agar antrean berjalan adil.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-[#5F7F42] font-bold shrink-0">⚡</span>
                            <div>
                                <b class="text-[#1F1812]">Prioritas Utama & Putar Langsung</b>
                                <p class="text-[11px] text-[#7A6A58] mt-0.5">
                                    Lagu request pelanggan selalu diprioritaskan. Jika antrean request kosong, lagumu langsung diputar seketika dan lagu bawaan kafe otomatis di-pause.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-[#1F1812] font-bold shrink-0">🎟️</span>
                            <div>
                                <b class="text-[#1F1812]">1 Struk = 1 Lagu Favorit</b>
                                <p class="text-[11px] text-[#7A6A58] mt-0.5">
                                    Setiap transaksi berhak atas 1x request lagu. Ingin request lagu lagi? Silakan nikmati pesanan berikutnya di kasir.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-red-600 font-bold shrink-0">🛡️</span>
                            <div>
                                <b class="text-[#1F1812]">Bebas Konten Dewasa & NSFW (18+)</b>
                                <p class="text-[11px] text-[#7A6A58] mt-0.5">
                                    Sistem memblokir video berkategori dewasa / NSFW / age-restricted demi kenyamanan bersama seluruh keluarga dan pengunjung kafe.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            <!-- GRID KANAN: LIVE STATION, STATUS PESANAN & ANTREAN (lg:col-span-5) -->
            <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-6">

                <!-- WIDGET NOW PLAYING LIVE DI KAFE (INTERACTIVE VINYL DISC) -->
                <section class="bg-gradient-to-r from-[#17110C] via-[#221811] to-[#17110C] text-[#F7F3EC] border border-[#3A2C20] rounded-2xl p-4 sm:p-5 shadow-xl relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-36 h-36 bg-[#D9973E]/15 rounded-full blur-3xl pointer-events-none"></div>

                    <!-- Top bar widget -->
                    <div class="flex items-center justify-between border-b border-white/10 pb-3 mb-3.5">
                        <div class="flex items-center gap-2">
                            <!-- Dynamic equalizer wave -->
                            <div class="flex items-end gap-0.5 h-3.5 w-3.5 shrink-0">
                                <span class="w-0.5 bg-[#5F7F42] rounded-full" :class="isPlaying ? 'animate-eq-1' : 'h-1.5 opacity-40'"></span>
                                <span class="w-0.5 bg-[#D9973E] rounded-full" :class="isPlaying ? 'animate-eq-2' : 'h-2 opacity-40'"></span>
                                <span class="w-0.5 bg-[#5F7F42] rounded-full" :class="isPlaying ? 'animate-eq-3' : 'h-1 opacity-40'"></span>
                                <span class="w-0.5 bg-[#D9973E] rounded-full" :class="isPlaying ? 'animate-eq-4' : 'h-2.5 opacity-40'"></span>
                            </div>
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#5F7F42] font-bold">
                                Sedang Diputar di Kafe
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <template x-if="nowPlaying && nowPlaying.type === 'customer_request'">
                                <span class="font-mono text-[9px] uppercase tracking-wider px-2 py-0.5 bg-[#D9973E]/20 text-[#D9973E] border border-[#D9973E]/40 rounded-full font-semibold">
                                    ★ Request Pengunjung
                                </span>
                            </template>
                            <span class="font-mono text-[10px] text-[#A89A85] bg-black/40 px-2 py-0.5 rounded border border-white/5"
                                  x-text="queueCount + ' Lagu di Antrean'"></span>
                        </div>
                    </div>

                    <!-- Main track info & vinyl disc -->
                    <div class="flex items-center gap-4">
                        <!-- Mini Spinning Vinyl Disc -->
                        <div class="relative w-16 h-16 sm:w-20 sm:h-20 shrink-0">
                            <div class="w-full h-full rounded-full bg-[#120D09] border-2 border-[#3A2C20] shadow-lg flex items-center justify-center relative overflow-hidden"
                                 :class="isPlaying ? 'animate-spin-vinyl' : ''">
                                <div class="w-full h-full rounded-full border border-dashed border-[#443527] flex items-center justify-center p-2 sm:p-2.5">
                                    <div class="w-full h-full rounded-full border border-[#D9973E]/40 overflow-hidden bg-[#1E1610] flex items-center justify-center">
                                        <template x-if="nowPlaying && nowPlaying.thumbnail_url">
                                            <img :src="nowPlaying.thumbnail_url" alt="Cover" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!nowPlaying || !nowPlaying.thumbnail_url">
                                            <span class="text-sm font-serif text-[#D9973E]">☕</span>
                                        </template>
                                    </div>
                                </div>
                                <div class="absolute inset-0 m-auto w-3 h-3 rounded-full bg-[#D9973E] border border-[#140E0A]"></div>
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm sm:text-base font-bold text-[#F7F3EC] truncate leading-tight font-serif drop-shadow-xs"
                                x-text="nowPlaying ? (nowPlaying.song_title || nowPlaying.title) : 'Memuat Musik Kafe...'">
                            </h3>
                            <div class="text-xs text-[#A89A85] truncate mt-0.5 font-mono"
                                 x-text="nowPlaying ? (nowPlaying.artist || 'Artis Musik') : '{{ config('cafe.name') }} Cafe Vibe'">
                            </div>

                            <template x-if="nowPlaying && nowPlaying.customer_name">
                                <div class="mt-1 text-[10px] font-mono text-[#D9973E] flex items-center gap-1 font-semibold truncate">
                                    <span>★ Diminta oleh:</span>
                                    <span class="text-white" x-text="nowPlaying.customer_name"></span>
                                </div>
                            </template>

                            <!-- Synchronized timeline progress -->
                            <div class="mt-2.5">
                                <div class="w-full bg-[#140E0A] h-2 rounded-full overflow-hidden border border-white/10">
                                    <div class="bg-gradient-to-r from-[#D9973E] to-[#5F7F42] h-full transition-all duration-300 rounded-full"
                                         :style="'width: ' + playbackProgressPercent + '%'"></div>
                                </div>
                                <div class="mt-1 flex items-center justify-between font-mono text-[9px] text-[#A89A85]">
                                    <span class="text-[#D9973E] font-bold" x-text="playbackCurrentTimeFormatted">00:00</span>
                                    <span class="text-[8px] text-[#7A6A58] uppercase tracking-wider">// Live Cafe Sync</span>
                                    <span x-text="playbackDurationFormatted">00:00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- KARTU STATUS PERSIAPAN PESANAN PELANGGAN (KITCHEN & BARISTA LIVE TRACKER) -->
                <template x-if="orderInfo">
                    <div class="p-4 sm:p-5 border-2 rounded-2xl shadow-md transition"
                         :class="{
                            'bg-[#5F7F42]/10 border-[#5F7F42] text-[#1F1812]': orderInfo.prep_status === 'ready',
                            'bg-yellow-50 border-[#D9973E] text-[#1F1812]': orderInfo.prep_status === 'preparing',
                            'bg-blue-50 border-blue-300 text-[#1F1812]': orderInfo.prep_status === 'pending',
                            'bg-gray-50 border-gray-300 text-gray-700': orderInfo.prep_status === 'completed'
                         }">
                        <div class="flex items-center justify-between border-b pb-2.5 mb-3"
                             :class="orderInfo.prep_status === 'ready' ? 'border-[#5F7F42]/30' : 'border-[#E0D8CC]'">
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-bold"
                                  :class="{
                                    'text-[#5F7F42]': orderInfo.prep_status === 'ready',
                                    'text-[#D9973E]': orderInfo.prep_status === 'preparing',
                                    'text-blue-600': orderInfo.prep_status === 'pending',
                                    'text-gray-500': orderInfo.prep_status === 'completed'
                                  }">
                                STATUS PESANAN DI KASIR & DAPUR
                            </span>
                            <span class="font-mono text-xs font-bold px-2 py-0.5 bg-white/80 border rounded"
                                  x-text="'No. ' + orderInfo.code"></span>
                        </div>

                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-full flex items-center justify-center font-bold text-lg shrink-0 shadow-sm"
                                 :class="{
                                    'bg-[#5F7F42] text-white animate-bounce': orderInfo.prep_status === 'ready',
                                    'bg-[#D9973E] text-white animate-spin-vinyl': orderInfo.prep_status === 'preparing',
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
                                            PESANAN SUDAH SIAP DIAMBIL!
                                        </div>
                                        <div class="text-xs text-[#1F1812] mt-0.5 font-medium">
                                            Silakan ambil pesanan Anda di <b>Meja Kasir</b> sekarang. Selamat menikmati!
                                        </div>
                                    </div>
                                </template>

                                <template x-if="orderInfo.prep_status === 'preparing'">
                                    <div>
                                        <div class="font-serif font-bold text-sm text-[#D9973E] leading-tight">
                                            Sedang Diracik & Dimasak...
                                        </div>
                                        <div class="text-xs text-[#5C4D3C] mt-0.5">
                                            Barista & tim dapur sedang menyiapkan hidangan kopi Anda dengan penuh cita rasa.
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
                                            Terima kasih telah berkunjung ke {{ config('cafe.name') }}!
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ANTREAN LAGU KAFE SAAT INI (PREVIEW LIST) -->
                <section class="bg-white border border-[#E5DDD0] rounded-2xl p-5 sm:p-6 shadow-md">
                    <div class="flex items-center justify-between border-b border-[#E5DDD0] pb-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base text-[#D9973E]">📋</span>
                            <span class="font-mono text-xs uppercase tracking-widest text-[#1F1812] font-bold">
                                Antrean Request Berikutnya
                            </span>
                        </div>
                        <span class="font-mono text-xs font-bold text-[#D9973E] bg-[#D9973E]/10 px-2 py-0.5 rounded-full border border-[#D9973E]/30"
                              x-text="queue.length + ' lagu menunggu'"></span>
                    </div>

                    <!-- LIVE QUEUE PRIORITY BADGE -->
                    <div class="mb-3 p-2.5 rounded-xl text-xs font-mono flex items-center gap-2 border shadow-xs"
                         :class="(queue.filter(i => i.type === 'request' || i.is_request).length === 0)
                             ? 'bg-[#5F7F42]/10 border-[#5F7F42]/30 text-[#3D5A24]'
                             : 'bg-[#D9973E]/10 border-[#D9973E]/30 text-[#9E641B]'">
                        <span class="text-base shrink-0" x-text="(queue.filter(i => i.type === 'request' || i.is_request).length === 0) ? '✨' : '⚡'"></span>
                        <div class="min-w-0 flex-1 leading-tight text-[11px]">
                            <template x-if="queue.filter(i => i.type === 'request' || i.is_request).length === 0">
                                <span><b>Antrean request kosong!</b> Request lagumu sekarang & akan <b>langsung diputar seketika</b> (lagu bawaan di-pause).</span>
                            </template>
                            <template x-if="queue.filter(i => i.type === 'request' || i.is_request).length > 0">
                                <span>Lagu request pengunjung <b>diprioritaskan</b> sebelum playlist bawaan kafe diputar kembali.</span>
                            </template>
                        </div>
                    </div>

                    <template x-if="queue.length === 0">
                        <div class="text-center py-6 text-xs text-[#7A6A58] font-mono space-y-1">
                            <div class="text-2xl opacity-40">☕</div>
                            <div class="font-semibold text-[#1F1812]">Antrean request sedang kosong.</div>
                            <div class="text-[11px]">Request lagumu sekarang dan jadilah yang pertama diputar berikutnya!</div>
                        </div>
                    </template>

                    <template x-if="queue.length > 0">
                        <div class="space-y-2 max-h-[380px] overflow-y-auto pr-1">
                            <template x-for="(item, index) in queue" :key="item.id + '_' + (item.type || 'req')">
                                <div class="flex items-center justify-between p-3 rounded-xl text-xs transition"
                                     :class="(mySong && item.id === mySong.id && (item.type === 'request' || item.is_request))
                                        ? 'bg-[#5F7F42]/10 border-2 border-[#5F7F42] shadow-xs'
                                        : 'bg-[#FAF7F2] border border-[#EAE2D5] hover:border-[#D9973E]/50'">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <span class="font-mono font-bold w-6 text-center shrink-0"
                                              :class="(mySong && item.id === mySong.id && (item.type === 'request' || item.is_request)) ? 'text-[#5F7F42]' : 'text-[#D9973E]'"
                                              x-text="'#' + (index + 1)"></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-medium text-[#1F1812] truncate font-serif" x-text="item.song_title || item.title"></div>
                                            <div class="text-[10px] text-[#7A6A58] truncate font-mono mt-0.5 flex items-center gap-1.5">
                                                <span x-text="item.artist || 'Artis YouTube'"></span>
                                                <template x-if="item.customer_name">
                                                    <span class="flex items-center gap-1">
                                                        <span>&bull;</span>
                                                        <span class="text-[#D9973E]" x-text="'Req: ' + item.customer_name"></span>
                                                    </span>
                                                </template>
                                                <template x-if="!item.customer_name && (item.type === 'default' || !item.is_request)">
                                                    <span class="flex items-center gap-1">
                                                        <span>&bull;</span>
                                                        <span class="text-[#5F7F42]">Playlist Kafe</span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <template x-if="mySong && item.id === mySong.id && (item.type === 'request' || item.is_request)">
                                        <span class="font-mono text-[9px] uppercase tracking-wider text-white bg-[#5F7F42] px-2 py-0.5 rounded-full font-bold shrink-0 ml-2 animate-pulse shadow-xs">
                                            ✨ Lagu Kamu
                                        </span>
                                    </template>
                                    <template x-if="!(mySong && item.id === mySong.id && (item.type === 'request' || item.is_request))">
                                        <div>
                                            <template x-if="item.type === 'request' || item.is_request">
                                                <span class="font-mono text-[9px] uppercase tracking-wider text-[#D9973E] bg-[#D9973E]/10 px-2 py-0.5 border border-[#D9973E]/25 rounded shrink-0 font-bold ml-2">
                                                    ★ Request
                                                </span>
                                            </template>
                                            <template x-if="item.type === 'default' || !item.is_request">
                                                <span class="font-mono text-[9px] uppercase tracking-wider text-[#5F7F42] bg-[#5F7F42]/10 px-2 py-0.5 border border-[#5F7F42]/25 rounded shrink-0 font-bold ml-2">
                                                    🎵 Bawaan
                                                </span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </section>

                <!-- SHORTCUT KE DISPLAY TV -->
                <div class="p-4 bg-[#FAF7F2] border border-[#E5DDD0] rounded-2xl flex items-center justify-between text-xs text-[#1F1812] shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">📺</span>
                        <div>
                            <div class="font-bold font-serif">Layar Display TV Kafe</div>
                            <div class="text-[11px] text-[#7A6A58]">Lihat tampilan monitor musik live kafe</div>
                        </div>
                    </div>
                    <a href="{{ route('music.display') }}" target="_blank"
                       class="px-3.5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-[11px] uppercase tracking-wider font-bold rounded-xl transition shadow-xs">
                        Buka TV ›
                    </a>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection
