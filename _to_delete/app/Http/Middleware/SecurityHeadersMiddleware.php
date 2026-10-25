<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach security headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Remove PHP version leak header before response is sent
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        $response = $next($request);

        // Security headers
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy (Report-Only mode untuk memantau tanpa memblokir fungsi Alpine/Livewire & CDN)
        $csp = "default-src 'self'; "
             . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com; "
             . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; "
             . "font-src 'self' https://fonts.gstatic.com data:; "
             . "img-src 'self' data: blob: https://ui-avatars.com https://server.arcgisonline.com https://*.tile.openstreetmap.org https:; "
             . "connect-src 'self' ws: wss: https://server.arcgisonline.com; "
             . "frame-ancestors 'self'";
        $response->headers->set('Content-Security-Policy-Report-Only', $csp);

        // Remove any X-Powered-By on Symfony response object
        $response->headers->remove('X-Powered-By');

        // HSTS (Strict-Transport-Security) only if accessed via HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
