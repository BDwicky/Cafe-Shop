<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Laporan Penjualan — {{ $from->format('d/m/Y') }} @if($from->format('Y-m-d') !== $to->format('Y-m-d')) - {{ $to->format('d/m/Y') }} @endif</title>
    <style>
        @page {
            size: auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            background: #eee;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .paper-selector {
            max-width: 72mm;
            margin: 6mm auto 2mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .paper-selector .label {
            color: #555;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        .paper-selector .btn-group {
            display: flex;
            gap: 4px;
        }
        .paper-selector button {
            padding: 4px 10px;
            border: 1px solid #1F1812;
            background: #fff;
            color: #1F1812;
            font-family: monospace;
            font-size: 11px;
            cursor: pointer;
            border-radius: 2px;
        }
        .paper-selector button.active {
            background: #1F1812;
            color: #fff;
            font-weight: bold;
        }
        .receipt {
            width: 72mm;
            margin: 0 auto 6mm;
            padding: 4mm 3mm;
            background: #fff;
            color: #000;
            font-size: 9pt;
            line-height: 1.4;
            transition: width 0.15s ease;
        }
        .receipt .center {
            text-align: center;
        }
        .receipt .brand {
            font-size: 13pt;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .receipt .title {
            font-size: 10pt;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: 1.5mm;
        }
        .receipt .meta {
            font-size: 8.5pt;
            color: #111;
        }
        .receipt .dashed {
            border-top: 1px dashed #000;
            margin: 2.5mm 0;
            padding-top: 2mm;
        }
        .receipt .double {
            border-top: 3px double #000;
            margin: 2.5mm 0;
            padding-top: 2mm;
        }
        .receipt table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .receipt td {
            padding: 0.8mm 0;
            vertical-align: top;
        }
        .receipt td.r {
            text-align: right;
            white-space: nowrap;
        }
        .receipt .section-title {
            font-weight: 800;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 1mm;
        }
        .receipt .tot {
            font-size: 10.5pt;
            font-weight: 800;
        }
        .receipt .signatures {
            display: flex;
            justify-content: space-between;
            text-align: center;
            margin-top: 4mm;
            padding-top: 2mm;
        }
        .receipt .sig-box {
            width: 45%;
        }
        .receipt .sig-line {
            border-bottom: 1px solid #000;
            margin-top: 11mm;
        }
        .actions {
            max-width: 72mm;
            margin: 0 auto 8mm;
            display: flex;
            gap: 8px;
        }
        .actions a, .actions button {
            flex: 1;
            padding: 10px;
            font-family: inherit;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            cursor: pointer;
            border: 1px solid #1F1812;
            background: #fff;
            color: #1F1812;
            text-decoration: none;
            text-align: center;
        }
        .actions button {
            background: #1F1812;
            color: #F7F3EC;
            font-weight: bold;
        }

        /* Khusus Printer Kertas 58mm */
        .receipt.p-58mm {
            width: 48mm;
            padding: 2.5mm 1.5mm;
            font-size: 7.5pt;
            line-height: 1.3;
        }
        .receipt.p-58mm .brand { font-size: 10.5pt; letter-spacing: 0.08em; }
        .receipt.p-58mm .title { font-size: 8.5pt; }
        .receipt.p-58mm .meta { font-size: 7pt; }
        .receipt.p-58mm table { font-size: 7.5pt; }
        .receipt.p-58mm .box { font-size: 7.5pt; padding: 1.5mm; }
        .actions.p-58mm, .paper-selector.p-58mm { max-width: 48mm; }

        @media print {
            body {
                background: #fff;
            }
            .receipt {
                margin: 0 auto;
                padding: 2mm 1mm;
                box-shadow: none;
            }
            .actions, .paper-selector {
                display: none !important;
            }
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
        <!-- Header Struk -->
        <div class="center">
            <div class="brand">{{ config('cafe.name') }}</div>
            <div class="meta">{{ config('cafe.address') }}</div>
            <div class="title">*** LAPORAN KASIR ***</div>
        </div>

        <!-- Meta Info Laporan -->
        <div class="dashed meta">
            <table>
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

        <!-- Ringkasan Penjualan -->
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

        <!-- Rincian Metode Pembayaran -->
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

        <!-- 10 Menu Terlaris -->
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

        <!-- Rincian Harian (Jika periode > 1 hari) -->
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

        <!-- Tanda Tangan Rekonsiliasi Kasir -->
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

        <!-- Footer Struk -->
        <div class="center meta" style="margin-top: 3.5mm; border-top: 1px dashed #000; padding-top: 2.5mm;">
            *** TUTUP KASIR // REKONSILIASI ***<br>
            Dicetak oleh Sistem Kasir {{ config('cafe.name') }}<br>
            Simpan struk ini sebagai bukti rekonsiliasi kas.
        </div>
    </div>

    <!-- Tombol Aksi (Layar Saja, Sembunyi Saat Cetak) -->
    <div class="actions" id="actions-bar">
        <button type="button" onclick="window.print()">Cetak Struk</button>
        <a href="{{ route('kasir.laporan', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}">‹ Kembali</a>
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
