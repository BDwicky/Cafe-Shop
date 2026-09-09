<?php

use App\Http\Controllers\Auth\KasirLoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\ReportController;
use App\Models\Menu;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $featured = Menu::available()->with('category')->orderBy('sort_order')->limit(6)->get();

    return view('landing', compact('featured'));
})->name('landing');

Route::get('/menu', [PublicMenuController::class, 'index'])->name('menu.public');

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

    // Kelola menu & kategori
    Route::resource('menu', MenuController::class)->except(['show']);
    Route::patch('/menu/{menu}/toggle', [MenuController::class, 'toggle'])->name('menu.toggle');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Laporan
    Route::get('/laporan', [ReportController::class, 'index'])->name('laporan');
});
