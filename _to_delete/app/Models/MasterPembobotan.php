<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPembobotan extends Model
{
    protected $table = 'master_pembobotan';
    protected $primaryKey = 'id_pembobotan';
    public $timestamps = false;

    protected $fillable = [
        'id_periode',
        'weight_saq',
        'weight_visitasi',
        'weight_wawancara',
        'threshold_saq',
        'bobot_indikator',
        'stage_order',
    ];

    protected $casts = [
        'weight_saq'       => 'float',
        'weight_visitasi'  => 'float',
        'weight_wawancara' => 'float',
        'threshold_saq'    => 'float',
        'bobot_indikator'  => 'array',
        'stage_order'      => 'array',
    ];

    protected static function booted()
    {
        static::saved(function ($model) {
            if ($model->id_periode) {
                \Illuminate\Support\Facades\Cache::forget('pembobotan_stage_order_' . $model->id_periode);
                \Illuminate\Support\Facades\Cache::forget('pembobotan_config_' . $model->id_periode);
                \Illuminate\Support\Facades\Cache::forget('pembobotan_indicator_order_' . $model->id_periode);
                \Illuminate\Support\Facades\Cache::forget('saq_category_weights_' . $model->id_periode);
            }
            \Illuminate\Support\Facades\Cache::forget('pembobotan_config_default');
            \Illuminate\Support\Facades\Cache::forget('saq_category_weights_default');
            \Illuminate\Support\Facades\Cache::forget('periods_desc');
        });

        static::deleted(function ($model) {
            if ($model->id_periode) {
                \Illuminate\Support\Facades\Cache::forget('pembobotan_stage_order_' . $model->id_periode);
                \Illuminate\Support\Facades\Cache::forget('pembobotan_config_' . $model->id_periode);
                \Illuminate\Support\Facades\Cache::forget('pembobotan_indicator_order_' . $model->id_periode);
                \Illuminate\Support\Facades\Cache::forget('saq_category_weights_' . $model->id_periode);
            }
            \Illuminate\Support\Facades\Cache::forget('pembobotan_config_default');
            \Illuminate\Support\Facades\Cache::forget('saq_category_weights_default');
            \Illuminate\Support\Facades\Cache::forget('periods_desc');
        });
    }

    /**
     * The canonical list of all possible stage keys.
     */
    public static function allStageKeys(): array
    {
        return ['saq', 'visitasi', 'wawancara'];
    }

    /**
     * Default ordered stage array (fallback when stage_order is null).
     */
    public static function getDefaultStageOrder(): array
    {
        return ['saq', 'visitasi', 'wawancara'];
    }

    /**
     * Get stage order for the currently active period or specific period ID.
     */
    public static function getActiveStageOrder(?int $periodId = null): array
    {
        $targetPeriod = $periodId ? Period::find($periodId) : Period::getActivePeriod();
        if (!$targetPeriod) {
            return self::getDefaultStageOrder();
        }

        $cacheKey = 'pembobotan_stage_order_' . $targetPeriod->id_periode;
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function() use ($targetPeriod) {
            $pembobotan = self::where('id_periode', $targetPeriod->id_periode)->first();
            return $pembobotan ? $pembobotan->resolvedStageOrder() : self::getDefaultStageOrder();
        });
    }

    /**
     * Cek apakah visitasi memerlukan kelulusan/nilai wawancara terlebih dahulu.
     */
    public static function visitasiRequiresWawancara(?int $periodId = null, ?array $stageOrder = null): bool
    {
        $order = $stageOrder ?? self::getActiveStageOrder($periodId);
        $posWawancara = array_search('wawancara', $order);
        $posVisitasi  = array_search('visitasi', $order);
        return ($posWawancara !== false && $posVisitasi !== false && $posWawancara < $posVisitasi);
    }

    /**
     * Cek apakah wawancara memerlukan kelulusan/nilai visitasi terlebih dahulu.
     */
    public static function wawancaraRequiresVisitasi(?int $periodId = null, ?array $stageOrder = null): bool
    {
        $order = $stageOrder ?? self::getActiveStageOrder($periodId);
        $posWawancara = array_search('wawancara', $order);
        $posVisitasi  = array_search('visitasi', $order);
        return ($posVisitasi !== false && $posWawancara !== false && $posVisitasi < $posWawancara);
    }

    /**
     * Human-readable label for a stage key.
     */
    public static function stageLabel(string $key): string
    {
        return match ($key) {
            'saq'       => 'Kuesioner SAQ',
            'visitasi'  => 'Visitasi dan Verifikasi',
            'wawancara' => 'Presentasi dan Wawancara',
            default     => ucfirst($key),
        };
    }

    /**
     * Short label for display in tables/headers.
     */
    public static function stageLabelShort(string $key): string
    {
        return match ($key) {
            'saq'       => 'SAQ',
            'visitasi'  => 'Visitasi dan Verifikasi',
            'wawancara' => 'Presentasi dan Wawancara',
            default     => ucfirst($key),
        };
    }

    /**
     * Get the resolved, validated stage order for this record.
     * Guarantees all 3 stages are present and in a valid order.
     */
    public function resolvedStageOrder(): array
    {
        $order = $this->stage_order;
        if (is_string($order)) {
            $order = json_decode($order, true);
        }
        if (!is_array($order)) {
            $order = [];
        }
        $all   = self::allStageKeys();

        // Filter to valid keys only, preserve order
        $filtered = array_values(array_intersect($order, $all));

        // Append any missing keys at the end
        foreach ($all as $key) {
            if (!in_array($key, $filtered)) {
                $filtered[] = $key;
            }
        }

        return $filtered;
    }

    /**
     * Get the 1-indexed position of a stage key in the resolved order.
     */
    public function stagePosition(string $key): int
    {
        $order = $this->resolvedStageOrder();
        $pos   = array_search($key, $order);
        return $pos !== false ? (int)$pos + 1 : 0;
    }

    /**
     * Get the resolved, validated indicator order for this record.
     * The order is determined by the array keys of $this->bobot_indikator.
     */
    public function resolvedIndicatorOrder(): array
    {
        $allIndicatorIds = MasterIndikator::getCachedIds();
        $savedKeys = !empty($this->bobot_indikator) ? array_keys($this->bobot_indikator) : [];

        // Cast to int and filter valid IDs only
        $savedIds = array_map('intval', $savedKeys);
        $filtered = array_values(array_intersect($savedIds, $allIndicatorIds));

        // Append any missing indicator IDs
        foreach ($allIndicatorIds as $id) {
            if (!in_array((int)$id, $filtered)) {
                $filtered[] = (int)$id;
            }
        }

        return $filtered;
    }

    /**
     * Get indicator order for the currently active period or specific period ID.
     */
    public static function getActiveIndicatorOrder(?int $periodId = null): array
    {
        $targetPeriod = $periodId ? Period::find($periodId) : Period::getActivePeriod();
        if (!$targetPeriod) {
            return MasterIndikator::getCachedIds();
        }

        $cacheKey = 'pembobotan_indicator_order_' . $targetPeriod->id_periode;
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function() use ($targetPeriod) {
            $pembobotan = self::where('id_periode', $targetPeriod->id_periode)->first();
            return $pembobotan
                ? $pembobotan->resolvedIndicatorOrder()
                : MasterIndikator::getCachedIds();
        });
    }

    public function period()
    {
        return $this->belongsTo(Period::class, 'id_periode', 'id_periode');
    }
}
