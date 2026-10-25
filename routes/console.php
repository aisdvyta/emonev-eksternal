<?php

use App\Models\SyncLog;
use Illuminate\Support\Facades\Schedule;

// Bersihkan log sinkronisasi lama (data minimization). Butuh cron `php artisan schedule:run` tiap menit.
Schedule::call(fn () => SyncLog::where('created_at', '<', now()->subDays(90))->delete())
    ->name('prune-sync-logs')
    ->daily();

// Sinkronisasi lewat Google Drive (hanya bila EXTERNAL_DRIVE_ENABLED=true). Butuh cron schedule:run tiap menit.
if (config('external.drive.enabled')) {
    Schedule::command('drive:sync')
        ->cron('*/'.max(1, (int) config('external.drive.poll_minutes', 2)).' * * * *')
        ->withoutOverlapping(10);
}
