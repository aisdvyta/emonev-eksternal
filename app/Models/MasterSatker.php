<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSatker extends Model
{
    protected $table = 'master_satker';
    protected $primaryKey = 'kode_satker';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];
}
