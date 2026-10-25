<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $activePeriod = \App\Models\Period::orderByDesc('periode_start')->first() ?? \App\Models\Period::getActivePeriod();

        return view('login', compact('activePeriod'));
    }

    /**
     * Handle authentication attempt.
     */
    public function login(Request $request)
    {
        $request->validate([
            'nama_user' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        $nama_user = $request->input('nama_user');
        $password = $request->input('password');

        // Check if nama_user is email or username
        $loginField = filter_var($nama_user, FILTER_VALIDATE_EMAIL) ? 'email' : 'nama_user';

        $credentials = [
            $loginField => $nama_user,
            'password' => $password,
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if ($request->expectsJson()) {
                $redirectResponse = $this->redirectBasedOnRole(Auth::user());
                return response()->json([
                    'success' => true,
                    'redirect_url' => $redirectResponse->getTargetUrl()
                ]);
            }
            return $this->redirectBasedOnRole(Auth::user());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'errors' => [
                    'nama_user' => ['Username/Email atau Password salah.']
                ]
            ], 422);
        }

        return back()->withErrors([
            'nama_user' => 'Username/Email atau Password salah.',
        ])->onlyInput('nama_user');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request, \App\Services\Auth\SsoService $ssoService)
    {
        $isSso = $request->session()->get('is_sso', false);
        $idToken = $request->session()->get('sso_id_token');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($isSso) {
            try {
                return redirect()->away($ssoService->getLogoutUrl($idToken));
            } catch (\Exception $e) {
                // Fallback to normal redirect if Keycloak provider logout fails
            }
        }

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }

    /**
     * Helper to redirect user based on their role.
     */
    protected function redirectBasedOnRole($user)
    {
        if (!$user->role) {
            abort(403, 'User has no assigned role.');
        }

        $role = strtolower($user->role->nama_role);

        switch ($role) {
            case 'admin':
                return redirect()->intended(route('admin.index'));
            case 'super_evaluator':
                return redirect()->intended(route('super-evaluator.index'));
            case 'evaluator':
                return redirect()->intended(route('evaluator.index'));
            case 'verifikator':
                return redirect()->intended(route('verifikator.index'));
            case 'operator':
                return redirect()->intended(route('operator.index'));
            case 'penilai_eksternal':
                return redirect()->intended(route('penilai-eksternal.index'));
            default:
                abort(403, 'Unauthorized role.');
        }
    }
}
