<?php

use App\Http\Controllers\RouterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
});

Route::prefix('router')->name('router.')->group(function () {
    Route::get('/status', [RouterController::class, 'status'])->name('status');
    Route::get('/traffic', [RouterController::class, 'traffic'])->name('traffic');
    Route::get('/top-connections', [RouterController::class, 'topConnections'])->name('top-connections');
    Route::get('/system', [RouterController::class, 'systemUtilization'])->name('system');
    Route::get('/network', [RouterController::class, 'networkInfo'])->name('network');
    Route::get('/broadcast', [RouterController::class, 'broadcastUpdate'])->name('broadcast');
});
