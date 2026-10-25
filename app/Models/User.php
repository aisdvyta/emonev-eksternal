<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Akun penilai eksternal. ID sama dengan id_user di sistem pusat.
 * Akun dibuat/diperbarui lewat sinkronisasi (snapshot), bukan pendaftaran mandiri.
 */
class User extends Authenticatable
{
    protected $table = 'users';
    protected $primaryKey = 'id_user';
    public $incrementing = false;
    protected $keyType = 'int';
    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function visitasiEvaluations()
    {
        return $this->hasMany(VisitasiEvaluator::class, 'id_user', 'id_user');
    }

    public function interviewEvaluations()
    {
        return $this->hasMany(InterviewEvaluator::class, 'id_user', 'id_user');
    }
}
