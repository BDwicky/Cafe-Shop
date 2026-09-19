@extends('kasir.app')

@section('title', 'KDS — Layar Dapur & Barista')

@section('content')
@php
    $initialOrders = $orders->map(function ($o) use ($station) {
        $items = $o->items->map(function ($item) {
            $slug = $item->menu?->category?->slug ?? '';
            return [
                'id' => $item->id,
                'name' => $item->menu_name,
                'qty' => $item->qty,
                'is_drink' => in_array($slug, \App\Services\KitchenService::DRINK_CATEGORIES, true),
                'is_food' => in_array($slug, \App\Services\KitchenService::FOOD_CATEGORIES, true),
                'category' => $item->menu?->category?->name ?? '',
            ];
        });

        if ($station === 'barista') {
            $items = $items->where('is_drink', true)->values();
        } elseif ($station === 'kitchen') {
            $items = $items->where('is_food', true)->values();
        }

        return [
            'id' => (int) $o->id,
            'code' => $o->code,
            'music_code' => $o->music_code,
            'customer_name' => $o->customer_name ?: 'Pelanggan',
            'order_type' => $o->order_type,
            'note' => $o->note,
            'prep_status' => $o->prep_status,
            'created_at_time' => $o->created_at->format('H:i'),
            'elapsed_minutes' => (int) $o->created_at->diffInMinutes(now()),
            'items' => $items,
        ];
    })->values();
@endphp

