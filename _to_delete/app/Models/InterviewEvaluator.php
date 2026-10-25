<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewEvaluator extends Model
{
    protected $table = 'interview_evaluators';
    public $timestamps = false;

    protected $fillable = [
        'id_interview',
        'id_user',
        'score',
        'catatan',
        'is_draft',
    ];

    protected $casts = [
        'score'    => 'float',
        'is_draft' => 'boolean',
    ];

    /**
     * Accessor: ambil nama dari relasi User
     */
    public function getNameAttribute(): string
    {
        return $this->user?->name ?? $this->user?->nama_user ?? 'Penilai Eksternal';
    }

    /**
     * Accessor: ambil instansi dari relasi User
     */
    public function getInstansiAttribute(): string
    {
        return $this->user?->instansi ?? '';
    }

    /**
     * Accessor: cek apakah penilai eksternal dari relasi User
     */
    public function getIsExternalAttribute(): bool
    {
        if (!$this->user) {
            return false;
        }
        return (bool) ($this->user->is_eksternal || (method_exists($this->user, 'hasRole') && $this->user->hasRole('penilai_eksternal')));
    }

    public function interview()
    {
        return $this->belongsTo(Interview::class, 'id_interview', 'id_interview');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /**
     * Alias accessor agar kompatibel dengan view/kode yang memanggil ->note
     */
    public function getNoteAttribute()
    {
        return $this->catatan;
    }

    public function setNoteAttribute($value)
    {
        $this->catatan = $value;
    }
}
