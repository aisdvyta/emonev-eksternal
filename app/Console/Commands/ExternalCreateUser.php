<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ExternalCreateUser extends Command
{
    protected $signature = 'external:create-user
        {id_user : ID pengguna (HARUS sama dengan id_user di sistem pusat)}
        {email}
        {name}
        {--nama-user= : Username opsional}
        {--password= : Kata sandi awal (jika kosong akan ditanyakan)}';

    protected $description = 'Membuat/memperbarui akun penilai eksternal secara manual (biasanya akun datang dari sinkronisasi pusat).';

    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Kata sandi (min. 10 karakter)');
        if (! is_string($password) || strlen($password) < 10) {
            $this->error('Kata sandi minimal 10 karakter.');

            return self::FAILURE;
        }

        User::query()->updateOrCreate(
            ['id_user' => (int) $this->argument('id_user')],
            [
                'email' => mb_strtolower($this->argument('email')),
                'name' => $this->argument('name'),
                'nama_user' => $this->option('nama-user') ?: null,
                'password' => Hash::make($password),
                'is_active' => true,
            ]
        );

        $this->info('Akun disimpan.');

        return self::SUCCESS;
    }
}
