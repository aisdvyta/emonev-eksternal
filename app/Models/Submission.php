<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $table = 'submissions';
    protected $primaryKey = 'id_submission';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $guarded = [];

    public const STATUS_VERIFIED = 3;
    public const STATUS_VISITASI = 5;
    public const STATUS_COMPLETED = 7;
    public const STATUS_GUGUR = 8;

    public function result()
    {
        return $this->belongsTo(Result::class, 'id_result', 'id_result');
    }

    public function evaluators()
    {
        return $this->hasMany(SubmissionEvaluator::class, 'id_submission', 'id_submission');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'id_submission', 'id_submission');
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->status == self::STATUS_GUGUR => 'Gugur',
            $this->status >= self::STATUS_COMPLETED => 'Selesai',
            $this->status == 6 => 'Wawancara selesai',
            $this->status == self::STATUS_VISITASI => 'Visitasi & wawancara',
            $this->status == 4 => 'Dievaluasi',
            $this->status == self::STATUS_VERIFIED => 'Terverifikasi',
            default => 'Belum diverifikasi',
        };
    }
}
