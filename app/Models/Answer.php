<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jawaban satker. Sengaja tidak memuat skor/catatan evaluator.
 */
class Answer extends Model
{
    protected $table = 'answer';
    protected $primaryKey = 'id_answer';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['answer' => 'boolean'];
    }
}
