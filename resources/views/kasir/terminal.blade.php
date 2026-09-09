@extends('kasir.app')

@section('title', 'Terminal')

@section('content')
<div x-data="pos()" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- KIRI: daftar menu di paper -->
    <div class="lg:col-span-2">
        <div class="flex items-baseline justify-between mb-4">
            <h1 class="text-2xl tracking-tight font-medium">Terminal Kasir</h1>
            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]" x-text="items.length + ' item di cart'"></span>
        </div>

        <!-- Tab kategori -->
        <div class="flex flex-wrap gap-2 mb-4">
            <button @click="cat = 'all'"
                    :class="cat === 'all' ? 'bg-[#1F1812] text-[#F7F3EC] border-[#1F1812]' : 'bg-white text-[#2A211A] border-[#E4DCCC] hover:border-[#B5762A]'"
                    class="border px-4 py-2 font-mono text-[11px] uppercase tracking-[0.15em]">Semua</button>
            @foreach($menus->pluck('category')->unique('id') as $category)
                <button @click="cat = {{ $category->id }}"
                        :class="cat === {{ $category->id }} ? 'bg-[#1F1812] text-[#F7F3EC] border-[#1F1812]' : 'bg-white text-[#2A211A] border-[#E4DCCC] hover:border-[#B5762A]'"
                        class="border px-4 py-2 font-mono text-[11px] uppercase tracking-[0.15em]">{{ $category->name }}</button>
            @endforeach
        </div>

        <!-- Grid menu -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($menus as $menu)
                <button @click="add({{ $menu->id }})"
                        :class="cat !== 'all' && cat !== {{ $menu->category_id }} ? 'hidden' : ''"
                        @disabled(! $menu->is_available)
                        class="text-left bg-white border border-[#E4DCCC] p-4 hover:border-[#B5762A] transition-colors @unless($menu->is_available) opacity-40 cursor-not-allowed @endunless">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-sm font-medium leading-snug">{{ $menu->name }}</span>
                        <span class="mt-1.5 h-1.5 w-1.5 rounded-full shrink-0 {{ $menu->is_available ? 'bg-[#5F7F42]' : 'bg-[#8A7B66]' }}"></span>
                    </div>
                    <div class="mt-1 font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">{{ $menu->category->name }}</div>
                    <div class="mt-3 font-mono text-lg text-[#B5762A]">Rp {{ number_format($menu->price, 0, ',', '.') }}</div>
                    @unless($menu->is_available)
                        <div class="mt-2 font-mono text-[10px] uppercase tracking-[0.2em] text-[#C4553D]">Habis</div>
                    @endunless
                </button>
            @endforeach
        </div>
    </div>

    <!-- KANAN: cart band espresso -->
    <div class="lg:col-span-1">
        <div class="lg:sticky lg:top-6 bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] p-5 flex flex-col min-h-[60vh]">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">Pesanan</div>

            <!-- Daftar item -->
            <div class="mt-3 flex-1 space-y-2 overflow-y-auto max-h-[40vh]">
                <template x-if="items.length === 0">
                    <div class="text-sm text-[#A89A85] py-8 text-center">Klik menu untuk menambah.</div>
                </template>
                <template x-for="(item, idx) in items" :key="item.id">
                    <div class="bg-[#2A211A] border border-[#3A3026] p-3">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-sm" x-text="item.name"></span>
                            <button @click="remove(idx)" class="text-[#A89A85] hover:text-[#C4553D] text-xs leading-none">×</button>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center border border-[#3A3026]">
                                <button @click="dec(idx)" class="px-3 py-1 hover:bg-[#1F1812] font-mono">−</button>
                                <span class="px-3 font-mono text-sm" x-text="item.qty"></span>
                                <button @click="inc(idx)" class="px-3 py-1 hover:bg-[#1F1812] font-mono">+</button>
                            </div>
                            <span class="font-mono text-sm text-[#D9973E]" x-text="fmt(item.price * item.qty)"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Ringkasan -->
            <div class="mt-4 border-t border-[#3A3026] pt-4 space-y-3">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">Tipe</label>
                        <select x-model="orderType" class="mt-1 w-full bg-[#2A211A] border border-[#3A3026] text-[#F7F3EC] px-2 py-2 text-sm focus:outline-none focus:border-[#D9973E]">
                            <option value="dine_in">Dine In</option>
                            <option value="take_away">Take Away</option>
                        </select>
                    </div>
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">Bayar</label>
                        <select x-model="method" @change="methodChange()" class="mt-1 w-full bg-[#2A211A] border border-[#3A3026] text-[#F7F3EC] px-2 py-2 text-sm focus:outline-none focus:border-[#D9973E]">
                            <option value="cash">Cash</option>
                            <option value="qris">QRIS</option>
                            <option value="debit">Debit</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">Nama Pelanggan (opsional)</label>
                    <input x-model="customerName" type="text" maxlength="100"
                           class="mt-1 w-full bg-[#2A211A] border border-[#3A3026] text-[#F7F3EC] px-2 py-2 text-sm focus:outline-none focus:border-[#D9973E]">
                </div>

                <div class="flex items-center justify-between text-sm">
                    <span class="text-[#A89A85]">Subtotal</span>
                    <span class="font-mono" x-text="fmt(subtotal)"></span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[#A89A85] text-sm shrink-0">Diskon</span>
                    <input x-model.number="discount" @input="syncPaidIfNotCash()" type="number" min="0" :max="subtotal" :disabled="method !== 'cash'"
                           class="w-28 bg-[#2A211A] border border-[#3A3026] text-[#F7F3EC] px-2 py-1.5 text-right font-mono text-sm focus:outline-none focus:border-[#D9973E] disabled:opacity-40">
                </div>
                <div class="flex items-center justify-between border-t border-[#3A3026] pt-3">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">Total</span>
                    <span class="font-mono text-2xl text-[#D9973E]" x-text="fmt(total)"></span>
                </div>
                <div class="flex items-center justify-between gap-2" x-show="method === 'cash'">
                    <span class="text-[#A89A85] text-sm shrink-0">Tunai</span>
                    <input x-model.number="paid" type="number" min="0"
                           class="w-28 bg-[#2A211A] border border-[#3A3026] text-[#F7F3EC] px-2 py-1.5 text-right font-mono text-sm focus:outline-none focus:border-[#D9973E]">
                </div>
                <div class="flex items-center justify-between text-sm" x-show="method === 'cash'">
                    <span class="text-[#A89A85]">Kembalian</span>
                    <span class="font-mono" x-text="fmt(Math.max(change, 0))"></span>
                </div>

                <p class="text-xs text-[#C4553D]" x-show="error" x-text="error"></p>

                <button @click="submit()" :disabled="items.length === 0 || submitting || (method === 'cash' && paid < total)"
                        class="w-full bg-[#D9973E] text-[#1F1812] px-6 py-3.5 font-mono text-xs uppercase tracking-[0.2em] hover:bg-[#B5762A] hover:text-white disabled:opacity-40 disabled:cursor-not-allowed">
                    <span x-show="!submitting">Bayar ›</span>
                    <span x-show="submitting">Memproses…</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function pos() {
    return {
        menus: @json($menuData),
        cat: 'all',
        items: [],
        orderType: 'dine_in',
        method: 'cash',
        discount: 0,
        paid: 0,
        customerName: '',
        error: '',
        submitting: false,

        get subtotal() { return this.items.reduce((s, i) => s + i.price * i.qty, 0); },
        get total() { return Math.max(this.subtotal - (parseInt(this.discount) || 0), 0); },
        get change() { return (parseInt(this.paid) || 0) - this.total; },

        fmt(v) { return 'Rp ' + (v || 0).toLocaleString('id-ID'); },

        add(id) {
            const m = this.menus.find(x => x.id === id);
            if (!m || !m.available) return;
            const found = this.items.find(i => i.id === id);
            if (found) found.qty++;
            else this.items.push({ id: m.id, name: m.name, price: m.price, qty: 1 });
            this.error = '';
        },
        inc(idx) { this.items[idx].qty++; },
        dec(idx) {
            this.items[idx].qty--;
            if (this.items[idx].qty <= 0) this.items.splice(idx, 1);
        },
        remove(idx) { this.items.splice(idx, 1); },

        syncPaidIfNotCash() { if (this.method !== 'cash') this.paid = this.total; },
        methodChange() {
            if (this.method !== 'cash') { this.paid = this.total; this.discount = 0; }
        },

        async submit() {
            this.submitting = true;
            this.error = '';
            try {
                const res = await fetch(@js(route('kasir.orders.store')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        items: this.items.map(i => ({ menu_id: i.id, qty: i.qty })),
                        order_type: this.orderType,
                        payment_method: this.method,
                        discount: parseInt(this.discount) || 0,
                        paid_amount: parseInt(this.paid) || 0,
                        customer_name: this.customerName || null,
                    }),
                });
                const data = await res.json();
                if (!res.ok) { this.error = data.message || 'Gagal menyimpan transaksi.'; return; }
                window.location = data.receipt_url;
            } catch (e) {
                this.error = 'Kesalahan jaringan. Coba lagi.';
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>
@endsection
