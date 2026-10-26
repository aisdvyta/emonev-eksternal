<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan akun yang sedang login masih aktif. Pusat menonaktifkan akun lewat sinkronisasi
 * (is_active=false), dan sesi yang sedang berjalan harus langsung berhenti berlaku.
 */
class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => 'Akun Anda tidak aktif. Hubungi admin HaloKIP.',
            ]);
        }

        return $next($request);
    }
}
