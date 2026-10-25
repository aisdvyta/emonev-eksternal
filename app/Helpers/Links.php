<?php

namespace App\Helpers;

class Links
{
    /**
     * Mengembalikan URL hanya jika skemanya http/https. Mencegah javascript:/data: dari data yang
     * berasal dari satker (jawaban/tautan bukti) menjadi tautan yang bisa diklik.
     */
    public static function safe(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }

        return preg_match('#^https?://#i', $value) && filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }
}
