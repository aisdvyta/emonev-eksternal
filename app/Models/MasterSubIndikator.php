<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSubIndikator extends Model
{
    protected $table = 'master_sub_indikator';
    protected $primaryKey = 'id_sub_indicators';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $guarded = [];

    public function pertanyaans()
    {
        return $this->hasMany(MasterPertanyaan::class, 'id_sub_indicators', 'id_sub_indicators')
            ->orderBy('order_no')->orderBy('id_master_pertanyaan');
    }
}
