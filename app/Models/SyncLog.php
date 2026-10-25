<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $table = 'sync_logs';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['ok' => 'boolean', 'created_at' => 'datetime'];
    }

    public static function record(string $action, bool $ok, ?string $summary = null, ?string $ip = null): void
    {
        try {
            static::create([
                'action' => $action,
                'ok' => $ok,
                'summary' => $summary !== null ? mb_substr($summary, 0, 1000) : null,
                'ip' => $ip,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Log audit tidak boleh menggagalkan sinkronisasi.
            report($e);
        }
    }
}
