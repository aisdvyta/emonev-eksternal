<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterLink extends Model
{
    protected $table = 'master_link';
    protected $primaryKey = 'id_link';
    public $timestamps = false;

    protected $fillable = [
        'id_periode',
        'link_panduan',
        'link_drive_review_saq',
    ];

    public function period()
    {
        return $this->belongsTo(Period::class, 'id_periode', 'id_periode');
    }
}
