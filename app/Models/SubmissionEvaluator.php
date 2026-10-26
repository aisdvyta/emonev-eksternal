<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionEvaluator extends Model
{
    protected $table = 'submission_evaluators';
    public $timestamps = false;

    protected $fillable = [
        'id_submission',
        'id_user',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class, 'id_submission', 'id_submission');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
