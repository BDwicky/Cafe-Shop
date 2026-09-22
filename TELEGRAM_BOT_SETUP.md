# Panduan Setup Bot Telegram — Notifikasi & Rekap Penjualan

Dokumen ini menjelaskan langkah demi langkah cara membuat bot Telegram, mendapatkan kredensial, dan menghubungkannya ke aplikasi **Cafe Shop** agar Owner menerima notifikasi setiap transaksi masuk serta rekapitulasi penjualan menu secara otomatis.

---

## Fitur Bot Telegram

1. **Notifikasi Transaksi Realtime**:
   - Terkirim seketika setiap kali kasir menyelesaikan transaksi pembayaran di terminal POS.
   - Menyajikan informasi lengkap: Nomor Nota, Waktu Transaksi, Nama Pelanggan, Tipe Order (*Dine In* / *Take Away*), Metode Pembayaran, Rincian seluruh item menu (jumlah x harga), Subtotal, Diskon, Total Bayar, Kembalian, dan Nama Kasir.
2. **Rekapitulasi Penjualan Seluruh Menu (7 Hari & Bulanan)**:
   - Menyajikan rangkuman total omzet, jumlah struk/transaksi, total item terjual, dan rata-rata transaksi.
   - Menyajikan ranking penjualan seluruh menu kopi, minuman, dan makanan (diurutkan dari yang paling laris).
3. **Pemicu Fleksibel**:
   - **Otomatis Realtime**: Saat transaksi di kasir POS berhasil.
   - **Otomatis Berkala**: Terjadwal setiap Senin pukul 08:00 WIB (rekap 7 hari) dan setiap tanggal 1 awal bulan pukul 08:00 WIB (rekap bulanan).
   - **Manual via Web POS**: Tombol *"Kirim ke Telegram"* di menu **Laporan Penjualan** (`/kasir/laporan`).
   - **Manual via Terminal (CLI)**: Perintah Artisan `php artisan telegram:sales-recap`.

---

## Langkah 1: Buat Bot Telegram via @BotFather

1. Buka aplikasi **Telegram** di smartphone atau desktop Anda.
2. Cari kontak resmi **`@BotFather`** (pastikan bercentang biru).
3. Klik tombol **Start** atau kirim pesan:
   ```text
   /start
   ```
4. Kirim perintah untuk membuat bot baru:
   ```text
   /newbot
   ```
5. Masukkan nama tampilan bot yang Anda inginkan, misalnya:
   ```text
   Notifikasi Cafe Shop
   ```
6. Masukkan username untuk bot (wajib berakhiran kata `bot`, tanpa spasi), misalnya:
   ```text
   CafeShopPOS_bot
   ```
7. BotFather akan mengirimkan pesan konfirmasi beserta **HTTP API Token**. Simpan token ini baik-baik.
   > **Contoh Token:** `7123456789:AAFlkjw98234-exampleToken_ABCDEF`

---

## Langkah 2: Dapatkan Chat ID Anda (Owner / Grup)

Bot Telegram tidak dapat memulai obrolan ke akun pribadi sebelum Anda menekan tombol Start di bot tersebut.

### A. Jika Notifikasi Dikirim ke Akun Pribadi Owner:
1. Buka bot yang baru saja Anda buat di Telegram (misal: `@CafeShopPOS_bot`).
2. Klik tombol **Start** atau ketikkan `/start`.
3. Untuk mengetahui Chat ID numerik akun Telegram Anda:
   - Cari kontak bot **`@userinfobot`** di Telegram.
   - Klik **Start**. Bot tersebut akan membalas dengan menampilkan **Id** akun Anda (contoh: `123456789`).
   - *Alternatif tanpa bot lain:* Buka browser dan akses tautan berikut (ganti `<TOKEN_ANDA>` dengan token dari BotFather):
     ```text
     https://api.telegram.org/bot<TOKEN_ANDA>/getUpdates
     ```
     Cari bagian `"chat":{"id":123456789,...}`. Angka tersebut adalah Chat ID Anda.

### B. Jika Notifikasi Dikirim ke Grup Telegram (Misal: Grup Manajemen Kafe):
1. Buat grup di Telegram atau gunakan grup yang sudah ada.
2. Masukkan bot Anda ke dalam grup tersebut sebagai anggota / administrator.
3. Kirim satu pesan sembarang di dalam grup (misal: "Halo").
4. Buka tautan berikut di browser:
   ```text
   https://api.telegram.org/bot<TOKEN_ANDA>/getUpdates
   ```
5. Cari bagian `"chat":{"id":-1001234567890,...}`.
   > *Catatan: Chat ID untuk grup Telegram biasanya diawali dengan tanda minus `-` (contoh: `-1001234567890` atau `-987654321`). Wajib menyertakan tanda minus tersebut.*

---

## Langkah 3: Konfigurasi File `.env`

Buka file `.env` di folder utama proyek `Cafe Shop`, lalu tambahkan atau sesuaikan baris berikut:

