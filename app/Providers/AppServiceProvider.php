<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('id');

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Login: 5 percobaan/menit per kombinasi akun+IP.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(mb_strtolower((string) $request->input('login')).'|'.$request->ip());
        });

        // Verifikasi kode OTP.
        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(6)->by(($request->session()->get('otp_user_id') ?? 'x').'|'.$request->ip());
        });

        // API sinkronisasi: dibatasi per IP penelepon.
        RateLimiter::for('sync', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
