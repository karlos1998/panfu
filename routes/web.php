<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\PlayerHomeController as AdminPlayerHomeController;
use App\Http\Controllers\Admin\PlayerStateController as AdminPlayerStateController;
use App\Http\Controllers\Admin\PublicRoomController as AdminPublicRoomController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserRelationController as AdminUserRelationController;
use App\Http\Controllers\Admin\UserSessionController as AdminUserSessionController;
use App\Http\Controllers\Panfu\HomeController;
use App\Http\Controllers\Panfu\InformationServerProxyController;
use App\Http\Controllers\Panfu\LocaleController;
use App\Http\Controllers\Panfu\PlayController;
use App\Http\Controllers\Panfu\ShopController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/language/{locale}', LocaleController::class)
    ->whereIn('locale', ['de', 'en', 'pl'])
    ->name('panfu.language');

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

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'admin'])
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

        Route::post('/users/{user}/inventory', [AdminInventoryController::class, 'store'])->name('users.inventory.store');
        Route::patch('/users/{user}/inventory/{inventory}', [AdminInventoryController::class, 'update'])->name('users.inventory.update');
        Route::delete('/users/{user}/inventory/{inventory}', [AdminInventoryController::class, 'destroy'])->name('users.inventory.destroy');

        Route::post('/users/{user}/states', [AdminPlayerStateController::class, 'store'])->name('users.states.store');
        Route::patch('/users/{user}/states/{state}', [AdminPlayerStateController::class, 'update'])->name('users.states.update');
        Route::delete('/users/{user}/states/{state}', [AdminPlayerStateController::class, 'destroy'])->name('users.states.destroy');

        Route::post('/users/{user}/relations', [AdminUserRelationController::class, 'store'])->name('users.relations.store');
        Route::delete('/users/{user}/relations/{relation}', [AdminUserRelationController::class, 'destroy'])->name('users.relations.destroy');
        Route::delete('/users/{user}/sessions/{session}', [AdminUserSessionController::class, 'destroy'])->name('users.sessions.destroy');

        Route::redirect('/rooms', '/admin/rooms/homes')->name('rooms.index');
        Route::get('/rooms/homes', [AdminPlayerHomeController::class, 'index'])->name('rooms.homes.index');
        Route::get('/rooms/homes/{user}', [AdminPlayerHomeController::class, 'show'])->name('rooms.homes.show');
        Route::get('/rooms/public', [AdminPublicRoomController::class, 'index'])->name('rooms.public.index');
        Route::get('/rooms/public/{room}', [AdminPublicRoomController::class, 'show'])->name('rooms.public.show');
    });

require __DIR__.'/auth.php';
