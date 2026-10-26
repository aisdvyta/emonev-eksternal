<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $table = 'result';
    protected $primaryKey = 'id_result';
    public $timestamps = false;

    protected $fillable = [
        'kode_satker',
        'id_periode',
        'ques_score',
        'visit_score',
        'interview_score',
        'final_score',
        'index_label',
        'published_at',
    ];

    public function satker()
    {
        return $this->belongsTo(MasterSatker::class, 'kode_satker', 'kode_satker');
    }

    public function period()
    {
        return $this->belongsTo(Period::class, 'id_periode', 'id_periode');
    }

    public function submission()
    {
        return $this->hasOne(Submission::class, 'id_result', 'id_result');
    }

    public function visitasi()
    {
        return $this->hasOne(Visitasi::class, 'id_result', 'id_result');
    }

    public function interview()
    {
        return $this->hasOne(Interview::class, 'id_result', 'id_result');
    }
}
