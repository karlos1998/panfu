<?php

use App\Http\Controllers\Panfu\HomeController;
use App\Http\Controllers\Panfu\InformationServerProxyController;
use App\Http\Controllers\Panfu\PlayController;
use App\Http\Controllers\Panfu\ShopController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', function () {
    return redirect()->route('play');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/play', PlayController::class)->middleware(['auth', 'verified'])->name('play');

Route::get('/api/shop', ShopController::class)->middleware(['auth', 'verified'])->name('panfu.shop');

Route::match(['get', 'post'], '/InformationServer/{path?}', InformationServerProxyController::class)
    ->where('path', '.*')
    ->name('panfu.information-server');

Route::middleware('auth')->group(function () {
    Route::redirect('/profile', '/account/settings')->name('profile.edit');
    Route::get('/account/settings', [ProfileController::class, 'edit'])->name('account.settings');
    Route::patch('/account/settings', [ProfileController::class, 'update'])->name('account.settings.update');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
