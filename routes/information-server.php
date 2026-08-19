<?php

use App\Http\Controllers\AmfGatewayController;
use Illuminate\Support\Facades\Route;

Route::post('/', AmfGatewayController::class)->name('gateway');
