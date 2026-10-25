<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewEvaluator extends Model
{
    protected $table = 'interview_evaluators';
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

    public function interview()
    {
        return $this->belongsTo(Interview::class, 'id_interview', 'id_interview');
    }
}
