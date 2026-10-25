<?php

namespace App\Helpers;

class SecureId
{
    private static function getKey()
    {
        $key = config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }
        return substr($key, 0, 16);
    }

    public static function encrypt($id)
    {
        if (empty($id)) return null;
        $key = self::getKey();
        $encrypted = openssl_encrypt((string)$id, 'AES-128-ECB', $key);
        return str_replace(['+', '/', '='], ['-', '_', ''], $encrypted);
    }

    public static function decrypt($encrypted)
    {
        if (empty($encrypted)) return null;
        $key = self::getKey();
        $base64 = str_replace(['-', '_'], ['+', '/'], $encrypted);
        
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        $decrypted = openssl_decrypt($base64, 'AES-128-ECB', $key);
        if ($decrypted === false) {
            abort(404, 'Invalid ID');
        }
        return $decrypted;
    }
}
