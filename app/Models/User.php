<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $table = 'users';
    protected $primaryKey = 'id_user';
    public $timestamps = false;

    protected $fillable = [
        'kode_satker',
        'nama_user',
        'name',
        'instansi',
        'is_eksternal',
        'email',
        'password',
        'nip_pendek',
        'nip_panjang',
        'profile_pic',
        'foto_url',
    ];

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('operators_by_satker');
            \Illuminate\Support\Facades\Cache::forget('evaluators_list');
            \Illuminate\Support\Facades\Cache::forget('evaluators_with_roles');
            \Illuminate\Support\Facades\Cache::forget('evaluators_with_roles_wawancara');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('operators_by_satker');
            \Illuminate\Support\Facades\Cache::forget('evaluators_list');
            \Illuminate\Support\Facades\Cache::forget('evaluators_with_roles');
            \Illuminate\Support\Facades\Cache::forget('evaluators_with_roles_wawancara');
        });
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_eksternal' => 'boolean',
            // NOTE: Jangan pakai cast 'hashed' di sini karena Auth::attempt()
            // akan double-hash password dan login selalu gagal.
            // Hash dilakukan manual saat seeder/create user.
        ];
    }

    /**
     * Override agar Auth::attempt() tahu field password di DB (tidak double-hash).
     */
    public function getAuthPassword(): string
    {
        return $this->password;
    }

    /**
     * Accessor: ambil name jika ada, fallback ke nama_user.
     */
    public function getNameAttribute(): string
    {
        return $this->attributes['name'] ?? $this->nama_user ?? '';
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
    }

    public function getRoleAttribute()
    {
        $role = $this->roles->first();
        if (!$role) {
            return null;
        }
        return (object)[
            'id_role' => $role->id,
            'nama_role' => $role->name,
        ];
    }

    public function getIdRoleAttribute()
    {
        $role = $this->roles->first();
        return $role ? $role->id : null;
    }



    public function masterSatker()
    {
        return $this->belongsTo(MasterSatker::class, 'kode_satker', 'kode_satker');
    }

    public function submissions()
    {
        return $this->hasManyThrough(
            Submission::class,
            Result::class,
            'kode_satker', // Foreign key on Result table
            'id_result',   // Foreign key on Submission table
            'kode_satker', // Local key on User table
            'id_result'    // Local key on Result table
        );
    }

    public function verifiedSubmissions()
    {
        return $this->hasMany(Submission::class, 'verified_by', 'id_user');
    }

    public function assignedSubmissions()
    {
        return $this->belongsToMany(Submission::class, 'submission_evaluators', 'id_user', 'id_submission');
    }

    public function interviewEvaluations()
    {
        return $this->hasMany(InterviewEvaluator::class, 'id_user', 'id_user');
    }

    public function visitasiEvaluations()
    {
        return $this->hasMany(VisitasiEvaluator::class, 'id_user', 'id_user');
    }
}
