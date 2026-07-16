<?php

use App\Http\Controllers\RouterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/router/status', [RouterController::class, 'status'])->name('router.status');
Route::get('/router/traffic', [RouterController::class, 'traffic'])->name('router.traffic');
Route::get('/router/top-connections', [RouterController::class, 'topConnections'])->name('router.top-connections');
Route::get('/router/system', [RouterController::class, 'systemUtilization'])->name('router.system');
Route::get('/router/network', [RouterController::class, 'networkInfo'])->name('router.network');
Route::get('/router/broadcast', [RouterController::class, 'broadcastUpdate'])->name('router.broadcast');
