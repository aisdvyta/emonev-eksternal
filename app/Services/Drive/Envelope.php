<?php

namespace App\Services\Drive;

use RuntimeException;

/**
 * Amplop terenkripsi (AES-256-GCM) untuk pesan sinkronisasi lewat perantara pihak ketiga (Google Drive).
 * Isinya tidak terbaca dan tidak bisa diubah/dipalsukan tanpa rahasia bersama (SATELLITE_SECRET / SYNC_SECRET).
 *
 * Format: "HK1." + base64url( iv[12] . tag[16] . ciphertext ). Teks ASCII murni, aman untuk upload multipart.
 */
final class Envelope
{
    private const PREFIX = 'HK1.';
    private const AAD = 'halokip-drive-v1';

    /** @param  array<string, mixed>  $message */
    public static function seal(array $message, string $secret): string
    {
        if ($secret === '') {
            throw new RuntimeException('Rahasia enkripsi kosong.');
        }

        $plain = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::key($secret), OPENSSL_RAW_DATA, $iv, $tag, self::AAD, 16);
        if ($cipher === false) {
            throw new RuntimeException('Enkripsi gagal.');
        }

        return self::PREFIX . rtrim(strtr(base64_encode($iv . $tag . $cipher), '+/', '-_'), '=');
    }

    /**
     * @param  string|array<int, string>  $secrets  rahasia aktif (+ rahasia lama untuk rotasi)
     * @return array<string, mixed>
     */
    public static function open(string $blob, string|array $secrets): array
    {
        $blob = trim($blob);
        if (! str_starts_with($blob, self::PREFIX)) {
            throw new RuntimeException('Format amplop tidak dikenal.');
        }

        $raw = base64_decode(strtr(substr($blob, strlen(self::PREFIX)), '-_', '+/'), true);
        if ($raw === false || strlen($raw) < 29) {
            throw new RuntimeException('Amplop rusak.');
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);

        foreach ((array) $secrets as $secret) {
            if (! is_string($secret) || $secret === '') {
                continue;
            }
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::key($secret), OPENSSL_RAW_DATA, $iv, $tag, self::AAD);
            if ($plain !== false) {
                $data = json_decode($plain, true);
                if (! is_array($data)) {
                    throw new RuntimeException('Isi amplop bukan JSON.');
                }

                return $data;
            }
        }

        throw new RuntimeException('Amplop tidak bisa dibuka (rahasia salah atau data diubah).');
    }

    private static function key(string $secret): string
    {
        return hash_hkdf('sha256', $secret, 32, self::AAD);
    }
}
