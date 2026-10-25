<?php

namespace App\Console\Commands;

use App\Services\Drive\DriveSyncService;
use Illuminate\Console\Command;

class DriveSync extends Command
{
    protected $signature = 'drive:sync';
    protected $description = 'Sinkronisasi dengan pusat lewat Google Drive (snapshot, ack, nilai)';

    public function handle(DriveSyncService $sync): int
    {
        if (! config('external.drive.enabled')) {
            $this->warn('Sinkronisasi Drive nonaktif (EXTERNAL_DRIVE_ENABLED=false).');

            return self::SUCCESS;
        }

        $res = $sync->run();
        $res['ok'] ? $this->info($res['message']) : $this->error($res['message']);

        return $res['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
