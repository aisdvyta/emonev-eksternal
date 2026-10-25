<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Penilai\DashboardController;
use App\Http\Controllers\Penilai\ProfileController;
use App\Http\Controllers\Penilai\SaqController;
use App\Http\Controllers\Penilai\VisitasiController;
use App\Http\Controllers\Penilai\WawancaraController;
use Illuminate\Support\Facades\Route;

/*
| Portal satelit penilai eksternal. Semua rute penilai berada di bawah /penilai-eksternal
| (nama rute dan path sengaja sama dengan sistem pusat).
| Tidak ada SSO, pendaftaran mandiri, maupun reset kata sandi publik.
*/

// Tamu otomatis diarahkan ke /login oleh middleware auth. Tanpa closure agar `route:cache` aman.
Route::get('/', [DashboardController::class, 'index'])->middleware(['auth', 'active'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.attempt');

    Route::get('/login/kode', [OtpController::class, 'show'])->name('login.otp');
    Route::post('/login/kode', [OtpController::class, 'verify'])->middleware('throttle:otp')->name('login.otp.verify');
    Route::post('/login/kode/kirim-ulang', [OtpController::class, 'resend'])->middleware('throttle:otp')->name('login.otp.resend');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::prefix('penilai-eksternal')->name('penilai-eksternal.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        // SAQ: hanya lihat
        Route::get('/monitor-kues', [SaqController::class, 'monitor'])->name('monitor-kues');
        Route::get('/review-kues/{submission}', [SaqController::class, 'review'])->name('review-kues');

        // Visitasi
        Route::get('/monitor-visitasi', [VisitasiController::class, 'monitor'])->name('monitor-visitasi');
        Route::get('/review-visitasi/{id}', [VisitasiController::class, 'review'])->name('review-visitasi');
        Route::post('/review-visitasi/{id}/save', [VisitasiController::class, 'save'])->name('review-visitasi.save');

        // Wawancara
        Route::get('/monitor-wawancara', [WawancaraController::class, 'monitor'])->name('monitor-wawancara');
        Route::get('/review-wawancara/{id}', [WawancaraController::class, 'review'])->name('review-wawancara');
        Route::post('/review-wawancara/{id}/save', [WawancaraController::class, 'save'])->name('review-wawancara.save');
    });

    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
