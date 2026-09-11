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

<div class="h-full flex flex-col overflow-hidden bg-[#1F1812]" x-data="kitchenKds()">

    <!-- TOPBAR KDS -->
    <div class="px-5 py-3 border-b border-[#3A3026] bg-[#140E0A] text-[#F7F3EC] flex flex-wrap items-center justify-between gap-4 shrink-0 select-none">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-[#D9973E] text-[#1F1812] flex items-center justify-center font-bold text-base">
                ⚡
            </div>
            <div>
                <h1 class="text-base font-serif font-bold tracking-tight">KDS — Antrean Dapur & Barista</h1>
                <p class="text-[11px] text-[#A89A85] font-mono">Kitchen Display System & Monitor Pesanan Siap</p>
            </div>
        </div>

        <!-- TABS STATION SELECTOR -->
        <div class="flex items-center gap-1 bg-[#1F1812] p-1 border border-[#3A3026]">
            <button type="button" @click="setStation('all')"
                    class="px-3.5 py-1.5 font-mono text-xs uppercase tracking-wider transition flex items-center gap-2"
                    :class="station === 'all' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow' : 'text-[#A89A85] hover:text-[#F7F3EC]'">
                <span>Semua</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px]"
                      :class="station === 'all' ? 'bg-[#1F1812] text-[#D9973E]' : 'bg-[#2A211A] text-[#A89A85]'"
                      x-text="counts.all"></span>
            </button>

            <button type="button" @click="setStation('barista')"
                    class="px-3.5 py-1.5 font-mono text-xs uppercase tracking-wider transition flex items-center gap-2"
                    :class="station === 'barista' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow' : 'text-[#A89A85] hover:text-[#F7F3EC]'">
                <span>☕ Bar Barista</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px]"
                      :class="station === 'barista' ? 'bg-[#1F1812] text-[#D9973E]' : 'bg-[#2A211A] text-[#A89A85]'"
                      x-text="counts.barista"></span>
            </button>

            <button type="button" @click="setStation('kitchen')"
                    class="px-3.5 py-1.5 font-mono text-xs uppercase tracking-wider transition flex items-center gap-2"
                    :class="station === 'kitchen' ? 'bg-[#D9973E] text-[#1F1812] font-bold shadow' : 'text-[#A89A85] hover:text-[#F7F3EC]'">
                <span>🍳 Kitchen (Makanan)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px]"
                      :class="station === 'kitchen' ? 'bg-[#1F1812] text-[#D9973E]' : 'bg-[#2A211A] text-[#A89A85]'"
                      x-text="counts.kitchen"></span>
            </button>
        </div>

        <!-- SOUND ALERT TOGGLE & REFRESH -->
        <div class="flex items-center gap-3">
            <button type="button" @click="soundEnabled = !soundEnabled"
                    class="px-3 py-1.5 border border-[#3A3026] text-xs font-mono text-[#A89A85] hover:text-[#F7F3EC] transition flex items-center gap-1.5">
                <span x-text="soundEnabled ? '🔔 Alert On' : '🔕 Alert Off'"></span>
            </button>
            <button type="button" @click="fetchOrders()"
                    class="px-3 py-1.5 bg-[#2A211A] border border-[#3A3026] text-xs font-mono text-[#D9973E] hover:text-[#F7F3EC] transition">
                ⟳ Refresh
            </button>
        </div>
    </div>

    <!-- MAIN BODY: ORDER CARDS GRID -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-[#18110B]">
        <!-- KONDISI KOSONG -->
        <div x-show="orders.length === 0" class="h-full flex flex-col items-center justify-center text-center p-8">
            <div class="w-16 h-16 rounded-full bg-[#2A211A] border border-[#3A3026] flex items-center justify-center text-2xl text-[#5F7F42] mb-3">
                ✓
            </div>
            <h3 class="text-xl font-serif font-bold text-[#F7F3EC]">Semua Pesanan Selesai!</h3>
            <p class="text-xs text-[#A89A85] font-mono mt-1">
                Saat ada pesanan baru dari kasir, tiket akan otomatis muncul di sini.
            </p>
        </div>

        <!-- GRID KARTU PESANAN -->
        <div x-show="orders.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <template x-for="order in orders" :key="order.id">
                <div class="flex flex-col justify-between bg-[#1F1812] border-2 shadow-xl transition relative overflow-hidden"
                     :class="{
                        'border-[#5F7F42]': order.prep_status === 'ready',
                        'border-[#D9973E]': order.prep_status === 'preparing',
                        'border-[#3A3026]': order.prep_status === 'pending'
                     }">

                    <!-- HEADER KARTU -->
                    <div class="p-3.5 border-b border-[#3A3026]"
                         :class="{
                            'bg-[#5F7F42]/15': order.prep_status === 'ready',
                            'bg-[#D9973E]/15': order.prep_status === 'preparing',
                            'bg-[#2A211A]': order.prep_status === 'pending'
                         }">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <!-- TIMER ELAPSED -->
                            <div class="flex items-center gap-1.5 font-mono text-xs font-bold px-2 py-0.5"
                                 :class="{
                                    'bg-red-500/20 text-red-400 animate-pulse': order.elapsed_minutes >= 12,
                                    'bg-yellow-500/20 text-yellow-400': order.elapsed_minutes >= 6 && order.elapsed_minutes < 12,
                                    'bg-[#5F7F42]/20 text-[#5F7F42]': order.elapsed_minutes < 6
                                 }">
                                <span>⏱</span>
                                <span x-text="order.elapsed_minutes + ' mnt lalu'"></span>
                            </div>

                            <!-- ORDER TYPE -->
                            <span class="font-mono text-[10px] uppercase px-2 py-0.5 border"
                                  :class="order.order_type === 'dine_in' ? 'border-[#D9973E] text-[#D9973E]' : 'border-blue-400 text-blue-400'"
                                  x-text="order.order_type === 'dine_in' ? 'Dine In' : 'Take Away'"></span>
                        </div>

                        <!-- CUSTOMER NAME & CODE -->
                        <div class="flex items-baseline justify-between mt-2">
                            <h2 class="text-base font-bold text-[#F7F3EC] truncate" x-text="order.customer_name"></h2>
                            <span class="font-mono text-xs text-[#A89A85]" x-text="order.code"></span>
                        </div>
                    </div>

                    <!-- BODY: ITEM LIST WITH INTERACTIVE CHECKLIST -->
                    <div class="p-3.5 flex-1 space-y-2 overflow-y-auto max-h-60" x-data="{ checkedItems: {} }">
                        <template x-for="item in order.items" :key="item.id">
                            <div class="flex items-start gap-2.5 p-2 bg-[#140E0A] border border-[#2A211A] cursor-pointer select-none"
                                 @click="checkedItems[item.id] = !checkedItems[item.id]"
                                 :class="{ 'opacity-40 line-through': checkedItems[item.id] }">
                                <span class="font-mono font-bold text-sm px-1.5 py-0.2 bg-[#D9973E] text-[#1F1812] shrink-0"
                                      x-text="item.qty + 'x'"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-xs text-[#F7F3EC] leading-tight" x-text="item.name"></div>
                                    <div class="text-[10px] font-mono text-[#7A6A58] mt-0.5" x-text="item.category"></div>
                                </div>
                            </div>
                        </template>

                        <!-- CATATAN KHUSUS PELANGGAN -->
                        <div x-show="order.note" class="p-2 bg-yellow-950/40 border border-yellow-800/60 text-yellow-200 text-xs font-mono">
                            <span class="font-bold">Catatan:</span> <span x-text="order.note"></span>
                        </div>
                    </div>

                    <!-- FOOTER: TOMBOL AKSI ALUR KDS (Menggunakan x-show langsung, bukan template x-if) -->
                    <div class="p-3 border-t border-[#3A3026] bg-[#140E0A] space-y-2">

                        <!-- STATUS: PENDING (BELUM DIMULAI) -->
                        <button type="button"
                                x-show="order.prep_status === 'pending'"
                                @click="updateStatus(order.id, 'preparing')"
                                :disabled="Boolean(actionLoading[order.id])"
                                :class="Boolean(actionLoading[order.id]) ? 'opacity-50 cursor-wait' : ''"
                                class="w-full py-2.5 bg-[#D9973E] hover:bg-[#c4842e] text-[#1F1812] font-mono text-xs font-bold uppercase tracking-wider transition">
                            <span x-show="!actionLoading[order.id]">▶ Mulai Diracik / Dimasak</span>
                            <span x-show="actionLoading[order.id]">Memproses...</span>
                        </button>

                        <!-- STATUS: PREPARING (SEDANG DIBUAT) -->
                        <button type="button"
                                x-show="order.prep_status === 'preparing'"
                                @click="updateStatus(order.id, 'ready')"
                                :disabled="Boolean(actionLoading[order.id])"
                                :class="Boolean(actionLoading[order.id]) ? 'opacity-50 cursor-wait' : ''"
                                class="w-full py-2.5 bg-[#5F7F42] hover:bg-[#4d6935] text-white font-mono text-xs font-bold uppercase tracking-wider transition shadow-lg animate-pulse">
                            <span x-show="!actionLoading[order.id]">✓ Pesanan Siap & Panggil Kasir</span>
                            <span x-show="actionLoading[order.id]">Memproses...</span>
                        </button>

                        <!-- STATUS: READY (SIAP DIAMBIL DI KASIR) -->
                        <div x-show="order.prep_status === 'ready'" class="space-y-2">
                            <div class="p-1.5 bg-[#5F7F42]/20 border border-[#5F7F42] text-center font-mono text-xs font-bold text-[#5F7F42] uppercase tracking-wider">
                                SIAP DIAMBIL DI KASIR
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button"
                                        @click="updateStatus(order.id, 'completed')"
                                        :disabled="Boolean(actionLoading[order.id])"
                                        :class="Boolean(actionLoading[order.id]) ? 'opacity-50 cursor-wait' : ''"
                                        class="py-2 bg-[#D9973E] hover:bg-[#c4842e] text-[#1F1812] font-mono text-[11px] font-bold uppercase tracking-wider transition">
                                    <span x-show="!actionLoading[order.id]">Diserahkan ✓</span>
                                    <span x-show="actionLoading[order.id]">...</span>
                                </button>
                                <button type="button"
                                        @click="recall(order.id)"
                                        :disabled="Boolean(actionLoading[order.id])"
                                        title="Panggil ulang nama pelanggan via suara"
                                        class="py-2 bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[#F7F3EC] font-mono text-[11px] uppercase tracking-wider transition">
                                    <span x-show="!actionLoading[order.id]">📢 Panggil Ulang</span>
                                    <span x-show="actionLoading[order.id]">...</span>
                                </button>
                            </div>
                        </div>

                        <!-- TOMBOL CETAK TIKET DAPUR KERTAS -->
                        <div class="flex justify-end pt-1">
                            <a :href="'/kasir/kitchen/orders/' + order.id + '/ticket?station=' + station" target="_blank"
                               class="text-[10px] font-mono text-[#A89A85] hover:text-[#D9973E] transition flex items-center gap-1">
                                <span>⎙ Cetak Tiket</span>
                            </a>
                        </div>

                    </div>

                </div>
            </template>
        </div>
    </div>
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
            lastOrderCount: {{ $orders->count() }},
            _pollTimer: null,

            init() {
                // Polling pesanan baru setiap 4 detik
                this._pollTimer = setInterval(() => this.fetchOrders(true), 4000);
            },

            destroy() {
                if (this._pollTimer) clearInterval(this._pollTimer);
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

                // Sinyal TV Display jika status ready
                if (newStatus === 'ready' && typeof BroadcastChannel !== 'undefined') {
                    try {
                        const ch = new BroadcastChannel('cafe_soundstation_sync');
                        ch.postMessage({ type: 'ORDER_READY', orderId: id });
                    } catch (e) {}
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
                        } catch (e) {}
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
            }
        };
    }
</script>
@endsection
