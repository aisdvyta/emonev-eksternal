<?php

namespace App\Helpers;

/**
 * Centralized helper for Jawa Timur geographic region mapping.
 *
 * Sebelumnya ada di 5 controller berbeda (SAQController, EvaluasiController,
 * SubmissionController, VerifikasiController, SuperEvaluatorController) — identik semua.
 * Sekarang cukup panggil dari sini.
 */
class RegionMapHelper
{
    /**
     * Mapping dari nama satker BPS ke region key.
     * @return array<string, string>
     */
    public static function getSatkerToRegion(): array
    {
        return [
            // Surabaya Raya
            'BPS Kota Surabaya'         => 'surabaya',
            'BPS Kabupaten Sidoarjo'    => 'surabaya',
            'BPS Kabupaten Gresik'      => 'surabaya',
            'BPS Kabupaten Mojokerto'   => 'surabaya',
            'BPS Kota Mojokerto'        => 'surabaya',

            // Malang Raya
            'BPS Kabupaten Malang'      => 'malang',
            'BPS Kota Malang'           => 'malang',
            'BPS Kota Batu'             => 'malang',

            // Mataraman Barat
            'BPS Kabupaten Madiun'      => 'mataraman_barat',
            'BPS Kota Madiun'           => 'mataraman_barat',
            'BPS Kabupaten Pacitan'     => 'mataraman_barat',
            'BPS Kabupaten Ponorogo'    => 'mataraman_barat',
            'BPS Kabupaten Magetan'     => 'mataraman_barat',
            'BPS Kabupaten Ngawi'       => 'mataraman_barat',
            'BPS Kabupaten Trenggalek'  => 'mataraman_barat',
            'BPS Kabupaten Nganjuk'     => 'mataraman_barat',

            // Mataraman Timur
            'BPS Kabupaten Kediri'      => 'mataraman_timur',
            'BPS Kota Kediri'           => 'mataraman_timur',
            'BPS Kabupaten Blitar'      => 'mataraman_timur',
            'BPS Kota Blitar'           => 'mataraman_timur',
            'BPS Kabupaten Tulungagung' => 'mataraman_timur',
            'BPS Kabupaten Jombang'     => 'mataraman_timur',

            // Pantura
            'BPS Kabupaten Bojonegoro'  => 'pantura',
            'BPS Kabupaten Tuban'       => 'pantura',
            'BPS Kabupaten Lamongan'    => 'pantura',

            // Tapal Kuda Tengah
            'BPS Kabupaten Jember'      => 'tapal_kuda_tengah',
            'BPS Kabupaten Lumajang'    => 'tapal_kuda_tengah',
            'BPS Kabupaten Probolinggo' => 'tapal_kuda_tengah',
            'BPS Kota Probolinggo'      => 'tapal_kuda_tengah',
            'BPS Kabupaten Pasuruan'    => 'tapal_kuda_tengah',
            'BPS Kota Pasuruan'         => 'tapal_kuda_tengah',

            // Ujung Timur
            'BPS Kabupaten Banyuwangi'  => 'ujung_timur',
            'BPS Kabupaten Bondowoso'   => 'ujung_timur',
            'BPS Kabupaten Situbondo'   => 'ujung_timur',

            // Madura
            'BPS Kabupaten Bangkalan'   => 'madura',
            'BPS Kabupaten Sampang'     => 'madura',
            'BPS Kabupaten Pamekasan'   => 'madura',
            'BPS Kabupaten Sumenep'     => 'madura',
        ];
    }

    /**
     * Default region metadata (fallback scores sebelum data aktual dihitung).
     * @return array<string, array>
     */
    public static function getDefaultRegionScores(): array
    {
        return [
            'surabaya'          => ['name' => 'Surabaya & Sidoarjo', 'score' => 0.0,  'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'malang'            => ['name' => 'Malang Raya',          'score' => 0.0,  'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'mataraman_barat'   => ['name' => 'Mataraman Barat',      'score' => 0.0,  'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'mataraman_timur'   => ['name' => 'Mataraman Timur',      'score' => 0.0,  'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'pantura'           => ['name' => 'Pantura',              'score' => 0.0,  'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'tapal_kuda_tengah' => ['name' => 'Tapal Kuda Tengah',   'score' => 0.0,  'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'ujung_timur'       => ['name' => 'Ujung Timur',         'score' => 0.0,  'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'madura'            => ['name' => 'Wilayah Madura',      'score' => 0.0,  'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
        ];
    }

    /**
     * Resolve hex color for a given score.
     * @param float $score
     * @return string hex color
     */
    public static function scoreToColor(float $score): string
    {
        if ($score <= 0)    return '#94a3b8'; // belum dinilai
        if ($score >= 90)   return '#388E3C'; // hijau tua  — Informatif
        if ($score >= 80)   return '#68B92E'; // hijau muda — Menuju Informatif
        if ($score >= 60)   return '#4C84B0'; // biru       — Cukup Informatif
        if ($score >= 40)   return '#F7B750'; // kuning     — Kurang Informatif
        return '#E82D1F';                      // merah      — Tidak Informatif
    }

    /**
     * Resolve predikat label for a given score.
     */
    public static function scoreToStatus(float $score): string
    {
        if ($score <= 0)    return 'BELUM DINILAI';
        if ($score >= 90)   return 'Informatif';
        if ($score >= 80)   return 'Menuju Informatif';
        if ($score >= 60)   return 'Cukup Informatif';
        if ($score >= 40)   return 'Kurang Informatif';
        return 'Tidak Informatif';
    }

    /**
     * Hitung region scores dari kumpulan satker scores aktual.
     *
     * @param array<string, array{score: float}> $satkerScores  key = nama satker (tanpa "BPS")
     * @param array<string, float>               $satkerRawMap  key = nama satker FULL → score
     * @return array<string, array>
     */
    public static function buildRegionScores(array $satkerRawScores): array
    {
        $regionMap    = self::getSatkerToRegion();
        $regionScores = self::getDefaultRegionScores();

        foreach ($satkerRawScores as $satkerName => $score) {
            $regionKey = $regionMap[$satkerName] ?? null;
            if (!$regionKey || $score <= 0) {
                continue;
            }
            $regionScores[$regionKey]['sum']   = ($regionScores[$regionKey]['sum']   ?? 0) + $score;
            $regionScores[$regionKey]['count'] = ($regionScores[$regionKey]['count'] ?? 0) + 1;
        }

        foreach ($regionScores as $key => &$r) {
            if (isset($r['count']) && $r['count'] > 0) {
                $r['score']  = round($r['sum'] / $r['count'], 2);
                $r['status'] = self::scoreToStatus($r['score']);
                $r['color']  = self::scoreToColor($r['score']);
            }
            unset($r['sum'], $r['count']);
        }
        unset($r);

        return $regionScores;
    }
}
