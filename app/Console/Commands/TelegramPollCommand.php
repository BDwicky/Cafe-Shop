<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramPollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll {--once : Jalankan polling sekali lalu berhenti}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jalankan daemon polling Telegram untuk menerima dan membalas perintah bot secara lokal';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegramService): int
    {
        $botToken = config('services.telegram.bot_token');

        if (empty($botToken)) {
            $this->error('TELEGRAM_BOT_TOKEN belum diatur di .env!');

            return self::FAILURE;
        }

        // Daftarkan perintah bot dan kirim keyboard menu ke owner
        $telegramService->registerBotCommands();
        $this->info('✓ Bot commands (/hari, /minggu, /bulan, /menu) terdaftar di Telegram.');

        $isOnce = $this->option('once');
        $this->info('Memulai polling pesan Telegram... (Tekan Ctrl+C untuk berhenti)');

        $offset = 0;

        while (true) {
            try {
                $response = Http::timeout(25)->get("https://api.telegram.org/bot{$botToken}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => 20,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $updates = $data['result'] ?? [];

                    foreach ($updates as $update) {
                        $updateId = (int) ($update['update_id'] ?? 0);
                        $offset = max($offset, $updateId + 1);

                        if (isset($update['message'])) {
                            $from = $update['message']['from']['first_name'] ?? 'User';
                            $text = $update['message']['text'] ?? '';
                            $this->line("Pesan dari [{$from}]: {$text}");

                            $telegramService->handleIncomingMessage($update['message']);
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->warn('Polling error: '.$e->getMessage());
                sleep(2);
            }

            if ($isOnce) {
                break;
            }

            usleep(500000); // 0.5s pause
        }

        return self::SUCCESS;
    }
}
