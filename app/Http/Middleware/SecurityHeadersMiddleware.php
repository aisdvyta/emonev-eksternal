<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        // Tampilan disalin dari sistem pusat (Alpine/Livewire memakai script inline), jadi CSP dijalankan
        // dalam mode Report-Only seperti di pusat. Pembatasan sumber lain tetap berlaku.
        $response->headers->set(
            'Content-Security-Policy-Report-Only',
            "default-src 'self'; "
            ."script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com; "
            ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; "
            ."font-src 'self' https://fonts.gstatic.com data:; "
            ."img-src 'self' data: blob: https:; "
            ."connect-src 'self' ws: wss: https://server.arcgisonline.com; "
            ."object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
        );

        $response->headers->remove('X-Powered-By');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Halaman berisi data penilaian: jangan disimpan di cache bersama/peramban.
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
