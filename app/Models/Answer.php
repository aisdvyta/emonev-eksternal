<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Answer extends Model
{
    use HasUuids;

    protected $table = 'answer';
    protected $primaryKey = 'id_answer';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_question',
        'id_submission',
        'id_indicators',
        'answer',
        'link_ppid',
        'link_drive',
        'answer_score',
        'evaluator_note',
        'evaluator_at',
        'verifikator_note',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class, 'id_submission', 'id_submission');
    }

    public function question()
    {
        return $this->belongsTo(MasterPertanyaan::class, 'id_question', 'id_master_pertanyaan');
    }

    public function indicator()
    {
        return $this->belongsTo(MasterIndikator::class, 'id_indicators', 'id_indicators');
    }
}
