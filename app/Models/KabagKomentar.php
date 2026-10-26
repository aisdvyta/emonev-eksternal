<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KabagKomentar extends Model
{
    protected $table = 'kabag_komentars';
    protected $primaryKey = 'id_komentar_kabag';
    public $timestamps = false;

    protected $fillable = [
        'id_periode',
        'id_user',
        'komentar',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function periode()
    {
        return $this->belongsTo(Period::class, 'id_periode', 'id_periode');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
