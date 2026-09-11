<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Dapur #{{ $order->code }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #eee; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        .ticket { width: 72mm; margin: 6mm auto; padding: 4mm; background: #fff; color: #000; font-size: 10pt; line-height: 1.4; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .dashed { border-top: 1px dashed #000; margin: 2mm 0; padding-top: 2mm; }
        .title { font-size: 14pt; font-weight: bold; text-transform: uppercase; }
        .station { font-size: 12pt; font-weight: bold; padding: 1mm 2mm; border: 1px solid #000; display: inline-block; margin: 1.5mm 0; }
        .cust { font-size: 13pt; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 2mm; }
        td { padding: 1.5mm 0; vertical-align: top; }
        .qty { width: 30px; font-weight: bold; font-size: 13pt; }
        .item-name { font-size: 11pt; font-weight: bold; }
        .note-box { border: 1.5px solid #000; padding: 2mm; margin-top: 2mm; font-size: 9pt; }
        .actions { max-width: 72mm; margin: 0 auto 6mm; text-align: center; }
        .actions button { padding: 8px 16px; font-family: inherit; font-size: 12px; font-weight: bold; cursor: pointer; background: #000; color: #fff; border: none; }
        @media print {
            body { background: #fff; }
            .actions { display: none !important; }
            .ticket { width: 100%; margin: 0; padding: 2mm; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center">
            <div class="station">
                @if ($station === 'barista')
                    ★ TIKET BARISTA (MINUMAN) ★
                @elseif ($station === 'kitchen')
                    ★ TIKET KITCHEN (MAKANAN) ★
                @else
                    ★ TIKET PESANAN DAPUR & BAR ★
                @endif
            </div>
            <div class="title">{{ $order->code }}</div>
            <div class="cust">{{ $order->customer_name ?: 'Pelanggan' }}</div>
            <div style="font-size: 9pt;">{{ $order->order_type === 'dine_in' ? 'MAKAN DI TEMPAT (Dine In)' : 'BUNGKUS (Take Away)' }}</div>
            <div style="font-size: 8pt; color: #555;">{{ $order->created_at->format('d/m/Y H:i') }} &bull; Kasir: {{ $order->cashier?->name ?? '-' }}</div>
        </div>

        <div class="dashed">
            <table>
                @foreach ($order->items as $item)
                    @php
                        $catSlug = $item->menu?->category?->slug ?? '';
                        $isDrink = in_array($catSlug, \App\Services\KitchenService::DRINK_CATEGORIES, true);
                        $isFood = in_array($catSlug, \App\Services\KitchenService::FOOD_CATEGORIES, true);
                    @endphp
                    @if ($station === 'all' || ($station === 'barista' && $isDrink) || ($station === 'kitchen' && $isFood))
                        <tr>
                            <td class="qty">{{ $item->qty }}x</td>
                            <td>
                                <div class="item-name">{{ $item->menu_name }}</div>
                                <div style="font-size: 8pt; color: #555;">{{ $item->menu?->category?->name ?? '' }}</div>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </table>
        </div>

        @if ($order->note)
            <div class="note-box">
                <b>CATATAN KHUSUS:</b><br>
                {{ $order->note }}
            </div>
        @endif

        <div class="dashed center" style="font-size: 8.5pt;">
            Selesai disiapkan? Letakkan di Meja Kasir.
        </div>
    </div>

    <div class="actions">
        <button type="button" onclick="window.print()">Cetak Tiket (Print)</button>
    </div>

    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>
