@extends('kasir.app')

@section('title', 'Catat Pengeluaran Toko & Operasional')

@section('content')
<div class="space-y-6" x-data="{
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

    <!-- 1. HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-[#E4DCCC]">
        <div>
            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                Pengeluaran Toko & Operasional
            </h1>
            <p class="font-mono text-xs text-[#8A7B66] mt-0.5">
                Catat seluruh biaya belanja bahan, token listrik, air, gas, kemasan, dan pemeliharaan kafe untuk menghitung laba bersih.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="showAddExpenseModal = true"
                    class="px-4 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold transition shadow-sm flex items-center gap-1.5">
                <span>+ Catat Pengeluaran</span>
            </button>
        </div>
    </div>

    <!-- FLASH STATUS -->
    @if (session('success'))
        <div class="p-3.5 bg-[#5F7F42]/10 border border-[#5F7F42] text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-[#5F7F42] font-bold text-sm">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
        </div>
    @endif

    <!-- 2. KARTU STATISTIK RINGKASAN PENGELUARAN -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- Total Seluruh Pengeluaran -->
        <div class="bg-white border border-[#E4DCCC] p-4 shadow-xs">
            <div class="flex items-center justify-between text-[#C84B31]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Total Pengeluaran</span>
                <span class="text-base">💸</span>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="font-mono text-xs text-[#C84B31] font-semibold">Rp</span>
                <span class="font-mono text-2xl sm:text-3xl font-bold text-[#1F1812]">
                    {{ number_format($stats['total_amount'], 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-1 text-[11px] text-[#8A7B66] font-mono">{{ $stats['count'] }} transaksi tercatat</div>
        </div>

        <!-- Belanja Bahan Baku (Restock) -->
        <div class="bg-white border border-[#E4DCCC] p-4 shadow-xs">
            <div class="flex items-center justify-between text-[#5F7F42]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Belanja Bahan (Restock)</span>
                <span class="text-base">📦</span>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="font-mono text-xs text-[#5F7F42] font-semibold">Rp</span>
                <span class="font-mono text-2xl sm:text-3xl font-bold text-[#1F1812]">
                    {{ number_format($stats['restock_amount'], 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-1 text-[11px] text-[#8A7B66] font-mono">Modal bahan baku</div>
        </div>

        <!-- Operasional & Utilitas -->
        <div class="bg-white border border-[#E4DCCC] p-4 shadow-xs">
            <div class="flex items-center justify-between text-[#D9973E]">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] font-semibold">Operasional & Listrik</span>
                <span class="text-base">⚡</span>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="font-mono text-xs text-[#D9973E] font-semibold">Rp</span>
                <span class="font-mono text-2xl sm:text-3xl font-bold text-[#1F1812]">
                    {{ number_format($stats['operational_amount'], 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-1 text-[11px] text-[#8A7B66] font-mono">Biaya harian toko</div>
        </div>

        <!-- Navigasi Laba Bersih -->
        <div class="bg-[#1F1812] text-[#F7F3EC] p-4 shadow-xs flex flex-col justify-between">
            <div>
                <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#D9973E] font-semibold">
                    Laporan Laba Toko
                </div>
                <div class="text-xs font-mono text-[#A89A85] mt-1">
                    Cek perbandingan omzet vs total biaya toko
                </div>
            </div>
            <a href="{{ route('kasir.laporan') }}"
               class="mt-3 px-3 py-1.5 bg-[#D9973E] hover:bg-amber-400 text-[#1F1812] font-mono text-xs font-bold uppercase tracking-wider text-center transition">
                Buka Laporan Laba ›
            </a>
        </div>
    </div>

    <!-- 3. FILTER & PENCARIAN -->
    <div class="bg-white border border-[#E4DCCC] p-4">
        <form method="GET" action="{{ route('kasir.expenses.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari judul, no bukti, atau supplier..."
                       class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
            </div>

            <div class="w-48">
                <select name="category" onchange="this.form.submit()"
                        class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
                    <option value="all">Semua Kategori</option>
                    @foreach ($categories as $k => $lbl)
                        <option value="{{ $k }}" {{ $category === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-1.5">
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="bg-[#F7F3EC] border border-[#E4DCCC] px-2.5 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
                <span class="font-mono text-xs text-[#8A7B66]">s/d</span>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="bg-[#F7F3EC] border border-[#E4DCCC] px-2.5 py-2 text-xs font-mono text-[#1F1812] focus:outline-hidden focus:border-[#D9973E]">
            </div>

            <button type="submit"
                    class="px-4 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold transition">
                Filter
            </button>
            @if ($search !== '' || $category !== 'all' || $dateFrom || $dateTo)
                <a href="{{ route('kasir.expenses.index') }}"
                   class="px-3 py-2 bg-[#F7F3EC] text-[#8A7B66] hover:text-[#1F1812] font-mono text-xs transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- 4. TABEL PENGELUARAN -->
    <div class="bg-white border border-[#E4DCCC] overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#2A211A] text-[#F7F3EC] font-mono uppercase text-[10px] tracking-wider">
                        <th class="py-3 px-4">No. Bukti / Tanggal</th>
                        <th class="py-3 px-4">Pengeluaran & Keterangan</th>
                        <th class="py-3 px-3">Kategori</th>
                        <th class="py-3 px-3">Metode & Supplier</th>
                        <th class="py-3 px-3 text-right">Nominal (Rp)</th>
                        <th class="py-3 px-3">Petugas</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4DCCC] font-mono text-[#1F1812]">
                    @forelse ($expenses as $exp)
                        <tr class="hover:bg-[#F7F3EC]/70 transition">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-bold text-[#1F1812]">{{ $exp->expense_number }}</div>
                                <div class="text-[10px] text-[#8A7B66]">{{ $exp->expense_date->translatedFormat('d M Y') }}</div>
                            </td>
                            <td class="py-3 px-4 font-sans max-w-sm">
                                <div class="font-bold text-sm text-[#1F1812]">{{ $exp->title }}</div>
                                @if ($exp->notes)
                                    <div class="text-xs text-[#8A7B66] mt-0.5">{{ $exp->notes }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2 py-0.5 bg-[#F7F3EC] border border-[#E4DCCC] text-[10px]">
                                    {{ $exp->category_name }}
                                </span>
                            </td>
                            <td class="py-3 px-3">
                                <div class="uppercase text-[10px] font-semibold text-[#1F1812]">{{ $exp->payment_method }}</div>
                                <div class="text-[10px] text-[#8A7B66]">{{ $exp->supplier ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-sm text-[#C84B31]">
                                Rp {{ number_format($exp->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-[#8A7B66] text-[11px] whitespace-nowrap">
                                {{ $exp->user->name ?? 'Kasir' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('kasir.expenses.destroy', $exp) }}"
                                      onsubmit="return confirm('Hapus catatan pengeluaran #{{ $exp->expense_number }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus"
                                            class="px-2 py-1 bg-white border border-red-200 text-red-600 hover:bg-red-50 text-[11px] transition">
                                        ✕ Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-[#8A7B66]">
                                <div class="text-3xl mb-2">🧾</div>
                                <div class="font-serif text-base font-bold text-[#1F1812]">Belum ada pengeluaran dicatat</div>
                                <div class="font-mono text-xs mt-1">Catat belanja bahan, token listrik, dan biaya operasional toko lainnya.</div>
                                <button type="button" @click="showAddExpenseModal = true"
                                        class="mt-4 px-4 py-2 bg-[#1F1812] text-[#F7F3EC] font-mono text-xs uppercase font-bold hover:bg-[#D9973E] hover:text-[#1F1812] transition">
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
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="showAddExpenseModal = false">
        <div class="bg-white border border-[#3A3026] w-full max-w-lg shadow-2xl p-6 relative"
             @click.away="showAddExpenseModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-[#E4DCCC]">
                <h3 class="font-serif font-bold text-xl text-[#1F1812]">Catat Pengeluaran Toko</h3>
                <button type="button" @click="showAddExpenseModal = false" class="text-[#8A7B66] hover:text-black font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('kasir.expenses.store') }}" class="mt-4 space-y-3 font-mono text-xs">
                @csrf

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">Judul Pengeluaran <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required placeholder="Contoh: Beli Token Listrik PLN, Beli Gas Elpiji, Belanja Cup 16oz"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Kategori <span class="text-red-500">*</span></label>
                        <select name="category" required
                                class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                            @foreach ($categories as $k => $lbl)
                                <option value="{{ $k }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Nominal Biaya (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="amount" required min="100" placeholder="Contoh: 150000"
                               class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Tanggal Pengeluaran <span class="text-red-500">*</span></label>
                        <input type="date" name="expense_date" value="{{ now()->toDateString() }}" required
                               class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                    </div>
                    <div>
                        <label class="block text-[#1F1812] font-semibold mb-1">Metode Bayar <span class="text-red-500">*</span></label>
                        <select name="payment_method" required
                                class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                            <option value="cash">Tunai (Kas Kasir)</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS / E-Wallet</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">Supplier / Toko Penjual</label>
                    <input type="text" name="supplier" placeholder="Contoh: Toko Plastik Jaya, Indomaret, PLN"
                           class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]">
                </div>

                <div>
                    <label class="block text-[#1F1812] font-semibold mb-1">Catatan Tambahan</label>
                    <textarea name="notes" rows="2" placeholder="Keterangan nota atau keperluan belanja..."
                              class="w-full bg-[#F7F3EC] border border-[#E4DCCC] px-3 py-2 text-xs focus:outline-hidden focus:border-[#D9973E]"></textarea>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-[#E4DCCC]">
                    <button type="button" @click="showAddExpenseModal = false"
                            class="px-4 py-2 bg-[#F7F3EC] text-[#8A7B66] hover:text-[#1F1812]">Batal</button>
                    <button type="submit"
                            class="px-5 py-2 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-bold uppercase tracking-wider">
                        Simpan Pengeluaran
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
