<?php

use App\Models\SyncLog;
use Illuminate\Support\Facades\Schedule;

// Bersihkan log sinkronisasi lama (data minimization). Butuh cron `php artisan schedule:run` tiap menit.
Schedule::call(fn () => SyncLog::where('created_at', '<', now()->subDays(90))->delete())
    ->name('prune-sync-logs')
    ->daily();
