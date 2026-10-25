<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Kode verifikasi 6 digit lewat email setelah kata sandi benar.
 * Kode disimpan sebagai HMAC (bukan teks biasa), kedaluwarsa otomatis, dan dibatasi jumlah percobaannya.
 */
class LoginOtp
{
    public function issue(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $ttl = (int) config('external.otp_ttl_minutes', 10);

        Cache::put($this->key($user), [
            'hash' => $this->hash($code),
            'attempts' => 0,
        ], now()->addMinutes($ttl));

        $appName = config('app.name');
        $body = "Kode verifikasi login {$appName} Anda: {$code}\n\n"
            ."Kode berlaku {$ttl} menit. Jangan berikan kode ini kepada siapa pun, termasuk yang mengaku petugas BPS.\n"
            .'Jika Anda tidak sedang mencoba masuk, abaikan email ini dan segera ganti kata sandi.';

        Mail::raw($body, function ($message) use ($user, $appName) {
            $message->to($user->email)->subject("Kode verifikasi login {$appName}");
        });
    }

    public function verify(User $user, string $code): bool
    {
        $key = $this->key($user);
        $state = Cache::get($key);
        if (! is_array($state)) {
            return false;
        }

        $state['attempts'] = ((int) $state['attempts']) + 1;
        if ($state['attempts'] > (int) config('external.otp_max_attempts', 5)) {
            Cache::forget($key);

            return false;
        }

        if (hash_equals($state['hash'], $this->hash(trim($code)))) {
            Cache::forget($key);

            return true;
        }

        Cache::put($key, $state, now()->addMinutes((int) config('external.otp_ttl_minutes', 10)));

        return false;
    }

    private function key(User $user): string
    {
        return 'login-otp:'.$user->id_user;
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