```env
# ===================================================================
# KONFIGURASI NOTIFIKASI TELEGRAM (OWNER / MANAGEMENT)
# ===================================================================
TELEGRAM_BOT_TOKEN=7123456789:AAFlkjw98234-exampleToken_ABCDEF
TELEGRAM_OWNER_CHAT_ID=123456789
TELEGRAM_NOTIFICATIONS_ENABLED=true
```

> **Catatan:**
> - Jika ingin menonaktifkan pengiriman notifikasi sementara waktu (misal saat proses testing massal), ubah `TELEGRAM_NOTIFICATIONS_ENABLED=false`.

Setelah mengubah file `.env`, jalankan perintah berikut di terminal untuk membersihkan cache konfigurasi:
```bash
php artisan config:clear
```

---

## Langkah 4: Uji Coba Pengiriman Pesan & Menu Interaktif

### 1. Tombol Menu Interaktif di Aplikasi Telegram
Bot dilengkapi dengan tombol menu interaktif yang dapat langsung disentuh dari aplikasi Telegram:
- **`📊 Rekap Hari Ini`** (atau ketik `/hari`): Menampilkan total omzet, jumlah transaksi, dan seluruh menu yang terjual hari ini.
- **`📅 Rekap 7 Hari`** (atau ketik `/minggu`): Menampilkan rangkuman 1 minggu beserta ranking menu terlaris.
- **`🗓️ Rekap Bulan Ini`** (atau ketik `/bulan`): Menampilkan rekapitulasi omzet dan penjualan bulan berjalan.
- **`📱 Tampilkan Menu`** (atau ketik `/menu`): Menampilkan kembali tombol pintasan keyboard.

> **Tips di Lingkungan Lokal (Testing):**
> Untuk mengaktifkan respon otomatis bot secara lokal di komputer kasir / laptop, jalankan:
> ```bash
> php artisan telegram:poll
> ```
> *Untuk server produksi dengan domain HTTPS, sistem juga telah menyediakan endpoint webhook siap pakai di `/telegram/webhook`.*

### 2. Uji Rekapitulasi Penjualan via Artisan CLI
Jalankan perintah ini di PowerShell atau Terminal:
```bash
# Uji Rekap Harian (Hari Ini)
php artisan telegram:sales-recap --period=today

# Uji Rekap Mingguan (7 Hari Terakhir)
php artisan telegram:sales-recap --period=7days

# Uji Rekap Bulanan (Bulan Ini)
php artisan telegram:sales-recap --period=month
```
Jika konfigurasi benar, pesan rekap penjualan menu akan langsung masuk ke aplikasi Telegram Anda.

### 3. Uji Transaksi Masuk Realtime
1. Masuk ke halaman Kasir POS (`/kasir`).
2. Masukkan pesanan menu, klik **Bayar**, lalu selesaikan pembayaran.
3. Periksa Telegram Anda: Rincian nota transaksi lengkap akan masuk secara realtime.

### 4. Uji via Tombol di Halaman Laporan POS
1. Buka halaman **Laporan Penjualan** (`/kasir/laporan`).
2. Di pojok kanan atas, klik tombol dropdown **Kirim ke Telegram**.
3. Pilih **Rekap Hari Ini**, **Rekap 7 Hari Terakhir**, atau **Rekap Bulan Ini**.
4. Notifikasi konfirmasi sukses akan tampil dan pesan langsung terkirim ke Telegram.

---

## Langkah 5: Menjalankan Scheduler Otomatis (Cron Job)

Aplikasi telah diprogram untuk mengirimkan rekapitulasi secara otomatis di `routes/console.php`:
- **Rekap Harian**: Setiap malam pukul **22:00 WIB** (rekapitulasi omzet dan menu hari itu saat tutup kasir).
- **Rekap Mingguan**: Setiap hari Senin pukul **08:00 WIB** (rekapitulasi 7 hari terakhir).
- **Rekap Bulanan**: Setiap tanggal 1 awal bulan pukul **08:00 WIB** (rekapitulasi bulan berjalan/penuh).

Agar jadwal otomatis ini berjalan, pastikan Laravel Scheduler aktif:

### Untuk Server Linux / Hosting (Crontab):
Tambahkan baris berikut ke crontab server (`crontab -e`):
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Untuk Lingkungan Lokal / Windows (Testing):
Jalankan perintah berikut di satu jendela PowerShell terpisah:
```bash
php artisan schedule:work
```

---

## Keamanan & Failsafe

- **Non-blocking (Failsafe)**: Jika server Telegram mengalami gangguan, internet kafe terputus sesaat, atau token salah, transaksi kasir di POS **tetap berhasil 100% tanpa error**. Sistem mencatat log kegagalan ke `storage/logs/laravel.log` tanpa mengganggu proses checkout pelanggan.
- **Auto Chunking**: Jika menu yang terjual sangat banyak dan melebihi batas 4.096 karakter Telegram, sistem secara otomatis memecah pesan menjadi beberapa bagian yang rapi dan terurut.
