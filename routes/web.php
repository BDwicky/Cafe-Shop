<?php

use App\Http\Controllers\Auth\KasirLoginController;
use App\Http\Controllers\KasirController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing'); // placeholder, diganti Phase 7

Route::get('/kasir/login', [KasirLoginController::class, 'showForm'])->name('kasir.login');
Route::post('/kasir/login', [KasirLoginController::class, 'authenticate'])->name('kasir.authenticate');
Route::post('/kasir/logout', [KasirLoginController::class, 'logout'])
    ->name('kasir.logout')->middleware('auth');

Route::middleware('auth')->prefix('kasir')->name('kasir.')->group(function () {
    Route::get('/', [KasirController::class, 'index'])->name('terminal');
    Route::get('/orders', [KasirController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{order}/receipt', [KasirController::class, 'receipt'])->name('receipt');
    Route::post('/orders/{order}/void', [KasirController::class, 'void'])->name('orders.void');
    Route::post('/orders', [KasirController::class, 'store'])->name('orders.store');

    // Stub sementara — diganti controller asli di Phase 5 & 6
    Route::view('/menu', 'kasir.menu-stub')->name('menu.index');
    Route::view('/laporan', 'kasir.laporan-stub')->name('laporan');
});
