<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginOtp;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function show(Request $request)
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.otp');
    }

    public function verify(Request $request, LoginOtp $otp)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['login' => 'Sesi verifikasi berakhir. Silakan masuk lagi.']);
        }

        $data = $request->validate(['kode' => 'required|string|digits:6']);

        if (! $otp->verify($user, $data['kode'])) {
            return back()->withErrors(['kode' => 'Kode salah atau sudah kedaluwarsa.']);
        }

        return LoginController::finish($request, $user);
    }

    public function resend(Request $request, LoginOtp $otp)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        try {
            $otp->issue($user);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['kode' => 'Kode tidak dapat dikirim ulang. Coba lagi nanti.']);
        }

        return back()->with('success', 'Kode baru telah dikirim ke email Anda.');
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('otp_user_id');
        if (! $id) {
            return null;
        }

        $user = User::find($id);

        return $user && $user->is_active ? $user : null;
    }
}
