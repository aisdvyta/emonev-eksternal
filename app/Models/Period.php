<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    protected $table = 'periods';
    protected $primaryKey = 'id_periode';
    public $timestamps = false;

    protected static $cachedNames = [];
    protected static $activePeriodCache = null;
    protected static $allNamesPrewarmed = false;

    protected $fillable = [
        'periode_start',
        'periode_end',
        'saq_start',
        'saq_end',
        'eval_start',
        'eval_end',
        'visit_start',
        'visit_end',
        'interview_start',
        'interview_end',
        'result_start',
        'result_end',
        'link_panduan',
    ];

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('periods_desc');
            self::$activePeriodCache = null;
            self::$cachedNames = [];
            self::$allNamesPrewarmed = false;
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('periods_desc');
            self::$activePeriodCache = null;
            self::$cachedNames = [];
            self::$allNamesPrewarmed = false;
        });
    }

    /**
     * Dapatkan periode yang sedang aktif (berdasarkan tanggal sekarang).
     * Jika tidak ada periode aktif saat ini, ambil periode terbaru.
     *
     * @return static|null
     */
    public static function getActivePeriod(): ?self
    {
        // Static in-request cache: controller & Livewire sering memanggil ini dalam 1 request
        if (self::$activePeriodCache !== null) {
            return self::$activePeriodCache === false ? null : self::$activePeriodCache;
        }

        $periods = \Illuminate\Support\Facades\Cache::remember('periods_desc', 3600, function () {
            return static::orderByDesc('periode_start')->get();
        });

        $today = now()->toDateString();
        $period = $periods->first(function ($p) use ($today) {
            return $p->periode_start <= $today && $p->periode_end >= $today;
        }) ?? $periods->first();

        self::$activePeriodCache = $period ?? false;
        return $period;
    }

    /**
     * Cek apakah saat ini ada periode KIP yang sedang aktif berjalan.
     * Suatu periode dianggap aktif berjalan jika tanggal hari ini berada dalam rentang periode_start dan periode_end.
     *
     * @return bool
     */
    public static function hasActivePeriod(): bool
    {
        $periods = \Illuminate\Support\Facades\Cache::remember('periods_desc', 3600, function () {
            return static::orderByDesc('periode_start')->get();
        });

        $today = now()->toDateString();
        return $periods->contains(function ($p) use ($today) {
            return $p->periode_start && $p->periode_end
                && $p->periode_start <= $today
                && $p->periode_end >= $today;
        });
    }

    /**
     * Resolve periode berdasarkan ID pilihan (jika ada), atau fallback ke getActivePeriod().
     *
     * @param int|null $periodId
     * @return static|null
     */
    public static function resolvePeriod(?int $periodId = null): ?self
    {
        if ($periodId) {
            $periods = \Illuminate\Support\Facades\Cache::remember('periods_desc', 3600, function () {
                return static::orderByDesc('periode_start')->get();
            });

            $period = $periods->firstWhere('id_periode', (int) $periodId);
            if ($period) {
                return $period;
            }
        }

        return static::getActivePeriod();
    }

    /**
     * Dapatkan daftar periode yang tersedia sesuai role user (Operator & Verifikator tidak melihat periode masa depan).
     *
     * @param User|null $user
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function getAvailablePeriodsForUser(?User $user = null)
    {
        $user = $user ?? auth()->user();
        $periods = \Illuminate\Support\Facades\Cache::remember('periods_desc', 3600, function () {
            return static::orderByDesc('periode_start')->get();
        });

        // Evaluator, Super Evaluator, Admin dapat melihat seluruh periode (termasuk masa depan)
        if ($user && $user->hasAnyRole(['evaluator', 'super_evaluator', 'admin'])) {
            return $periods;
        }

        // Untuk Operator & Verifikator (serta guest): sembunyikan periode masa depan (misal 2027 jika belum mulai)
        $today = now()->toDateString();
        $activePeriod = static::getActivePeriod();

        return $periods->filter(function ($p) use ($today, $activePeriod) {
            if ($activePeriod && $p->id_periode === $activePeriod->id_periode) {
                return true;
            }
            return $p->periode_start && $p->periode_start <= $today;
        })->values();
    }

    /**
     * Susun ulang tanggal-tanggal tahapan secara berurutan sesuai urutan tahapan baru ($stageOrder),
     * dengan tetap mempertahankan durasi hari masing-masing tahapan.
     *
     * @param array $stageOrder Daftar key tahapan, misal: ['saq', 'wawancara', 'visitasi']
     * @return bool True jika berhasil diupdate
     */
    public function realignStageDates(array $stageOrder): bool
    {
        // Hitung durasi hari tiap tahapan (dengan fallback aman)
        $durSaq       = ($this->saq_start && $this->saq_end) ? max(1, \Carbon\Carbon::parse($this->saq_start)->diffInDays(\Carbon\Carbon::parse($this->saq_end)) + 1) : 30;
        $durEval      = ($this->eval_start && $this->eval_end) ? max(1, \Carbon\Carbon::parse($this->eval_start)->diffInDays(\Carbon\Carbon::parse($this->eval_end)) + 1) : 14;
        $durVisit     = ($this->visit_start && $this->visit_end) ? max(1, \Carbon\Carbon::parse($this->visit_start)->diffInDays(\Carbon\Carbon::parse($this->visit_end)) + 1) : 14;
        $durInterview = ($this->interview_start && $this->interview_end) ? max(1, \Carbon\Carbon::parse($this->interview_start)->diffInDays(\Carbon\Carbon::parse($this->interview_end)) + 1) : 10;
        $durResult    = ($this->result_start && $this->result_end)
            ? max(1, \Carbon\Carbon::parse($this->result_start)->diffInDays(\Carbon\Carbon::parse($this->result_end)) + 1)
            : 2;

        // Ambil tanggal mulai awal (periode_start atau tanggal tahapan paling awal)
        $availableStarts = array_filter([
            $this->periode_start,
            $this->saq_start,
            $this->eval_start,
            $this->visit_start,
            $this->interview_start,
        ]);
        $minStart = !empty($availableStarts) ? min($availableStarts) : now()->toDateString();

        $cursor = \Carbon\Carbon::parse($minStart);
        $newDates = [];

        foreach ($stageOrder as $stgKey) {
            if ($stgKey === 'saq') {
                $saqStart = $cursor->copy();
                $saqEnd   = $saqStart->copy()->addDays($durSaq - 1);
                $evalStart = $saqEnd->copy()->addDay();
                $evalEnd   = $evalStart->copy()->addDays($durEval - 1);

                $newDates['saq_start']  = $saqStart->toDateString();
                $newDates['saq_end']    = $saqEnd->toDateString();
                $newDates['eval_start'] = $evalStart->toDateString();
                $newDates['eval_end']   = $evalEnd->toDateString();

                $cursor = $evalEnd->copy()->addDay();
            } elseif ($stgKey === 'visitasi') {
                $visitStart = $cursor->copy();
                $visitEnd   = $visitStart->copy()->addDays($durVisit - 1);

                $newDates['visit_start'] = $visitStart->toDateString();
                $newDates['visit_end']   = $visitEnd->toDateString();

                $cursor = $visitEnd->copy()->addDay();
            } elseif ($stgKey === 'wawancara') {
                $interviewStart = $cursor->copy();
                $interviewEnd   = $interviewStart->copy()->addDays($durInterview - 1);

                $newDates['interview_start'] = $interviewStart->toDateString();
                $newDates['interview_end']   = $interviewEnd->toDateString();

                $cursor = $interviewEnd->copy()->addDay();
            }
        }

        // Penilaian Akhir (Result) selalu di akhir
        $resultStart = $cursor->copy();
        $resultEnd   = $resultStart->copy()->addDays($durResult - 1);

        $newDates['result_start'] = $resultStart->toDateString();
        $newDates['result_end']   = $resultEnd->toDateString();

        if (!$this->periode_start) {
            $newDates['periode_start'] = $minStart;
        }
        $newDates['periode_end'] = $resultEnd->toDateString();

        return $this->update($newDates);
    }

    /**
     * Cek apakah suatu tahapan sudah mulai dibuka (hari ini >= tanggal mulai).
     *
     * @param string $stage 'saq', 'eval', 'visitasi', 'wawancara', 'result'
     * @return bool
     */
    public function isStageStarted(string $stage): bool
    {
        $today = now()->toDateString();
        $start = match ($stage) {
            'saq' => $this->saq_start,
            'eval', 'saq_eval' => $this->eval_start,
            'visit', 'visitasi' => $this->visit_start,
            'interview', 'wawancara' => $this->interview_start,
            'result', 'penilaian' => $this->result_start,
            default => null,
        };

        return !empty($start) && $today >= $start;
    }

    /**
     * Cek apakah suatu tahapan sudah berakhir (hari ini > tanggal selesai).
     *
     * @param string $stage 'saq', 'eval', 'visitasi', 'wawancara', 'result'
     * @return bool
     */
    public function isStageEnded(string $stage): bool
    {
        $today = now()->toDateString();
        $end = match ($stage) {
            'saq' => $this->saq_end,
            'eval', 'saq_eval' => $this->eval_end,
            'visit', 'visitasi' => $this->visit_end,
            'interview', 'wawancara' => $this->interview_end,
            'result', 'penilaian' => $this->result_end,
            default => null,
        };

        return !empty($end) && $today > $end;
    }

    /**
     * Cek apakah suatu tahapan sedang aktif dibuka (berada dalam jangka waktu mulai dan selesai).
     *
     * @param string $stage 'saq', 'eval', 'visitasi', 'wawancara', 'result'
     * @return bool
     */
    public function isStageActive(string $stage): bool
    {
        return $this->isStageStarted($stage) && !$this->isStageEnded($stage);
    }

    /**
     * Hitung tahapan fase aktif dan persentase stepper MURNI berdasarkan tanggal hari ini vs tanggal periode di database dan urutan tahapan aktif.
     * @param mixed $ignored
     * @return array{phase: string, stepperPercent: int}
     */
    public function getPhaseInfo($ignored = null): array
    {
        $today = now()->toDateString();

        if ($this->result_end && $today > $this->result_end) {
            return ['phase' => 'Selesai / Terpublikasi', 'stepperPercent' => 100];
        }

        $stageOrder = MasterPembobotan::getActiveStageOrder($this->id_periode);

        $stages = [];
        foreach ($stageOrder as $stgKey) {
            if ($stgKey === 'saq') {
                $stages[] = [
                    'phase' => 'Pengisian Kuesioner',
                    'start' => $this->saq_start,
                    'end'   => $this->saq_end,
                ];
                $stages[] = [
                    'phase' => 'Evaluasi Kuesioner',
                    'start' => $this->eval_start,
                    'end'   => $this->eval_end,
                ];
            } elseif ($stgKey === 'visitasi') {
                $stages[] = [
                    'phase' => 'Visitasi Lapangan',
                    'start' => $this->visit_start,
                    'end'   => $this->visit_end,
                ];
            } elseif ($stgKey === 'wawancara') {
                $stages[] = [
                    'phase' => 'Wawancara',
                    'start' => $this->interview_start,
                    'end'   => $this->interview_end,
                ];
            }
        }

        $stages[] = [
            'phase' => 'Sidang Penilaian Akhir',
            'start' => $this->result_start,
            'end'   => $this->result_end,
        ];

        $totalStages = count($stages);

        // 1. Cek apakah hari ini berada dalam rentang salah satu stage
        foreach ($stages as $idx => $stg) {
            if ($stg['start'] && $stg['end'] && $today >= $stg['start'] && $today <= $stg['end']) {
                $pct = $totalStages > 1 ? (int) round(($idx / ($totalStages - 1)) * 100) : 0;
                return ['phase' => $stg['phase'], 'stepperPercent' => $pct];
            }
        }

        // 2. Jika hari ini sebelum stage pertama
        if (!empty($stages[0]['start']) && $today < $stages[0]['start']) {
            return ['phase' => $stages[0]['phase'], 'stepperPercent' => 0];
        }

        // 3. Jika hari ini berada di antara jeda stage atau setelah stage tertentu
        for ($i = $totalStages - 1; $i >= 0; $i--) {
            if (!empty($stages[$i]['start']) && $today >= $stages[$i]['start']) {
                $pct = $totalStages > 1 ? (int) round(($i / ($totalStages - 1)) * 100) : 0;
                return ['phase' => $stages[$i]['phase'], 'stepperPercent' => $pct];
            }
        }

        return ['phase' => 'Pengisian Kuesioner', 'stepperPercent' => 0];
    }

    public function getTahunAttribute()
    {
        return $this->periode_start ? \Carbon\Carbon::parse($this->periode_start)->year : null;
    }

    public function getNamaAttribute()
    {
        if (!$this->periode_start) {
            return '-';
        }
        $year = \Carbon\Carbon::parse($this->periode_start)->year;

        // Pre-warm semua tahun sekaligus dari cache agar 0 DB query
        if (!self::$allNamesPrewarmed) {
            self::$allNamesPrewarmed = true;
            $periods = \Illuminate\Support\Facades\Cache::remember('periods_desc', 3600, function() {
                return static::orderByDesc('periode_start')->get();
            });
            $allPeriods = $periods->sortBy('periode_start')->groupBy(fn($p) => \Carbon\Carbon::parse($p->periode_start)->year);
            $romanMap = ['', ' II', ' III', ' IV', ' V', ' VI', ' VII', ' VIII', ' IX', ' X'];
            foreach ($allPeriods as $yr => $periodsInYear) {
                $index = 0;
                foreach ($periodsInYear as $p) {
                    $suffix = $romanMap[$index] ?? ' ' . ($index + 1);
                    self::$cachedNames[$yr][$p->id_periode] = 'Evaluasi KIP' . $suffix . ' ' . $yr;
                    $index++;
                }
            }
        }

        return self::$cachedNames[$year][$this->id_periode] ?? 'Evaluasi KIP ' . $year;
    }

    public function results()
    {
        return $this->hasMany(Result::class, 'id_periode', 'id_periode');
    }

    public function submissions()
    {
        return $this->hasManyThrough(
            Submission::class,
            Result::class,
            'id_periode',  // Foreign key on Result table
            'id_result',   // Foreign key on Submission table
            'id_periode',  // Local key on Period table
            'id_result'    // Local key on Result table
        );
    }

    public function visitations()
    {
        return $this->hasManyThrough(
            Visitasi::class,
            Result::class,
            'id_periode',  // Foreign key on Result table
            'id_result',   // Foreign key on Visitasi table
            'id_periode',  // Local key on Period table
            'id_result'    // Local key on Result table
        );
    }

    public function interviews()
    {
        return $this->hasManyThrough(
            Interview::class,
            Result::class,
            'id_periode',  // Foreign key on Result table
            'id_result',   // Foreign key on Interview table
            'id_periode',  // Local key on Period table
            'id_result'    // Local key on Result table
        );
    }

    public function kabagKomentars()
    {
        return $this->hasMany(KabagKomentar::class, 'id_periode', 'id_periode');
    }

    public function skSertifikat()
    {
        return $this->hasOne(SkSertifikat::class, 'id_periode', 'id_periode')->whereNull('id_submission');
    }

    public function pembobotan()
    {
        return $this->hasOne(MasterPembobotan::class, 'id_periode', 'id_periode');
    }

    public function masterLink()
    {
        return $this->hasOne(MasterLink::class, 'id_periode', 'id_periode');
    }

    public function getLinkPanduanAttribute()
    {
        return $this->masterLink?->link_panduan;
    }

    public function getLinkDriveReviewSaqAttribute()
    {
        return $this->masterLink?->link_drive_review_saq;
    }
}
