<?php

use App\Http\Controllers\Auth\KasirLoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\KasirMusicController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MusicRequestController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\ReportController;
use App\Models\Menu;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $featured = Menu::available()->with('category')->orderBy('sort_order')->limit(6)->get();

    return view('landing', compact('featured'));
})->name('landing');

Route::get('/menu', [PublicMenuController::class, 'index'])->name('menu.public');

// Request Musik Pelanggan & Display Kafe (Publik)
Route::prefix('music')->name('music.')->group(function () {
    Route::get('/request', [MusicRequestController::class, 'index'])->name('request');
    Route::post('/validate-code', [MusicRequestController::class, 'validateCode'])->name('validate_code');
    Route::get('/search', [MusicRequestController::class, 'search'])->name('search');
    Route::post('/request', [MusicRequestController::class, 'store'])->name('store');
    Route::get('/status', [MusicRequestController::class, 'status'])->name('status');
    Route::get('/display', [MusicRequestController::class, 'display'])->name('display');
    Route::get('/tts', [KasirMusicController::class, 'tts'])->name('tts');
});

Route::get('/kasir/login', [KasirLoginController::class, 'showForm'])->name('kasir.login');
Route::post('/kasir/login', [KasirLoginController::class, 'authenticate'])->name('kasir.authenticate');
Route::post('/kasir/logout', [KasirLoginController::class, 'logout'])
    ->name('kasir.logout')->middleware('auth');

Route::middleware('auth')->prefix('kasir')->name('kasir.')->group(function () {
    Route::get('/', [KasirController::class, 'index'])->name('terminal');
    Route::post('/promo/check', [KasirController::class, 'checkPromo'])->name('promo.check');
    Route::get('/orders', [KasirController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{order}/receipt', [KasirController::class, 'receipt'])->name('receipt');
    Route::post('/orders/{order}/void', [KasirController::class, 'void'])->name('orders.void');
    Route::post('/orders', [KasirController::class, 'store'])->name('orders.store');

    // Kitchen Display System (KDS) Barista & Kitchen
    Route::prefix('kitchen')->name('kitchen.')->group(function () {
        Route::get('/', [KitchenController::class, 'index'])->name('index');
        Route::get('/orders', [KitchenController::class, 'orders'])->name('orders');
        Route::post('/orders/{order}/status', [KitchenController::class, 'updateStatus'])->name('status');
        Route::post('/orders/{order}/recall', [KitchenController::class, 'recall'])->name('recall');
        Route::get('/orders/{order}/ticket', [KitchenController::class, 'ticket'])->name('ticket');
    });

    // Pemutar Musik & Sound Station Kafe
    Route::prefix('music')->name('music.')->group(function () {
        Route::get('/', [KasirMusicController::class, 'index'])->name('index');
        Route::get('/mini', [KasirMusicController::class, 'mini'])->name('mini');
        Route::post('/next-track', [KasirMusicController::class, 'nextTrack'])->name('next');
        Route::post('/requests/{musicRequest}/skip', [KasirMusicController::class, 'skip'])->name('skip');
        Route::post('/requests/{musicRequest}/reject', [KasirMusicController::class, 'reject'])->name('reject');
        Route::post('/requests/{musicRequest}/add-to-default', [KasirMusicController::class, 'addRequestToDefault'])->name('requests.add_to_default');
        Route::get('/sidebar-counts', [KasirMusicController::class, 'sidebarCounts'])->name('sidebar_counts');
        Route::get('/default-tracks', [KasirMusicController::class, 'defaultTracksJson'])->name('default.list');
        Route::post('/default-tracks', [KasirMusicController::class, 'storeDefaultTrack'])->name('default.store');
        Route::post('/default-tracks/batch', [KasirMusicController::class, 'storeBatchDefaultTracks'])->name('default.store_batch');
        Route::post('/default-tracks/reorder', [KasirMusicController::class, 'reorderDefaultTracks'])->name('default.reorder');
        Route::get('/inspect-link', [KasirMusicController::class, 'inspectLink'])->name('inspect');
        Route::patch('/default-tracks/{track}/toggle', [KasirMusicController::class, 'toggleDefaultTrack'])->name('default.toggle');
        Route::put('/default-tracks/{track}', [KasirMusicController::class, 'updateDefaultTrack'])->name('default.update');
        Route::delete('/default-tracks/{track}', [KasirMusicController::class, 'destroyDefaultTrack'])->name('default.destroy');

        // Panggilan Suara Pesanan Siap (Voice Announcer & Audio Ducking)
        Route::get('/pending-announcements', [KasirMusicController::class, 'pendingAnnouncements'])->name('announcements.pending');
        Route::post('/orders/{order}/announced', [KasirMusicController::class, 'markAnnounced'])->name('announcements.mark');
        Route::post('/playback-sync', [KasirMusicController::class, 'syncPlayback'])->name('playback.sync');

        // Sinkronisasi & Ambil Alih Master Host Antar-Device (Multi-Device Preemption & Remote)
        Route::post('/master-host/claim', [KasirMusicController::class, 'claimMasterHost'])->name('master.claim');
        Route::post('/master-host/heartbeat', [KasirMusicController::class, 'masterHeartbeat'])->name('master.heartbeat');
        Route::get('/master-host/status', [KasirMusicController::class, 'masterStatus'])->name('master.status');
        Route::post('/master-host/command', [KasirMusicController::class, 'sendRemoteCommand'])->name('master.command');
        Route::post('/master-host/release', [KasirMusicController::class, 'releaseMasterHost'])->name('master.release');
    });

    // Pengaturan Suara & Aksen Announcer serta Mode Adzan
    Route::get('/announcer', [KasirMusicController::class, 'announcerSettings'])->name('announcer.settings');
    Route::post('/announcer', [KasirMusicController::class, 'saveAnnouncerSettings'])->name('announcer.save');
    Route::get('/announcer/settings-json', [KasirMusicController::class, 'announcerSettingsJson'])->name('announcer.json');
    Route::get('/music/prayer-times', [KasirMusicController::class, 'prayerTimes'])->name('music.prayer-times');

    // Kelola menu & kategori
    Route::resource('menu', MenuController::class)->except(['show']);
    Route::patch('/menu/{menu}/toggle', [MenuController::class, 'toggle'])->name('menu.toggle');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Kelola Kupon & Promo Diskon
    Route::resource('promos', PromoController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('/promos/{promo}/toggle', [PromoController::class, 'toggle'])->name('promos.toggle');

    // Manajemen Inventaris & Bahan Baku (Stok & Resep BOM)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::put('/{ingredient}', [InventoryController::class, 'update'])->name('update');
        Route::delete('/{ingredient}', [InventoryController::class, 'destroy'])->name('destroy');
        Route::post('/restock', [InventoryController::class, 'restock'])->name('restock');
        Route::post('/waste', [InventoryController::class, 'waste'])->name('waste');
        Route::post('/adjustment', [InventoryController::class, 'adjustment'])->name('adjustment');
        Route::get('/history', [InventoryController::class, 'history'])->name('history');
        Route::get('/recipes', [InventoryController::class, 'recipes'])->name('recipes');
        Route::put('/recipes/{menu}', [InventoryController::class, 'updateRecipe'])->name('recipes.update');
    });

    // Pengeluaran Toko & Operasional Kasir
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::post('/', [ExpenseController::class, 'store'])->name('store');
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
    });

    // Laporan
    Route::get('/laporan', [ReportController::class, 'index'])->name('laporan');
    Route::get('/laporan/receipt', [ReportController::class, 'receipt'])->name('laporan.receipt');
});
