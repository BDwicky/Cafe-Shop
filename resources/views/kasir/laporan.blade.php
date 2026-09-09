@extends('kasir.app')

@section('title', 'Laporan Penjualan')

@section('content')
<div>
    <div class="flex flex-wrap items-baseline justify-between gap-3 mb-6">
        <h1 class="text-2xl tracking-tight font-medium">Laporan Penjualan</h1>
        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('kasir.laporan') }}" class="flex items-center gap-2">
                <input type="date" name="from" value="{{ $from->format('Y-m-d') }}"
                       class="bg-white border border-[#E4DCCC] px-3 py-2 text-sm focus:outline-none focus:border-[#B5762A]">
                <span class="text-[#8A7B66] text-sm">s/d</span>
                <input type="date" name="to" value="{{ $to->format('Y-m-d') }}"
                       class="bg-white border border-[#E4DCCC] px-3 py-2 text-sm focus:outline-none focus:border-[#B5762A]">
                <button class="border border-[#2A211A] px-4 py-2 font-mono text-[11px] uppercase tracking-[0.15em] hover:bg-[#2A211A] hover:text-[#F7F3EC]">Tampilkan</button>
            </form>
            <button onclick="window.print()"
                    class="border border-[#2A211A] px-4 py-2 font-mono text-[11px] uppercase tracking-[0.15em] hover:bg-[#2A211A] hover:text-[#F7F3EC] print:hidden">Cetak ›</button>
        </div>
    </div>

    <!-- Stat utama -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="bg-white border border-[#E4DCCC] p-5">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66]">Transaksi</div>
            <div class="mt-2 font-mono text-4xl">{{ number_format($totals->trx, 0, ',', '.') }}</div>
        </div>
        <div class="bg-[#1F1812] text-[#F7F3EC] border border-[#3A3026] p-5">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#A89A85]">Omzet</div>
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
</div>
@endsection
