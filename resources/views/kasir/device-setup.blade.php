@extends('kasir.app')

@section('title', 'Otorisasi Perangkat & Keamanan POS — ' . config('cafe.name'))

@section('content')
<div class="w-full min-h-full flex flex-col bg-[#FAF7F2]"
     x-data="deviceSetupPage()">

    <!-- ===================================================================
         TOPBAR COMMAND CENTER: DARK ROASTED ESPRESSO STRIP
         =================================================================== -->
    <header class="w-full px-4 sm:px-6 py-3.5 border-b border-[#3A3026] bg-[#1A130D] text-[#F7F3EC] flex flex-wrap items-center justify-between gap-4 shrink-0 select-none shadow-md">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#D9973E] text-[#1F1812] flex items-center justify-center font-bold text-lg shadow-[0_0_12px_rgba(217,151,62,0.35)] shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base sm:text-lg font-serif font-bold tracking-tight text-[#F7F3EC]">
                        Otorisasi Perangkat & Jaringan
                    </h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-emerald-950/80 text-emerald-300 border border-emerald-800/60 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Sistem Aktif</span>
                    </span>
                </div>
                <div class="flex items-center gap-2 text-[11px] font-mono text-[#A89A85]">
                    <a href="{{ route('kasir.terminal') }}" class="hover:text-[#D9973E] transition-colors">POS</a>
                    <span>/</span>
                    <span class="text-[#D9973E]">Perangkat & Jaringan</span>
                    <span>•</span>
                    <span>IP Anda: <code class="text-[#F7F3EC] font-bold">{{ $clientIp }}</code></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            @if ($isDeviceAuthorized)
                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-mono font-bold uppercase tracking-wider bg-[#261D16] text-emerald-400 border border-emerald-800/40">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Perangkat Ini Resmi</span>
                </span>
            @endif

            <a href="{{ route('kasir.terminal') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#2A2016] hover:bg-[#D9973E] hover:text-[#1F1812] text-[#F7F3EC] text-xs font-mono font-bold uppercase tracking-wider transition-all border border-[#3A2D22] shadow-xs active:scale-95">
                <span>←</span>
                <span>Kembali ke POS</span>
            </a>
        </div>
    </header>

    <!-- Flash Alerts -->
    @if (session('status'))
        <div class="mx-4 sm:mx-6 mt-4 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between gap-3 shadow-2xs shrink-0">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shrink-0">✓</span>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 font-bold text-sm cursor-pointer">&times;</button>
        </div>
    @endif

    @if (session('error'))
        <div class="mx-4 sm:mx-6 mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-center justify-between gap-3 shadow-2xs shrink-0">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs font-bold shrink-0">!</span>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-700 hover:text-rose-900 font-bold text-sm cursor-pointer">&times;</button>
        </div>
    @endif

    <!-- ===================================================================
         MAIN CONTENT BODY (CLEAN FLOW)
         =================================================================== -->
    <div class="p-4 sm:p-6 space-y-6 w-full">

        <!-- ===============================================================
             1. BARIS METRIK KPI PERANGKAT (4 KOLOM DI DESKTOP, FULL WIDTH)
             =============================================================== -->
        <div class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Card 1: Total Perangkat -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 shadow-[0_4px_20px_rgba(42,33,26,0.03)] flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-mono uppercase tracking-wider text-[#8A7B66] font-bold">Total Armada</div>
                    <div class="font-serif text-2xl font-bold text-[#1F1812] mt-0.5" x-text="totalFleetCount">{{ $devices->count() }}</div>
                    <div class="text-[10px] font-mono text-[#8A7B66] mt-0.5">Perangkat Terdaftar</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-[#FAF7F2] border border-[#E8E1D5] flex items-center justify-center text-stone-700 shadow-2xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>

            <!-- Card 2: Perangkat Aktif -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 shadow-[0_4px_20px_rgba(42,33,26,0.03)] flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-mono uppercase tracking-wider text-emerald-700 font-bold">Izin Aktif</div>
                    <div class="font-serif text-2xl font-bold text-emerald-700 mt-0.5" x-text="activeFleetCount">{{ $devices->where('is_revoked', false)->count() }}</div>
                    <div class="text-[10px] font-mono text-emerald-600 mt-0.5">Siap Transaksi POS</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center shadow-2xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Card 3: Akses Dicabut -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 shadow-[0_4px_20px_rgba(42,33,26,0.03)] flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-mono uppercase tracking-wider text-rose-700 font-bold">Akses Dicabut</div>
                    <div class="font-serif text-2xl font-bold text-rose-700 mt-0.5" x-text="revokedFleetCount">{{ $devices->where('is_revoked', true)->count() }}</div>
                    <div class="text-[10px] font-mono text-rose-600 mt-0.5">Diblokir Jarak Jauh</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center justify-center shadow-2xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>

            <!-- Card 4: Perangkat Anda Saat Ini -->
            <div class="bg-gradient-to-br from-[#1F1812] to-[#2E2319] text-white rounded-2xl p-4 shadow-sm flex items-center justify-between">
                <div class="min-w-0 flex-1 pr-2">
                    <div class="text-[10px] font-mono uppercase tracking-wider text-amber-300 font-bold">Perangkat Ini</div>
                    <div class="font-bold text-sm text-white truncate mt-0.5">
                        {{ $currentDevice ? $currentDevice->device_name : ($isDeviceAuthorized ? 'Token Legacy Aktif' : 'Tanpa Token') }}
                    </div>
                    <div class="text-[10px] font-mono text-stone-300 mt-0.5 truncate">
                        IP: {{ $clientIp }}
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 text-amber-300 flex items-center justify-center shadow-2xs shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>

        </div>

        <!-- ===============================================================
             2. BAGIAN TENGAH (2 KOLOM): REGISTRASI QR & PENGATURAN KUNCI
             =============================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 w-full">

            <!-- KOLOM KIRI (7 Kolom di Desktop): REGISTRASI TABLET BARU / SCAN QR -->
            <div class="lg:col-span-7 w-full">
                <div class="bg-white border border-[#E4DCCC] rounded-3xl p-6 sm:p-7 shadow-[0_4px_20px_rgba(42,33,26,0.03)] relative overflow-hidden h-full flex flex-col justify-between">
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 rounded-full border border-[#D9973E]/10 pointer-events-none"></div>

                    <div class="space-y-5">
                        <!-- Card Header -->
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-[#E8E1D5]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-[#1F1812] text-amber-400 flex items-center justify-center shadow-xs shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="font-serif text-lg sm:text-xl font-bold text-[#1F1812]">
                                        Daftarkan Tablet Kasir Baru
                                    </h2>
                                    <p class="text-xs text-[#8A7B66] font-mono">Scan kamera atau gunakan tautan pendaftaran</p>
                                </div>
                            </div>

                            <!-- Badge Status QR -->
                            <div class="flex items-center gap-2">
                                <template x-if="enrollmentStatus === 'active'">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-amber-50 text-amber-900 border border-amber-300 shadow-2xs shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>Sekali Pakai (15 Menit)</span>
                                    </span>
                                </template>
                                <template x-if="enrollmentStatus === 'consumed'">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-rose-50 text-rose-800 border border-rose-300 shadow-2xs shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                        <span>QR Sudah Terpakai</span>
                                    </span>
                                </template>
                                <template x-if="enrollmentStatus === 'none'">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-stone-100 text-stone-600 border border-stone-300 shadow-2xs shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>
                                        <span>Belum Ada QR</span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Alert Khusus: Notasi jika QR Sudah Terpakai -->
                        <div x-show="enrollmentStatus === 'consumed'" x-cloak class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-950 flex items-start gap-2.5 shadow-2xs">
                            <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <strong class="text-rose-900">Kode QR Sebelumnya Sudah Terpakai:</strong>
                                <span class="text-rose-800 block mt-0.5">
                                    Kode QR otorisasi telah digunakan untuk mendaftarkan perangkat <strong x-text="consumedDevice ? `'${consumedDevice}'` : 'baru'"></strong> dan langsung dinonaktifkan demi keamanan.
                                    Sistem <strong>tidak melakukan auto-regenerate</strong>. Silakan klik tombol <strong>'Buat QR Baru'</strong> di bawah ini jika ingin mendaftarkan perangkat kasir lain.
                                </span>
                            </div>
                        </div>

                        <!-- Alert Khusus: Belum Ada QR -->
                        <div x-show="enrollmentStatus === 'none'" x-cloak class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-950 flex items-start gap-2.5 shadow-2xs">
                            <svg class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <strong class="text-amber-900">Belum Ada Kode QR Aktif:</strong>
                                <span class="text-amber-800 block mt-0.5">
                                    Untuk mencegah pendaftaran liar, sistem tidak membuat QR secara otomatis. Klik tombol <strong>'Buat QR Baru'</strong> untuk membuat kode otorisasi sekali pakai saat Anda siap mendaftarkan perangkat.
                                </span>
                            </div>
                        </div>

                        <!-- QR Code & Instructions Grid -->
                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                            
                            <!-- QR Frame -->
                            <div class="bg-[#FAF7F2] p-3.5 rounded-2xl border border-[#E4DCCC] shadow-xs shrink-0 flex flex-col items-center">
                                
                                <!-- Frame Aktif (dengan Sensor Blur Default) -->
                                <template x-if="enrollmentStatus === 'active' && qrCodeUri">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-white p-2.5 rounded-xl border border-[#E8E1D5] shadow-2xs relative overflow-hidden group">
                                            <img :src="qrCodeUri" alt="QR Code Otorisasi Kasir"
                                                 :class="{'filter blur-md select-none transition-all duration-300': !showQr}"
                                                 class="w-40 h-40 object-contain rounded-lg">

                                            <!-- Sensor Overlay (Saat QR Tertutup / Blur) -->
                                            <div x-show="!showQr && !isGeneratingQr"
                                                 @click="showQr = true"
                                                 class="absolute inset-0 bg-stone-900/60 backdrop-blur-xs flex flex-col items-center justify-center p-3 text-center cursor-pointer transition-all hover:bg-stone-900/70">
                                                <div class="w-8 h-8 rounded-full bg-stone-800/80 border border-stone-600/50 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform shadow-xs">
                                                    <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                                    </svg>
                                                </div>
                                                <span class="text-[11px] font-mono font-bold text-amber-300">QR Disensor</span>
                                                <span class="text-[9px] font-sans text-stone-200 mt-0.5">Klik untuk melihat</span>
                                            </div>

                                            <!-- Loading State -->
                                            <div x-show="isGeneratingQr" x-cloak class="absolute inset-0 bg-white/85 backdrop-blur-xs flex items-center justify-center rounded-xl">
                                                <span class="text-xs font-mono text-[#8A7B66] font-bold animate-pulse">Membuat QR...</span>
                                            </div>
                                        </div>

                                        <!-- Tombol Interaktif Buka / Tutup Sensor -->
                                        <button type="button" @click="showQr = !showQr"
                                                class="mt-2 px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold text-[#B5762A] hover:text-[#1F1812] hover:bg-[#EFE9DE] transition-all cursor-pointer">
                                            <span x-text="showQr ? 'Sembunyikan QR' : 'Tampilkan QR'"></span>
                                        </button>
                                    </div>
                                </template>

                                <!-- Frame Status: Sudah Terpakai -->
                                <template x-if="enrollmentStatus === 'consumed'">
                                    <div class="w-44 h-44 rounded-xl bg-rose-50 border-2 border-dashed border-rose-300 flex flex-col items-center justify-center p-3 text-center relative overflow-hidden">
                                        <div class="w-10 h-10 rounded-2xl bg-rose-100 border border-rose-200 text-rose-700 flex items-center justify-center mb-1.5 shadow-2xs">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                        </div>
                                        <span class="text-[11px] font-mono font-bold text-rose-700 uppercase tracking-wide">Sudah Terpakai</span>
                                        <span class="text-[10px] text-stone-600 font-sans mt-1 leading-tight" x-text="consumedDevice ? `Perangkat: ${consumedDevice}` : 'Telah digunakan pendaftaran'"></span>
                                        <span class="text-[9px] text-rose-600 font-mono font-bold mt-1.5 bg-rose-100 px-2 py-0.5 rounded">Hangus</span>
                                    </div>
                                </template>

                                <!-- Frame Status: Belum Ada QR -->
                                <template x-if="enrollmentStatus === 'none'">
                                    <div class="w-44 h-44 rounded-xl bg-stone-100/80 border-2 border-dashed border-stone-300 flex flex-col items-center justify-center p-3 text-center">
                                        <div class="w-10 h-10 rounded-2xl bg-stone-200/70 border border-stone-300 text-stone-600 flex items-center justify-center mb-1.5 shadow-2xs">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                            </svg>
                                        </div>
                                        <span class="text-[11px] font-mono font-bold text-stone-600 uppercase tracking-wide">Belum Ada QR</span>
                                        <span class="text-[9px] text-stone-500 font-sans mt-1 leading-tight">Tekan tombol di bawah untuk membuat QR otorisasi</span>
                                    </div>
                                </template>

                                <!-- Baris Tombol Aksi Manual -->
                                <div class="mt-2.5 flex items-center gap-1.5 w-full">
                                    <button type="button" @click="generateNewQr()" :disabled="isGeneratingQr"
                                            title="Buat QR Sekali Pakai Baru Secara Manual"
                                            :class="enrollmentStatus === 'consumed' ? 'bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] hover:text-white font-bold' : 'bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] hover:text-white'"
                                            class="flex-1 px-3 py-2 border border-[#B5762A]/40 text-[11px] font-mono font-bold rounded-xl transition-all shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                                        <svg class="w-3.5 h-3.5 shrink-0" :class="{'animate-spin': isGeneratingQr}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        <span x-text="isGeneratingQr ? 'Memproses...' : (enrollmentStatus === 'consumed' ? 'Buat QR Baru' : 'Buat QR Baru')">Buat QR Baru</span>
                                    </button>
                                    <button type="button" @click="printQr()" :disabled="enrollmentStatus !== 'active' || !qrCodeUri"
                                            title="Cetak Lembar QR"
                                            class="px-3 py-2 bg-white hover:bg-stone-100 text-[#1F1812] border border-[#E4DCCC] text-[11px] font-mono font-bold rounded-xl transition-all shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                                        <svg class="w-3.5 h-3.5 text-stone-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        <span>Cetak</span>
                                    </button>
                                </div>
                                <div class="mt-2 text-[10px] font-mono text-[#8A7B66] text-center">
                                    Hangus seketika setelah di-scan
                                </div>
                            </div>

                            <!-- Step Guide -->
                            <div class="flex-1 space-y-2.5 text-left w-full">
                                <h3 class="font-serif text-sm font-bold text-[#1F1812]">
                                    Cara Menghubungkan Tablet Kasir:
                                </h3>

                                <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-[#FAF7F2] border border-[#E8E1D5]">
                                    <span class="w-5 h-5 rounded-lg bg-[#1F1812] text-white flex items-center justify-center font-mono text-[10px] font-bold shrink-0">1</span>
                                    <div class="text-[11px] text-[#2A211A] leading-tight">
                                        Klik <strong>'Tampilkan QR'</strong> untuk membuka sensor privasi.
                                    </div>
                                </div>

                                <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-[#FAF7F2] border border-[#E8E1D5]">
                                    <span class="w-5 h-5 rounded-lg bg-[#1F1812] text-white flex items-center justify-center font-mono text-[10px] font-bold shrink-0">2</span>
                                    <div class="text-[11px] text-[#2A211A] leading-tight">
                                        Buka kamera tablet kasir, arahkan ke QR dan klik tautan pendaftaran.
                                    </div>
                                </div>

                                <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-[#FAF7F2] border border-[#E8E1D5]">
                                    <span class="w-5 h-5 rounded-lg bg-[#B5762A] text-white flex items-center justify-center font-mono text-[10px] font-bold shrink-0">3</span>
                                    <div class="text-[11px] text-[#2A211A] leading-tight">
                                        Perangkat terdaftar resmi (1 tahun). <strong>QR langsung berubah 'Sudah Terpakai' dan hangus seketika</strong> tanpa auto-regenerate.
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Tautan Alternatif (WhatsApp) -->
                    <div class="mt-5 pt-4 border-t border-[#E8E1D5]">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] font-bold">
                                Tautan Otorisasi Sekali Pakai:
                            </label>
                            <span x-show="copied" x-cloak class="text-xs font-mono text-emerald-600 font-bold">
                                ✓ Tautan berhasil disalin!
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly
                                   :value="enrollmentStatus === 'active' ? authorizeUrl : (enrollmentStatus === 'consumed' ? '[Kode QR Sudah Hangus / Terpakai]' : '[Belum ada QR aktif - Buat QR Baru]')"
                                   class="flex-1 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3 py-2 text-xs font-mono text-[#1F1812] select-all focus:outline-none shadow-2xs"
                                   :class="{'text-stone-400 italic': enrollmentStatus !== 'active'}">
                            <button type="button" @click="copyLink(authorizeUrl)" :disabled="enrollmentStatus !== 'active' || !authorizeUrl"
                                    class="px-3.5 py-2 bg-[#B5762A] hover:bg-[#1F1812] text-white font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all shadow-2xs active:scale-95 cursor-pointer shrink-0 disabled:opacity-40 disabled:cursor-not-allowed">
                                <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                            </button>
                        </div>
                        <div class="text-[10px] font-mono text-[#8A7B66] mt-1.5">
                            Tautan ini mengandung token sekali pakai yang berlaku 15 menit.
                        </div>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN (5 Kolom di Desktop): KUNCI RAHASIA & WHITELIST JARINGAN -->
            <div class="lg:col-span-5 space-y-6 w-full">

                <!-- Card Kunci Rahasia Keamanan (KASIR_DEVICE_SECRET) -->
                <div class="bg-white border border-[#E4DCCC] rounded-3xl p-5 sm:p-6 shadow-[0_4px_20px_rgba(42,33,26,0.03)]">
                    <div class="flex items-center justify-between pb-3 border-b border-[#E8E1D5]">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-center justify-center text-sm shadow-2xs">
                                <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>
                            <h3 class="font-serif text-sm font-bold text-[#1F1812]">Kunci Rahasia Keamanan</h3>
                        </div>
                        <button type="button" @click="showSecret = !showSecret"
                                class="text-xs font-mono text-[#B5762A] hover:underline font-semibold cursor-pointer">
                            <span x-text="showSecret ? 'Sembunyikan' : 'Tampilkan'">Tampilkan</span>
                        </button>
                    </div>

                    <div class="mt-3 p-3 rounded-2xl bg-[#FAF7F2] border border-[#E4DCCC] space-y-2">
                        <div class="font-mono text-xs font-bold text-[#1F1812] tracking-wider truncate"
                              x-text="showSecret ? '{{ $secret }}' : '••••••••••••••••••••••••••••••••••••'">
                            ••••••••••••••••••••••••••••••••••••
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-1 border-t border-[#E8E1D5]">
                            <button type="button" @click="copyLink('{{ $secret }}')"
                                    class="px-2.5 py-1 bg-white hover:bg-stone-100 text-[#1F1812] border border-[#E4DCCC] text-[11px] font-mono font-bold rounded-lg transition-all shadow-2xs cursor-pointer">
                                Salin
                            </button>
                            <button type="button" @click="showRegenerateModal = true"
                                    class="px-2.5 py-1 bg-amber-100 hover:bg-amber-200 text-amber-950 border border-amber-300 text-[11px] font-mono font-bold rounded-lg transition-all shadow-2xs flex items-center gap-1 cursor-pointer">
                                <svg class="w-3 h-3 text-amber-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span>Regenerate</span>
                            </button>
                        </div>
                    </div>

                    <p class="mt-2 text-[11px] text-[#6B5A4B] font-serif italic">
                        Kunci induk pembuatan QR. Jika dicurigai bocor, klik Regenerate untuk menggantinya.
                    </p>
                </div>

                <!-- Card Whitelist Subnet / IP Kafe -->
                <div class="bg-white border border-[#E4DCCC] rounded-3xl p-5 sm:p-6 shadow-[0_4px_20px_rgba(42,33,26,0.03)]">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-[#E8E1D5]">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-center text-sm shadow-2xs">
                            <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                        </div>
                        <h3 class="font-serif text-sm font-bold text-[#1F1812]">Whitelist Jaringan Kafe</h3>
                    </div>

                    <div class="mt-3 space-y-2">
                        <p class="text-[11px] text-[#6B5A4B] font-serif">
                            IP yang diizinkan mengakses kasir langsung tanpa memerlukan token:
                        </p>

                        <div class="flex flex-wrap gap-1.5 pt-0.5">
                            @forelse ($allowedIps as $ipRule)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg font-mono text-[11px] font-bold bg-[#FAF7F2] border border-[#E4DCCC] text-[#1F1812]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>{{ $ipRule }}</span>
                                </span>
                            @empty
                                <span class="text-xs text-stone-400 font-mono">Semua IP dibatasi</span>
                            @endforelse
                        </div>

                        @if ($isDeviceAuthorized)
                            <div class="pt-3 mt-3 border-t border-[#E8E1D5] flex items-center justify-between gap-2">
                                <span class="text-[11px] font-mono text-[#8A7B66]">Otorisasi Browser Ini:</span>
                                <form method="POST" action="{{ route('kasir.device-revoke') }}"
                                      @submit.prevent="confirmRevokeSelf($el)">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-mono text-[11px] font-bold rounded-lg transition-all shadow-2xs cursor-pointer active:scale-95">
                                        <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        <span>Cabut Token Ini</span>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>

        <!-- ===============================================================
             3. BAGIAN BAWAH (LEBAR PENUH): DAFTAR PERANGKAT KASIR TERDAFTAR (FULL WIDTH)
             =============================================================== -->
        <div class="w-full bg-white border border-[#E4DCCC] rounded-3xl shadow-[0_6px_25px_rgba(42,33,26,0.04)] overflow-hidden">
            
            <!-- Table Header & Filter Controls -->
            <div class="w-full p-5 sm:p-6 border-b border-[#E8E1D5] flex flex-col md:flex-row md:items-center justify-between gap-4 bg-[#FAF7F2]/50">
                <div class="flex items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-serif text-lg sm:text-xl font-bold text-[#1F1812]">
                                Daftar Perangkat Kasir Terdaftar
                            </h2>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Real-time Sync</span>
                            </span>
                        </div>
                        <p class="text-xs text-[#6B5A4B] font-serif italic mt-0.5">
                            Pantau setiap tablet kasir resmi dan cabut izin (*revoke*) perangkat lain dari jarak jauh secara mandiri.
                        </p>
                    </div>
                </div>

                <!-- Search and Status Filters -->
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <input type="text" x-model="searchQuery" placeholder="Cari nama atau IP..."
                               class="pl-8 pr-3 py-1.5 bg-white border border-[#E4DCCC] rounded-xl text-xs font-mono text-[#1F1812] placeholder-stone-400 focus:outline-none focus:border-[#B5762A] focus:ring-1 focus:ring-[#B5762A] w-44 sm:w-56 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-stone-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Status Filter Buttons -->
                    <div class="inline-flex p-0.5 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] text-xs font-mono">
                        <button type="button" @click="statusFilter = 'all'"
                                :class="statusFilter === 'all' ? 'bg-[#1F1812] text-white shadow-2xs' : 'text-[#6B5A4B] hover:text-[#1F1812]'"
                                class="px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer">
                            <span x-text="'Semua (' + totalFleetCount + ')'">Semua ({{ $devices->count() }})</span>
                        </button>
                        <button type="button" @click="statusFilter = 'active'"
                                :class="statusFilter === 'active' ? 'bg-emerald-700 text-white shadow-2xs' : 'text-[#6B5A4B] hover:text-[#1F1812]'"
                                class="px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer">
                            <span x-text="'Aktif (' + activeFleetCount + ')'">Aktif ({{ $devices->where('is_revoked', false)->count() }})</span>
                        </button>
                        <button type="button" @click="statusFilter = 'revoked'"
                                :class="statusFilter === 'revoked' ? 'bg-rose-700 text-white shadow-2xs' : 'text-[#6B5A4B] hover:text-[#1F1812]'"
                                class="px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer">
                            <span x-text="'Dicabut (' + revokedFleetCount + ')'">Dicabut ({{ $devices->where('is_revoked', true)->count() }})</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="totalFleetCount === 0" x-cloak class="py-16 px-6 text-center">
                <div class="w-16 h-16 rounded-3xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-stone-400 mx-auto mb-3 shadow-2xs">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-serif text-lg font-bold text-[#1F1812]">Belum Ada Perangkat Terdaftar</h3>
                <p class="text-xs sm:text-sm text-[#6B5A4B] font-serif italic max-w-md mx-auto mt-1 leading-relaxed">
                    Arahkan kamera tablet kasir ke kode QR di atas untuk mendaftarkan perangkat ke database secara otomatis.
                </p>
            </div>

            <!-- Filtered Empty State -->
            <div x-show="totalFleetCount > 0 && filteredDevices.length === 0" x-cloak class="py-12 px-6 text-center text-xs font-mono text-[#8A7B66]">
                Tidak ada perangkat yang cocok dengan kata kunci pencarian atau filter status.
            </div>

            <!-- Device Items List (Real-time x-for, Full Width) -->
            <div x-show="filteredDevices.length > 0" class="w-full divide-y divide-[#E8E1D5]">
                <template x-for="device in filteredDevices" :key="device.id">
                    <div x-transition:leave="transition ease-out duration-200"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="w-full p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors hover:bg-[#FAF7F2]/60"
                         :class="device.is_current ? 'bg-amber-50/30' : ''">
                        
                        <!-- Left: Device Details & Inline Rename -->
                        <div class="flex items-start sm:items-center gap-3.5 flex-1 min-w-0">
                            <div class="w-11 h-11 rounded-2xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center shrink-0 shadow-2xs mt-0.5 sm:mt-0 text-stone-600">
                                <template x-if="device.device_type === 'desktop'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </template>
                                <template x-if="device.device_type !== 'desktop'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </template>
                            </div>

                            <div class="min-w-0 flex-1">
                                
                                <!-- View Mode: Name & Badges -->
                                <div x-show="editingDeviceId !== device.id" class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-bold text-sm text-[#1F1812] truncate max-w-xs sm:max-w-sm md:max-w-md lg:max-w-xl"
                                        x-text="device.device_name"
                                        :title="device.device_name">
                                    </h4>

                                    <!-- Edit Name Button -->
                                    <button type="button" @click="startRename(device)"
                                            class="p-1 text-stone-400 hover:text-[#B5762A] hover:bg-stone-100 rounded text-xs transition-colors cursor-pointer"
                                            title="Ubah nama perangkat">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>

                                    <!-- Current Device Badge -->
                                    <template x-if="device.is_current">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-[#1F1812] text-amber-300 shadow-2xs">
                                            ★ Perangkat Ini
                                        </span>
                                    </template>

                                    <!-- Active / Revoked Badge (Reaktif via Alpine) -->
                                    <template x-if="!device.is_revoked">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                            <span>Aktif</span>
                                        </span>
                                    </template>
                                    <template x-if="device.is_revoked">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                            <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            <span>Akses Dicabut</span>
                                        </span>
                                    </template>
                                </div>

                                <!-- Edit Mode: Inline Form -->
                                <div x-show="editingDeviceId === device.id" x-cloak class="flex items-center gap-2 mt-1">
                                    <form @submit.prevent="submitRename(device)"
                                          class="flex items-center gap-2 flex-wrap">
                                        <input type="text" name="device_name" :id="'rename-input-' + device.id"
                                               x-model="editingDeviceName" required maxlength="100"
                                               class="bg-white border border-[#B5762A] rounded-xl px-3 py-1 text-xs font-mono text-[#1F1812] focus:outline-none focus:ring-1 focus:ring-[#B5762A] shadow-2xs">
                                        <button type="submit" class="px-3 py-1 bg-[#1F1812] hover:bg-[#B5762A] text-white text-[11px] font-mono font-bold rounded-xl transition-all cursor-pointer">
                                            Simpan
                                        </button>
                                        <button type="button" @click="cancelRename()" class="px-2.5 py-1 text-stone-500 hover:text-stone-800 text-[11px] font-mono cursor-pointer">
                                            Batal
                                        </button>
                                    </form>
                                </div>

                                <!-- Metadata Row: OS, Browser, IP, Waktu Aktif -->
                                <div class="flex items-center gap-2 sm:gap-3 text-[11px] text-[#8A7B66] font-mono mt-1.5 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.2 rounded border text-[10px] font-semibold"
                                          :class="getPlatformColor(device.platform)"
                                          x-text="(device.platform || 'Platform') + ' • ' + (device.browser || 'Browser')">
                                    </span>
                                    <span>•</span>
                                    <span>IP: <code class="text-[#1F1812] font-bold" x-text="device.ip_address || '-'"></code></span>
                                    <span>•</span>
                                    <span :title="'Aktivitas terakhir'" class="inline-flex items-center gap-1">
                                        <svg class="w-3 h-3 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span x-text="device.last_active_at || 'Belum aktif'"></span>
                                    </span>
                                    <span class="hidden lg:inline" x-text="'• Terdaftar: ' + (device.created_at_formatted || '-')"></span>
                                </div>

                            </div>
                        </div>

                        <!-- Right: Action Buttons (Reaktif, Custom Themed Modal & Toast) -->
                        <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                            <!-- Revoke Button -->
                            <button type="button"
                                    x-show="!device.is_revoked"
                                    @click="confirmRevoke(device)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-mono font-bold uppercase tracking-wider transition-all shadow-2xs cursor-pointer active:scale-95">
                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <span>Cabut Izin</span>
                            </button>

                            <!-- Restore Button -->
                            <button type="button"
                                    x-show="device.is_revoked"
                                    @click="confirmRestore(device)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-mono font-bold uppercase tracking-wider transition-all shadow-2xs cursor-pointer active:scale-95">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Aktifkan</span>
                            </button>

                            <!-- Delete Button -->
                            <button type="button"
                                    @click="confirmDelete(device)"
                                    class="p-2 rounded-xl text-stone-400 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition-all cursor-pointer active:scale-95"
                                    title="Hapus dari daftar riwayat">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>

                    </div>
                </template>
                </div>

        </div>

    </div>

    <!-- ===================================================================
         MODAL KONFIRMASI REGENERATE KUNCI RAHASIA
         =================================================================== -->
    <div x-show="showRegenerateModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity"
         @keydown.escape.window="showRegenerateModal = false">
        
        <div class="bg-white border border-[#E4DCCC] rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative overflow-hidden animate-fadeIn"
             @click.away="showRegenerateModal = false">
            
            <div class="flex items-center gap-3 pb-4 border-b border-[#E8E1D5]">
                <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-900 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-5 h-5 text-amber-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-serif text-lg font-bold text-[#1F1812]">Regenerate Kunci Rahasia</h3>
                    <p class="text-xs text-[#8A7B66] font-mono">Keamanan Terminal POS</p>
                </div>
            </div>

            <p class="mt-4 text-xs text-[#6B5A4B] font-serif leading-relaxed">
                Kunci rahasia baru akan digenerate secara acak. <b>QR Code dan tautan otorisasi lama otomatis tidak berlaku lagi</b> untuk mendaftarkan perangkat baru.
            </p>

            <form method="POST" action="{{ route('kasir.device-secret.regenerate') }}" class="mt-4 space-y-4">
                @csrf

                <!-- Checkbox Opsi Cabut Semua Perangkat dengan Pengecualian Eksekutor -->
                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="revoke_all_devices" value="1"
                               class="mt-0.5 rounded border-rose-300 text-rose-600 focus:ring-rose-500 cursor-pointer">
                        <div class="text-xs">
                            <span class="font-bold text-rose-900 block">Cabut juga izin SEMUA perangkat lain yang terdaftar saat ini</span>
                            <span class="text-rose-700 text-[11px] block mt-0.5 font-serif">
                                Centang ini jika kunci rahasia bocor dan Anda ingin memaksa seluruh tablet kasir lain scan QR baru. <b>Perangkat yang sedang Anda gunakan saat ini akan dikecualikan</b> sehingga tidak ter-logout.
                            </span>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" @click="showRegenerateModal = false"
                            class="px-4 py-2 text-xs font-mono font-bold text-stone-600 hover:text-stone-900 rounded-xl cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2.5 bg-[#1F1812] hover:bg-[#B5762A] text-white text-xs font-mono font-bold uppercase tracking-wider rounded-xl transition-all shadow-xs cursor-pointer active:scale-95">
                        Ya, Generate Kunci Baru
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

<script>
    function deviceSetupPage() {
        return {
            searchQuery: '',
            statusFilter: 'all', // 'all', 'active', 'revoked'
            copied: false,
            showSecret: false,
            showRegenerateModal: false,
            editingDeviceId: null,
            editingDeviceName: '',
            qrCodeUri: '{{ $qrCodeUri }}',
            authorizeUrl: '{{ $authorizeUrl }}',
            enrollmentStatus: '{{ $enrollmentStatus ?? 'none' }}', // 'active', 'consumed', 'none'
            consumedAt: '{{ $consumedAt ?? '' }}',
            consumedDevice: '{{ $consumedDevice ?? '' }}',
            showQr: false, // Default sensor blur untuk privasi & keamanan
            isGeneratingQr: false,
            isFetchingFleet: false,

            // Real-time Fleet Data State
            devices: {{ Js::from($initialDevices) }},
            _pollTimer: null,
            _broadcastChannel: null,

            init() {
                // Polling armada perangkat secara realtime setiap 3.5 detik
                this._pollTimer = setInterval(() => this.fetchFleetData(true), 3500);

                if (typeof BroadcastChannel !== 'undefined') {
                    try {
                        this._broadcastChannel = new BroadcastChannel('cafe_device_sync');
                        this._broadcastChannel.onmessage = (event) => {
                            if (event.data && event.data.type === 'DEVICE_UPDATE') {
                                this.fetchFleetData(false);
                            }
                        };
                    } catch (e) {}
                }

                const cleanupFn = () => {
                    if (this._pollTimer) {
                        clearInterval(this._pollTimer);
                        this._pollTimer = null;
                    }
                    if (this._broadcastChannel) {
                        this._broadcastChannel.close();
                        this._broadcastChannel = null;
                    }
                };

                window.addEventListener('kasir:page-leave', cleanupFn);
                if (typeof this.$cleanup === 'function') {
                    this.$cleanup(cleanupFn);
                }
            },

            get totalFleetCount() {
                return this.devices.length;
            },

            get activeFleetCount() {
                return this.devices.filter(d => !d.is_revoked).length;
            },

            get revokedFleetCount() {
                return this.devices.filter(d => d.is_revoked).length;
            },

            get filteredDevices() {
                return this.devices.filter(d => {
                    if (this.statusFilter === 'active' && d.is_revoked) return false;
                    if (this.statusFilter === 'revoked' && !d.is_revoked) return false;

                    if (!this.searchQuery.trim()) return true;

                    const q = this.searchQuery.toLowerCase();
                    const name = (d.device_name || '').toLowerCase();
                    const ip = (d.ip_address || '').toLowerCase();
                    const platform = (d.platform || '').toLowerCase();
                    return name.includes(q) || ip.includes(q) || platform.includes(q);
                });
            },

            getPlatformColor(platform) {
                const p = (platform || '').toLowerCase();
                if (p.includes('ios') || p.includes('ipados')) return 'text-slate-800 bg-slate-100 border-slate-200';
                if (p.includes('android')) return 'text-emerald-800 bg-emerald-50 border-emerald-200';
                if (p.includes('windows')) return 'text-sky-800 bg-sky-50 border-sky-200';
                if (p.includes('mac')) return 'text-purple-800 bg-purple-50 border-purple-200';
                return 'text-stone-800 bg-stone-100 border-stone-200';
            },

            async fetchFleetData(isSilent = true) {
                if (this.isFetchingFleet) return;
                this.isFetchingFleet = true;

                try {
                    const res = await fetch('{{ route('kasir.device-fleet.data') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) return;
                    const data = await res.json();

                    if (Array.isArray(data.devices)) {
                        // Notifikasi jika ada perangkat baru yang terdaftar via scan QR
                        if (this.devices.length > 0 && data.devices.length > this.devices.length) {
                            const currentIds = new Set(this.devices.map(d => d.id));
                            const newDevices = data.devices.filter(d => !currentIds.has(d.id));
                            if (newDevices.length > 0 && window.customToast) {
                                const latest = newDevices[0];
                                window.customToast({
                                    message: 'Perangkat baru terhubung: ' + latest.device_name,
                                    type: 'success'
                                });
                            }
                        }

                        if (!this.editingDeviceId) {
                            this.devices = data.devices;
                        } else {
                            this.devices = data.devices.map(d => {
                                if (d.id === this.editingDeviceId) {
                                    return { ...d, device_name: this.editingDeviceName };
                                }
                                return d;
                            });
                        }

                        // Perbarui status enrollment otorisasi perangkat secara realtime
                        if (data.enrollment) {
                            this.enrollmentStatus = data.enrollment.status || 'none';
                            if (data.enrollment.status === 'consumed') {
                                this.consumedAt = data.enrollment.consumed_at || '';
                                this.consumedDevice = data.enrollment.device_name || '';
                                this.qrCodeUri = '';
                                this.authorizeUrl = '';
                            } else if (data.enrollment.status === 'active' && data.enrollment.qr_code_uri) {
                                this.qrCodeUri = data.enrollment.qr_code_uri;
                                this.authorizeUrl = data.enrollment.authorize_url;
                            } else if (data.enrollment.status === 'none') {
                                this.qrCodeUri = '';
                                this.authorizeUrl = '';
                            }
                        }
                    }
                } catch (e) {
                    console.error('[DeviceFleet] Gagal memuat data armada:', e);
                } finally {
                    this.isFetchingFleet = false;
                }
            },

            broadcastChange() {
                if (this._broadcastChannel) {
                    try {
                        this._broadcastChannel.postMessage({ type: 'DEVICE_UPDATE' });
                    } catch (e) {}
                }
            },

            copyLink(url) {
                if (!url) return;
                navigator.clipboard.writeText(url).then(() => {
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2500);
                    if (window.customToast) {
                        window.customToast({ message: 'Tautan otorisasi berhasil disalin!', type: 'success' });
                    }
                });
            },

            startRename(device) {
                this.editingDeviceId = device.id;
                this.editingDeviceName = device.device_name;
                this.$nextTick(() => {
                    const input = document.getElementById('rename-input-' + device.id);
                    if (input) input.focus();
                });
            },

            cancelRename() {
                this.editingDeviceId = null;
                this.editingDeviceName = '';
            },

            async submitRename(device) {
                const trimmed = this.editingDeviceName.trim();
                if (!trimmed) return;

                try {
                    const res = await fetch(device.rename_url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            _method: 'PATCH',
                            device_name: trimmed
                        })
                    });

                    const data = await res.json();
                    if (data.success) {
                        device.device_name = data.device_name;
                        this.editingDeviceId = null;
                        this.broadcastChange();
                        if (window.customToast) {
                            window.customToast({
                                message: data.message || 'Nama perangkat berhasil diperbarui.',
                                type: 'success'
                            });
                        }
                    } else {
                        throw new Error(data.message || 'Gagal menyimpan');
                    }
                } catch (err) {
                    if (window.customToast) {
                        window.customToast({ message: 'Gagal memperbarui nama perangkat.', type: 'error' });
                    }
                }
            },

            async confirmRevoke(device) {
                const ok = await window.customConfirm({
                    title: 'Cabut Izin (Blokir)',
                    message: `Apakah Anda yakin ingin memblokir/mencabut izin akses untuk '${device.device_name}'? Perangkat ini akan langsung diblokir seketika dan tidak dapat mendaftar ulang via scan QR.`,
                    type: 'danger',
                    confirmText: 'Cabut Izin',
                    cancelText: 'Batal'
                });

                if (!ok) return;

                try {
                    const res = await fetch(device.revoke_url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await res.json();
                    if (data.success) {
                        device.is_revoked = true;
                        this.broadcastChange();
                        if (window.customToast) {
                            window.customToast({
                                message: data.message || `Akses untuk '${device.device_name}' berhasil dicabut.`,
                                type: 'warning'
                            });
                        }
                        if (data.is_self) {
                            setTimeout(() => window.location.reload(), 1200);
                        }
                    }
                } catch (err) {
                    if (window.customToast) {
                        window.customToast({ message: 'Gagal mencabut izin perangkat.', type: 'error' });
                    }
                }
            },

            async confirmRestore(device) {
                const ok = await window.customConfirm({
                    title: 'Aktifkan Kembali Perangkat',
                    message: `Aktifkan kembali akses kasir untuk '${device.device_name}'?`,
                    type: 'info',
                    confirmText: 'Aktifkan',
                    cancelText: 'Batal'
                });

                if (!ok) return;

                try {
                    const res = await fetch(device.restore_url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await res.json();
                    if (data.success) {
                        device.is_revoked = false;
                        this.broadcastChange();
                        if (window.customToast) {
                            window.customToast({
                                message: data.message || `Akses untuk '${device.device_name}' berhasil diaktifkan kembali.`,
                                type: 'success'
                            });
                        }
                    }
                } catch (err) {
                    if (window.customToast) {
                        window.customToast({ message: 'Gagal mengaktifkan perangkat.', type: 'error' });
                    }
                }
            },

            async confirmDelete(device) {
                const ok = await window.customConfirm({
                    title: 'Hapus Riwayat Perangkat',
                    message: `Hapus perangkat '${device.device_name}' dari riwayat pendaftaran? Catatan: Jika ingin memblokir perangkat agar tidak bisa scan QR lagi, gunakan tombol 'Cabut Izin (Blokir)'.`,
                    type: 'danger',
                    confirmText: 'Hapus',
                    cancelText: 'Batal'
                });

                if (!ok) return;

                try {
                    const res = await fetch(device.delete_url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            _method: 'DELETE'
                        })
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.devices = this.devices.filter(d => d.id !== device.id);
                        this.broadcastChange();
                        if (window.customToast) {
                            window.customToast({
                                message: data.message || `Perangkat '${device.device_name}' telah dihapus dari riwayat.`,
                                type: 'success'
                            });
                        }
                    }
                } catch (err) {
                    if (window.customToast) {
                        window.customToast({ message: 'Gagal menghapus perangkat.', type: 'error' });
                    }
                }
            },

            async confirmRevokeSelf(formElement) {
                const ok = await window.customConfirm({
                    title: 'Cabut Otorisasi Browser Ini',
                    message: 'Apakah Anda yakin ingin mencabut otorisasi browser ini? Anda akan logout dari kasir dan memerlukan scan QR ulang.',
                    type: 'danger',
                    confirmText: 'Cabut Token',
                    cancelText: 'Batal'
                });

                if (ok) {
                    formElement.submit();
                }
            },

            printQr() {
                if (!this.qrCodeUri) return;
                const qrWindow = window.open('', '_blank');
                if (!qrWindow) return;
                const doc = qrWindow.document;
                doc.title = 'QR Code Otorisasi Kasir — {{ config('cafe.name') }}';
                doc.body.style.fontFamily = 'sans-serif';
                doc.body.style.display = 'flex';
                doc.body.style.flexDirection = 'column';
                doc.body.style.alignItems = 'center';
                doc.body.style.justifyContent = 'center';
                doc.body.style.height = '100vh';
                doc.body.style.margin = '0';
                doc.body.style.textAlign = 'center';

                const h2 = doc.createElement('h2');
                h2.textContent = '{{ config('cafe.name') }} POS';
                h2.style.fontSize = '24px';
                h2.style.marginBottom = '8px';
                h2.style.color = '#1F1812';
                doc.body.appendChild(h2);

                const p = doc.createElement('p');
                p.textContent = 'Scan menggunakan kamera tablet kasir untuk mengotorisasi terminal resmi';
                p.style.fontSize = '14px';
                p.style.color = '#666';
                p.style.marginTop = '0';
                p.style.marginBottom = '16px';
                doc.body.appendChild(p);

                const badge = doc.createElement('div');
                badge.textContent = 'Sekali Pakai • Berlaku {{ $enrollmentTtlMinutes ?? 15 }} Menit';
                badge.style.display = 'inline-block';
                badge.style.background = '#FEF3C7';
                badge.style.color = '#92400E';
                badge.style.border = '1px solid #FCD34D';
                badge.style.padding = '5px 14px';
                badge.style.borderRadius = '9999px';
                badge.style.fontWeight = 'bold';
                badge.style.fontSize = '13px';
                badge.style.marginBottom = '20px';
                badge.style.fontFamily = 'monospace';
                doc.body.appendChild(badge);

                doc.body.appendChild(doc.createElement('br'));

                const img = doc.createElement('img');
                img.src = this.qrCodeUri;
                img.alt = 'QR Code Otorisasi';
                img.style.width = '280px';
                img.style.height = '280px';
                img.style.border = '2px solid #E8E1D5';
                img.style.borderRadius = '16px';
                img.style.padding = '12px';
                doc.body.appendChild(img);

                const note = doc.createElement('div');
                note.textContent = 'QR Code ini hangus seketika setelah pertama kali di-scan.';
                note.style.marginTop = '20px';
                note.style.fontSize = '13px';
                note.style.color = '#78350F';
                note.style.fontWeight = '500';
                doc.body.appendChild(note);

                setTimeout(() => {
                    qrWindow.focus();
                    qrWindow.print();
                }, 300);
            },

            async generateNewQr() {
                if (this.isGeneratingQr) return;
                this.isGeneratingQr = true;
                try {
                    const res = await fetch('{{ route('kasir.device-enrollment.create') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await res.json();
                    if (data.ok && data.qr_code_uri) {
                        this.enrollmentStatus = 'active';
                        this.qrCodeUri = data.qr_code_uri;
                        this.authorizeUrl = data.authorize_url;
                        this.showQr = true;
                        this.consumedAt = '';
                        this.consumedDevice = '';
                        this.broadcastChange();
                        if (window.customToast) {
                            window.customToast({
                                message: data.message || 'QR Code sekali pakai baru berhasil dibuat!',
                                type: 'success'
                            });
                        }
                    } else {
                        throw new Error(data.message || 'Gagal membuat QR baru');
                    }
                } catch (err) {
                    if (window.customToast) {
                        window.customToast({
                            message: err.message || 'Gagal membuat QR code baru.',
                            type: 'error'
                        });
                    }
                } finally {
                    this.isGeneratingQr = false;
                }
            }
        };
    }
</script>
@endsection
