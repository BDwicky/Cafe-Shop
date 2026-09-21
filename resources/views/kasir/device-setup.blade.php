@extends('kasir.app')

@section('title', 'Otorisasi Perangkat & Keamanan POS — ' . config('cafe.name'))

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-[#FAF7F2]"
     x-data="{
         searchQuery: '',
         statusFilter: 'all', // 'all', 'active', 'revoked'
         copied: false,
         showSecret: false,
         showRegenerateModal: false,
         editingDeviceId: null,
         editingDeviceName: '',
         qrCodeUri: '{{ $qrCodeUri }}',
         authorizeUrl: '{{ $authorizeUrl }}',
         isGeneratingQr: false,

         // Reactive Fleet Tracking (Tanpa Refresh Halaman)
         deletedDeviceIds: [],
         revokedStates: {
             @foreach ($devices as $d)
                 {{ $d->id }}: {{ $d->is_revoked ? 'true' : 'false' }},
             @endforeach
         },
         deviceNames: {
             @foreach ($devices as $d)
                 {{ $d->id }}: '{{ addslashes($d->device_name) }}',
             @endforeach
         },

         get totalFleetCount() {
             return Math.max(0, {{ $devices->count() }} - this.deletedDeviceIds.length);
         },

         get activeFleetCount() {
             let count = 0;
             for (const [id, isRevoked] of Object.entries(this.revokedStates)) {
                 if (!this.deletedDeviceIds.includes(Number(id)) && !isRevoked) {
                     count++;
                 }
             }
             return count;
         },

         get revokedFleetCount() {
             let count = 0;
             for (const [id, isRevoked] of Object.entries(this.revokedStates)) {
                 if (!this.deletedDeviceIds.includes(Number(id)) && isRevoked) {
                     count++;
                 }
             }
             return count;
         },

         isDeleted(id) {
             return this.deletedDeviceIds.includes(id);
         },

         isRevoked(id) {
             return Boolean(this.revokedStates[id]);
         },

         getDeviceName(id, fallback) {
             return this.deviceNames[id] || fallback;
         },

         matchesFilter(id, name, ip, platform) {
             if (this.isDeleted(id)) return false;

             const isRev = this.isRevoked(id);
             if (this.statusFilter === 'active' && isRev) return false;
             if (this.statusFilter === 'revoked' && !isRev) return false;

             if (!this.searchQuery.trim()) return true;

             const q = this.searchQuery.toLowerCase();
             const devName = (this.deviceNames[id] || name).toLowerCase();
             return devName.includes(q) || (ip && ip.includes(q)) || (platform && platform.toLowerCase().includes(q));
         },

         copyLink(url) {
             navigator.clipboard.writeText(url).then(() => {
                 this.copied = true;
                 setTimeout(() => this.copied = false, 2500);
                 if (window.customToast) {
                     window.customToast({ message: 'Tautan otorisasi berhasil disalin!', type: 'success' });
                 }
             });
         },

         startRename(id, currentName) {
             this.editingDeviceId = id;
             this.editingDeviceName = this.deviceNames[id] || currentName;
             this.$nextTick(() => {
                 const input = document.getElementById('rename-input-' + id);
                 if (input) input.focus();
             });
         },

         cancelRename() {
             this.editingDeviceId = null;
             this.editingDeviceName = '';
         },

         async submitRename(id, renameUrl) {
             const trimmed = this.editingDeviceName.trim();
             if (!trimmed) return;

             try {
                 const res = await fetch(renameUrl, {
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
                     this.deviceNames[id] = data.device_name;
                     this.editingDeviceId = null;
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

         async confirmRevoke(id, name, revokeUrl) {
             const ok = await window.customConfirm({
                 title: 'Cabut Izin (Blokir)',
                 message: `Apakah Anda yakin ingin memblokir/mencabut izin akses untuk '${name}'? Perangkat ini akan langsung diblokir seketika dan tidak dapat mendaftar ulang via scan QR.`,
                 type: 'danger',
                 confirmText: 'Cabut Izin',
                 cancelText: 'Batal'
             });

             if (!ok) return;

             try {
                 const res = await fetch(revokeUrl, {
                     method: 'POST',
                     headers: {
                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                         'Accept': 'application/json',
                         'X-Requested-With': 'XMLHttpRequest'
                     }
                 });

                 const data = await res.json();
                 if (data.success) {
                     this.revokedStates[id] = true;
                     if (window.customToast) {
                         window.customToast({
                             message: data.message || `Akses untuk '${name}' berhasil dicabut.`,
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

         async confirmRestore(id, name, restoreUrl) {
             const ok = await window.customConfirm({
                 title: 'Aktifkan Kembali Perangkat',
                 message: `Aktifkan kembali akses kasir untuk '${name}'?`,
                 type: 'info',
                 confirmText: 'Aktifkan',
                 cancelText: 'Batal'
             });

             if (!ok) return;

             try {
                 const res = await fetch(restoreUrl, {
                     method: 'POST',
                     headers: {
                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                         'Accept': 'application/json',
                         'X-Requested-With': 'XMLHttpRequest'
                     }
                 });

                 const data = await res.json();
                 if (data.success) {
                     this.revokedStates[id] = false;
                     if (window.customToast) {
                         window.customToast({
                             message: data.message || `Akses untuk '${name}' berhasil diaktifkan kembali.`,
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

         async confirmDelete(id, name, deleteUrl) {
             const ok = await window.customConfirm({
                 title: 'Hapus Riwayat Perangkat',
                 message: `Hapus perangkat '${name}' dari riwayat pendaftaran? Catatan: Jika ingin memblokir perangkat agar tidak bisa scan QR lagi, gunakan tombol 'Cabut Izin (Blokir)'.`,
                 type: 'danger',
                 confirmText: 'Hapus',
                 cancelText: 'Batal'
             });

             if (!ok) return;

             try {
                 const res = await fetch(deleteUrl, {
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
                     this.deletedDeviceIds.push(id);
                     if (window.customToast) {
                         window.customToast({
                             message: data.message || `Perangkat '${name}' telah dihapus dari riwayat.`,
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
             const qrWindow = window.open('', '_blank');
             qrWindow.document.write(`
                 <html>
                 <head>
                     <title>QR Code Otorisasi Kasir — {{ config('cafe.name') }}</title>
                     <style>
                         body { font-family: sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #fff; text-align: center; }
                         h2 { font-size: 24px; margin-bottom: 8px; color: #1F1812; }
                         p { font-size: 14px; color: #666; margin-top: 0; margin-bottom: 16px; }
                         .badge { display: inline-block; background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; padding: 5px 14px; border-radius: 9999px; font-weight: bold; font-size: 13px; margin-bottom: 20px; font-family: monospace; }
                         img { width: 280px; height: 280px; border: 2px solid #E8E1D5; border-radius: 16px; padding: 12px; }
                         .note { margin-top: 20px; font-size: 13px; color: #78350F; font-weight: 500; }
                     </style>
                 </head>
                 <body>
                     <h2>{{ config('cafe.name') }} POS</h2>
                     <p>Scan menggunakan kamera tablet kasir untuk mengotorisasi terminal resmi</p>
                     <div class='badge'>⏱️ Sekali Pakai • Berlaku {{ $enrollmentTtlMinutes ?? 15 }} Menit</div><br>
                     <img src='${this.qrCodeUri}' alt='QR Code Otorisasi'>
                     <div class='note'>QR Code ini hangus seketika setelah pertama kali di-scan.</div>
                     <script>window.onload = function() { window.print(); }<\/script>
                 </body>
                 </html>
             `);
             qrWindow.document.close();
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
                     this.qrCodeUri = data.qr_code_uri;
                     this.authorizeUrl = data.authorize_url;
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
     }">

    <!-- ===================================================================
         TOPBAR COMMAND CENTER: DARK ROASTED ESPRESSO STRIP
         =================================================================== -->
    <header class="px-4 sm:px-6 py-3.5 border-b border-[#3A3026] bg-[#1A130D] text-[#F7F3EC] flex flex-wrap items-center justify-between gap-4 shrink-0 select-none shadow-md">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#D9973E] text-[#1F1812] flex items-center justify-center font-bold text-lg shadow-[0_0_12px_rgba(217,151,62,0.35)] shrink-0">
                🛡️
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
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#2A2016] hover:bg-[#D9973E] hover:text-[#1F1812] text-[#F7F3EC] text-xs font-mono font-bold uppercase tracking-wider transition-all border border-[#3A3026] shadow-xs active:scale-95">
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
         MAIN SCROLLABLE CONTENT BODY (1 HALAMAN PENUH, TANPA TAB)
         =================================================================== -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">

        <!-- ===============================================================
             1. BARIS METRIK KPI PERANGKAT (4 KOLOM DI DESKTOP)
             =============================================================== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Card 1: Total Perangkat -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 shadow-[0_4px_20px_rgba(42,33,26,0.03)] flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-mono uppercase tracking-wider text-[#8A7B66] font-bold">Total Armada</div>
                    <div class="font-serif text-2xl font-bold text-[#1F1812] mt-0.5" x-text="totalFleetCount">{{ $devices->count() }}</div>
                    <div class="text-[10px] font-mono text-[#8A7B66] mt-0.5">Perangkat Terdaftar</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-[#FAF7F2] border border-[#E8E1D5] flex items-center justify-center text-xl shadow-2xs">
                    🖥️
                </div>
            </div>

            <!-- Card 2: Perangkat Aktif -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 shadow-[0_4px_20px_rgba(42,33,26,0.03)] flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-mono uppercase tracking-wider text-emerald-700 font-bold">Izin Aktif</div>
                    <div class="font-serif text-2xl font-bold text-emerald-700 mt-0.5" x-text="activeFleetCount">{{ $devices->where('is_revoked', false)->count() }}</div>
                    <div class="text-[10px] font-mono text-emerald-600 mt-0.5">Siap Transaksi POS</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center text-xl shadow-2xs">
                    🟢
                </div>
            </div>

            <!-- Card 3: Akses Dicabut -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 shadow-[0_4px_20px_rgba(42,33,26,0.03)] flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-mono uppercase tracking-wider text-rose-700 font-bold">Akses Dicabut</div>
                    <div class="font-serif text-2xl font-bold text-rose-700 mt-0.5" x-text="revokedFleetCount">{{ $devices->where('is_revoked', true)->count() }}</div>
                    <div class="text-[10px] font-mono text-rose-600 mt-0.5">Diblokir Jarak Jauh</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center justify-center text-xl shadow-2xs">
                    🔒
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
                <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-xl shadow-2xs shrink-0">
                    📱
                </div>
            </div>

        </div>

        <!-- ===============================================================
             2. BAGIAN TENGAH (2 KOLOM): REGISTRASI QR & PENGATURAN KUNCI
             =============================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- KOLOM KIRI (7 Kolom di Desktop): REGISTRASI TABLET BARU / SCAN QR -->
            <div class="lg:col-span-7">
                <div class="bg-white border border-[#E4DCCC] rounded-3xl p-6 sm:p-7 shadow-[0_4px_20px_rgba(42,33,26,0.03)] relative overflow-hidden h-full flex flex-col justify-between">
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 rounded-full border border-[#D9973E]/10 pointer-events-none"></div>

                    <div>
                        <!-- Card Header -->
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-[#E8E1D5]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-[#1F1812] text-amber-400 flex items-center justify-center text-lg shadow-xs shrink-0">
                                    ⚡
                                </div>
                                <div>
                                    <h2 class="font-serif text-lg sm:text-xl font-bold text-[#1F1812]">
                                        Daftarkan Tablet Kasir Baru
                                    </h2>
                                    <p class="text-xs text-[#8A7B66] font-mono">Scan kamera atau gunakan tautan pendaftaran</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-amber-50 text-amber-900 border border-amber-300 shadow-2xs shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                <span>Sekali Pakai (15 Menit)</span>
                            </span>
                        </div>

                        <!-- QR Code & Instructions Grid -->
                        <div class="mt-5 flex flex-col sm:flex-row items-center sm:items-start gap-5">
                            
                            <!-- QR Frame -->
                            <div class="bg-[#FAF7F2] p-3.5 rounded-2xl border border-[#E4DCCC] shadow-xs shrink-0 flex flex-col items-center">
                                <div class="bg-white p-2.5 rounded-xl border border-[#E8E1D5] shadow-2xs relative">
                                    <img :src="qrCodeUri" alt="QR Code Otorisasi Kasir" class="w-40 h-40 object-contain rounded-lg">
                                    <div x-show="isGeneratingQr" x-cloak class="absolute inset-0 bg-white/85 backdrop-blur-xs flex items-center justify-center rounded-xl">
                                        <span class="text-xs font-mono text-[#8A7B66] font-bold animate-pulse">Membuat QR...</span>
                                    </div>
                                </div>
                                <div class="mt-2.5 flex items-center gap-1.5 w-full">
                                    <button type="button" @click="generateNewQr()" :disabled="isGeneratingQr"
                                            title="Buat QR Sekali Pakai Baru"
                                            class="flex-1 px-2.5 py-1.5 bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] hover:text-white border border-[#B5762A]/40 text-[11px] font-mono font-bold rounded-xl transition-all shadow-2xs flex items-center justify-center gap-1 cursor-pointer disabled:opacity-50">
                                        <span :class="{'animate-spin': isGeneratingQr}">🔄</span>
                                        <span x-text="isGeneratingQr ? '...' : 'QR Baru'">QR Baru</span>
                                    </button>
                                    <button type="button" @click="printQr()"
                                            title="Cetak Lembar QR"
                                            class="px-2.5 py-1.5 bg-white hover:bg-stone-100 text-[#1F1812] border border-[#E4DCCC] text-[11px] font-mono font-bold rounded-xl transition-all shadow-2xs flex items-center justify-center gap-1 cursor-pointer">
                                        <span>🖨️</span>
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
                                        Buka kamera bawaan iPad atau tablet Android kasir.
                                    </div>
                                </div>

                                <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-[#FAF7F2] border border-[#E8E1D5]">
                                    <span class="w-5 h-5 rounded-lg bg-[#1F1812] text-white flex items-center justify-center font-mono text-[10px] font-bold shrink-0">2</span>
                                    <div class="text-[11px] text-[#2A211A] leading-tight">
                                        Arahkan ke kode QR dan klik notifikasi tautan browser.
                                    </div>
                                </div>

                                <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-[#FAF7F2] border border-[#E8E1D5]">
                                    <span class="w-5 h-5 rounded-lg bg-[#B5762A] text-white flex items-center justify-center font-mono text-[10px] font-bold shrink-0">3</span>
                                    <div class="text-[11px] text-[#2A211A] leading-tight">
                                        Perangkat terdaftar resmi (1 tahun). <strong>QR code langsung hangus seketika</strong> sehingga tidak bisa dipakai ulang di browser/perangkat lain.
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
                            <input type="text" readonly :value="authorizeUrl"
                                   class="flex-1 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3 py-2 text-xs font-mono text-[#1F1812] select-all focus:outline-none shadow-2xs">
                            <button type="button" @click="copyLink(authorizeUrl)"
                                    class="px-3.5 py-2 bg-[#B5762A] hover:bg-[#1F1812] text-white font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all shadow-2xs active:scale-95 cursor-pointer shrink-0">
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
            <div class="lg:col-span-5 space-y-6">

                <!-- Card Kunci Rahasia Keamanan (KASIR_DEVICE_SECRET) -->
                <div class="bg-white border border-[#E4DCCC] rounded-3xl p-5 sm:p-6 shadow-[0_4px_20px_rgba(42,33,26,0.03)]">
                    <div class="flex items-center justify-between pb-3 border-b border-[#E8E1D5]">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-center justify-center text-sm shadow-2xs">
                                🔑
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
                                <span>🔄</span>
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
                            🌐
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
                                            class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-mono text-[11px] font-bold rounded-lg transition-all shadow-2xs cursor-pointer active:scale-95">
                                        🔒 Cabut Token Ini
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>

        <!-- ===============================================================
             3. BAGIAN BAWAH (LEBAR PENUH): DAFTAR PERANGKAT KASIR TERDAFTAR
             =============================================================== -->
        <div class="bg-white border border-[#E4DCCC] rounded-3xl shadow-[0_6px_25px_rgba(42,33,26,0.04)] overflow-hidden">
            
            <!-- Table Header & Filter Controls -->
            <div class="p-5 sm:p-6 border-b border-[#E8E1D5] flex flex-col md:flex-row md:items-center justify-between gap-4 bg-[#FAF7F2]/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#1F1812] text-white flex items-center justify-center text-lg shadow-2xs shrink-0">
                        🖥️
                    </div>
                    <div>
                        <h2 class="font-serif text-lg sm:text-xl font-bold text-[#1F1812]">
                            Daftar Perangkat Kasir Terdaftar
                        </h2>
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
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-stone-400 text-xs">🔍</span>
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
            <div x-show="totalFleetCount === 0" class="py-16 px-6 text-center" style="display: {{ $devices->isEmpty() ? 'block' : 'none' }};">
                <div class="w-16 h-16 rounded-3xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-3xl mx-auto mb-3 shadow-2xs">
                    📱
                </div>
                <h3 class="font-serif text-lg font-bold text-[#1F1812]">Belum Ada Perangkat Terdaftar</h3>
                <p class="text-xs sm:text-sm text-[#6B5A4B] font-serif italic max-w-md mx-auto mt-1 leading-relaxed">
                    Arahkan kamera tablet kasir ke kode QR di atas untuk mendaftarkan perangkat ke database secara otomatis.
                </p>
            </div>

            @if (! $devices->isEmpty())
                <!-- Device Items List -->
                <div x-show="totalFleetCount > 0" class="divide-y divide-[#E8E1D5]">
                    @foreach ($devices as $device)
                        @php
                            $isCurrent = $currentDevice && $currentDevice->id === $device->id;
                            $deviceIcon = match($device->device_type) {
                                'mobile' => '📲',
                                'desktop' => '💻',
                                default => '📱',
                            };
                            $platformColor = match(strtolower($device->platform ?? '')) {
                                'ios', 'ipados' => 'text-slate-800 bg-slate-100 border-slate-200',
                                'android' => 'text-emerald-800 bg-emerald-50 border-emerald-200',
                                'windows' => 'text-sky-800 bg-sky-50 border-sky-200',
                                'macos' => 'text-purple-800 bg-purple-50 border-purple-200',
                                default => 'text-stone-800 bg-stone-100 border-stone-200',
                            };
                        @endphp

                        <div x-show="matchesFilter({{ $device->id }}, '{{ addslashes($device->device_name) }}', '{{ $device->ip_address }}', '{{ addslashes($device->platform ?? '') }}')"
                             x-transition:leave="transition ease-out duration-200"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors hover:bg-[#FAF7F2]/60 {{ $isCurrent ? 'bg-amber-50/30' : '' }}">
                            
                            <!-- Left: Device Details & Inline Rename -->
                            <div class="flex items-start sm:items-center gap-3.5 flex-1 min-w-0">
                                <div class="w-11 h-11 rounded-2xl bg-[#FAF7F2] border border-[#E4DCCC] flex items-center justify-center text-xl shrink-0 shadow-2xs mt-0.5 sm:mt-0">
                                    {{ $deviceIcon }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    
                                    <!-- View Mode: Name & Badges -->
                                    <div x-show="editingDeviceId !== {{ $device->id }}" class="flex items-center gap-2 flex-wrap">
                                        <h4 class="font-bold text-sm text-[#1F1812] truncate max-w-[280px]"
                                            x-text="getDeviceName({{ $device->id }}, '{{ addslashes($device->device_name) }}')"
                                            title="{{ $device->device_name }}">
                                            {{ $device->device_name }}
                                        </h4>

                                        <!-- Edit Name Button -->
                                        <button type="button" @click="startRename({{ $device->id }}, getDeviceName({{ $device->id }}, '{{ addslashes($device->device_name) }}'))"
                                                class="p-1 text-stone-400 hover:text-[#B5762A] hover:bg-stone-100 rounded text-xs transition-colors cursor-pointer"
                                                title="Ubah nama perangkat">
                                            ✏️
                                        </button>

                                        <!-- Current Device Badge -->
                                        @if ($isCurrent)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-[#1F1812] text-amber-300 shadow-2xs">
                                                ★ Perangkat Ini
                                            </span>
                                        @endif

                                        <!-- Active / Revoked Badge (Reaktif via Alpine) -->
                                        <template x-if="!isRevoked({{ $device->id }})">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                                <span>Aktif</span>
                                            </span>
                                        </template>
                                        <template x-if="isRevoked({{ $device->id }})">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                                <span>🔒</span>
                                                <span>Akses Dicabut (Banned)</span>
                                            </span>
                                        </template>
                                    </div>

                                    <!-- Edit Mode: Inline Form -->
                                    <div x-show="editingDeviceId === {{ $device->id }}" x-cloak class="flex items-center gap-2 mt-1">
                                        <form @submit.prevent="submitRename({{ $device->id }}, '{{ route('kasir.devices.rename', $device) }}')"
                                              class="flex items-center gap-2 flex-wrap">
                                            <input type="text" name="device_name" id="rename-input-{{ $device->id }}"
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
                                        <span class="inline-flex items-center px-2 py-0.2 rounded border text-[10px] font-semibold {{ $platformColor }}">
                                            {{ $device->platform ?? 'Platform' }} • {{ $device->browser ?? 'Browser' }}
                                        </span>
                                        <span>•</span>
                                        <span>IP: <code class="text-[#1F1812] font-bold">{{ $device->ip_address ?? '-' }}</code></span>
                                        <span>•</span>
                                        <span title="Aktivitas terakhir">
                                            🕒 {{ $device->last_active_at ? $device->last_active_at->diffForHumans() : 'Belum aktif' }}
                                        </span>
                                        <span class="hidden lg:inline">• Terdaftar: {{ $device->created_at->format('d M Y') }}</span>
                                    </div>

                                </div>
                            </div>

                            <!-- Right: Action Buttons (Reaktif, Custom Themed Modal & Toast) -->
                            <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                <!-- Revoke Button -->
                                <button type="button"
                                        x-show="!isRevoked({{ $device->id }})"
                                        @click="confirmRevoke({{ $device->id }}, getDeviceName({{ $device->id }}, '{{ addslashes($device->device_name) }}'), '{{ route('kasir.devices.revoke', $device) }}')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-mono font-bold uppercase tracking-wider transition-all shadow-2xs cursor-pointer active:scale-95">
                                    <span>🔒</span>
                                    <span>Cabut Izin (Blokir)</span>
                                </button>

                                <!-- Restore Button -->
                                <button type="button"
                                        x-show="isRevoked({{ $device->id }})"
                                        @click="confirmRestore({{ $device->id }}, getDeviceName({{ $device->id }}, '{{ addslashes($device->device_name) }}'), '{{ route('kasir.devices.restore', $device) }}')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-mono font-bold uppercase tracking-wider transition-all shadow-2xs cursor-pointer active:scale-95">
                                    <span>✓</span>
                                    <span>Aktifkan Kembali</span>
                                </button>

                                <!-- Delete Button -->
                                <button type="button"
                                        @click="confirmDelete({{ $device->id }}, getDeviceName({{ $device->id }}, '{{ addslashes($device->device_name) }}'), '{{ route('kasir.devices.destroy', $device) }}')"
                                        class="p-2 rounded-xl text-stone-400 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition-all cursor-pointer active:scale-95"
                                        title="Hapus dari daftar riwayat">
                                    🗑️
                                </button>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif

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
                <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-900 flex items-center justify-center text-xl font-bold shrink-0 shadow-2xs">
                    🔄
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
@endsection
