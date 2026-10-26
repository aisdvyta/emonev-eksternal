<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSubIndikator extends Model
{
    protected $table = 'master_sub_indikator';
    protected $primaryKey = 'id_sub_indicators';
    public $timestamps = false;

    protected $fillable = [
        'id_indicators',
        'nama_sub_indikator',
    ];

    public function indikator()
    {
        return $this->belongsTo(MasterIndikator::class, 'id_indicators', 'id_indicators');
    }

    public function pertanyaan()
    {
        return $this->hasMany(MasterPertanyaan::class, 'id_sub_indicators', 'id_sub_indicators');
    }

    public function pertanyaans()
    {
        return $this->hasMany(MasterPertanyaan::class, 'id_sub_indicators', 'id_sub_indicators');
    }
}
