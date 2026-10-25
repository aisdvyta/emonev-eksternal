<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request, LoginOtp $otp)
    {
        $data = $request->validate([
            'login' => 'required|string|max:191',
            'password' => 'required|string|max:255',
        ]);

        $login = trim($data['login']);
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;
        $user = User::query()
            ->where($isEmail ? 'email' : 'nama_user', $isEmail ? mb_strtolower($login) : $login)
            ->first();

        // Hash::check tetap dijalankan walau akun tidak ada, agar waktu respons tidak membocorkan keberadaan akun.
        $passwordOk = Hash::check($data['password'], $user?->password ?? '$2y$12$DPzRSU0YskyG/QRXTchj0OmcH.KUEBdmIMgv2VHiFPQ/TuB2v6p7O');

        if (! $user || ! $passwordOk || ! $user->is_active) {
            return back()->withErrors(['login' => 'Akun atau kata sandi salah.'])->onlyInput('login');
        }

        if (config('external.login_otp')) {
            try {
                $otp->issue($user);
            } catch (\Throwable $e) {
                report($e);

                return back()->withErrors(['login' => 'Kode verifikasi tidak dapat dikirim. Coba lagi beberapa saat atau hubungi admin.'])
                    ->onlyInput('login');
            }

            $request->session()->regenerate();
            $request->session()->put('otp_user_id', $user->id_user);

            return redirect()->route('login.otp');
        }

        return $this->completeLogin($request, $user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }

    public static function finish(Request $request, User $user)
    {
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('otp_user_id');
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('penilai-eksternal.index'));
    }

    private function completeLogin(Request $request, User $user)
    {
        return self::finish($request, $user);
    }
}
