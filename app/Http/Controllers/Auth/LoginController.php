<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login lokal untuk penilai eksternal (disalin dari LoginController pusat; SSO BPS tidak ada di satelit).
 * Hanya akun berperan penilai_eksternal yang boleh masuk.
 */
class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('penilai-eksternal.index');
        }

        $activePeriod = \App\Models\Period::orderByDesc('periode_start')->first() ?? \App\Models\Period::getActivePeriod();

        return view('login', compact('activePeriod'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'nama_user' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        $nama_user = trim((string) $request->input('nama_user'));
        $password = $request->input('password');

        // Username atau email
        $isEmail = filter_var($nama_user, FILTER_VALIDATE_EMAIL) !== false;
        $credentials = [
            $isEmail ? 'email' : 'nama_user' => $isEmail ? mb_strtolower($nama_user) : $nama_user,
            'password' => $password,
        ];

        if (Auth::attempt($credentials) && Auth::user()->hasRole('penilai_eksternal')) {
            $request->session()->regenerate();
            Auth::user()->forceFill(['last_login_at' => now()])->save();
            $redirect = redirect()->intended(route('penilai-eksternal.index'));

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'redirect_url' => $redirect->getTargetUrl()]);
            }

            return $redirect;
        }

        // Akun bukan penilai eksternal tidak boleh punya sesi di sini.
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return response()->json(['errors' => ['nama_user' => ['Username/Email atau Password salah.']]], 422);
        }

        return back()->withErrors(['nama_user' => 'Username/Email atau Password salah.'])->onlyInput('nama_user');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }
}
