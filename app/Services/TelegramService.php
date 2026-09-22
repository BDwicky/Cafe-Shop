<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Cek apakah kredensial Telegram bot sudah dikonfigurasi & aktif.
     */
    public function isConfigured(): bool
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.owner_chat_id');
        $enabled = config('services.telegram.enabled', true);

        return ! empty($token) && ! empty($chatId) && (bool) $enabled;
    }

    /**
     * Kirim pesan teks ke Telegram dengan auto-chunking (jika > 4000 karakter).
     */
    public function sendMessage(string $text, ?string $chatId = null, string $parseMode = 'HTML'): bool
    {
        $botToken = config('services.telegram.bot_token');
        $targetChatId = $chatId ?: config('services.telegram.owner_chat_id');

        if (empty($botToken) || empty($targetChatId)) {
            Log::info('[TelegramService] Pengiriman dilewati: Bot token atau chat ID belum diatur.');

            return false;
        }

        if (! config('services.telegram.enabled', true)) {
            Log::info('[TelegramService] Pengiriman dilewati: Notifikasi Telegram dinonaktifkan.');

            return false;
        }

        $endpoint = "https://api.telegram.org/bot{$botToken}/sendMessage";

        // Pecah pesan jika mendekati batas 4096 karakter Telegram
        $chunks = $this->splitMessage($text, 3800);
        $allSuccess = true;

        foreach ($chunks as $chunk) {
            try {
                $response = Http::timeout(10)->post($endpoint, [
                    'chat_id' => $targetChatId,
                    'text' => $chunk,
                    'parse_mode' => $parseMode,
                    'disable_web_page_preview' => true,
                ]);

                if (! $response->successful()) {
                    Log::warning('[TelegramService] Gagal mengirim pesan ke Telegram: '.$response->body());
                    $allSuccess = false;
                }
            } catch (\Throwable $e) {
                Log::error('[TelegramService] Terjadi exception saat mengirim ke Telegram: '.$e->getMessage());
                $allSuccess = false;
            }
        }

        return $allSuccess;
    }

    /**
     * Kirim notifikasi transaksi baru ke Telegram Owner.
     */
    public function sendTransactionNotification(Order $order): bool
    {
        try {
            $order->loadMissing(['items', 'cashier']);

            $orderCode = e($order->code);
            $customerName = e($order->customer_name ?: 'Pelanggan Umum');
            $orderType = strtoupper($order->order_type ?: 'DINE IN');
            $paymentMethod = strtoupper($order->payment_method ?: 'TUNAI');
            $cashierName = e($order->cashier?->name ?: 'Kasir');
            $timeFormatted = $order->created_at ? $order->created_at->translatedFormat('d M Y, H:i') : now()->translatedFormat('d M Y, H:i');

            $lines = [];
            $lines[] = '☕ <b>TRANSAKSI BARU MASUK!</b>';
            $lines[] = '━━━━━━━━━━━━━━━━━━━━━━';
            $lines[] = "🧾 <b>Nota:</b> <code>{$orderCode}</code>";
            $lines[] = "⏰ <b>Waktu:</b> {$timeFormatted} WIB";
            $lines[] = "👤 <b>Pelanggan:</b> {$customerName} ({$orderType})";
            $lines[] = "💳 <b>Metode:</b> {$paymentMethod}";
            $lines[] = '';
            $lines[] = '📋 <b>Rincian Pesanan:</b>';

            foreach ($order->items as $item) {
                $itemName = e($item->menu_name);
                $qty = (int) $item->qty;
                $price = number_format($item->price, 0, ',', '.');
                $lineTotal = number_format($item->line_total, 0, ',', '.');

                $lines[] = " • <b>{$qty}x</b> {$itemName}";
                $lines[] = "    @ Rp {$price} = <b>Rp {$lineTotal}</b>";
            }

            $lines[] = '━━━━━━━━━━━━━━━━━━━━━━';
            $lines[] = '💵 Subtotal: Rp '.number_format($order->subtotal, 0, ',', '.');

            if ($order->discount > 0) {
                $promoLabel = $order->promo_code ? " ({$order->promo_code})" : '';
                $lines[] = "🏷️ Diskon{$promoLabel}: -Rp ".number_format($order->discount, 0, ',', '.');
            }

            $lines[] = '💰 <b>TOTAL: Rp '.number_format($order->total, 0, ',', '.').'</b>';
            $lines[] = '💵 Bayar: Rp '.number_format($order->paid_amount, 0, ',', '.');
            $lines[] = '🪙 Kembali: Rp '.number_format($order->change_amount, 0, ',', '.');

            if (! empty($order->note)) {
                $lines[] = '📝 <i>Catatan: '.e($order->note).'</i>';
            }

            $lines[] = '━━━━━━━━━━━━━━━━━━━━━━';
            $lines[] = "👨‍🍳 <i>Kasir: {$cashierName}</i>";

            $message = implode("\n", $lines);

            return $this->sendMessage($message);
        } catch (\Throwable $e) {
            Log::error('[TelegramService] Gagal memproses format notifikasi transaksi: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Buat dan kirim rekapitulasi penjualan seluruh menu (7 Hari atau Bulanan) ke Telegram Owner.
     *
     * @param  string  $period  '7days'|'month'|'30days'
     * @return array{success: bool, message: string, total_omzet?: int, total_trx?: int, total_items?: int}
     */
    public function sendSalesRecap(string $period = 'today', ?string $chatId = null): array
    {
        try {
            $period = strtolower(trim($period));

            if ($period === 'today' || $period === 'day' || $period === 'daily' || $period === 'hari') {
                $from = now()->startOfDay();
                $to = now()->endOfDay();
                $periodTitle = 'HARI INI ('.now()->translatedFormat('d M Y').')';
            } elseif ($period === 'month' || $period === 'monthly' || $period === 'bulan') {
                $from = now()->startOfMonth();
                $to = now()->endOfDay();
                $periodTitle = 'BULAN INI ('.now()->translatedFormat('F Y').')';
            } elseif ($period === '30days') {
                $from = now()->subDays(29)->startOfDay();
                $to = now()->endOfDay();
                $periodTitle = '30 HARI TERAKHIR';
            } else {
                // Default: 7 hari terakhir / mingguan
                $from = now()->subDays(6)->startOfDay();
                $to = now()->endOfDay();
                $periodTitle = '7 HARI TERAKHIR';
            }

            $ordersQuery = Order::query()
                ->where('status', 'paid')
                ->whereBetween('created_at', [$from, $to]);

            $totals = (clone $ordersQuery)->selectRaw('
                COUNT(*) as total_trx,
                COALESCE(SUM(total), 0) as total_omzet,
                COALESCE(SUM(discount), 0) as total_discount,
                COALESCE(AVG(total), 0) as avg_basket
            ')->first();

            $totalTrx = (int) ($totals->total_trx ?? 0);
            $totalOmzet = (int) ($totals->total_omzet ?? 0);
            $totalDiscount = (int) ($totals->total_discount ?? 0);
            $avgBasket = (int) ($totals->avg_basket ?? 0);

            // Rincian Penjualan Seluruh Menu yang Terjual
            $menuSales = OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', 'paid')
                ->whereBetween('orders.created_at', [$from, $to])
                ->selectRaw('
                    order_items.menu_name,
                    SUM(order_items.qty) as total_qty,
                    SUM(order_items.line_total) as total_omzet
                ')
                ->groupBy('order_items.menu_name')
                ->orderByDesc('total_qty')
                ->orderByDesc('total_omzet')
                ->get();

            $totalItemsSold = (int) $menuSales->sum('total_qty');

            // Ringkasan Pembayaran
            $byPayment = (clone $ordersQuery)
                ->selectRaw('payment_method, SUM(total) as omzet, COUNT(*) as count')
                ->groupBy('payment_method')
                ->get();

            $dateRangeStr = $from->translatedFormat('d M Y').' - '.$to->translatedFormat('d M Y');

            $lines = [];
            $lines[] = "📊 <b>REKAP PENJUALAN {$periodTitle}</b>";
            $lines[] = '━━━━━━━━━━━━━━━━━━━━━━';
            $lines[] = "📅 <b>Periode:</b> {$dateRangeStr}";
            $lines[] = '💰 <b>Total Omzet:</b> Rp '.number_format($totalOmzet, 0, ',', '.');
            $lines[] = '🧾 <b>Total Transaksi:</b> '.number_format($totalTrx, 0, ',', '.').' pesanan';
            $lines[] = '📦 <b>Total Cup / Menu Terjual:</b> '.number_format($totalItemsSold, 0, ',', '.').' item';
            $lines[] = '🏷️ <b>Total Diskon Diberikan:</b> Rp '.number_format($totalDiscount, 0, ',', '.');
            $lines[] = '🛒 <b>Rata-rata Keranjang (Basket):</b> Rp '.number_format($avgBasket, 0, ',', '.');
            $lines[] = '━━━━━━━━━━━━━━━━━━━━━━';

            if ($byPayment->isNotEmpty()) {
                $lines[] = '💳 <b>Metode Pembayaran:</b>';
                foreach ($byPayment as $pm) {
                    $methodName = strtoupper($pm->payment_method ?: 'LAINNYA');
                    $lines[] = " • {$methodName}: Rp ".number_format($pm->omzet, 0, ',', '.')." ({$pm->count} trx)";
                }
                $lines[] = '━━━━━━━━━━━━━━━━━━━━━━';
            }

            $lines[] = '🍽️ <b>PENJUALAN SEMUA MENU:</b>';
            if ($menuSales->isEmpty()) {
                $lines[] = '<i>Belum ada menu yang terjual pada periode ini.</i>';
            } else {
                $rank = 1;
                foreach ($menuSales as $item) {
                    $medal = match ($rank) {
                        1 => '🥇',
                        2 => '🥈',
                        3 => '🥉',
                        default => "{$rank}.",
                    };

                    $name = e($item->menu_name);
                    $qty = number_format($item->total_qty, 0, ',', '.');
                    $omzetItem = number_format($item->total_omzet, 0, ',', '.');

                    $lines[] = "{$medal} <b>{$name}</b>: {$qty} porsi (Rp {$omzetItem})";
                    $rank++;
                }
            }

            $lines[] = '━━━━━━━━━━━━━━━━━━━━━━';
            $lines[] = '🤖 <i>Laporan otomatis Bot Telegram Kafe • '.now()->translatedFormat('d M Y, H:i').' WIB</i>';

            $fullMessage = implode("\n", $lines);

            $sent = $this->sendMessage($fullMessage, $chatId);

            if (! $sent) {
                return [
                    'success' => false,
                    'message' => 'Gagal mengirim pesan rekap ke Telegram. Pastikan BOT_TOKEN dan OWNER_CHAT_ID telah valid.',
                ];
            }

            return [
                'success' => true,
                'message' => "Rekap penjualan ({$periodTitle}) berhasil dikirim ke Telegram Owner!",
                'total_omzet' => $totalOmzet,
                'total_trx' => $totalTrx,
                'total_items' => $totalItemsSold,
            ];
        } catch (\Throwable $e) {
            Log::error('[TelegramService] Error saat membuat rekap penjualan: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat membuat rekap: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Kirim pesan dengan keyboard interaktif Telegram (ReplyKeyboardMarkup).
     *
     * @param  array<string, mixed>  $keyboard
     */
    public function sendMessageWithKeyboard(string $text, array $keyboard, ?string $chatId = null): bool
    {
        $botToken = config('services.telegram.bot_token');
        $targetChatId = $chatId ?: config('services.telegram.owner_chat_id');

        if (empty($botToken) || empty($targetChatId) || ! config('services.telegram.enabled', true)) {
            return false;
        }

        $endpoint = "https://api.telegram.org/bot{$botToken}/sendMessage";

        try {
            $response = Http::timeout(10)->post($endpoint, [
                'chat_id' => $targetChatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('[TelegramService] Exception sendMessageWithKeyboard: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Kirim pesan sambutan beserta tombol menu rekap interaktif ke Telegram Owner.
     */
    public function sendMenuKeyboard(?string $chatId = null): bool
    {
        $lines = [
            '☕ <b>Menu Rekapitulasi Penjualan Cafe</b>',
            '━━━━━━━━━━━━━━━━━━━━━━',
            'Pilih menu rekap penjualan yang ingin Anda lihat:',
            '',
            '• <b>📊 Rekap Hari Ini</b>: Laporan transaksi & penjualan hari ini',
            '• <b>📅 Rekap 7 Hari</b>: Rangkuman omzet & ranking menu 1 minggu',
            '• <b>🗓️ Rekap Bulan Ini</b>: Rekapitulasi omzet & penjualan bulan ini',
            '',
            '<i>Ketuk salah satu tombol di bawah untuk meminta rekap otomatis seketika:</i>',
        ];

        $keyboard = [
            'keyboard' => [
                [
                    ['text' => '📊 Rekap Hari Ini'],
                    ['text' => '📅 Rekap 7 Hari'],
                ],
                [
                    ['text' => '🗓️ Rekap Bulan Ini'],
                ],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];

        return $this->sendMessageWithKeyboard(implode("\n", $lines), $keyboard, $chatId);
    }

    /**
     * Tangani pesan masuk dari Telegram (perintah /hari, /minggu, /bulan, /menu, dsb).
     *
     * @param  array<string, mixed>  $message
     */
    public function handleIncomingMessage(array $message): bool
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = strtolower(trim($message['text'] ?? ''));

        if (empty($chatId) || empty($text)) {
            return false;
        }

        // 1. Rekap Harian (Perhari)
        if (str_contains($text, 'hari') || $text === '/hari' || $text === '/today' || $text === '/day') {
            $this->sendSalesRecap('today', $chatId);

            return true;
        }

        // 2. Rekap Mingguan (7 Hari)
        if (str_contains($text, 'minggu') || str_contains($text, '7 hari') || $text === '/minggu' || $text === '/weekly' || $text === '/7days') {
            $this->sendSalesRecap('7days', $chatId);

            return true;
        }

        // 3. Rekap Bulanan (Bulan)
        if (str_contains($text, 'bulan') || $text === '/bulan' || $text === '/month' || $text === '/monthly') {
            $this->sendSalesRecap('month', $chatId);

            return true;
        }

        // 4. Default: Tampilkan tombol menu interaktif
        return $this->sendMenuKeyboard($chatId);
    }

    /**
     * Daftarkan daftar perintah bot ke Telegram Bot API (setMyCommands).
     */
    public function registerBotCommands(): bool
    {
        $botToken = config('services.telegram.bot_token');
        if (empty($botToken)) {
            return false;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/setMyCommands", [
                'commands' => [
                    ['command' => 'hari', 'description' => '📊 Rekap Penjualan Hari Ini'],
                    ['command' => 'minggu', 'description' => '📅 Rekap Penjualan 7 Hari Terakhir'],
                    ['command' => 'bulan', 'description' => '🗓️ Rekap Penjualan Bulan Ini'],
                    ['command' => 'menu', 'description' => '📱 Tampilkan Tombol Menu Interaktif'],
                ],
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('[TelegramService] Gagal mendaftarkan setMyCommands: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Membagi teks panjang menjadi potongan-potongan di batas baris baru.
     *
     * @return list<string>
     */
    protected function splitMessage(string $text, int $maxLength = 3800): array
    {
        if (mb_strlen($text) <= $maxLength) {
            return [$text];
        }

        $chunks = [];
        $lines = explode("\n", $text);
        $currentChunk = '';

        foreach ($lines as $line) {
            if (mb_strlen($currentChunk) + mb_strlen($line) + 1 > $maxLength) {
                if (! empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = '';
                }
            }

            $currentChunk .= ($currentChunk === '' ? '' : "\n").$line;
        }

        if (! empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }
}
