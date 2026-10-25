<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterIndikator extends Model
{
    protected $table = 'master_indikator';
    protected $primaryKey = 'id_indicators';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $guarded = [];

    public function subIndikators()
    {
        return $this->hasMany(MasterSubIndikator::class, 'id_indicators', 'id_indicators')->orderBy('id_sub_indicators');
    }
}
