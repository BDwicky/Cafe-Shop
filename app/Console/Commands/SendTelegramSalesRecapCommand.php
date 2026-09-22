<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SendTelegramSalesRecapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:sales-recap 
                            {--period=today : Periode rekapitulasi: today, 7days, month, atau 30days} 
                            {--chat-id= : Target Telegram chat ID (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim laporan rekapitulasi penjualan dan semua menu yang terjual ke Telegram Owner';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegramService): int
    {
        $period = (string) $this->option('period') ?: '7days';
        $chatId = $this->option('chat-id') ? (string) $this->option('chat-id') : null;

        $this->info("Menyiapkan rekapitulasi penjualan menu ({$period})...");

        $result = $telegramService->sendSalesRecap($period, $chatId);

        if ($result['success']) {
            $this->info("✓ {$result['message']}");
            $this->table(
                ['Metrik', 'Nilai'],
                [
                    ['Total Omzet', 'Rp '.number_format($result['total_omzet'] ?? 0, 0, ',', '.')],
                    ['Total Transaksi', number_format($result['total_trx'] ?? 0, 0, ',', '.')],
                    ['Total Item/Cup Terjual', number_format($result['total_items'] ?? 0, 0, ',', '.')],
                ]
            );

            return self::SUCCESS;
        }

        $this->error("✕ {$result['message']}");

        return self::FAILURE;
    }
}
