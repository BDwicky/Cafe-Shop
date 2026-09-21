@extends('kasir.app')

@section('title', 'Otorisasi Perangkat & Keamanan POS — ' . config('cafe.name'))

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto space-y-6" x-data="{
    copied: false,
    showSecret: false,
    editingDeviceId: null,
    editingDeviceName: '',
    copyLink(url) {
        navigator.clipboard.writeText(url).then(() => {
            this.copied = true;
            setTimeout(() => this.copied = false, 2500);
        });
    },
    startRename(id, currentName) {
        this.editingDeviceId = id;
        this.editingDeviceName = currentName;
        this.$nextTick(() => {
            const input = document.getElementById('rename-input-' + id);
            if (input) input.focus();
        });
    },
    cancelRename() {
        this.editingDeviceId = null;
        this.editingDeviceName = '';
    }
}">

    <!-- Top Navigation & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-[#E4DCCC]">
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
                Kelola perangkat kasir resmi, monitor alamat IP jaringan, dan cabut izin (*revoke*) perangkat lain secara instan dari jarak jauh.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('kasir.terminal') }}"
               class="px-4 py-2 rounded-xl bg-[#1F1812] hover:bg-[#B5762A] text-white text-xs font-mono font-bold uppercase tracking-wider transition-all shadow-xs active:scale-95">
                Buka Terminal POS
            </a>
        </div>
    </div>

    <!-- Status / Flash Notifications -->
    @if (session('status'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between gap-3 shadow-2xs">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shrink-0">✓</span>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 font-bold text-sm cursor-pointer">&times;</button>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-center justify-between gap-3 shadow-2xs">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs font-bold shrink-0">!</span>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-700 hover:text-rose-900 font-bold text-sm cursor-pointer">&times;</button>
        </div>
    @endif

    <!-- Main Grid: 2 Columns (Status Perangkat Ini & Pendaftaran QR) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ===================================================================
             KOLOM KIRI: STATUS PERANGKAT SAAT INI (5 Kolom di Desktop)
             =================================================================== -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Card Status Perangkat Ini -->
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
                    @if ($currentDevice)
                        <div class="p-3 rounded-xl bg-[#FAF7F2] border border-[#E8E1D5] flex items-center justify-between">
                            <span class="font-mono text-[11px] text-[#8A7B66] font-semibold">Nama Perangkat:</span>
                            <span class="font-mono font-bold text-xs text-[#1F1812] bg-white px-2.5 py-1 rounded-lg border border-[#E8E1D5] shadow-2xs truncate max-w-[200px]" title="{{ $currentDevice->device_name }}">
                                {{ $currentDevice->device_name }}
                            </span>
                        </div>
                    @endif

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
                            {{ $isDeviceAuthorized ? ($currentDevice ? 'Database Terverifikasi' : 'Aktif (HMAC-SHA256)') : 'Tidak Tersimpan' }}
                        </span>
                    </div>
                </div>

                <!-- Tombol Revoke / Cabut Otorisasi Perangkat Ini -->
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
                            Tablet akan otomatis membuka browser dan terdaftar di database sebagai terminal kasir resmi. Token berlaku selama <b>1 tahun</b>.
                        </p>
                        <div class="text-[11px] font-mono text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 inline-block">
                            ✓ Otomatis simpan identitas perangkat di database
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

    <!-- ===================================================================
         BAGIAN UTAMA: DAFTAR SEMUA PERANGKAT KASIR TERDAFTAR (MULTI-DEVICE)
         =================================================================== -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl p-6 sm:p-8 shadow-[0_4px_20px_rgba(42,33,26,0.04)]">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-[#E8E1D5]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#FAF7F2] border border-[#E8E1D5] flex items-center justify-center text-lg shadow-2xs">
                    🖥️
                </div>
                <div>
                    <h2 class="font-serif text-lg sm:text-xl font-bold text-[#1F1812]">
                        Daftar Perangkat Kasir Terdaftar
                    </h2>
                    <p class="text-xs text-[#8A7B66] font-mono">
                        Pantau perangkat aktif dan cabut izin (*revoke*) perangkat lain dari jarak jauh
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-bold uppercase tracking-wider bg-[#FAF7F2] border border-[#E4DCCC] text-[#1F1812]">
                    <span>Total:</span>
                    <span class="text-[#B5762A]">{{ $devices->count() }} Perangkat</span>
                </span>
            </div>
        </div>

        @if ($devices->isEmpty())
            <div class="py-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-2xl mx-auto mb-3 shadow-2xs">
                    📱
                </div>
                <h3 class="font-serif text-base font-bold text-[#1F1812]">Belum Ada Perangkat yang Didaftarkan</h3>
                <p class="text-xs text-[#6B5A4B] font-serif italic max-w-md mx-auto mt-1 leading-relaxed">
                    Arahkan kamera tablet atau ponsel kasir ke kode QR di atas untuk mendaftarkan perangkat ke database secara otomatis.
                </p>
            </div>
        @else
            <div class="mt-6 divide-y divide-[#E8E1D5]">
                @foreach ($devices as $device)
                    @php
                        $isCurrent = $currentDevice && $currentDevice->id === $device->id;
                        $deviceIcon = match($device->device_type) {
                            'mobile' => '📲',
                            'desktop' => '💻',
                            default => '📱',
                        };
                    @endphp
                    <div class="py-4 first:pt-0 last:pb-0 flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors hover:bg-[#FAF7F2]/50 rounded-xl px-2 sm:px-3">
                        
                        <!-- Info Utama Perangkat -->
                        <div class="flex items-start sm:items-center gap-3.5 flex-1 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-lg shrink-0 shadow-2xs mt-0.5 sm:mt-0">
                                {{ $deviceIcon }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <!-- Mode Tampilan / Mode Edit Nama Inline -->
                                <div x-show="editingDeviceId !== {{ $device->id }}" class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-bold text-sm text-[#1F1812] truncate max-w-[280px]" title="{{ $device->device_name }}">
                                        {{ $device->device_name }}
                                    </h4>

                                    <!-- Tombol Edit Nama Inline -->
                                    <button type="button" @click="startRename({{ $device->id }}, '{{ addslashes($device->device_name) }}')"
                                            class="text-stone-400 hover:text-[#B5762A] text-xs transition-colors cursor-pointer"
                                            title="Ubah nama perangkat">
                                        ✏️
                                    </button>

                                    <!-- Badges -->
                                    @if ($isCurrent)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-[#1F1812] text-amber-300 shadow-2xs">
                                            ★ Perangkat Ini
                                        </span>
                                    @endif

                                    @if (! $device->is_revoked)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                            🔴 Akses Dicabut
                                        </span>
                                    @endif
                                </div>

                                <!-- Form Edit Nama Inline (Alpine.js) -->
                                <div x-show="editingDeviceId === {{ $device->id }}" x-cloak class="flex items-center gap-2 mt-1">
                                    <form method="POST" action="{{ route('kasir.devices.rename', $device) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="device_name" id="rename-input-{{ $device->id }}"
                                               x-model="editingDeviceName" required maxlength="100"
                                               class="bg-white border border-[#B5762A] rounded-lg px-2.5 py-1 text-xs font-mono text-[#1F1812] focus:outline-none focus:ring-1 focus:ring-[#B5762A]">
                                        <button type="submit" class="px-2.5 py-1 bg-[#1F1812] hover:bg-[#B5762A] text-white text-[11px] font-mono font-bold rounded-lg transition-all cursor-pointer">
                                            Simpan
                                        </button>
                                        <button type="button" @click="cancelRename()" class="px-2 py-1 text-stone-500 hover:text-stone-800 text-[11px] font-mono cursor-pointer">
                                            Batal
                                        </button>
                                    </form>
                                </div>

                                <!-- Metadata Baris Kedua -->
                                <div class="flex items-center gap-2 sm:gap-3 text-[11px] text-[#8A7B66] font-mono mt-1 flex-wrap">
                                    <span>{{ $device->platform ?? 'Platform' }} • {{ $device->browser ?? 'Browser' }}</span>
                                    <span>•</span>
                                    <span>IP: <code class="text-[#1F1812] font-semibold">{{ $device->ip_address ?? '-' }}</code></span>
                                    <span>•</span>
                                    <span title="Terakhir aktif">
                                        🕒 {{ $device->last_active_at ? $device->last_active_at->diffForHumans() : 'Baru saja' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi Remote -->
                        <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                            @if (! $device->is_revoked)
                                <!-- Tombol Cabut Izin Remote -->
                                <form method="POST" action="{{ route('kasir.devices.revoke', $device) }}"
                                      onsubmit="return confirm('Apakah Anda yakin ingin mencabut izin untuk \'{{ addslashes($device->device_name) }}\'? Perangkat tersebut akan langsung diblokir dari web kasir.');">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-mono font-bold uppercase tracking-wider transition-all shadow-2xs cursor-pointer active:scale-95">
                                        <span>🔒</span>
                                        <span>Cabut Izin</span>
                                    </button>
                                </form>
                            @else
                                <!-- Tombol Pulihkan / Aktifkan Kembali -->
                                <form method="POST" action="{{ route('kasir.devices.restore', $device) }}"
                                      onsubmit="return confirm('Aktifkan kembali akses untuk \'{{ addslashes($device->device_name) }}\'?');">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-mono font-bold uppercase tracking-wider transition-all shadow-2xs cursor-pointer active:scale-95">
                                        <span>✓</span>
                                        <span>Aktifkan Kembali</span>
                                    </button>
                                </form>
                            @endif

                            <!-- Tombol Hapus Riwayat -->
                            <form method="POST" action="{{ route('kasir.devices.destroy', $device) }}"
                                  onsubmit="return confirm('Hapus perangkat \'{{ addslashes($device->device_name) }}\' dari daftar riwayat?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-2 rounded-xl text-stone-400 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition-all cursor-pointer"
                                        title="Hapus riwayat perangkat">
                                    🗑️
                                </button>
                            </form>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

    </div>

</div>
@endsection
