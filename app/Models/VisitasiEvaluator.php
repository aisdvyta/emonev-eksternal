<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitasiEvaluator extends Model
{
    protected $table = 'visitasi_evaluators';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'is_draft' => 'boolean',
            'pending_sync' => 'boolean',
            'revision' => 'integer',
            'saved_at' => 'datetime',
        ];
    }

    public function visitasi()
    {
        return $this->belongsTo(Visitasi::class, 'id_visit', 'id_visit');
    }
}
