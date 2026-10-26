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

    protected $fillable = [
        'kode_satker',
        'nama_satker',
    ];

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('satkers_not_3500');
            \Illuminate\Support\Facades\Cache::forget('satkers_ordered');
            \Illuminate\Support\Facades\Cache::forget('total_kabkot');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('satkers_not_3500');
            \Illuminate\Support\Facades\Cache::forget('satkers_ordered');
            \Illuminate\Support\Facades\Cache::forget('total_kabkot');
        });
    }

    public function users()
    {
        return $this->hasMany(User::class, 'kode_satker', 'kode_satker');
    }

    /**
     * Hitung total satker Kabupaten/Kota (tidak termasuk BPS Provinsi 3500).
     */
    public static function countKabkot(): int
    {
        return (int) \Illuminate\Support\Facades\Cache::remember('total_kabkot', 86400, function () {
            return static::where('kode_satker', '!=', '3500')->count();
        });
    }
}
