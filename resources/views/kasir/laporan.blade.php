@extends('kasir.app')

@section('title', 'Laporan Penjualan')

@section('content')
<div>
    <!-- TAMPILAN DASHBOARD (LAYAR MONITOR / TABLET) -->
    <div class="screen-dashboard-container p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl tracking-tight font-medium">Laporan Penjualan</h1>
                <p class="font-mono text-xs text-[#8A7B66] mt-0.5">Rekapitulasi penjualan kasir, omzet, dan metode pembayaran.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 print:hidden">
                <form method="GET" action="{{ route('kasir.laporan') }}" class="flex items-center gap-2">
                    <input type="date" name="from" value="{{ $from->format('Y-m-d') }}"
                           class="bg-white border border-[#E4DCCC] px-3 py-2 text-sm focus:outline-none focus:border-[#B5762A]">
                    <span class="text-[#8A7B66] text-sm">s/d</span>
                    <input type="date" name="to" value="{{ $to->format('Y-m-d') }}"
                           class="bg-white border border-[#E4DCCC] px-3 py-2 text-sm focus:outline-none focus:border-[#B5762A]">
                    <button class="border border-[#2A211A] px-4 py-2 font-mono text-[11px] uppercase tracking-[0.15em] hover:bg-[#2A211A] hover:text-[#F7F3EC] transition-colors">Tampilkan</button>
                </form>

                <!-- Tombol Cetak Format Struk Thermal 80mm -->
                <a href="{{ route('kasir.laporan.receipt', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}"
                   target="_blank"
                   class="bg-[#1F1812] text-[#F7F3EC] border border-[#1F1812] px-4 py-2 font-mono text-[11px] uppercase tracking-[0.15em] hover:bg-[#D9973E] hover:text-[#1F1812] transition-colors flex items-center gap-2 shadow-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Cetak Struk (80mm) ›</span>
                </a>
            </div>
        </div>

    <!-- Stat utama -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="bg-white border border-[#E4DCCC] p-5">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Transaksi</div>
            <div class="mt-2 font-mono text-4xl">{{ number_format($totals->trx, 0, ',', '.') }}</div>
        </div>
        <div class="bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] p-5">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">Omzet Penjualan</div>
            <div class="mt-2 font-mono text-4xl text-[#D9973E]">Rp {{ number_format($totals->omzet, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white border border-[#E4DCCC] p-5">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Rata-rata / Basket</div>
            <div class="mt-2 font-mono text-4xl">Rp {{ number_format(round($totals->avg_basket), 0, ',', '.') }}</div>
        </div>
        <div class="bg-white border border-[#E4DCCC] p-5">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Item Terjual</div>
            <div class="mt-2 font-mono text-4xl">{{ number_format($itemsSold, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Analisis Keuangan: HPP Modal, Pengeluaran & Laba Bersih Toko -->
    <div class="mb-6 bg-white border border-[#E4DCCC] p-5 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-[#E4DCCC] gap-2 mb-4">
            <div>
                <h3 class="font-serif font-bold text-lg text-[#1F1812]">Analisis Keuangan & Laba Toko</h3>
                <p class="font-mono text-xs text-[#8A7B66] mt-0.5">
                    Perhitungan otomatis dari resep BOM bahan baku dan buku pengeluaran kasir.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('kasir.expenses.index') }}"
                   class="px-3 py-1.5 bg-[#F7F3EC] border border-[#E4DCCC] hover:border-[#1F1812] font-mono text-xs text-[#1F1812] transition">
                    + Pengeluaran Toko
                </a>
                <a href="{{ route('kasir.inventory.index') }}"
                   class="px-3 py-1.5 bg-[#F7F3EC] border border-[#E4DCCC] hover:border-[#1F1812] font-mono text-xs text-[#1F1812] transition">
                    📦 Master Stok Bahan
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. HPP Bahan Baku Terpakai -->
            <div class="p-4 bg-[#F7F3EC]/70 border border-[#E4DCCC]">
                <div class="flex items-center justify-between text-[#8A7B66]">
                    <span class="font-mono text-[10px] uppercase tracking-wider font-semibold">HPP Bahan Terjual</span>
                    <span class="text-xs">🌱</span>
                </div>
                <div class="mt-2 font-mono text-2xl font-bold text-[#1F1812]">
                    Rp {{ number_format($finance['cogs'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-[11px] font-mono text-[#8A7B66]">
                    Modal resep bahan yang terpakai
                </div>
            </div>

            <!-- 2. Laba Kotor (Gross Profit) -->
            <div class="p-4 bg-emerald-50/50 border border-emerald-200">
                <div class="flex items-center justify-between text-[#5F7F42]">
                    <span class="font-mono text-[10px] uppercase tracking-wider font-semibold">Laba Kotor (Gross)</span>
                    <span class="font-mono text-xs font-bold">{{ $finance['gross_margin'] }}%</span>
                </div>
                <div class="mt-2 font-mono text-2xl font-bold text-[#5F7F42]">
                    Rp {{ number_format($finance['gross_profit'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-[11px] font-mono text-[#5F7F42]">
                    Omzet dikurangi HPP bahan
                </div>
            </div>

            <!-- 3. Total Pengeluaran Toko (Expenses) -->
            <div class="p-4 bg-red-50/40 border border-red-200">
                <div class="flex items-center justify-between text-[#C84B31]">
                    <span class="font-mono text-[10px] uppercase tracking-wider font-semibold">Pengeluaran Toko</span>
                    <span class="text-xs">🧾</span>
                </div>
                <div class="mt-2 font-mono text-2xl font-bold text-[#C84B31]">
                    Rp {{ number_format($finance['total_expenses'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-[11px] font-mono text-[#8A7B66]">
                    Restock: Rp {{ number_format($finance['restock_expenses'], 0, ',', '.') }} • Opr: Rp {{ number_format($finance['operational_expenses'], 0, ',', '.') }}
                </div>
            </div>

            <!-- 4. Laba Bersih Toko (Net Profit) -->
            @php
                $isNetPositive = $finance['net_profit'] >= 0;
            @endphp
            <div class="p-4 {{ $isNetPositive ? 'bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026]' : 'bg-red-100 text-red-900 border border-red-300' }}">
                <div class="flex items-center justify-between {{ $isNetPositive ? 'text-[#D9973E]' : 'text-red-700' }}">
                    <span class="font-mono text-[10px] uppercase tracking-wider font-bold">Laba Bersih Toko</span>
                    <span class="font-mono text-xs font-bold">{{ $finance['net_margin'] }}%</span>
                </div>
                <div class="mt-2 font-mono text-2xl font-bold {{ $isNetPositive ? 'text-[#D9973E]' : 'text-red-700' }}">
                    Rp {{ number_format($finance['net_profit'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-[11px] font-mono {{ $isNetPositive ? 'text-[#A89A85]' : 'text-red-600' }}">
                    Omzet dikurangi total biaya operasional
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Metode bayar -->
        <div class="bg-white border border-[#E4DCCC] p-5">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] mb-3">Metode Bayar</div>
            <table class="w-full text-sm">
                @forelse ($byMethod as $m)
                    <tr class="border-b border-[#E4DCCC] last:border-0">
                        <td class="py-2 uppercase font-mono text-xs">{{ $m->payment_method }}</td>
                        <td class="py-2 text-right font-mono">{{ number_format($m->c, 0, ',', '.') }} trx</td>
                        <td class="py-2 text-right font-mono">Rp {{ number_format($m->t, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td class="py-4 text-[#8A7B66]">Tidak ada transaksi.</td></tr>
                @endforelse
            </table>
        </div>

        <!-- Best seller -->
        <div class="bg-white border border-[#E4DCCC] p-5">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] mb-3">10 Menu Terlaris</div>
            <table class="w-full text-sm">
                @forelse ($best as $b)
                    <tr class="border-b border-[#E4DCCC] last:border-0">
                        <td class="py-2">{{ $b->menu_name }}</td>
                        <td class="py-2 text-right font-mono">{{ $b->qty }} pcs</td>
                        <td class="py-2 text-right font-mono">Rp {{ number_format($b->omzet, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td class="py-4 text-[#8A7B66]">Tidak ada penjualan.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    <!-- Per hari (bar CSS murni) -->
    @if ($perDay->count())
        <div class="bg-white border border-[#E4DCCC] p-5 mt-6">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] mb-4">Omzet per Hari</div>
            @php
                $max = $perDay->max('t') ?: 1;
            @endphp
            <div class="space-y-2">
                @foreach ($perDay as $d)
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-xs text-[#8A7B66] w-24 shrink-0">{{ \Illuminate\Support\Str::of($d->d)->explode('-')->reverse()->implode('/') }}</span>
                        <div class="flex-1 h-5 bg-[#F7F3EC] border border-[#E4DCCC]">
                            <div class="h-full bg-[#B5762A]" style="width: {{ round($d->t / $max * 100) }}%"></div>
                        </div>
                        <span class="font-mono text-xs w-32 text-right">Rp {{ number_format($d->t, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    </div> <!-- end .screen-dashboard-container -->

    <!-- FORMAT STRUK THERMAL 80MM (TAMPIL HANYA SAAT PRINT LANGSUNG DARI HALAMAN INI) -->
    <div class="print-receipt-container hidden">
        <div class="receipt-inner">
            <div class="center">
                <div class="brand">{{ config('cafe.name') }}</div>
                <div class="meta">{{ config('cafe.address') }}</div>
                <div class="title">*** LAPORAN KASIR ***</div>
            </div>

            <div class="dashed meta">
                <table style="font-size: 8.5pt;">
                    <tr>
                        <td>Tgl Cetak</td>
                        <td class="r">{{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <td>Periode</td>
                        <td class="r">
                            @if ($from->format('Y-m-d') === $to->format('Y-m-d'))
                                {{ $from->format('d/m/Y') }}
                            @else
                                {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Kasir</td>
                        <td class="r">{{ auth()->user()->name ?? 'Kasir' }}</td>
                    </tr>
                    <tr>
                        <td>Status Shift</td>
                        <td class="r">TUTUP / REKONSILIASI</td>
                    </tr>
                </table>
            </div>

            <div class="dashed">
                <div class="section-title">Ringkasan Penjualan</div>
                <table>
                    <tr>
                        <td>Total Transaksi</td>
                        <td class="r"><b>{{ number_format($totals->trx, 0, ',', '.') }}</b> Trx</td>
                    </tr>
                    <tr>
                        <td>Total Item Terjual</td>
                        <td class="r"><b>{{ number_format($itemsSold, 0, ',', '.') }}</b> pcs</td>
                    </tr>
                    <tr>
                        <td>Rata-rata / Trx</td>
                        <td class="r">Rp {{ number_format(round($totals->avg_basket), 0, ',', '.') }}</td>
                    </tr>
                    <tr class="tot" style="border-top: 1px dashed #000; padding-top: 1.5mm;">
                        <td style="padding-top: 1.5mm;">TOTAL OMZET</td>
                        <td class="r" style="padding-top: 1.5mm;">Rp {{ number_format($totals->omzet, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <div class="dashed">
                <div class="section-title">Metode Pembayaran</div>
                <table>
                    @forelse ($byMethod as $m)
                        <tr>
                            <td style="text-transform: uppercase;">
                                {{ $m->payment_method === 'cash' ? 'Tunai (Cash)' : strtoupper($m->payment_method) }}
                                <span style="font-size: 7.5pt; color: #333;">({{ $m->c }} trx)</span>
                            </td>
                            <td class="r">Rp {{ number_format($m->t, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" style="font-style: italic; color: #555;">Tidak ada transaksi.</td></tr>
                    @endforelse
                </table>
            </div>

            <div class="dashed">
                <div class="section-title">10 Menu Terlaris</div>
                <table>
                    @forelse ($best as $idx => $b)
                        <tr>
                            <td style="padding-bottom: 1mm;">
                                {{ $idx + 1 }}. {{ $b->menu_name }}<br>
                                &nbsp;&nbsp;&nbsp;<span style="font-size: 7.5pt; color: #444;">{{ $b->qty }} pcs terjual</span>
                            </td>
                            <td class="r" style="vertical-align: top;">
                                Rp {{ number_format($b->omzet, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" style="font-style: italic; color: #555;">Tidak ada penjualan.</td></tr>
                    @endforelse
                </table>
            </div>

            @if ($perDay->count() > 1)
                <div class="dashed">
                    <div class="section-title">Rincian Per Hari</div>
                    <table>
                        @foreach ($perDay as $d)
                            <tr>
                                <td>
                                    {{ \Illuminate\Support\Str::of($d->d)->explode('-')->reverse()->implode('/') }}
                                    <span style="font-size: 7.5pt; color: #444;">({{ $d->c }} trx)</span>
                                </td>
                                <td class="r">Rp {{ number_format($d->t, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            <div class="dashed">
                <div class="signatures">
                    <div class="sig-box">
                        <div class="meta">Kasir Bertugas</div>
                        <div class="sig-line"></div>
                        <div class="meta" style="margin-top: 1mm;">{{ auth()->user()->name ?? 'Kasir' }}</div>
                    </div>
                    <div class="sig-box">
                        <div class="meta">Supervisor / Owner</div>
                        <div class="sig-line"></div>
                        <div class="meta" style="margin-top: 1mm;">( .................... )</div>
                    </div>
                </div>
            </div>

            <div class="center meta" style="margin-top: 3.5mm; border-top: 1px dashed #000; padding-top: 2.5mm;">
                *** TUTUP KASIR // REKONSILIASI ***<br>
                Dicetak oleh Sistem Kasir {{ config('cafe.name') }}<br>
                Simpan struk ini sebagai bukti rekonsiliasi kas.
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    @page {
        size: 80mm auto;
        margin: 0;
    }
    aside, header, nav, .print\:hidden, form {
        display: none !important;
    }
    body, html, main {
        background: #fff !important;
        overflow: visible !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .screen-dashboard-container {
        display: none !important;
    }
    .print-receipt-container {
        display: block !important;
        width: 72mm !important;
        margin: 0 auto !important;
        padding: 2mm 1mm !important;
        background: #fff !important;
        color: #000 !important;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        font-size: 9pt !important;
        line-height: 1.4 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .print-receipt-container .center { text-align: center; }
    .print-receipt-container .brand { font-size: 13pt; font-weight: bold; letter-spacing: 0.12em; text-transform: uppercase; }
    .print-receipt-container .title { font-size: 10pt; font-weight: bold; letter-spacing: 0.08em; text-transform: uppercase; margin-top: 1.5mm; }
    .print-receipt-container .meta { font-size: 8.5pt; color: #111; }
    .print-receipt-container .dashed { border-top: 1px dashed #000; margin: 2.5mm 0; padding-top: 2mm; }
    .print-receipt-container table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    .print-receipt-container td { padding: 0.8mm 0; vertical-align: top; }
    .print-receipt-container td.r { text-align: right; white-space: nowrap; }
    .print-receipt-container .section-title { font-weight: bold; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 1mm; }
    .print-receipt-container .tot { font-size: 10.5pt; font-weight: bold; }
    .print-receipt-container .signatures { display: flex; justify-content: space-between; text-align: center; margin-top: 4mm; padding-top: 2mm; }
    .print-receipt-container .sig-box { width: 45%; }
    .print-receipt-container .sig-line { border-bottom: 1px solid #000; margin-top: 11mm; }
}
</style>
@endsection
