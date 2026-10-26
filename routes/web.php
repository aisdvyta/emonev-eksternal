<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
| Portal satelit penilai eksternal. Rute, nama rute, controller, dan tampilan DISALIN dari emonev-refactor
| (sistem pusat); yang berbeda hanya: login lokal untuk penilai eksternal (tanpa SSO) dan penyimpanan nilai
| yang disinkronkan ke pusat (lihat App\Services\EvaluationService).
| Tidak ada SSO, pendaftaran mandiri, maupun reset kata sandi publik.
*/

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('/template-saq', [\App\Http\Controllers\Evaluasi\SaqController::class, 'downloadPublicTemplateSaq'])->name('public.template-saq');
Route::post('/login', [LoginController::class, 'login'])->name('login.post')->middleware('throttle:login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

// ==========================================
// EVALUASI - WAWANCARA
// ==========================================
Route::prefix('evaluasi/wawancara')->middleware(['auth'])->group(function () {
    Route::get('/export', [\App\Http\Controllers\Evaluasi\WawancaraController::class, 'exportWawancara'])->name('evaluasi.wawancara.export');
    Route::get('/monitoring', [\App\Http\Controllers\Evaluasi\WawancaraController::class, 'monitorWawancara'])->middleware('role:evaluator|super_evaluator|penilai_eksternal|admin')->name('evaluasi.wawancara.monitoring');
    Route::post('/schedule-batch', [\App\Http\Controllers\Evaluasi\WawancaraController::class, 'saveScheduleBatch'])->middleware('role:super_evaluator|admin')->name('evaluasi.wawancara.schedule-batch');
    Route::post('/schedule/{id}', [\App\Http\Controllers\Evaluasi\WawancaraController::class, 'saveSchedule'])->middleware('role:super_evaluator|admin')->name('evaluasi.wawancara.schedule');
    Route::get('/review/{id}', [\App\Http\Controllers\Evaluasi\WawancaraController::class, 'reviewWawancara'])->middleware('role:evaluator|super_evaluator|penilai_eksternal|admin')->name('evaluasi.wawancara.review');
    Route::post('/review/{id}/save', [\App\Http\Controllers\Evaluasi\WawancaraController::class, 'saveReview'])->middleware('role:evaluator|super_evaluator|penilai_eksternal|admin')->name('evaluasi.wawancara.review.save');
    Route::get('/{id_periode?}', [\App\Http\Controllers\Evaluasi\WawancaraController::class, 'wawancara'])->name('evaluasi.wawancara.index');
});

// ==========================================
// EVALUASI - VISITASI
// ==========================================
Route::prefix('evaluasi/visitasi')->middleware(['auth'])->group(function () {
    Route::get('/export', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'exportVisitasi'])->name('evaluasi.visitasi.export');
    Route::get('/monitoring', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'monitorVisitasi'])->middleware('role:evaluator|super_evaluator|penilai_eksternal|admin')->name('evaluasi.visitasi.monitoring');
    Route::post('/schedule-batch', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'saveScheduleBatch'])->middleware('role:super_evaluator|admin')->name('evaluasi.visitasi.schedule-batch');
    Route::post('/schedule/{id}', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'saveSchedule'])->middleware('role:super_evaluator|admin')->name('evaluasi.visitasi.schedule');
    Route::get('/review/{id}', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'reviewVisitasi'])->middleware('role:evaluator|super_evaluator|penilai_eksternal|admin')->name('evaluasi.visitasi.review');
    Route::post('/review/{id}/save', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'saveReview'])->middleware('role:evaluator|super_evaluator|penilai_eksternal|admin')->name('evaluasi.visitasi.review.save');
    Route::post('/link-bukti', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'saveLinkBuktiVisitasi'])->middleware('role:operator')->name('evaluasi.visitasi.link-bukti');
    Route::get('/{id_periode?}', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'visitasi'])->name('evaluasi.visitasi.index');
});

// ==========================================
// EVALUASI - PENILAIAN
// ==========================================
Route::prefix('evaluasi/penilaian')->middleware(['auth'])->group(function () {
    Route::get('/export', [\App\Http\Controllers\Evaluasi\PenilaianController::class, 'export'])->name('evaluasi.penilaian.export');
    Route::get('/donut-data', [\App\Http\Controllers\Evaluasi\PenilaianController::class, 'donutData'])->name('evaluasi.penilaian.donut-data');
    Route::post('/{id}/publish', [\App\Http\Controllers\Evaluasi\PenilaianController::class, 'publish'])->middleware('role:super_evaluator|admin')->name('evaluasi.penilaian.publish');
    Route::post('/{id}/komentar', [\App\Http\Controllers\Evaluasi\PenilaianController::class, 'saveKomentar'])->middleware('role:super_evaluator|admin')->name('evaluasi.penilaian.komentar');
    Route::get('/{id_periode?}', [\App\Http\Controllers\Evaluasi\PenilaianController::class, 'index'])->name('evaluasi.penilaian.index');
});

