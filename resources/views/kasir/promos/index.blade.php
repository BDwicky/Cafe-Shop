@extends('kasir.app')

@section('title', 'Kupon & Promo Diskon Kasir')

@section('content')
<div class="w-full p-4 sm:p-6 space-y-6" x-data="{
    showCreateModal: false,
    showEditModal: false,
    createType: 'percentage',
    editForm: {
        id: null,
        actionUrl: '',
        code: '',
        name: '',
        type: 'percentage',
        discount_value: '',
        max_discount: '',
        min_order: 0,
        usage_limit: '',
        start_date: '',
        end_date: '',
        description: '',
        is_active: true
    },
    openEditModal(promo) {
        this.editForm = {
            id: promo.id,
            actionUrl: '{{ url('/kasir/promos') }}/' + promo.id,
            code: promo.code,
            name: promo.name,
            type: promo.type,
            discount_value: promo.discount_value,
            max_discount: promo.max_discount || '',
            min_order: promo.min_order || 0,
            usage_limit: promo.usage_limit || '',
            start_date: promo.start_date || '',
            end_date: promo.end_date || '',
            description: promo.description || '',
            is_active: !!promo.is_active
        };
        this.showEditModal = true;
    },
    copyCode(code) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(code).then(() => {
                if (window.customToast) {
                    window.customToast({
                        message: '📋 Kode kupon ' + code + ' berhasil disalin!',
                        type: 'success',
                        duration: 2500
                    });
                }
            }).catch(() => {});
        }
    }
}">

    <!-- 1. HEADER HALAMAN -->
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-[#E4DCCC]">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                    Kupon & Promo Diskon
                </h1>
                <span class="px-2.5 py-0.5 rounded-full bg-[#D9973E]/15 border border-[#D9973E]/30 text-[#B5762A] font-mono text-xs font-bold">
                    {{ $stats['total'] }} Kupon Terdaftar
                </span>
            </div>
            <p class="font-sans text-xs sm:text-sm text-[#8A7B66] mt-1 max-w-2xl">
                Kelola kode kupon potongan belanja untuk kasir. Kupon aktif dapat langsung diterapkan di Terminal POS saat melayani pelanggan.
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('kasir.terminal') }}"
               class="px-4 py-2.5 bg-white border border-[#E4DCCC] hover:border-[#D9973E] text-[#1F1812] rounded-xl font-mono text-xs uppercase tracking-wider font-semibold transition shadow-xs flex items-center gap-2">
                <svg class="w-4 h-4 text-[#8A7B66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Terminal POS</span>
            </a>

            <button type="button"
                    @click="showCreateModal = true"
                    class="px-4 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#FAF7F2] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer active:scale-98">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buat Kupon Baru</span>
            </button>
        </div>
    </header>

    <!-- NOTIFIKASI SUKSES -->
    @if (session('success'))
        <div class="p-4 rounded-2xl bg-[#5F7F42]/10 border border-[#5F7F42]/30 text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-full bg-[#5F7F42] text-white flex items-center justify-center font-bold text-xs shrink-0">✓</div>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="w-6 h-6 rounded-full hover:bg-black/5 text-[#8A7B66] hover:text-[#1F1812] flex items-center justify-center font-bold transition">✕</button>
        </div>
    @endif

    <!-- 2. KARTU STATISTIK RINGKASAN -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Total Kupon -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 shadow-xs transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="font-mono text-[11px] uppercase tracking-wider font-semibold text-[#8A7B66]">Total Kupon</span>
                <span class="w-8 h-8 rounded-full bg-[#D9973E]/10 text-[#D9973E] flex items-center justify-center text-sm font-bold">🏷️</span>
            </div>
            <div class="mt-3 font-mono text-2xl sm:text-3xl font-bold text-[#1F1812]">
                {{ $stats['total'] }}
            </div>
            <div class="mt-2 text-[11px] text-[#8A7B66] font-mono">
                Semua kupon yang pernah dibuat
            </div>
        </div>

        <!-- Kupon Aktif -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 shadow-xs transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="font-mono text-[11px] uppercase tracking-wider font-semibold text-[#8A7B66]">Kupon Siap Digunakan</span>
                <span class="w-8 h-8 rounded-full bg-[#5F7F42]/10 text-[#5F7F42] flex items-center justify-center text-sm font-bold">✓</span>
            </div>
            <div class="mt-3 font-mono text-2xl sm:text-3xl font-bold text-[#5F7F42]">
                {{ $stats['active'] }}
            </div>
            <div class="mt-2 text-[11px] text-[#8A7B66] font-mono">
                Bisa diinput kasir saat transaksi
            </div>
        </div>

        <!-- Total Pemakaian -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 shadow-xs transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="font-mono text-[11px] uppercase tracking-wider font-semibold text-[#8A7B66]">Total Digunakan</span>
                <span class="w-8 h-8 rounded-full bg-[#261D16]/10 text-[#1F1812] flex items-center justify-center text-sm font-bold">📈</span>
            </div>
            <div class="mt-3 font-mono text-2xl sm:text-3xl font-bold text-[#1F1812]">
                {{ number_format($stats['total_used'], 0, ',', '.') }}x
            </div>
            <div class="mt-2 text-[11px] text-[#8A7B66] font-mono">
                Kali diskon berhasil diaplikasikan
            </div>
        </div>
    </div>

    <!-- 3. FILTER & PENCARIAN -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <form method="GET" action="{{ route('kasir.promos.index') }}" class="flex-1 w-full flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <svg class="w-4 h-4 text-[#8A7B66] absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari kode kupon atau nama promo..."
                       class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 pl-10 pr-4 py-2 rounded-xl text-xs sm:text-sm text-[#1F1812] transition font-sans">
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select name="status" onchange="this.form.submit()"
                        class="bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl text-xs font-mono text-[#1F1812] cursor-pointer">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Hanya Aktif</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>

                @if($search || $status !== 'all')
                    <a href="{{ route('kasir.promos.index') }}"
                       class="px-3 py-2 text-xs font-mono text-[#8A7B66] hover:text-[#1F1812] bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl transition shrink-0"
                       title="Reset Filter">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- 4. TABEL DAFTAR KUPON DISKON -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#FAF7F2] border-b border-[#E4DCCC] font-mono text-[10px] uppercase tracking-wider text-[#8A7B66]">
                        <th class="py-3.5 px-4 font-semibold">Kode Kupon</th>
                        <th class="py-3.5 px-4 font-semibold">Nama & Keterangan</th>
                        <th class="py-3.5 px-4 font-semibold">Bentuk Diskon</th>
                        <th class="py-3.5 px-4 font-semibold">Syarat Belanja</th>
                        <th class="py-3.5 px-4 font-semibold">Kuota Pemakaian</th>
                        <th class="py-3.5 px-4 font-semibold text-center">Status</th>
                        <th class="py-3.5 px-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4DCCC]/60 font-sans text-xs sm:text-sm">
                    @forelse ($promos as $promo)
                        @php
                            $isExpired = $promo->end_date && $promo->end_date->isPast();
                            $isQuotaExceeded = $promo->usage_limit && $promo->used_count >= $promo->usage_limit;
                        @endphp
                        <tr class="hover:bg-[#FAF7F2]/60 transition-colors {{ (!$promo->is_active || $isExpired || $isQuotaExceeded) ? 'opacity-70 bg-gray-50/50' : '' }}">
                            <!-- Kode Kupon & Tombol Salin -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <button type="button"
                                        @click="copyCode('{{ $promo->code }}')"
                                        title="Klik untuk salin kode kupon"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-[#FAF7F2] hover:bg-[#D9973E]/15 border border-[#E4DCCC] hover:border-[#D9973E] text-[#1F1812] hover:text-[#B5762A] font-mono text-xs font-bold tracking-wider transition group cursor-pointer active:scale-95 shadow-2xs">
                                    <span>🎟️</span>
                                    <span>{{ $promo->code }}</span>
                                    <span class="text-[10px] text-[#8A7B66] group-hover:text-[#B5762A] opacity-70">📋</span>
                                </button>
                            </td>

                            <!-- Nama & Keterangan -->
                            <td class="py-3.5 px-4 min-w-[200px]">
                                <div class="font-bold text-[#1F1812] flex items-center gap-2">
                                    <span>{{ $promo->name }}</span>
                                    @if ($isExpired)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-red-100 text-red-700 border border-red-200">Kedaluwarsa</span>
                                    @elseif ($isQuotaExceeded)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-amber-100 text-amber-800 border border-amber-200">Kuota Habis</span>
                                    @endif
                                </div>
                                @if ($promo->description)
                                    <div class="text-[11px] text-[#8A7B66] mt-0.5">{{ $promo->description }}</div>
                                @endif
                                @if ($promo->start_date || $promo->end_date)
                                    <div class="text-[10px] font-mono text-[#B5762A] mt-1 flex items-center gap-1">
                                        <span>📅</span>
                                        <span>
                                            {{ $promo->start_date ? $promo->start_date->format('d/m/Y') : 'Sekarang' }}
                                            s/d
                                            {{ $promo->end_date ? $promo->end_date->format('d/m/Y') : 'Selamanya' }}
                                        </span>
                                    </div>
                                @endif
                            </td>

                            <!-- Bentuk Diskon -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if ($promo->type === 'percentage')
                                    <div class="font-mono font-bold text-[#5F7F42] text-sm">
                                        {{ $promo->discount_value }}%
                                    </div>
                                    @if ($promo->max_discount)
                                        <div class="text-[10px] font-mono text-[#8A7B66]">
                                            Maks. Rp {{ number_format($promo->max_discount, 0, ',', '.') }}
                                        </div>
                                    @endif
                                @else
                                    <div class="font-mono font-bold text-[#5F7F42] text-sm">
                                        Rp {{ number_format($promo->discount_value, 0, ',', '.') }}
                                    </div>
                                    <div class="text-[10px] font-mono text-[#8A7B66]">Potongan Tetap</div>
                                @endif
                            </td>

                            <!-- Syarat Belanja -->
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs">
                                @if ($promo->min_order > 0)
                                    <span class="text-[#1F1812] font-semibold">Min. Rp {{ number_format($promo->min_order, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-[#8A7B66]">Tanpa Minimal</span>
                                @endif
                            </td>

                            <!-- Terpakai & Kuota -->
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-[#1F1812]">{{ $promo->used_count }}x</span>
                                    @if ($promo->usage_limit)
                                        <span class="text-[#8A7B66]"> / {{ $promo->usage_limit }}</span>
                                    @else
                                        <span class="text-[10px] text-[#8A7B66]"> (Unlimited)</span>
                                    @endif
                                </div>
                                @if ($promo->usage_limit)
                                    @php
                                        $percent = min(100, round(($promo->used_count / $promo->usage_limit) * 100));
                                    @endphp
                                    <div class="w-24 bg-[#E4DCCC]/70 rounded-full h-1.5 mt-1 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-300 {{ $percent >= 100 ? 'bg-red-500' : ($percent >= 80 ? 'bg-[#D9973E]' : 'bg-[#5F7F42]') }}"
                                             style="width: {{ $percent }}%"></div>
                                    </div>
                                @endif
                            </td>

                            <!-- Status Toggle (In-Place Instant AJAX Toggle - Musik Tidak Terjeda) -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-center"
                                x-data="{
                                    isActive: {{ $promo->is_active ? 'true' : 'false' }},
                                    loading: false,
                                    async toggleStatus() {
                                        if (this.loading) return;
                                        this.loading = true;
                                        try {
                                            const res = await fetch('{{ route('kasir.promos.toggle', $promo) }}', {
                                                method: 'PATCH',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json',
                                                    'X-Requested-With': 'XMLHttpRequest'
                                                }
                                            });
                                            if (res.ok) {
                                                const data = await res.json();
                                                this.isActive = !!data.is_active;
                                                if (window.customToast) {
                                                    window.customToast({
                                                        message: data.message || 'Status kupon berhasil diperbarui',
                                                        type: this.isActive ? 'success' : 'info'
                                                    });
                                                }
                                            } else {
                                                $refs.fallbackForm.submit();
                                            }
                                        } catch (e) {
                                            $refs.fallbackForm.submit();
                                        } finally {
                                            this.loading = false;
                                        }
                                    }
                                }">
                                <button type="button"
                                        @click="toggleStatus()"
                                        :disabled="loading"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider transition cursor-pointer disabled:opacity-50"
                                        :class="isActive ? 'bg-[#5F7F42]/15 text-[#5F7F42] border border-[#5F7F42]/30 hover:bg-[#5F7F42]/25' : 'bg-gray-200 text-gray-600 border border-gray-300 hover:bg-gray-300'"
                                        title="Klik untuk ubah status aktif/nonaktif">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="isActive ? 'bg-[#5F7F42] animate-pulse' : 'bg-gray-400'"></span>
                                    <span x-text="isActive ? 'Aktif' : 'Nonaktif'">{{ $promo->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </button>
                                <form x-ref="fallbackForm" method="POST" action="{{ route('kasir.promos.toggle', $promo) }}" class="hidden">
                                    @csrf
                                    @method('PATCH')
                                </form>
                            </td>

                            <!-- Aksi: Edit & Hapus -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Tombol Edit Modal -->
                                    <button type="button"
                                            @click="openEditModal({
                                                id: {{ $promo->id }},
                                                code: @js($promo->code),
                                                name: @js($promo->name),
                                                type: @js($promo->type),
                                                discount_value: {{ $promo->discount_value }},
                                                max_discount: {{ $promo->max_discount ?? 'null' }},
                                                min_order: {{ $promo->min_order ?? 0 }},
                                                usage_limit: {{ $promo->usage_limit ?? 'null' }},
                                                start_date: @js($promo->start_date ? $promo->start_date->format('Y-m-d') : ''),
                                                end_date: @js($promo->end_date ? $promo->end_date->format('Y-m-d') : ''),
                                                description: @js($promo->description ?? ''),
                                                is_active: {{ $promo->is_active ? 'true' : 'false' }}
                                            })"
                                            class="px-2.5 py-1.5 bg-[#FAF7F2] hover:bg-[#1F1812] text-[#1F1812] hover:text-[#FAF7F2] border border-[#E4DCCC] hover:border-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-2xs cursor-pointer active:scale-95">
                                        Edit ›
                                    </button>

                                    <!-- Tombol Hapus dengan Global Dialog -->
                                    <form method="POST" action="{{ route('kasir.promos.destroy', $promo) }}" class="inline"
                                          data-confirm="Hapus kupon '{{ $promo->code }}' dari katalog diskon?"
                                          data-confirm-title="Konfirmasi Hapus Kupon"
                                          data-confirm-type="danger"
                                          data-confirm-btn="Ya, Hapus Kupon">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 text-[#C4553D] hover:bg-red-50 border border-transparent hover:border-red-200 rounded-xl transition cursor-pointer"
                                                title="Hapus Kupon">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-[#8A7B66]">
                                <div class="text-4xl mb-2">🏷️</div>
                                <div class="font-serif text-lg font-bold text-[#1F1812]">Belum ada kupon diskon</div>
                                <p class="text-xs mt-1">Buat kode kupon diskon baru untuk memberikan potongan harga kepada pelanggan.</p>
                                <button type="button" @click="showCreateModal = true"
                                        class="mt-4 px-4 py-2 bg-[#D9973E] hover:bg-[#B5762A] text-[#1F1812] hover:text-white font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition cursor-pointer">
                                    + Buat Kupon Pertama
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($promos->hasPages())
            <div class="p-4 border-t border-[#E4DCCC]">
                {{ $promos->links() }}
            </div>
        @endif
    </div>

    <!-- 5. MODAL FORM BUAT KUPON BARU -->
    <div x-show="showCreateModal"
         x-cloak
         @keydown.window.escape="showCreateModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs transition-all">
        <div class="bg-white border border-[#E4DCCC] rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
             @click.away="showCreateModal = false">
            <!-- Header Modal -->
            <div class="p-4 bg-[#FAF7F2] border-b border-[#E4DCCC] flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-[#D9973E]/15 border border-[#D9973E]/30 flex items-center justify-center text-[#B5762A] text-base shrink-0">
                        🏷️
                    </span>
                    <div>
                        <h3 class="font-serif font-bold text-base text-[#1F1812]">Buat Kupon Diskon Baru</h3>
                        <p class="text-[11px] text-[#8A7B66]">Kode kupon ini dapat digunakan di kasir saat checkout.</p>
                    </div>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-[#8A7B66] hover:text-[#1F1812] text-xl font-bold p-1 cursor-pointer">✕</button>
            </div>

            <!-- Form Body -->
            <form method="POST" action="{{ route('kasir.promos.store') }}" class="p-5 sm:p-6 space-y-4 max-h-[85vh] overflow-y-auto">
                @csrf

                <!-- Kode Kupon & Tipe Diskon -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                            Kode Kupon (Wajib): <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" required maxlength="30"
                               placeholder="Contoh: HEMAT20"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl font-mono text-sm font-bold uppercase text-[#1F1812] tracking-wider transition"
                               oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                            Tipe Diskon: <span class="text-red-500">*</span>
                        </label>
                        <select name="type" x-model="createType" required
                                class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl text-xs font-mono font-bold text-[#1F1812] cursor-pointer transition">
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed">Nominal Rupiah (Rp)</option>
                        </select>
                    </div>
                </div>

                <!-- Nama Promo -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                        Nama Promo: <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required maxlength="100"
                           placeholder="Contoh: Diskon 20% Akhir Pekan"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl text-xs sm:text-sm text-[#1F1812] transition">
                </div>

                <!-- Nilai Diskon & Maksimal Diskon -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1"
                               x-text="createType === 'percentage' ? 'Besar Diskon (%): *' : 'Nominal Diskon (Rp): *'">
                        </label>
                        <div class="relative">
                            <input type="number" name="discount_value" required min="1"
                                   :max="createType === 'percentage' ? 100 : 100000000"
                                   placeholder="Misal: 20"
                                   class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl font-mono text-sm font-bold text-[#1F1812] transition">
                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 font-mono text-xs font-bold text-[#8A7B66]"
                                  x-text="createType === 'percentage' ? '%' : 'Rp'"></span>
                        </div>
                    </div>

                    <div x-show="createType === 'percentage'">
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                            Maks. Potongan (Rp):
                        </label>
                        <input type="number" name="max_discount" min="0"
                               placeholder="Opsional (misal: 15000)"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl font-mono text-xs sm:text-sm text-[#1F1812] transition">
                    </div>
                </div>

                <!-- Syarat Minimal Belanja & Batas Kuota -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                            Minimal Belanja (Rp):
                        </label>
                        <input type="number" name="min_order" min="0" value="0"
                               placeholder="0 = Tanpa syarat"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl font-mono text-xs sm:text-sm text-[#1F1812] transition">
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                            Batas Kuota Pemakaian:
                        </label>
                        <input type="number" name="usage_limit" min="1"
                               placeholder="Kosongkan jika tak terbatas"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl font-mono text-xs sm:text-sm text-[#1F1812] transition">
                    </div>
                </div>

                <!-- Periode Berlaku (Opsional) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] mb-1">
                            Tanggal Mulai (Opsional):
                        </label>
                        <input type="date" name="start_date"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3 py-1.5 rounded-xl font-mono text-xs text-[#1F1812] transition">
                    </div>
                    <div>
                        <label class="block font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] mb-1">
                            Tanggal Berakhir (Opsional):
                        </label>
                        <input type="date" name="end_date"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3 py-1.5 rounded-xl font-mono text-xs text-[#1F1812] transition">
                    </div>
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                        Deskripsi / Catatan Promo (Opsional):
                    </label>
                    <textarea name="description" rows="2" maxlength="255"
                              placeholder="Keterangan singkat tentang promo ini..."
                              class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl text-xs text-[#1F1812] transition"></textarea>
                </div>

                <!-- Tombol Submit -->
                <div class="pt-3 border-t border-[#E4DCCC] flex items-center justify-end gap-2.5">
                    <button type="button" @click="showCreateModal = false"
                            class="px-4 py-2 border border-[#E4DCCC] hover:bg-[#FAF7F2] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#FAF7F2] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-md cursor-pointer active:scale-98">
                        Simpan Kupon ›
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. MODAL FORM EDIT KUPON -->
    <div x-show="showEditModal"
         x-cloak
         @keydown.window.escape="showEditModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs transition-all">
        <div class="bg-white border border-[#E4DCCC] rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
             @click.away="showEditModal = false">
            <!-- Header Modal Edit -->
            <div class="p-4 bg-[#FAF7F2] border-b border-[#E4DCCC] flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-[#5F7F42]/15 border border-[#5F7F42]/30 flex items-center justify-center text-[#5F7F42] text-base shrink-0">
                        ✏️
                    </span>
                    <div>
                        <h3 class="font-serif font-bold text-base text-[#1F1812]" x-text="'Edit Kupon: ' + editForm.code">
                            Edit Kupon
                        </h3>
                        <p class="text-[11px] text-[#8A7B66]">Perbarui ketentuan atau batas penggunaan kupon diskon.</p>
                    </div>
                </div>
                <button type="button" @click="showEditModal = false" class="text-[#8A7B66] hover:text-[#1F1812] text-xl font-bold p-1 cursor-pointer">✕</button>
            </div>

            <!-- Form Edit Body -->
            <form method="POST" :action="editForm.actionUrl" class="p-5 sm:p-6 space-y-4 max-h-[85vh] overflow-y-auto">
                @csrf
                @method('PUT')

                <!-- Kode Kupon & Tipe Diskon -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                            Kode Kupon: <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" x-model="editForm.code" required maxlength="30"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl font-mono text-sm font-bold uppercase text-[#1F1812] tracking-wider transition"
                               oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                            Tipe Diskon: <span class="text-red-500">*</span>
                        </label>
                        <select name="type" x-model="editForm.type" required
                                class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl text-xs font-mono font-bold text-[#1F1812] cursor-pointer transition">
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed">Nominal Rupiah (Rp)</option>
                        </select>
                    </div>
                </div>

                <!-- Nama Promo -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                        Nama Promo: <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" x-model="editForm.name" required maxlength="100"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl text-xs sm:text-sm text-[#1F1812] transition">
                </div>

                <!-- Nilai Diskon & Maksimal Diskon -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1"
                               x-text="editForm.type === 'percentage' ? 'Besar Diskon (%): *' : 'Nominal Diskon (Rp): *'">
                        </label>
                        <div class="relative">
                            <input type="number" name="discount_value" x-model="editForm.discount_value" required min="1"
                                   :max="editForm.type === 'percentage' ? 100 : 100000000"
                                   class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl font-mono text-sm font-bold text-[#1F1812] transition">
                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 font-mono text-xs font-bold text-[#8A7B66]"
                                  x-text="editForm.type === 'percentage' ? '%' : 'Rp'"></span>
                        </div>
                    </div>

                    <div x-show="editForm.type === 'percentage'">
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                            Maks. Potongan (Rp):
                        </label>
                        <input type="number" name="max_discount" x-model="editForm.max_discount" min="0"
                               placeholder="Opsional (misal: 15000)"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl font-mono text-xs sm:text-sm text-[#1F1812] transition">
                    </div>
                </div>

                <!-- Syarat Minimal Belanja & Batas Kuota -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                            Minimal Belanja (Rp):
                        </label>
                        <input type="number" name="min_order" x-model="editForm.min_order" min="0"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl font-mono text-xs sm:text-sm text-[#1F1812] transition">
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                            Batas Kuota Pemakaian:
                        </label>
                        <input type="number" name="usage_limit" x-model="editForm.usage_limit" min="1"
                               placeholder="Kosongkan jika tak terbatas"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl font-mono text-xs sm:text-sm text-[#1F1812] transition">
                    </div>
                </div>

                <!-- Periode Berlaku (Opsional) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] mb-1">
                            Tanggal Mulai (Opsional):
                        </label>
                        <input type="date" name="start_date" x-model="editForm.start_date"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3 py-1.5 rounded-xl font-mono text-xs text-[#1F1812] transition">
                    </div>
                    <div>
                        <label class="block font-mono text-[10px] uppercase tracking-wider text-[#8A7B66] mb-1">
                            Tanggal Berakhir (Opsional):
                        </label>
                        <input type="date" name="end_date" x-model="editForm.end_date"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3 py-1.5 rounded-xl font-mono text-xs text-[#1F1812] transition">
                    </div>
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold mb-1">
                        Deskripsi / Catatan Promo (Opsional):
                    </label>
                    <textarea name="description" x-model="editForm.description" rows="2" maxlength="255"
                              placeholder="Keterangan singkat tentang promo ini..."
                              class="w-full bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D9973E]/20 px-3.5 py-2 rounded-xl text-xs text-[#1F1812] transition"></textarea>
                </div>

                <!-- Status Aktif Toggle -->
                <div class="pt-2 border-t border-[#F2EDE4] flex items-center justify-between">
                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold">
                            Status Kupon:
                        </label>
                        <span class="text-[11px] text-[#8A7B66]" x-text="editForm.is_active ? 'Kupon aktif & dapat diinput kasir' : 'Kupon dinonaktifkan sementara'"></span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" x-model="editForm.is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-[#D8CFC4] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-[#D8CFC4] after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5F7F42]"></div>
                    </label>
                </div>

                <!-- Tombol Submit Edit -->
                <div class="pt-3 border-t border-[#E4DCCC] flex items-center justify-end gap-2.5">
                    <button type="button" @click="showEditModal = false"
                            class="px-4 py-2 border border-[#E4DCCC] hover:bg-[#FAF7F2] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#FAF7F2] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition shadow-md cursor-pointer active:scale-98">
                        Simpan Perubahan ›
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
