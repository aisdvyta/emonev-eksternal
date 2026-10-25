<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $table = 'interview';
    protected $primaryKey = 'id_interview';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['interview_at' => 'date', 'is_final' => 'boolean'];
    }

    public function result()
    {
        return $this->belongsTo(Result::class, 'id_result', 'id_result');
    }

    public function evaluators()
    {
        return $this->hasMany(InterviewEvaluator::class, 'id_interview', 'id_interview');
    }
}