// ==========================================
// EVALUASI - SAQ
// ==========================================
Route::prefix('evaluasi/saq')->middleware(['auth'])->group(function () {
    Route::get('/export', [\App\Http\Controllers\Evaluasi\SaqController::class, 'export'])->name('evaluasi.saq.export');
    Route::get('/monitoring', [\App\Http\Controllers\Evaluasi\SaqController::class, 'monitorKues'])->middleware('role:evaluator|super_evaluator|admin')->name('evaluasi.saq.monitoring');
    Route::post('/schedule-batch', [\App\Http\Controllers\Evaluasi\SaqController::class, 'saveScheduleBatch'])->middleware('role:super_evaluator|admin')->name('evaluasi.saq.schedule-batch');
    Route::get('/review/{submission}', [\App\Http\Controllers\Evaluasi\SaqController::class, 'reviewKues'])->middleware('role:evaluator|super_evaluator|admin')->name('evaluasi.saq.review');
    Route::post('/review/save', [\App\Http\Controllers\Evaluasi\SaqController::class, 'saveReview'])->middleware('role:evaluator|super_evaluator|admin')->name('evaluasi.saq.review.save');
    Route::post('/save', [\App\Http\Controllers\Evaluasi\SaqController::class, 'saveKues'])->middleware('role:operator')->name('evaluasi.saq.save');
    Route::post('/verifikasi/draft/{id}', [\App\Http\Controllers\Evaluasi\SaqController::class, 'saveDraft'])->middleware('role:verifikator')->name('evaluasi.saq.verifikasi.draft');
    Route::post('/verifikasi/decision/{id}', [\App\Http\Controllers\Evaluasi\SaqController::class, 'processDecision'])->middleware('role:verifikator')->name('evaluasi.saq.verifikasi.decision');
    Route::get('/{id_periode?}', [\App\Http\Controllers\Evaluasi\SaqController::class, 'index'])->name('evaluasi.saq.index');
});

// Penilai Eksternal Routes
Route::prefix('penilai-eksternal')->middleware(['auth', 'role:penilai_eksternal'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('penilai-eksternal.index');

    Route::get('/monitor-kues', [\App\Http\Controllers\Evaluasi\SaqController::class, 'monitorKues'])->name('penilai-eksternal.monitor-kues');
    Route::get('/monitor-kues/export', [\App\Http\Controllers\Evaluasi\SaqController::class, 'exportKuesBatch'])->name('penilai-eksternal.monitor-kues.export');
    Route::get('/review-kues/{submission}', [\App\Http\Controllers\Evaluasi\SaqController::class, 'reviewKues'])->name('penilai-eksternal.review-kues');
    Route::get('/review-kues/{submission}/export', [\App\Http\Controllers\Evaluasi\SaqController::class, 'exportKues'])->name('penilai-eksternal.review-kues.export');
    Route::post('/review-kues/save', [\App\Http\Controllers\Evaluasi\SaqController::class, 'saveReview'])->name('penilai-eksternal.review-kues.save');

    Route::get('/monitor-visitasi', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'monitorVisitasi'])->name('penilai-eksternal.monitor-visitasi');
    Route::get('/monitor-visitasi/export', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'exportVisitasi'])->name('penilai-eksternal.monitor-visitasi.export');
    Route::get('/review-visitasi/{id}', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'reviewVisitasi'])->name('penilai-eksternal.review-visitasi');
    Route::post('/review-visitasi/{id}/save', [\App\Http\Controllers\Evaluasi\VisitasiController::class, 'saveReview'])->name('penilai-eksternal.review-visitasi.save');

    Route::get('/monitor-wawancara', [\App\Http\Controllers\Evaluasi\WawancaraController::class, 'monitorWawancara'])->name('penilai-eksternal.monitor-wawancara');
    Route::get('/monitor-wawancara/export', [\App\Http\Controllers\Evaluasi\WawancaraController::class, 'exportWawancara'])->name('penilai-eksternal.monitor-wawancara.export');
    Route::get('/review-wawancara/{id}', [\App\Http\Controllers\Evaluasi\WawancaraController::class, 'reviewWawancara'])->name('penilai-eksternal.review-wawancara');
    Route::post('/review-wawancara/{id}/save', [\App\Http\Controllers\Evaluasi\WawancaraController::class, 'saveReview'])->name('penilai-eksternal.review-wawancara.save');

    Route::get('/penilaian', [\App\Http\Controllers\Evaluasi\PenilaianController::class, 'index'])->name('penilai-eksternal.penilaian');
    Route::get('/penilaian/export', [\App\Http\Controllers\Evaluasi\PenilaianController::class, 'export'])->name('penilai-eksternal.penilaian.export');
    Route::get('/penilaian/donut-data', [\App\Http\Controllers\Evaluasi\PenilaianController::class, 'donutData'])->name('penilai-eksternal.penilaian.donut-data');
});

Route::middleware(['auth'])->group(function () {
    // Profile Routes
    Route::get('/profil', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
});
