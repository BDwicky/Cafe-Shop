@extends('kasir.app')

@section('title', 'Otorisasi Perangkat & Keamanan POS — ' . config('cafe.name'))

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto" x-data="{
    copied: false,
    showSecret: false,
    copyLink(url) {
        navigator.clipboard.writeText(url).then(() => {
            this.copied = true;
            setTimeout(() => this.copied = false, 2500);
        });
    }
}">

    <!-- Top Navigation & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-5 border-b border-[#E4DCCC]">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('kasir.terminal') }}"
                   class="inline-flex items-center gap-1.5 text-xs font-mono font-bold uppercase tracking-wider text-[#8A7B66] hover:text-[#1F1812] transition-colors group">
                    <span class="transition-transform group-hover:-translate-x-1">←</span>
                    <span>Kembali ke POS</span>
                </a>
                <span class="text-[#D5CCC0]">•</span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200">
                    <span>🛡️</span>
                    <span>Sistem Keamanan Terminal</span>
                </span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-[#1F1812]">
                Otorisasi Perangkat & Jaringan
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-[#6B5A4B] font-serif italic">
                Kelola perangkat kasir resmi, monitor alamat IP jaringan, dan scan QR untuk mendaftarkan tablet kasir baru secara instan.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('kasir.terminal') }}"
               class="px-4 py-2 rounded-xl bg-[#1F1812] hover:bg-[#B5762A] text-white text-xs font-mono font-bold uppercase tracking-wider transition-all shadow-xs active:scale-95">
                Buka Terminal POS
            </a>
        </div>
    </div>

    <!-- Main Grid: 2 Columns -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ===================================================================
             KOLOM KIRI: STATUS PERANGKAT SAAT INI (5 Kolom di Desktop)
             =================================================================== -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Card Status Perangkat -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-[0_4px_20px_rgba(42,33,26,0.04)] relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full border border-[#D9973E]/10 pointer-events-none"></div>

                <div class="flex items-center justify-between pb-4 border-b border-[#E8E1D5]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-[#FAF7F2] border border-[#E8E1D5] flex items-center justify-center text-base shadow-2xs">
                            📱
                        </div>
                        <div>
                            <h2 class="font-bold text-sm text-[#1F1812]">Perangkat Ini</h2>
                            <div class="font-mono text-[10px] text-[#8A7B66]">Status & Identitas Terminal</div>
                        </div>
                    </div>

                    @if ($isDeviceAuthorized)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                            Terdaftar Resmi
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-stone-100 text-stone-700 border border-stone-200">
                            Tanpa Token
                        </span>
                    @endif
                </div>

                <!-- Info List -->
                <div class="mt-4 space-y-3">
                    <div class="p-3 rounded-xl bg-[#FAF7F2] border border-[#E8E1D5] flex items-center justify-between">
                        <span class="font-mono text-[11px] text-[#8A7B66] font-semibold">IP Address Terdeteksi:</span>
                        <span class="font-mono font-bold text-xs text-[#1F1812] bg-white px-2.5 py-1 rounded-lg border border-[#E8E1D5] shadow-2xs">
                            {{ $clientIp }}
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-[#FAF7F2] border border-[#E8E1D5] flex items-center justify-between">
                        <span class="font-mono text-[11px] text-[#8A7B66] font-semibold">Status Jaringan:</span>
                        <span class="inline-flex items-center gap-1.5 font-mono text-[11px] font-semibold text-emerald-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                            Akses Diizinkan
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-[#FAF7F2] border border-[#E8E1D5] flex items-center justify-between">
                        <span class="font-mono text-[11px] text-[#8A7B66] font-semibold">Token Kriptografis:</span>
                        <span class="font-mono text-[11px] font-semibold {{ $isDeviceAuthorized ? 'text-emerald-700' : 'text-stone-600' }}">
                            {{ $isDeviceAuthorized ? 'Aktif (HMAC-SHA256)' : 'Tidak Tersimpan' }}
                        </span>
                    </div>
                </div>

                <!-- Tombol Revoke / Cabut Otorisasi -->
                @if ($isDeviceAuthorized)
                    <div class="mt-5 pt-4 border-t border-[#E8E1D5]">
                        <form method="POST" action="{{ route('kasir.device-revoke') }}"
                              onsubmit="return confirm('Apakah Anda yakin ingin mencabut otorisasi perangkat ini? Perangkat ini tidak akan lagi memiliki token terminal kasir resmi.');">
                            @csrf
                            <button type="submit"
                                    class="w-full py-2.5 px-4 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl font-mono text-xs uppercase tracking-wider font-bold transition-all shadow-2xs flex items-center justify-center gap-2 cursor-pointer">
                                <span>🔒</span>
                                <span>Cabut Otorisasi Perangkat Ini</span>
                            </button>
                        </form>
                        <p class="mt-2 text-[10px] text-stone-500 text-center font-mono">
                            Gunakan ini jika tablet kasir ini akan dipindahtangankan atau diservis.
                        </p>
                    </div>
                @endif
            </div>

            <!-- Card Panduan Subnet & Konfigurasi -->
            <div class="bg-[#FAF7F2] border border-[#E4DCCC] rounded-2xl p-5 shadow-2xs">
                <h3 class="font-bold text-xs font-mono uppercase tracking-wider text-[#1F1812] flex items-center gap-2 mb-2">
                    <span>⚙️</span>
                    <span>Whitelist Subnet / IP Kafe Aktif</span>
                </h3>
                <p class="text-xs text-[#6B5A4B] font-serif leading-relaxed mb-3">
                    Perangkat yang terhubung ke IP/subnet berikut dapat langsung mengakses web kasir tanpa diblokir:
                </p>

                <div class="flex flex-wrap gap-1.5">
                    @forelse ($allowedIps as $ipRule)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-[11px] font-semibold bg-white border border-[#E4DCCC] text-[#1F1812]">
                            {{ $ipRule }}
                        </span>
                    @empty
                        <span class="text-xs text-stone-400 font-mono">Semua IP dibatasi</span>
                    @endforelse
                </div>

                <div class="mt-3.5 pt-3 border-t border-[#E8E1D5] text-[11px] text-[#8A7B66] font-mono leading-relaxed">
                    Untuk menambah IP publik statis atau subnet Wi-Fi kafe, perbarui variabel <code class="text-[#B5762A] bg-white px-1 py-0.5 rounded border border-[#E4DCCC]">KASIR_ALLOWED_IPS</code> di file <code class="text-[#1F1812]">.env</code>.
                </div>
            </div>

        </div>

        <!-- ===================================================================
             KOLOM KANAN: REGISTRASI PERANGKAT BARU / SCAN QR (7 Kolom di Desktop)
             =================================================================== -->
        <div class="lg:col-span-7">

            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-6 sm:p-8 shadow-[0_4px_20px_rgba(42,33,26,0.04)] relative">

                <div class="flex items-center gap-3 pb-5 border-b border-[#E8E1D5]">
                    <div class="w-10 h-10 rounded-xl bg-[#1F1812] text-amber-400 flex items-center justify-center text-lg shadow-xs">
                        ⚡
                    </div>
                    <div>
                        <h2 class="font-serif text-lg sm:text-xl font-bold text-[#1F1812]">
                            Daftarkan Tablet Kasir Baru
                        </h2>
                        <p class="text-xs text-[#8A7B66] font-mono">Otorisasi Instan via Scan Kamera Tablet</p>
                    </div>
                </div>

                <!-- QR Code Box -->
                <div class="mt-6 flex flex-col sm:flex-row items-center gap-6 p-5 rounded-2xl bg-[#FAF7F2] border border-[#E8E1D5]">
                    <!-- QR Code Image with Premium Frame -->
                    <div class="bg-white p-3 rounded-2xl border border-[#E4DCCC] shadow-md shrink-0 flex items-center justify-center">
                        <img src="{{ $qrCodeUri }}" alt="QR Code Otorisasi Perangkat Kasir" class="w-44 h-44 object-contain rounded-lg">
                    </div>

                    <!-- Instructions -->
                    <div class="space-y-3 text-center sm:text-left">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#1F1812] text-white text-[10px] font-mono uppercase tracking-wider font-bold">
                            <span>📷</span>
                            <span>Scan Kamera Tablet</span>
                        </div>
                        <h3 class="font-serif text-base font-bold text-[#1F1812] leading-snug">
                            Arahkan kamera tablet kasir ke QR code di samping
                        </h3>
                        <p class="text-xs text-[#6B5A4B] font-serif leading-relaxed">
                            Tablet akan otomatis membuka browser dan mengesahkan perangkat sebagai terminal kasir resmi. Token berlaku selama <b>1 tahun</b>.
                        </p>
                        <div class="text-[11px] font-mono text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 inline-block">
                            ✓ Otomatis simpan cookie otorisasi
                        </div>
                    </div>
                </div>

                <!-- Manual Copy Link -->
                <div class="mt-6 space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] font-bold">
                                Tautan Otorisasi Instan:
                            </label>
                            <span x-show="copied" x-cloak class="text-xs font-mono text-emerald-600 font-bold">
                                ✓ Tautan berhasil disalin!
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly value="{{ $authorizeUrl }}"
                                   class="flex-1 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3.5 py-2.5 text-xs font-mono text-[#1F1812] select-all focus:outline-none">
                            <button type="button" @click="copyLink('{{ $authorizeUrl }}')"
                                    class="px-4 py-2.5 bg-[#B5762A] hover:bg-[#1F1812] text-white font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all shadow-2xs active:scale-95 cursor-pointer shrink-0">
                                <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                            </button>
                        </div>
                        <p class="mt-1 text-[11px] text-[#8A7B66] font-mono">
                            Kirim tautan ini via WhatsApp/pesan ke tablet kasir jika tidak ingin memindai QR Code.
                        </p>
                    </div>

                    <!-- Secret Key Section (Collapsible / Toggle) -->
                    <div class="pt-4 border-t border-[#E8E1D5]">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] font-bold">
                                Kunci Rahasia Perangkat (KASIR_DEVICE_SECRET):
                            </label>
                            <button type="button" @click="showSecret = !showSecret"
                                    class="text-xs font-mono text-[#B5762A] hover:underline font-semibold cursor-pointer">
                                <span x-text="showSecret ? 'Sembunyikan' : 'Tampilkan Kunci'">Tampilkan Kunci</span>
                            </button>
                        </div>
                        <div class="p-3 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] font-mono text-xs flex items-center justify-between">
                            <span class="text-[#1F1812] font-bold tracking-wider" x-text="showSecret ? '{{ $secret }}' : '••••••••••••••••••••••••••••'">
                                ••••••••••••••••••••••••••••
                            </span>
                            <button type="button" @click="copyLink('{{ $secret }}')"
                                    class="text-[11px] font-mono font-bold text-[#8A7B66] hover:text-[#1F1812] cursor-pointer">
                                Salin Kunci
                            </button>
                        </div>
                        <p class="mt-1.5 text-[11px] text-amber-900 font-sans leading-relaxed">
                            ⚠️ <b>Perhatian Keamanan:</b> Jaga kerahasiaan kunci ini. Hanya bagikan kepada staf yang Anda percaya untuk mendaftarkan terminal kasir resmi.
                        </p>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>
@endsection
