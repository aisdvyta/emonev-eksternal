<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPertanyaan extends Model
{
    protected $table = 'master_pertanyaan';
    protected $primaryKey = 'id_master_pertanyaan';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $guarded = [];
}