<div class="h-full flex flex-col overflow-hidden bg-[#140E0A]" x-data="kitchenKds()">

    <!-- 1. TOPBAR KDS (MODERN ESPRESSO BARISTA BAR) -->
    <header class="px-4 sm:px-6 py-3.5 border-b border-[#3A3026] bg-[#1A130D] text-[#F7F3EC] flex flex-wrap items-center justify-between gap-4 shrink-0 select-none shadow-md">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#D9973E] text-[#1F1812] flex items-center justify-center font-bold text-lg shadow-sm">
                ⚡
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base sm:text-lg font-serif font-bold tracking-tight text-[#F7F3EC]">
                        KDS Antrean Dapur & Barista
                    </h1>
                    <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#5F7F42]/20 text-[#85BF5C] border border-[#5F7F42]/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#85BF5C] animate-pulse"></span>
                        Live Monitor
                    </span>
                </div>
                <p class="text-[11px] text-[#A89A85] font-mono">
                    Kitchen Display System &bull; Kelola tiket peracikan pesanan secara realtime
                </p>
            </div>
        </div>

        <!-- TABS STATION SELECTOR (ROUNDED PILL TABS) -->
        <div class="flex items-center gap-1.5 bg-[#140E0A] p-1 rounded-2xl border border-[#3A3026] shadow-inner">
            <button type="button" @click="setStation('all')"
                    class="px-3.5 py-1.5 font-mono text-xs uppercase tracking-wider transition-all rounded-xl flex items-center gap-2 cursor-pointer"
                    :class="station === 'all' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md scale-102' : 'text-[#A89A85] hover:text-[#F7F3EC] hover:bg-[#2A211A]'">
                <span>Semua</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-extrabold"
                      :class="station === 'all' ? 'bg-[#1F1812] text-[#D9973E]' : 'bg-[#2A211A] text-[#A89A85]'"
                      x-text="counts.all"></span>
            </button>

            <button type="button" @click="setStation('barista')"
                    class="px-3.5 py-1.5 font-mono text-xs uppercase tracking-wider transition-all rounded-xl flex items-center gap-2 cursor-pointer"
                    :class="station === 'barista' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md scale-102' : 'text-[#A89A85] hover:text-[#F7F3EC] hover:bg-[#2A211A]'">
                <span>☕ Bar Barista</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-extrabold"
                      :class="station === 'barista' ? 'bg-[#1F1812] text-[#D9973E]' : 'bg-[#2A211A] text-[#A89A85]'"
                      x-text="counts.barista"></span>
            </button>

            <button type="button" @click="setStation('kitchen')"
                    class="px-3.5 py-1.5 font-mono text-xs uppercase tracking-wider transition-all rounded-xl flex items-center gap-2 cursor-pointer"
                    :class="station === 'kitchen' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow-md scale-102' : 'text-[#A89A85] hover:text-[#F7F3EC] hover:bg-[#2A211A]'">
                <span>🍳 Kitchen</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-extrabold"
                      :class="station === 'kitchen' ? 'bg-[#1F1812] text-[#D9973E]' : 'bg-[#2A211A] text-[#A89A85]'"
                      x-text="counts.kitchen"></span>
            </button>
        </div>

        <!-- SOUND ALERT TOGGLE, FULLSCREEN & REFRESH -->
        <div class="flex items-center gap-2 sm:gap-2.5">
            <button type="button" @click="soundEnabled = !soundEnabled"
                    class="px-3 py-1.5 rounded-xl border text-xs font-mono transition flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95"
                    :class="soundEnabled ? 'bg-[#5F7F42]/15 border-[#5F7F42]/60 text-[#85BF5C]' : 'bg-[#2A211A] border-[#3A3026] text-[#A89A85] hover:text-[#F7F3EC]'">
                <span x-text="soundEnabled ? '🔔 Alert On' : '🔕 Alert Off'"></span>
            </button>

            <button type="button" @click="toggleFullscreen()"
                    class="p-2 rounded-xl bg-[#2A211A] border border-[#3A3026] hover:border-[#D9973E] text-[#D9973E] hover:text-[#F7F3EC] transition flex items-center justify-center w-9 h-9 shadow-xs active:scale-95 cursor-pointer"
                    :title="isFullscreen ? 'Keluar Layar Penuh (Esc)' : 'Layar Penuh Tablet (F11)'">
                <svg x-show="!isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
                <svg x-show="isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v4m0 0H5m4 0L4 2m11 1v4m0 0h4m-4 0l5-5M9 21v-4m0 0H5m4 0l-5 5m11-1v-4m0 0h4m-4 0l5 5" />
                </svg>
            </button>

            <button type="button" @click="fetchOrders()"
                    class="px-3.5 py-1.5 rounded-xl bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] hover:border-[#D9973E]/50 text-xs font-mono font-bold text-[#D9973E] hover:text-[#F7F3EC] transition flex items-center gap-1.5 shadow-xs active:scale-95 cursor-pointer">
                <span>⟳</span>
                <span>Refresh</span>
            </button>
        </div>
    </header>

    <!-- 2. MAIN CANVAS: ORDER CARDS GRID -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-[#140E0A]">
        <!-- KONDISI KOSONG (ALL CAFE ORDERS DONE) -->
        <div x-show="orders.length === 0" class="h-full min-h-[380px] flex flex-col items-center justify-center text-center p-8">
            <div class="w-20 h-20 rounded-3xl bg-[#1F1812] border-2 border-[#3A3026] flex items-center justify-center text-3xl text-[#85BF5C] mb-4 shadow-xl">
                ☕
            </div>
            <h3 class="text-xl sm:text-2xl font-serif font-bold text-[#F7F3EC]">Semua Pesanan Selesai!</h3>
            <p class="text-xs sm:text-sm text-[#A89A85] font-mono mt-1.5 max-w-md">
                Antrean dapur & barista bersih. Tiket baru dari terminal kasir akan otomatis muncul secara instan di layar ini.
            </p>
            <div class="mt-4 flex items-center gap-2 font-mono text-[11px] text-[#7A6A58]">
                <span class="w-2 h-2 rounded-full bg-[#5F7F42] animate-ping"></span>
                <span>Auto-sync aktif setiap 4 detik</span>
            </div>
        </div>

        <!-- GRID KARTU PESANAN MODERN ROUNDED-2XL -->
        <div x-show="orders.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 sm:gap-5">
            <template x-for="order in orders" :key="order.id">
                <div class="flex flex-col justify-between bg-[#1F1812] rounded-2xl border-2 shadow-xl transition-all duration-200 relative overflow-hidden group hover:shadow-2xl"
                     :class="{
                        'border-[#5F7F42] ring-2 ring-[#5F7F42]/30': order.prep_status === 'ready',
                        'border-[#D9973E] ring-1 ring-[#D9973E]/20': order.prep_status === 'preparing',
                        'border-[#3A3026] hover:border-[#524436]': order.prep_status === 'pending'
                     }">

                    <!-- HEADER KARTU TIKET -->
                    <div class="p-4 border-b border-[#3A3026]"
                         :class="{
                            'bg-[#5F7F42]/15': order.prep_status === 'ready',
                            'bg-[#D9973E]/12': order.prep_status === 'preparing',
                            'bg-[#2A211A]/80': order.prep_status === 'pending'
                         }">
                        <!-- Top Row: Timer & Tipe Pesanan -->
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <!-- TIMER ELAPSED PILL -->
                            <div class="flex items-center gap-1.5 font-mono text-xs font-bold px-2.5 py-1 rounded-full border shadow-2xs"
                                 :class="{
                                    'bg-red-500/20 text-red-300 border-red-500/40 animate-pulse': order.elapsed_minutes >= 12,
                                    'bg-amber-500/20 text-amber-300 border-amber-500/40': order.elapsed_minutes >= 6 && order.elapsed_minutes < 12,
                                    'bg-[#5F7F42]/25 text-[#85BF5C] border-[#5F7F42]/50': order.elapsed_minutes < 6
                                 }">
                                <span>⏱</span>
                                <span x-text="order.elapsed_minutes + ' mnt lalu'"></span>
                            </div>

                            <!-- ORDER TYPE PILL -->
                            <span class="font-mono text-[10px] uppercase font-bold px-2.5 py-0.5 rounded-full border shadow-2xs"
                                  :class="order.order_type === 'dine_in'
                                      ? 'bg-[#D9973E]/15 border-[#D9973E]/40 text-[#E5A44B]'
                                      : 'bg-blue-500/15 border-blue-500/40 text-blue-300'"
                                  x-text="order.order_type === 'dine_in' ? 'Dine In' : 'Take Away'"></span>
                        </div>

                        <!-- Customer Name & Code -->
                        <div class="flex items-baseline justify-between mt-1 gap-2">
                            <h2 class="text-base sm:text-lg font-serif font-bold text-[#F7F3EC] truncate" x-text="order.customer_name"></h2>
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-[#140E0A] text-[#A89A85] border border-[#3A3026] shrink-0"
                                  x-text="order.code"></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] font-mono text-[#8A7B66] mt-1">
                            <span x-text="'Jam Masuk: ' + order.created_at_time"></span>
                            <span x-show="order.music_code" class="text-[#D9973E]" x-text="'Musik: ' + order.music_code"></span>
                        </div>
                    </div>

                    <!-- BODY: ITEM LIST WITH INTERACTIVE CHECKLIST -->
                    <div class="p-3.5 sm:p-4 flex-1 space-y-2 overflow-y-auto max-h-64" x-data="{ checkedItems: {} }">
                        <template x-for="item in order.items" :key="item.id">
                            <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-[#140E0A] border border-[#2E241B] hover:border-[#D9973E]/40 transition-all cursor-pointer select-none active:scale-98"
                                 @click="checkedItems[item.id] = !checkedItems[item.id]"
                                 :class="{ 'opacity-35 bg-[#140E0A]/50': checkedItems[item.id] }">
                                <!-- Qty Badge -->
                                <span class="font-mono font-extrabold text-xs px-2 py-0.5 rounded-lg bg-[#D9973E] text-[#1F1812] shrink-0 shadow-2xs"
                                      :class="{ 'bg-[#524436] text-[#A89A85]': checkedItems[item.id] }"
                                      x-text="item.qty + 'x'"></span>

                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-xs text-[#F7F3EC] leading-tight"
                                         :class="{ 'line-through text-[#8A7B66]': checkedItems[item.id] }"
                                         x-text="item.name"></div>
                                    <div class="text-[10px] font-mono text-[#7A6A58] mt-0.5 flex items-center gap-1.5" x-text="item.category"></div>
                                </div>

                                <!-- Checkbox Indicator Icon -->
                                <div class="w-4 h-4 rounded-md border flex items-center justify-center shrink-0 text-[10px]"
                                     :class="checkedItems[item.id] ? 'bg-[#5F7F42] border-[#5F7F42] text-white font-bold' : 'border-[#3A3026] text-transparent'">
                                    ✓
                                </div>
                            </div>
                        </template>

                        <!-- CATATAN KHUSUS PELANGGAN -->
                        <div x-show="order.note" class="p-2.5 rounded-xl bg-amber-950/40 border border-amber-800/60 text-amber-200 text-xs font-mono">
                            <span class="font-bold text-[#E5A44B]">Catatan:</span>
                            <span x-text="order.note"></span>
                        </div>
                    </div>

                    <!-- FOOTER: TOMBOL AKSI ALUR KDS (TOUCH-FRIENDLY & ROUNDED-XL) -->
                    <div class="p-3.5 border-t border-[#3A3026] bg-[#140E0A] space-y-2">

                        <!-- STATUS: PENDING (BELUM DIMULAI) -->
                        <button type="button"
                                x-show="order.prep_status === 'pending'"
                                @click="updateStatus(order.id, 'preparing')"
                                :disabled="Boolean(actionLoading[order.id])"
                                :class="Boolean(actionLoading[order.id]) ? 'opacity-50 cursor-wait' : ''"
                                class="w-full py-2.5 rounded-xl bg-[#D9973E] hover:bg-[#E5A44B] text-[#1F1812] font-mono text-xs font-extrabold uppercase tracking-wider transition-all shadow-md active:scale-98 cursor-pointer flex items-center justify-center gap-2">
                            <span x-show="!actionLoading[order.id]">▶ Mulai Diracik / Dimasak</span>
                            <span x-show="actionLoading[order.id]">Memproses...</span>
                        </button>

                        <!-- STATUS: PREPARING (SEDANG DIBUAT) -->
                        <button type="button"
                                x-show="order.prep_status === 'preparing'"
                                @click="updateStatus(order.id, 'ready')"
                                :disabled="Boolean(actionLoading[order.id])"
                                :class="Boolean(actionLoading[order.id]) ? 'opacity-50 cursor-wait' : ''"
                                class="w-full py-2.5 rounded-xl bg-[#5F7F42] hover:bg-[#6e934d] text-white font-mono text-xs font-extrabold uppercase tracking-wider transition-all shadow-lg animate-pulse active:scale-98 cursor-pointer flex items-center justify-center gap-2">
                            <span x-show="!actionLoading[order.id]">✓ Pesanan Siap & Panggil Kasir</span>
                            <span x-show="actionLoading[order.id]">Memproses...</span>
                        </button>

                        <!-- STATUS: READY (SIAP DIAMBIL DI KASIR) -->
                        <div x-show="order.prep_status === 'ready'" class="space-y-2">
                            <div class="p-2 rounded-xl bg-[#5F7F42]/20 border border-[#5F7F42] text-center font-mono text-xs font-bold text-[#85BF5C] uppercase tracking-wider flex items-center justify-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#85BF5C] animate-ping"></span>
                                <span>SIAP DIAMBIL DI KASIR</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button"
                                        @click="updateStatus(order.id, 'completed')"
                                        :disabled="Boolean(actionLoading[order.id])"
                                        :class="Boolean(actionLoading[order.id]) ? 'opacity-50 cursor-wait' : ''"
                                        class="py-2.5 rounded-xl bg-[#D9973E] hover:bg-[#E5A44B] text-[#1F1812] font-mono text-xs font-bold uppercase tracking-wider transition-all shadow-xs active:scale-98 cursor-pointer flex items-center justify-center gap-1">
                                    <span x-show="!actionLoading[order.id]">Diserahkan ✓</span>
                                    <span x-show="actionLoading[order.id]">...</span>
                                </button>
                                <button type="button"
                                        @click="recall(order.id)"
                                        :disabled="Boolean(actionLoading[order.id])"
                                        title="Panggil ulang nama pelanggan via suara"
                                        class="py-2.5 rounded-xl bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#F7F3EC] font-mono text-xs uppercase tracking-wider transition-all shadow-xs active:scale-98 cursor-pointer flex items-center justify-center gap-1">
                                    <span x-show="!actionLoading[order.id]">📢 Panggil</span>
                                    <span x-show="actionLoading[order.id]">...</span>
                                </button>
                            </div>
                        </div>

                        <!-- TOMBOL CETAK TIKET DAPUR KERTAS -->
                        <div class="flex justify-end pt-1">
                            <a :href="'/kasir/kitchen/orders/' + order.id + '/ticket?station=' + station" target="_blank"
                               class="text-[11px] font-mono text-[#A89A85] hover:text-[#D9973E] transition flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-[#2A211A]">
                                <span>⎙ Cetak Tiket</span>
                            </a>
                        </div>

                    </div>

                </div>
            </template>
        </div>
    </main>
</div>

<script>
    function kitchenKds() {
        return {
            station: '{{ $station }}',
            orders: @js($initialOrders),
            counts: { all: {{ $counts['all'] }}, barista: {{ $counts['barista'] }}, kitchen: {{ $counts['kitchen'] }} },
            loading: false,
            actionLoading: {},
            soundEnabled: true,
            isFullscreen: false,
            lastOrderCount: {{ $orders->count() }},
            _pollTimer: null,

            init() {
                this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
                const updateFs = () => {
                    this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
                };
                document.addEventListener('fullscreenchange', updateFs);
                document.addEventListener('webkitfullscreenchange', updateFs);
                document.addEventListener('msfullscreenchange', updateFs);

                // Polling pesanan baru setiap 4 detik
                this._pollTimer = setInterval(() => this.fetchOrders(true), 4000);

                const cleanupFn = () => {
                    if (this._pollTimer) {
                        clearInterval(this._pollTimer);
                        this._pollTimer = null;
                    }
                    document.removeEventListener('fullscreenchange', updateFs);
                    document.removeEventListener('webkitfullscreenchange', updateFs);
                    document.removeEventListener('msfullscreenchange', updateFs);
                    window.removeEventListener('kasir:page-leave', cleanupFn);
                };

                window.addEventListener('kasir:page-leave', cleanupFn);
                if (typeof this.$cleanup === 'function') {
                    this.$cleanup(cleanupFn);
                }
            },

            destroy() {
                if (this._pollTimer) {
                    clearInterval(this._pollTimer);
                    this._pollTimer = null;
                }
            },

            async fetchOrders(isSilent = false) {
                try {
                    const res = await fetch('/kasir/kitchen/orders?station=' + this.station, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) return;
                    const data = await res.json();

                    if (!isSilent && this.soundEnabled && data.orders.length > this.lastOrderCount) {
                        this.playNewOrderAlert();
                    }
                    this.lastOrderCount = data.orders.length;
                    this.orders = data.orders;
                    this.counts = data.counts;

                    // Update KDS badge di sidebar secara realtime
                    const totalActiveKds = (this.counts && typeof this.counts.all !== 'undefined') ? Number(this.counts.all) : this.orders.length;
                    window.dispatchEvent(new CustomEvent('kds:count', { detail: { count: totalActiveKds } }));
                    if (typeof BroadcastChannel !== 'undefined') {
                        try {
                            const ch = new BroadcastChannel('cafe_soundstation_sync');
                            ch.postMessage({ type: 'KDS_COUNT_UPDATE', count: totalActiveKds });
                        } catch (e) {}
                    }
                } catch (e) {
                    console.error('[KDS] Fetch Error:', e);
                }
            },

            setStation(newStation) {
                this.station = newStation;
                this.fetchOrders(true);
            },

            async updateStatus(orderId, newStatus) {
                const id = Number(orderId);
                if (this.actionLoading[id]) return;

                const orderIndex = this.orders.findIndex(o => Number(o.id) === id);
                if (orderIndex === -1) {
                    console.warn('[KDS] Pesanan tidak ditemukan di daftar:', id);
                    return;
                }

                const originalOrder = JSON.parse(JSON.stringify(this.orders[orderIndex]));
                const originalCounts = JSON.parse(JSON.stringify(this.counts));

                // 1. OPTIMISTIC UI INSTAN (0 milidetik langsung berubah di layar!)
                this.actionLoading = { ...this.actionLoading, [id]: true };

                if (newStatus === 'completed') {
                    // Langsung hilangkan kartu dari layar seketika
                    this.orders = this.orders.filter(o => Number(o.id) !== id);
                    if (this.counts.all > 0) this.counts.all--;
                    if (this.station !== 'all' && this.counts[this.station] > 0) {
                        this.counts[this.station]--;
                    }
                } else {
                    // Update objek array secara immutably agar Alpine mendeteksi perubahan seketika
                    this.orders = this.orders.map((o, idx) => idx === orderIndex ? { ...o, prep_status: newStatus } : o);
                }

                // SINKRONISASI INSTAN (0ms) KE SIDEBAR BADGE KASIR
                const optCount = (this.counts && typeof this.counts.all !== 'undefined') ? Number(this.counts.all) : this.orders.length;
                window.dispatchEvent(new CustomEvent('kds:count', { detail: { count: optCount } }));
                if (typeof BroadcastChannel !== 'undefined') {
                    try {
                        const ch = new BroadcastChannel('cafe_soundstation_sync');
                        ch.postMessage({ type: 'KDS_COUNT_UPDATE', count: optCount });
                    } catch (e) {}
                }

                // Sinyal TV Display & SoundStation jika status ready
                if (newStatus === 'ready') {
                    if (typeof BroadcastChannel !== 'undefined') {
                        try {
                            const ch = new BroadcastChannel('cafe_soundstation_sync');
                            ch.postMessage({ type: 'ORDER_READY', orderId: id });
                            ch.close();
                        } catch (e) {}
                    }
                    if (window.SoundStationHub && typeof window.SoundStationHub.sendCommand === 'function') {
                        window.SoundStationHub.sendCommand('CHECK_READY_ORDERS', { orderId: id, isRecall: false });
                    }
                }

                // 2. Kirim request AJAX ke server di background
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                    const res = await fetch(`/kasir/kitchen/orders/${id}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ status: newStatus })
                    });

                    if (res.ok) {
                        const resData = await res.json().catch(() => ({}));
                        if (typeof resData.kds_count !== 'undefined') {
                            window.dispatchEvent(new CustomEvent('kds:count', { detail: { count: Number(resData.kds_count) } }));
                            if (typeof BroadcastChannel !== 'undefined') {
                                try {
                                    const ch = new BroadcastChannel('cafe_soundstation_sync');
                                    ch.postMessage({ type: 'KDS_COUNT_UPDATE', count: Number(resData.kds_count) });
                                } catch (e) {}
                            }
                        }
                        this.fetchOrders(true);
                    } else {
                        const errData = await res.json().catch(() => ({}));
                        throw new Error(errData.message || 'Status server: ' + res.status);
                    }
                } catch (e) {
                    console.error('[KDS] Gagal memperbarui status pesanan:', e);
                    // Rollback ke status asli jika request gagal
                    if (newStatus === 'completed') {
                        this.orders.splice(orderIndex, 0, originalOrder);
                        this.orders = [...this.orders];
                        this.counts = originalCounts;
                    } else {
                        this.orders = this.orders.map((o, idx) => idx === orderIndex ? originalOrder : o);
                    }
                    const rollbackCount = (this.counts && typeof this.counts.all !== 'undefined') ? Number(this.counts.all) : this.orders.length;
                    window.dispatchEvent(new CustomEvent('kds:count', { detail: { count: rollbackCount } }));
                    if (window.customToast) {
                        window.customToast({ message: 'Gagal memperbarui status pesanan.', type: 'error' });
                    }
                } finally {
                    const nextLoading = { ...this.actionLoading };
                    delete nextLoading[id];
                    this.actionLoading = nextLoading;
                }
            },

            async recall(orderId) {
                const id = Number(orderId);
                if (this.actionLoading[id]) return;
                this.actionLoading = { ...this.actionLoading, [id]: true };

                try {
                    if (typeof BroadcastChannel !== 'undefined') {
                        try {
                            const ch = new BroadcastChannel('cafe_soundstation_sync');
                            ch.postMessage({ type: 'ORDER_READY', orderId: id, isRecall: true });
                            ch.close();
                        } catch (e) {}
                    }

                    if (window.SoundStationHub && typeof window.SoundStationHub.sendCommand === 'function') {
                        window.SoundStationHub.sendCommand('CHECK_READY_ORDERS', { orderId: id, isRecall: true });
                    }

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                    const res = await fetch(`/kasir/kitchen/orders/${id}/recall`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    if (res.ok) {
                        if (window.customToast) {
                            window.customToast({ message: 'Panggilan dikirim ke TV & pengeras suara.', type: 'success' });
                        }
                        this.fetchOrders(true);
                    }
                } catch (e) {
                    console.error('[KDS] Gagal memanggil ulang:', e);
                    if (window.customToast) {
                        window.customToast({ message: 'Gagal memanggil ulang.', type: 'error' });
                    }
                } finally {
                    const nextLoading = { ...this.actionLoading };
                    delete nextLoading[id];
                    this.actionLoading = nextLoading;
                }
            },

            playNewOrderAlert() {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                    osc.frequency.setValueAtTime(880, ctx.currentTime + 0.15); // A5
                    gain.gain.setValueAtTime(0.3, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.45);
                } catch (e) {}
            },

            toggleFullscreen() {
                if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
                    const docElm = document.documentElement;
                    if (docElm.requestFullscreen) {
                        docElm.requestFullscreen().catch(() => {});
                    } else if (docElm.webkitRequestFullscreen) {
                        docElm.webkitRequestFullscreen();
                    } else if (docElm.msRequestFullscreen) {
                        docElm.msRequestFullscreen();
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen().catch(() => {});
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    } else if (document.msExitFullscreen) {
                        document.msExitFullscreen();
                    }
                }
            }
        };
    }
</script>
@endsection
