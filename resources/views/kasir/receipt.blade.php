<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $order->code }}</title>
    <style>
        @page { size: auto; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #eee; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        .paper-selector { max-width: 72mm; margin: 6mm auto 2mm; display: flex; align-items: center; justify-content: space-between; font-size: 11px; font-family: system-ui, -apple-system, sans-serif; }
        .paper-selector .label { color: #555; font-size: 10px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; }
        .paper-selector .btn-group { display: flex; gap: 4px; }
        .paper-selector button { padding: 4px 10px; border: 1px solid #1F1812; background: #fff; color: #1F1812; font-family: monospace; font-size: 11px; cursor: pointer; border-radius: 2px; }
        .paper-selector button.active { background: #1F1812; color: #fff; font-weight: bold; }
        .receipt { width: 72mm; margin: 0 auto 6mm; padding: 4mm; background: #fff; color: #000; font-size: 10pt; line-height: 1.45; transition: width 0.15s ease; }
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
        .actions button { background: #1F1812; color: #F7F3EC; font-weight: bold; }
        .voided { text-align: center; font-weight: bold; font-size: 14pt; letter-spacing: 0.3em; border: 2px solid #000; padding: 2mm; margin: 2mm 0; }

        /* Khusus Printer Kertas 58mm */
        .receipt.p-58mm { width: 48mm; padding: 2.5mm 1.5mm; font-size: 8pt; line-height: 1.35; }
        .receipt.p-58mm .brand { font-size: 10.5pt; letter-spacing: 0.1em; }
        .receipt.p-58mm .meta { font-size: 7.5pt; }
        .receipt.p-58mm table { font-size: 8pt; }
        .receipt.p-58mm .tot { font-size: 10pt; }
        .receipt.p-58mm .qr-block img { width: 90px; height: 90px; }
        .receipt.p-58mm .wifi { font-size: 7.5pt; }
        .actions.p-58mm, .paper-selector.p-58mm { max-width: 48mm; }

        @media print {
            body { background: #fff; }
            .receipt { margin: 0 auto; box-shadow: none; }
            .actions, .paper-selector { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="paper-selector" id="paper-selector">
        <span class="label">Ukuran Kertas:</span>
        <div class="btn-group">
            <button type="button" id="btn-80" onclick="setPaper('80mm')">80mm</button>
            <button type="button" id="btn-58" onclick="setPaper('58mm')">58mm</button>
        </div>
    </div>

    <div class="receipt" id="receipt-card">
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

    <div class="actions" id="actions-bar">
        <button type="button" onclick="window.print()">Cetak Struk</button>
        <a href="{{ route('kasir.terminal') }}">Transaksi Baru ›</a>
    </div>

    <script>
        function setPaper(size) {
            const receipt = document.getElementById('receipt-card');
            const actions = document.getElementById('actions-bar');
            const selector = document.getElementById('paper-selector');
            const btn80 = document.getElementById('btn-80');
            const btn58 = document.getElementById('btn-58');

            if (size === '58mm') {
                receipt.classList.add('p-58mm');
                actions.classList.add('p-58mm');
                selector.classList.add('p-58mm');
                btn58.classList.add('active');
                btn80.classList.remove('active');
            } else {
                receipt.classList.remove('p-58mm');
                actions.classList.remove('p-58mm');
                selector.classList.remove('p-58mm');
                btn80.classList.add('active');
                btn58.classList.remove('active');
            }
            try {
                localStorage.setItem('pos_paper_size', size);
            } catch (e) {}
        }

        // Terapkan preferensi tersimpan
        const savedPaper = localStorage.getItem('pos_paper_size') || '80mm';
        setPaper(savedPaper);

        // Auto print setelah ukuran diterapkan
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>
