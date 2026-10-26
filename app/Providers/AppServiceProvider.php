<?php

namespace App\Providers;

use App\Helpers\SecureId;
use App\Models\Submission;
use App\Policies\SubmissionPolicy;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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

        // Login: 5 percobaan/menit per kombinasi akun+IP (seperti di pusat; kolom form = nama_user).
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(mb_strtolower((string) $request->input('nama_user')).'|'.$request->ip());
        });

        // API sinkronisasi: dibatasi per IP penelepon.
        RateLimiter::for('sync', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // --- Disalin dari AppServiceProvider pusat ---
        Gate::policy(Submission::class, SubmissionPolicy::class);

        // Register views/layouts/app.blade.php sebagai <x-layouts.app>
        Blade::component('layouts.app', 'layouts.app');

        // Eager-load masterSatker untuk auth user agar blade tidak lazy-load per-akses
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Authenticated::class,
            function ($event) {
                if ($event->user && ! $event->user->relationLoaded('masterSatker')) {
                    $event->user->load('masterSatker');
                }
            }
        );

        Route::bind('id_periode', function ($value) {
            if (is_numeric($value)) {
                return (int) $value;
            }

            return (int) SecureId::decrypt($value);
        });
    }
}
