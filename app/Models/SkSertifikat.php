<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkSertifikat extends Model
{
    protected $table = 'sk_sertifikat';
    protected $primaryKey = 'id_sk_sertifikat';
    public $timestamps = false;

    protected $fillable = [
        'id_periode',
        'id_submission',
        'uuid',
    ];

    protected static function booted()
    {
        static::saving(function ($skSertifikat) {
            if (empty($skSertifikat->uuid)) {
                $skSertifikat->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function getHasSertifikatAttribute()
    {
        if (is_null($this->id_submission)) {
            return file_exists(storage_path('app/uploads/sertifikat-global/' . $this->uuid . '.pdf'));
        }
        return file_exists(storage_path('app/uploads/sertifikat/' . $this->uuid . '.pdf'));
    }

    public function getHasSkAttribute()
    {
        if (is_null($this->id_submission)) {
            return file_exists(storage_path('app/uploads/sk-global/' . $this->uuid . '.pdf'));
        }
        return file_exists(storage_path('app/uploads/sk/' . $this->uuid . '.pdf'));
    }

    public function period()
    {
        return $this->belongsTo(Period::class, 'id_periode', 'id_periode');
    }

    public function submission()
    {
        return $this->belongsTo(Submission::class, 'id_submission', 'id_submission');
    }
}
