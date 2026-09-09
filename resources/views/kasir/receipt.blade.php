<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $order->code }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #eee; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        .receipt { width: 72mm; margin: 6mm auto; padding: 4mm; background: #fff; color: #000; font-size: 10pt; line-height: 1.45; }
        .receipt .center { text-align: center; }
        .receipt .brand { font-size: 13pt; font-weight: bold; letter-spacing: 0.15em; text-transform: uppercase; }
        .receipt .meta { font-size: 9pt; }
        .receipt .dashed { border-top: 1px dashed #000; margin: 3mm 0; padding-top: 3mm; }
        .receipt table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .receipt td { padding: 0.6mm 0; vertical-align: top; }
        .receipt td.r { text-align: right; white-space: nowrap; }
        .receipt .tot { font-size: 12pt; font-weight: bold; }
        .receipt .qr-block { text-align: center; border-top: 1px dashed #000; padding-top: 3mm; margin-top: 3mm; }
        .receipt .wifi { border-top: 1px dashed #000; margin-top: 3mm; padding-top: 2mm; text-align: center; font-size: 9pt; }
        .receipt .wifi b { text-transform: uppercase; letter-spacing: 0.1em; }
        .actions { max-width: 72mm; margin: 0 auto 8mm; display: flex; gap: 8px; }
        .actions a, .actions button { flex: 1; padding: 10px; font-family: inherit; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer; border: 1px solid #1F1812; background: #fff; color: #1F1812; text-decoration: none; text-align: center; }
        .actions button { background: #1F1812; color: #F7F3EC; }
        .voided { text-align: center; font-weight: bold; font-size: 14pt; letter-spacing: 0.3em; border: 2px solid #000; padding: 2mm; margin: 2mm 0; }
        @media print {
            body { background: #fff; }
            .receipt { margin: 0 auto; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="center">
            <div class="brand">{{ config('cafe.name') }}</div>
            <div class="meta">{{ config('cafe.address') }}</div>
        </div>

        @if ($order->status === 'voided')
            <div class="voided">VOID</div>
        @endif

        <div class="dashed meta">
            <div>No. <b>{{ $order->code }}</b></div>
            <div>{{ $order->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</div>
            <div>{{ $order->order_type === 'dine_in' ? 'Makan di tempat' : 'Bawa pulang' }}{{ $order->customer_name ? ' — ' . $order->customer_name : '' }}</div>
            <div>Kasir: {{ $order->cashier?->name ?? '-' }}</div>
        </div>

        <table>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->menu_name }}<br>&nbsp;&nbsp;{{ $item->qty }} x {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($item->line_total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>

        <div class="dashed">
            <table>
                <tr><td>Subtotal</td><td class="r">{{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
                @if ($order->discount > 0)
                    <tr><td>Diskon</td><td class="r">-{{ number_format($order->discount, 0, ',', '.') }}</td></tr>
                @endif
                <tr class="tot"><td>TOTAL</td><td class="r">Rp {{ number_format($order->total, 0, ',', '.') }}</td></tr>
                <tr><td>{{ $order->payment_method === 'cash' ? 'Tunai' : ucfirst($order->payment_method) }}</td><td class="r">{{ number_format($order->paid_amount, 0, ',', '.') }}</td></tr>
                @if ($order->payment_method === 'cash')
                    <tr><td>Kembali</td><td class="r">{{ number_format($order->change_amount, 0, ',', '.') }}</td></tr>
                @endif
            </table>
        </div>

        @if ($order->note)
            <div class="dashed meta">Catatan: {{ $order->note }}</div>
        @endif

        <div class="qr-block">
            <img src="{{ \App\Support\QrCode::dataUri(route('landing')) }}" width="120" height="120" alt="QR">
            <div class="meta" style="margin-top:2mm">Scan untuk lihat menu & info kami</div>
        </div>

        <div class="wifi">
            <div><b>WiFi Gratis</b></div>
            <div>SSID &nbsp;&nbsp;&nbsp;&nbsp;: {{ config('cafe.wifi_ssid') }}</div>
            <div>Password : {{ config('cafe.wifi_password') }}</div>
        </div>

        <div class="center meta" style="margin-top:3mm">
            {{ config('cafe.receipt_footer') }}
        </div>
    </div>

    <div class="actions">
        <button onclick="window.print()">Cetak</button>
        <a href="{{ route('kasir.terminal') }}">Transaksi Baru ›</a>
    </div>

    <script>window.print();</script>
</body>
</html>
