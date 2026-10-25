<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionEvaluator extends Model
{
    protected $table = 'submission_evaluators';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
