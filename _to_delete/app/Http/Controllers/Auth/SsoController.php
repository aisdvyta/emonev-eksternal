<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\Auth\SsoService;

class SsoController extends Controller
{
    public function __construct(
        protected SsoService $ssoService
    ) {}

    /**
     * Redirect user ke SSO BPS Keycloak Login
     */
    public function redirectToSso(Request $request)
    {
        // Simpan state di session untuk pencegahan CSRF
        $state = bin2hex(random_bytes(16));
        $request->session()->put('oauth2state', $state);

        $authUrl = $this->ssoService->getAuthorizationUrl($state);

        return redirect()->away($authUrl);
    }

    /**
     * Handle callback dari SSO BPS Keycloak
     */
    public function handleSsoCallback(Request $request)
    {
        // Deteksi jika ini adalah redirect pasca-logout dari Keycloak (tanpa parameter code atau error)
        if (!$request->has('code') && !$request->has('error')) {
            return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
        }

        $sessionState = $request->session()->get('oauth2state');
        $request->session()->forget('oauth2state');

        // Validasi parameter CSRF State
        if (empty($request->input('state')) || ($request->input('state') !== $sessionState)) {
            return redirect()->route('login')->with('error', 'Sesi login SSO tidak valid atau telah kadaluarsa.');
        }

        // Cek error dari callback
        if ($request->has('error')) {
            return redirect()->route('login')->with('error', 'Gagal autentikasi SSO: ' . $request->input('error_description', $request->input('error')));
        }

        try {
            // Ambil access token menggunakan authorization_code
            $tokenData = $this->ssoService->getAccessToken($request->input('code'));

            // Simpan id_token untuk keperluan Single Logout
            if (!empty($tokenData['id_token'])) {
                $request->session()->put('sso_id_token', $tokenData['id_token']);
            }

            // Ambil data profil pegawai dari SSO BPS
            $ssoUser = $this->ssoService->getUserInfo($tokenData['access_token']);
            $extracted = $this->ssoService->extractUserAttributes($ssoUser);

            $email      = $extracted['email'];
            $nipPanjang = $extracted['nip_panjang'];
            $nipPendek  = $extracted['nip_pendek'];
            $name       = $extracted['name'];
            $kodeOrg    = $extracted['kode_organisasi'];
            $fotoUrl    = $extracted['foto_url'];

            // Validasi jika email SSO tidak ditemukan
            if (empty($email)) {
                return redirect()->route('login')->with(
                    'error',
                    'Autentikasi gagal: Email akun SSO BPS Anda tidak ditemukan.'
                );
            }

            // Pencocokan akun di database lokal emonev-kip berdasarkan email
            $user = User::where('email', $email)->first();

            // Jika user belum terdaftar di sistem
            if (!$user) {
                return redirect()->route('login')->with(
                    'error',
                    'Akun BPS Anda (' . $email . ') belum terdaftar di sistem eMonev KIP. Silakan hubungi Administrator.'
                );
            }

            // Sync data profil dari SSO ke database lokal
            $updateData = [];

            if ($name && (empty($user->nama_user) || $user->nama_user === explode('@', $email)[0])) {
                $updateData['nama_user'] = $name;
            }

            if ($kodeOrg && empty($user->kode_satker)) {
                $rawKode = (string) $kodeOrg;

                // 1. Cek apakah kode_satker persis ada di master_satker
                if (\App\Models\MasterSatker::where('kode_satker', $rawKode)->exists()) {
                    $updateData['kode_satker'] = $rawKode;
                } else {
                    // 2. Cek apakah 4 digit depan (misal: "3500" dari "350000092000") ada di master_satker
                    $kode4Digit = substr($rawKode, 0, 4);
                    if (\App\Models\MasterSatker::where('kode_satker', $kode4Digit)->exists()) {
                        $updateData['kode_satker'] = $kode4Digit;
                    }
                }
            }

            if ($fotoUrl) {
                $updateData['foto_url']    = $fotoUrl;
                $updateData['profile_pic'] = $fotoUrl;
            }

            if (!empty($nipPanjang) && empty($user->nip_panjang)) {
                $updateData['nip_panjang'] = $nipPanjang;
            }

            if (!empty($nipPendek) && empty($user->nip_pendek)) {
                $updateData['nip_pendek'] = $nipPendek;
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            // Otorisasi Login User di Laravel Session
            Auth::login($user, true);
            $request->session()->put('is_sso', true);

            $request->session()->regenerate();

            return $this->redirectBasedOnRole($user)->with('success', 'Selamat datang kembali, ' . ($user->nama_user ?? 'Pengguna') . '!');
        } catch (Exception $e) {
            Log::error('SSO BPS Authentication Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return redirect()->route('login')->with('error', 'Gagal memproses autentikasi SSO: ' . $e->getMessage());
        }
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
            default:
                abort(403, 'Unauthorized role.');
        }
    }

    /**
     * Endpoint untuk memicu logout SSO BPS
     */
    public function logoutSso(Request $request)
    {
        $idToken = $request->session()->get('sso_id_token');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away($this->ssoService->getLogoutUrl($idToken));
    }
}
