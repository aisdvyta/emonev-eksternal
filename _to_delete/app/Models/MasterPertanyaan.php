<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPertanyaan extends Model
{
    protected $table = 'master_pertanyaan';
    protected $primaryKey = 'id_master_pertanyaan';
    public $timestamps = false;

    protected $fillable = [
        'order_no',
        'id_sub_indicators',
        'id_indicators',
        'ques_text',
        'ques_description',
        'doc_requirement',
        'link_drive',
        'link_ppid',
        'bobot_pertanyaan',
    ];

    public function getScoresAttribute()
    {
        return $this->attributes['bobot_pertanyaan'] ?? null;
    }

    public function setScoresAttribute($value)
    {
        $this->attributes['bobot_pertanyaan'] = $value;
    }

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('master_questions_count');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('master_questions_count');
        });
    }

    public static function getCachedCount(): int
    {
        return (int) \Illuminate\Support\Facades\Cache::remember('master_questions_count', 86400, function () {
            return static::count();
        });
    }

    public function indikator()
    {
        return $this->belongsTo(MasterIndikator::class, 'id_indicators', 'id_indicators');
    }

    public function subIndikator()
    {
        return $this->belongsTo(MasterSubIndikator::class, 'id_sub_indicators', 'id_sub_indicators');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'id_question', 'id_master_pertanyaan');
    }

    public function getPertanyaanAttribute()
    {
        return $this->ques_text;
    }

    public function getDeskripsiAttribute()
    {
        return $this->ques_description;
    }

    public function getBuktiDukungAttribute()
    {
        return $this->doc_requirement;
    }
}
