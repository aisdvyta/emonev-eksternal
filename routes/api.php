<?php

use App\Http\Controllers\Api\PingController;
use App\Http\Controllers\Api\ScoreController;
use App\Http\Controllers\Api\SnapshotController;
use Illuminate\Support\Facades\Route;

/*
| API sinkronisasi dengan sistem pusat. Prefix /api (dari bootstrap/app.php) + v1.
| Semua endpoint wajib bertanda tangan HMAC (middleware sync.signed).
| Pusat yang selalu memulai koneksi; satelit tidak pernah memanggil keluar.
*/
Route::prefix('v1')->middleware(['throttle:sync', 'sync.signed'])->group(function () {
    Route::get('ping', PingController::class)->name('api.ping');
    Route::post('sync/snapshot', [SnapshotController::class, 'store'])->name('api.snapshot');
    Route::get('sync/scores', [ScoreController::class, 'index'])->name('api.scores');
    Route::post('sync/ack', [ScoreController::class, 'ack'])->name('api.ack');
});
