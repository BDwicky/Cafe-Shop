@extends('kasir.app')

@section('title', 'Catat Pengeluaran Toko & Operasional')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{
    showAddExpenseModal: false,
    form: {
        title: '',
        category: 'operational',
        amount: '',
        expense_date: '{{ now()->toDateString() }}',
        payment_method: 'cash',
        supplier: '',
        notes: ''
    }
}">

    <!-- 1. HEADER SECTION -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2 border-b border-[#E4DCCC]">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-medium bg-[#FAF7F2] text-[#8A7B66] border border-[#E4DCCC] mb-2">
                <span class="w-2 h-2 rounded-full bg-[#C84B31]"></span>
                Biaya Operasional & Beban Usaha
            </div>
            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                Pengeluaran Toko & Operasional
            </h1>
            <p class="font-sans text-xs sm:text-sm text-[#8A7B66] mt-1 max-w-2xl">
                Catat seluruh biaya belanja bahan, token listrik, air, gas, kemasan, dan pemeliharaan kafe untuk menghitung laba bersih secara akurat.
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button" @click="showAddExpenseModal = true"
                    class="px-4 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#FAF7F2] hover:text-[#1F1812] rounded-xl font-mono text-xs uppercase tracking-wider font-bold transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Catat Pengeluaran</span>
            </button>
        </div>
    </div>

    <!-- FLASH STATUS NOTIFICATION -->
    @if (session('success'))
        <div class="p-4 rounded-2xl bg-[#5F7F42]/10 border border-[#5F7F42]/30 text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-full bg-[#5F7F42] text-white flex items-center justify-center font-bold text-xs shrink-0">✓</div>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="w-6 h-6 rounded-full hover:bg-black/5 text-[#8A7B66] hover:text-[#1F1812] flex items-center justify-center font-bold transition">✕</button>
        </div>
    @endif

    <!-- 2. KARTU STATISTIK RINGKASAN PENGELUARAN -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Seluruh Pengeluaran -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 shadow-xs transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="font-mono text-[11px] uppercase tracking-wider font-semibold text-[#8A7B66]">Total Pengeluaran</span>
                <span class="w-8 h-8 rounded-full bg-[#C84B31]/10 text-[#C84B31] flex items-center justify-center text-sm font-bold">💸</span>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span class="font-mono text-xs font-semibold text-[#C84B31]">Rp</span>
                <span class="font-mono text-2xl sm:text-3xl font-bold text-[#1F1812]">
                    {{ number_format($stats['total_amount'], 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-2 text-[11px] text-[#8A7B66] font-mono flex items-center gap-1.5">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#C84B31]"></span>
                {{ $stats['count'] }} transaksi tercatat
            </div>
        </div>

        <!-- Belanja Bahan Baku (Restock) -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 shadow-xs transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="font-mono text-[11px] uppercase tracking-wider font-semibold text-[#8A7B66]">Belanja Bahan (Restock)</span>
                <span class="w-8 h-8 rounded-full bg-[#5F7F42]/10 text-[#5F7F42] flex items-center justify-center text-sm font-bold">📦</span>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span class="font-mono text-xs font-semibold text-[#5F7F42]">Rp</span>
                <span class="font-mono text-2xl sm:text-3xl font-bold text-[#1F1812]">
                    {{ number_format($stats['restock_amount'], 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-2 text-[11px] text-[#8A7B66] font-mono flex items-center gap-1.5">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#5F7F42]"></span>
                Modal HPP bahan baku
            </div>
        </div>

        <!-- Operasional & Utilitas -->
        <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 shadow-xs transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="font-mono text-[11px] uppercase tracking-wider font-semibold text-[#8A7B66]">Operasional & Listrik</span>
                <span class="w-8 h-8 rounded-full bg-[#D9973E]/10 text-[#D9973E] flex items-center justify-center text-sm font-bold">⚡</span>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span class="font-mono text-xs font-semibold text-[#D9973E]">Rp</span>
                <span class="font-mono text-2xl sm:text-3xl font-bold text-[#1F1812]">
                    {{ number_format($stats['operational_amount'], 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-2 text-[11px] text-[#8A7B66] font-mono flex items-center gap-1.5">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#D9973E]"></span>
                Biaya harian & pemeliharaan
            </div>
        </div>

        <!-- Navigasi Laba Bersih -->
        <div class="bg-[#1F1812] rounded-2xl p-5 shadow-xs flex flex-col justify-between text-[#FAF7F2] transition hover:shadow-md">
            <div>
                <div class="flex items-center justify-between">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[#D9973E] font-bold">Laporan Laba Toko</span>
                    <span class="text-sm">📊</span>
                </div>
                <div class="text-xs font-sans text-[#A89A85] mt-2">
                    Cek perbandingan omzet penjualan vs seluruh biaya toko
                </div>
            </div>
            <a href="{{ route('kasir.laporan') }}"
               class="mt-4 px-3.5 py-2 bg-[#D9973E] hover:bg-amber-400 text-[#1F1812] rounded-xl font-mono text-xs font-bold uppercase tracking-wider text-center transition shadow-xs flex items-center justify-center gap-1.5">
                <span>Buka Laporan Laba</span>
                <span>›</span>
            </a>
        </div>
    </div>

    <!-- 3. FILTER & PENCARIAN -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl p-4 shadow-xs">
        <form method="GET" action="{{ route('kasir.expenses.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[220px] relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8A7B66]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari judul, no bukti, atau supplier..."
                       class="w-full bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl pl-10 pr-3.5 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
            </div>

            <div class="w-full sm:w-48">
                <select name="category" onchange="this.form.submit()"
                        class="w-full bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3.5 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
                    <option value="all">Semua Kategori</option>
                    @foreach ($categories as $k => $lbl)
                        <option value="{{ $k }}" {{ $category === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
                <span class="font-mono text-xs text-[#8A7B66]">s/d</span>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
            </div>

            <button type="submit"
                    class="px-5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#FAF7F2] hover:text-[#1F1812] rounded-xl font-mono text-xs uppercase tracking-wider font-bold transition shadow-xs">
                Filter
            </button>
            @if ($search !== '' || $category !== 'all' || $dateFrom || $dateTo)
                <a href="{{ route('kasir.expenses.index') }}"
                   class="px-3.5 py-2 bg-[#FAF7F2] border border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] rounded-xl font-mono text-xs transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- 4. TABEL PENGELUARAN -->
    <div class="bg-white border border-[#E4DCCC] rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#FAF7F2] border-b border-[#E4DCCC] text-[#8A7B66] font-mono text-[11px] uppercase tracking-wider">
                        <th class="py-3.5 px-5 font-semibold">No. Bukti / Tanggal</th>
                        <th class="py-3.5 px-4 font-semibold">Pengeluaran & Keterangan</th>
                        <th class="py-3.5 px-4 font-semibold">Kategori</th>
                        <th class="py-3.5 px-4 font-semibold">Metode & Supplier</th>
                        <th class="py-3.5 px-4 text-right font-semibold">Nominal (Rp)</th>
                        <th class="py-3.5 px-4 font-semibold">Petugas</th>
                        <th class="py-3.5 px-5 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4DCCC]/70 font-mono text-[#1F1812]">
                    @forelse ($expenses as $exp)
                        <tr class="hover:bg-[#FAF7F2]/50 transition">
                            <td class="py-4 px-5 whitespace-nowrap">
                                <div class="font-bold text-[#1F1812]">{{ $exp->expense_number }}</div>
                                <div class="text-[10px] text-[#8A7B66] mt-0.5">{{ $exp->expense_date->translatedFormat('d M Y') }}</div>
                            </td>
                            <td class="py-4 px-4 font-sans max-w-sm">
                                <div class="font-bold text-sm text-[#1F1812]">{{ $exp->title }}</div>
                                @if ($exp->notes)
                                    <div class="text-xs text-[#8A7B66] mt-0.5">{{ $exp->notes }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-2.5 py-1 rounded-full bg-[#FAF7F2] border border-[#E4DCCC] text-[11px] font-mono font-semibold text-[#1F1812]">
                                    {{ $exp->category_name }}
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="uppercase text-[10px] font-bold text-[#1F1812]">{{ $exp->payment_method }}</div>
                                <div class="text-[11px] text-[#8A7B66] mt-0.5">{{ $exp->supplier ?? '-' }}</div>
                            </td>
                            <td class="py-4 px-4 text-right font-bold text-sm text-[#C84B31] whitespace-nowrap">
                                Rp {{ number_format($exp->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-4 text-[#8A7B66] text-xs whitespace-nowrap">
                                {{ $exp->user->name ?? 'Kasir' }}
                            </td>
                            <td class="py-4 px-5 text-center">
                                <form method="POST" action="{{ route('kasir.expenses.destroy', $exp) }}"
                                      onsubmit="return confirm('Hapus catatan pengeluaran #{{ $exp->expense_number }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus"
                                            class="px-2.5 py-1.5 rounded-lg bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold transition shadow-2xs">
                                        ✕ Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-[#8A7B66]">
                                <div class="text-4xl mb-3">🧾</div>
                                <div class="font-serif text-lg font-bold text-[#1F1812]">Belum Ada Pengeluaran Dicatat</div>
                                <p class="font-sans text-xs text-[#8A7B66] mt-1 max-w-md mx-auto">
                                    Catat belanja bahan baku harian, tagihan listrik, air, gas, kemasan, atau biaya operasional kafe lainnya.
                                </p>
                                <button type="button" @click="showAddExpenseModal = true"
                                        class="mt-4 px-5 py-2.5 bg-[#1F1812] text-[#FAF7F2] rounded-xl font-mono text-xs uppercase font-bold hover:bg-[#D9973E] hover:text-[#1F1812] transition shadow-xs">
                                    + Catat Pengeluaran Pertama
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($expenses->hasPages())
            <div class="p-4 border-t border-[#E4DCCC]">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: CATAT PENGELUARAN BARU -->
    <div x-show="showAddExpenseModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#1F1812]/60 backdrop-blur-xs"
         @keydown.escape.window="showAddExpenseModal = false">
        <div class="bg-white border border-[#E4DCCC] rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden relative animate-in fade-in zoom-in-95 duration-150"
             @click.away="showAddExpenseModal = false">
            
            <div class="px-6 py-4 bg-[#FAF7F2] border-b border-[#E4DCCC] flex items-center justify-between">
                <div>
                    <h3 class="font-serif font-bold text-xl text-[#1F1812]">Catat Pengeluaran Toko</h3>
                    <p class="text-xs font-sans text-[#8A7B66] mt-0.5">Input biaya operasional dan belanja kafe</p>
                </div>
                <button type="button" @click="showAddExpenseModal = false"
                        class="w-8 h-8 rounded-full bg-white border border-[#E4DCCC] hover:bg-[#FAF7F2] text-[#8A7B66] hover:text-[#1F1812] flex items-center justify-center font-bold transition">
                    ✕
                </button>
            </div>

            <form method="POST" action="{{ route('kasir.expenses.store') }}" class="p-6 space-y-4 font-mono text-xs">
                @csrf

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1.5">Judul Pengeluaran <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required placeholder="Contoh: Beli Token Listrik PLN, Beli Gas Elpiji, Belanja Cup 16oz"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3.5 py-2.5 text-xs focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1.5">Kategori <span class="text-red-500">*</span></label>
                        <select name="category" required
                                class="w-full bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3.5 py-2.5 text-xs focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
                            @foreach ($categories as $k => $lbl)
                                <option value="{{ $k }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1.5">Nominal Biaya (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="amount" required min="100" placeholder="Contoh: 150000"
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3.5 py-2.5 text-xs focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1.5">Tanggal Pengeluaran <span class="text-red-500">*</span></label>
                        <input type="date" name="expense_date" value="{{ now()->toDateString() }}" required
                               class="w-full bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3.5 py-2.5 text-xs focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1.5">Metode Bayar <span class="text-red-500">*</span></label>
                        <select name="payment_method" required
                                class="w-full bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3.5 py-2.5 text-xs focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
                            <option value="cash">Tunai (Kas Kasir)</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS / E-Wallet</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1.5">Supplier / Toko Penjual</label>
                    <input type="text" name="supplier" placeholder="Contoh: Toko Plastik Jaya, Indomaret, PLN"
                           class="w-full bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3.5 py-2.5 text-xs focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition">
                </div>

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1.5">Catatan Tambahan</label>
                    <textarea name="notes" rows="2" placeholder="Keterangan nota atau keperluan belanja..."
                              class="w-full bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl px-3.5 py-2.5 text-xs focus:outline-hidden focus:border-[#D9973E] focus:ring-2 focus:ring-[#D9973E]/10 transition"></textarea>
                </div>

                <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-[#E4DCCC]">
                    <button type="button" @click="showAddExpenseModal = false"
                            class="px-4 py-2.5 rounded-xl bg-white border border-[#E4DCCC] text-[#8A7B66] hover:text-[#1F1812] hover:bg-[#FAF7F2] font-mono text-xs font-semibold transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-[#1F1812] hover:bg-[#D9973E] text-[#FAF7F2] hover:text-[#1F1812] font-mono text-xs font-bold uppercase tracking-wider transition shadow-sm">
                        Simpan Pengeluaran
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
