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

        // Halaman ini tidak memakai script inline maupun CDN, jadi CSP bisa ditegakkan (bukan Report-Only).
        // Saat development (Vite dev server di host lain) CSP dilewati.
        if (! app()->environment('local')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; "
                ."script-src 'self'; "
                ."style-src 'self' 'unsafe-inline'; "
                ."img-src 'self' data:; "
                ."font-src 'self' data:; "
                ."connect-src 'self'; "
                ."object-src 'none'; "
                ."base-uri 'self'; "
                ."form-action 'self'; "
                ."frame-ancestors 'none'"
            );
        }

        $response->headers->remove('X-Powered-By');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Halaman berisi data penilaian: jangan disimpan di cache bersama/peramban.
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
