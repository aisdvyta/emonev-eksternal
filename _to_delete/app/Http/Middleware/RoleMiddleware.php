<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has any of the allowed roles
        if ($user->hasRole($roles)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            abort(403, 'Unauthorized action. Required role: ' . implode(' or ', $roles));
        }

        $userRole = $user->getRoleNames()->first();
        if (!$userRole) {
            abort(403, 'Unauthorized action. User has no role.');
        }

        $userRole = strtolower($userRole);

        $redirects = [
            'admin' => 'admin.index',
            'super_evaluator' => 'super-evaluator.index',
            'evaluator' => 'evaluator.index',
            'verifikator' => 'verifikator.index',
            'operator' => 'operator.index',
            'penilai_eksternal' => 'penilai-eksternal.index',
            // 'kabag' dihapus — user kabag kini ber-role super_evaluator
        ];

        if (array_key_exists($userRole, $redirects)) {
            return redirect()->route($redirects[$userRole])->with('toast', [
                'type' => 'warning',
                'message' => 'Anda tidak memiliki akses ke halaman tersebut.',
            ]);
        }

        abort(403, 'Unauthorized action. Required role: ' . implode(' or ', $roles));
    }
}
