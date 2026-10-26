<?php

namespace App\Services;

/**
 * Hasil satu operasi simpan-nilai oleh EvaluationService.
 * Dibaca controller (untuk redirect + pesan) dan SatelliteSyncService (untuk menentukan ack).
 */
final class EvaluationResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly bool $finalized = false,
        public readonly ?float $average = null,
    ) {
    }

    public static function ok(string $message, bool $finalized = false, ?float $average = null): self
    {
        return new self(true, $message, $finalized, $average);
    }

    public static function rejected(string $message): self
    {
        return new self(false, $message);
    }
}
