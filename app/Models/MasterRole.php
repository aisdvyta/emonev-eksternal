<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterRole extends Model
{
    protected $table = 'master_role';
    protected $primaryKey = 'id_role';
    public $timestamps = false;

    protected $fillable = [
        'nama_role',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'id_role', 'id_role');
    }
}
