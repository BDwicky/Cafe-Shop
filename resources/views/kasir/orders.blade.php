@extends('kasir.app')

@section('title', 'Riwayat Transaksi')

@section('content')
<div>
    <div class="flex items-baseline justify-between mb-4">
        <h1 class="text-2xl tracking-tight font-medium">Riwayat Transaksi</h1>
        <form method="GET" action="{{ route('kasir.orders.index') }}" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ $date->format('Y-m-d') }}"
                   class="bg-white border border-[#E4DCCC] px-3 py-2 text-sm focus:outline-none focus:border-[#B5762A]">
            <button class="border border-[#2A211A] px-4 py-2 font-mono text-[11px] uppercase tracking-[0.15em] hover:bg-[#2A211A] hover:text-[#F7F3EC]">Tampilkan</button>
        </form>
    </div>

    <div class="bg-white border border-[#E4DCCC] overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-[#E4DCCC] font-mono text-[10px] uppercase tracking-[0.2em] text-[#8A7B66] text-left">
                    <th class="px-4 py-3">Jam</th>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Tipe</th>
                    <th class="px-4 py-3">Bayar</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $o)
                    <tr class="border-b border-[#E4DCCC] last:border-0">
                        <td class="px-4 py-3 font-mono">{{ $o->created_at->timezone('Asia/Jakarta')->format('H:i') }}</td>
                        <td class="px-4 py-3 font-mono">{{ $o->code }}</td>
                        <td class="px-4 py-3">{{ $o->order_type === 'dine_in' ? 'Dine In' : 'Take Away' }}</td>
                        <td class="px-4 py-3 uppercase font-mono text-xs">{{ $o->payment_method }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format($o->total, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full {{ $o->status === 'paid' ? 'bg-[#5F7F42]' : 'bg-[#C4553D]' }}"></span>
                                <span class="font-mono text-[10px] uppercase tracking-[0.15em] {{ $o->status === 'paid' ? 'text-[#5F7F42]' : 'text-[#C4553D]' }}">{{ $o->status === 'paid' ? 'Paid' : 'VOID' }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('kasir.receipt', $o) }}" class="font-mono text-[11px] uppercase tracking-[0.15em] hover:text-[#B5762A]">Struk ›</a>
                            @if ($o->status === 'paid')
                                <form method="POST" action="{{ route('kasir.orders.void', $o) }}" class="inline ml-3"
                                      onsubmit="return confirm('Void transaksi ini?')">
                                    @csrf
                                    <button class="font-mono text-[11px] uppercase tracking-[0.15em] text-[#C4553D] hover:underline">Void</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-[#8A7B66]">Belum ada transaksi pada tanggal ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
</div>
@endsection
