<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $table = 'result';
    protected $primaryKey = 'id_result';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $guarded = [];

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
