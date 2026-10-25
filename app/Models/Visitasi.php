<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitasi extends Model
{
    protected $table = 'visitasi';
    protected $primaryKey = 'id_visit';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['visit_at' => 'date', 'is_final' => 'boolean'];
    }

    public function result()
    {
        return $this->belongsTo(Result::class, 'id_result', 'id_result');
    }

    public function evaluators()
    {
        return $this->hasMany(VisitasiEvaluator::class, 'id_visit', 'id_visit');
    }
}
