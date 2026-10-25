<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi pemanggil API sinkronisasi (sistem pusat) dengan tanda tangan HMAC-SHA256.
 *
 * Header yang wajib:
 *   X-Sync-Timestamp : detik epoch UTC
 *   X-Sync-Nonce     : string acak 16-128 karakter, unik per request
 *   X-Sync-Signature : "v1=" + hex(HMAC_SHA256(secret, canonical))
 *
 * canonical = METODE "\n" REQUEST_URI(path+query persis seperti dikirim) "\n" timestamp "\n" nonce "\n" sha256(body)
 *
 * Rahasia TIDAK dikirim lewat jaringan. Tanda tangan mencakup isi body sehingga tidak bisa dimodifikasi,
 * dan nonce + timestamp mencegah request yang sama diputar ulang.
 */
class VerifySyncSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secrets = config('external.sync.secrets', []);
        if ($secrets === []) {
            return $this->deny(503, 'sync_not_configured');
        }

        $allowedIps = config('external.sync.allowed_ips', []);
        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            return $this->deny(403, 'ip_not_allowed');
        }

        $timestamp = (string) $request->header('X-Sync-Timestamp', '');
        $nonce = (string) $request->header('X-Sync-Nonce', '');
        $signature = strtolower((string) $request->header('X-Sync-Signature', ''));

        if (! ctype_digit($timestamp) || strlen($nonce) < 16 || strlen($nonce) > 128 || $signature === '') {
            return $this->deny(401, 'bad_headers');
        }

        $skew = (int) config('external.sync.max_skew', 300);
        if (abs(time() - (int) $timestamp) > $skew) {
            return $this->deny(401, 'stale_timestamp');
        }

        $canonical = implode("\n", [
            strtoupper($request->method()),
            $request->getRequestUri(),
            $timestamp,
            $nonce,
            hash('sha256', $request->getContent()),
        ]);

        $given = preg_replace('/^v1=/', '', $signature);
        $valid = false;
        foreach ($secrets as $secret) {
            if (hash_equals(hash_hmac('sha256', $canonical, $secret), $given)) {
                $valid = true;
                break;
            }
        }

        if (! $valid) {
            return $this->deny(401, 'bad_signature');
        }

        // Anti replay: nonce hanya boleh dipakai sekali dalam rentang toleransi waktu.
        if (! Cache::add('sync-nonce:'.hash('sha256', $nonce), 1, now()->addSeconds($skew * 2))) {
            return $this->deny(401, 'replayed_nonce');
        }

        return $next($request);
    }

    private function deny(int $status, string $code): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $code], $status);
    }
}
