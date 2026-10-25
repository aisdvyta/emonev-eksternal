<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ExternalSetPassword extends Command
{
    protected $signature = 'external:set-password {email} {--password= : Jika kosong akan ditanyakan}';

    protected $description = 'Mengatur ulang kata sandi seorang penilai eksternal.';

    public function handle(): int
    {
        $user = User::query()->where('email', mb_strtolower($this->argument('email')))->first();
        if (! $user) {
            $this->error('Akun tidak ditemukan.');

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Kata sandi baru (min. 10 karakter)');
        if (! is_string($password) || strlen($password) < 10) {
            $this->error('Kata sandi minimal 10 karakter.');

            return self::FAILURE;
        }

        $user->forceFill(['password' => Hash::make($password)])->save();
        $this->info('Kata sandi diperbarui.');

        return self::SUCCESS;
    }
}
