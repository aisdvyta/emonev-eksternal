<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterIndikator extends Model
{
    protected $table = 'master_indikator';
    protected $primaryKey = 'id_indicators';
    public $timestamps = false;

    protected $fillable = [
        'nama_indikator',
        'deskripsi_indikator',
    ];

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('master_indicator_ids');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('master_indicator_ids');
        });
    }

    /**
     * Dapatkan semua ID indikator yang ter-cache.
     * @return array<int>
     */
    public static function getCachedIds(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('master_indicator_ids', 86400, function () {
            return static::pluck('id_indicators')->map(fn($id) => (int)$id)->toArray();
        });
    }

    public function subIndikators()
    {
        return $this->hasMany(MasterSubIndikator::class, 'id_indicators', 'id_indicators');
    }

    public function pertanyaan()
    {
        return $this->hasMany(MasterPertanyaan::class, 'id_indicators', 'id_indicators');
    }

    public function pertanyaans()
    {
        return $this->hasMany(MasterPertanyaan::class, 'id_indicators', 'id_indicators');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'id_indicators', 'id_indicators');
    }

    /**
     * Ambil list indikator yang sudah diurutkan sesuai urutan indikator pada periode tertentu.
     */
    public static function getOrderedForPeriod(?int $periodId = null, array $with = [])
    {
        $order = MasterPembobotan::getActiveIndicatorOrder($periodId);
        $query = static::query();
        if (!empty($with)) {
            $query->with($with);
        }
        $indicators = $query->get();

        if (empty($order)) {
            return $indicators;
        }

        $orderMap = array_flip(array_values(array_map('intval', $order)));
        return $indicators->sortBy(function ($ind) use ($orderMap) {
            return $orderMap[(int)$ind->id_indicators] ?? 999;
        })->values();
    }
}
