<?php
 
namespace App\Livewire\Evaluasi;

use App\Models\Submission;
use App\Models\MasterSatker;
use App\Models\User;
use App\Models\Period;
use App\Models\Result;
use App\Models\Answer;
use App\Models\MasterIndikator;
use App\Models\MasterPertanyaan;
use App\Models\MasterLink;
use App\Services\PenilaianService;
use Livewire\Component;
use Livewire\WithPagination;

class PenilaianMonitoringTable extends Component
{
    use WithPagination;
 
    public $periodeId;
    public $perPage = 10;
 
    // Link Drive Review SAQ for current period
    public $driveReviewLink = '';

    // Server-side filter properties
    public $search = '';
    public $filterKabkots = [];   // array of satker names (lowercase)
    public $filterKodes = [];     // array of satker kodes
    public $filterPeriodes = [];  // array of period IDs
    public $filterStatuses = [];  // array of predikat values e.g. ['INFORMATIF', 'BELUM DINILAI']
 
    // Sorting properties
    public $sortField = 'calculated_final';
    public $sortDirection = 'desc';
 
    // Selected kabkot kode for histogram chart (single selection)
    public $chartKabkotKode = '';
 
    public function mount($periodeId)
    {
        $this->periodeId = $periodeId;
        $this->syncDriveReviewLink();
    }
 
    public function updatingPeriodeId()
    {
        $this->resetPage();
    }

    public function updatedPeriodeId()
    {
        $this->syncDriveReviewLink();
    }

    public function syncDriveReviewLink()
    {
        if ($this->periodeId) {
            $masterLink = MasterLink::where('id_periode', $this->periodeId)->first();
            $this->driveReviewLink = $masterLink?->link_drive_review_saq ?? '';
        } else {
            $this->driveReviewLink = '';
        }
    }

    public function saveDriveReviewLink()
    {
        $role = auth()->user()?->role?->nama_role;
        if (!in_array($role, ['super_evaluator', 'evaluator', 'admin'])) {
            return;
        }

        $this->validate([
            'driveReviewLink' => ['nullable', 'regex:/^https?:\/\//i', 'url'],
        ], [
            'driveReviewLink.regex' => 'Isikan Link dengan benar',
            'driveReviewLink.url' => 'Isikan Link dengan benar',
        ]);

        MasterLink::updateOrCreate(
            ['id_periode' => $this->periodeId],
            ['link_drive_review_saq' => $this->driveReviewLink]
        );

        \Illuminate\Support\Facades\Cache::forget('drive_review_saq_url_active');

        session()->flash('drive_link_status', 'Tautan Google Drive Hasil Review SAQ berhasil disimpan.');
    }
 
    public function updatingSearch()
    {
        $this->resetPage();
    }
 
    public function updatingFilterKabkots()
    {
        $this->resetPage();
    }
 
    public function updatingPerPage()
    {
        $this->resetPage();
    }
 
    public function updatingFilterStatuses()
    {
        $this->resetPage();
    }
 
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }
 
    /**
     * Called from JS when user changes status checkboxes.
     */
    public function setFilterStatuses(array $statuses)
    {
        $this->filterStatuses = $statuses;
        $this->resetPage();
    }
 
    /**
     * Called from JS when user changes kabkot checkboxes.
     */
    public function setFilterKabkots(array $names)
    {
        $this->filterKabkots = $names;
        $this->resetPage();
    }
 
    /**
     * Called from JS when user changes kode satker checkboxes.
     */
    public function setFilterKodes(array $kodes)
    {
        $this->filterKodes = $kodes;
        $this->resetPage();
    }
 
    /**
     * Called from JS when user changes period checkboxes in table.
     */
    public function setFilterPeriodes(array $periodIds)
    {
        $this->filterPeriodes = $periodIds;
        $this->resetPage();
    }
 
    /**
     * Called from JS when user selects a specific kabkot for the histogram chart.
     */
    public function setChartKabkot(string $kode)
    {
        $this->chartKabkotKode = $kode;
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterKabkots = [];
        $this->filterKodes = [];
        $this->filterPeriodes = [];
        $this->filterStatuses = [];
        $this->resetPage();
    }
 
    public function render()
    {
        $penilaianService = app(PenilaianService::class);
        $periods = \Illuminate\Support\Facades\Cache::remember('periods_desc', 3600, function() {
            return Period::orderByDesc('periode_start')->get();
        });

        $isExternal = auth()->user()?->hasRole('penilai_eksternal');
        if ($isExternal) {
            // Penilai eksternal HANYA boleh melihat periode yang SUDAH SELESAI / FINAL
            $periods = $periods->filter(function($p) {
                return $p->result_end && now()->toDateString() > $p->result_end;
            })->values();

            if (!$periods->contains('id_periode', $this->periodeId)) {
                $this->periodeId = $periods->first()?->id_periode;
            }

            if (!empty($this->filterPeriodes)) {
                $allowedIds = $periods->pluck('id_periode')->toArray();
                $this->filterPeriodes = array_values(array_intersect($this->filterPeriodes, $allowedIds));
            }
        }

        $period = $periods->firstWhere('id_periode', $this->periodeId);

        $satkers = \Illuminate\Support\Facades\Cache::remember('satkers_not_3500', 3600, function() {
            return MasterSatker::where('kode_satker', '!=', '3500')->get();
        });

        $selectedPeriodIds = !empty($this->filterPeriodes) ? $this->filterPeriodes : ($this->periodeId ? [$this->periodeId] : []);
        $periodsMap = $periods->keyBy('id_periode');

        // Only fetch submissions for currently active/filtered period IDs (no unnecessary historical fetch)
        $allPeriodIdsToFetch = array_values(array_unique(array_filter(array_merge($selectedPeriodIds, [$this->periodeId]))));

        $rawSubmissionsCollection = Submission::with([
            'result.satker',
            'result.period',
            'evaluators',
            'visitasi.evaluators.user.roles',
            'interview.evaluators.user.roles',
        ])
            ->whereHas('result', fn($q) => $q->whereIn('id_periode', $allPeriodIdsToFetch))
            ->get();

        // Preload operator & evaluator users into static cache to prevent N+1 queries during attribute resolution
        Submission::preloadUsers($rawSubmissionsCollection);

        $allSubmissions = $rawSubmissionsCollection->groupBy(fn($sub) => $sub->result ? $sub->result->id_periode : 0);

        $isExternal = auth()->user()?->hasRole('penilai_eksternal');
        $allRecords = collect();

        foreach ($selectedPeriodIds as $pId) {
            $periodObj = $periodsMap->get($pId);
            $isPeriodEnded = $periodObj && (($periodObj->result_end && now()->toDateString() > $periodObj->result_end) || (method_exists($periodObj, 'getPhaseInfo') && $periodObj->getPhaseInfo()['phase'] === 'Selesai / Terpublikasi'));

            $submissionsInPeriod = ($allSubmissions->get($pId) ?? collect())
                ->keyBy(fn ($sub) => $sub->result ? $sub->result->kode_satker : '');

            foreach ($satkers as $satker) {
                $sub = $submissionsInPeriod->get($satker->kode_satker);

                if (!$sub) {
                    $sub = Submission::makeDummy($satker, $periodObj, $pId);
                } else {
                    $isSatkerFinal = ($sub->status >= 7) || ($sub->result?->published_at !== null) || $isPeriodEnded || ($sub->status == 8);
                    $sub->is_final = $isSatkerFinal;

                    if ($isExternal && !$isSatkerFinal) {
                        $sub->calculated_final = 0.0;
                        $sub->predikat = 'BELUM DINILAI';
                    } else {
                        $sub->calculated_final = $penilaianService->calculateFinalScoreFromSubmission($sub);

                        if ($sub->status == 8) {
                            $sub->predikat = 'TIDAK INFORMATIF';
                        } elseif ($sub->status < 6) {
                            $sub->predikat = 'BELUM DINILAI';
                        } else {
                            $sub->predikat = $penilaianService->getPredikat($sub->calculated_final);
                        }
                    }
                }

                $allRecords->push($sub);
            }
        }

        // Calculate currentPeriodRecords: reuse from allRecords if current period was processed, otherwise compute
        if (in_array($this->periodeId, $selectedPeriodIds)) {
            $currentPeriodRecords = $allRecords->filter(fn($sub) => $sub->result?->id_periode == $this->periodeId)->values();
        } else {
            $currentPeriodRecords = collect();
            $periodObj = $periodsMap->get($this->periodeId);
            $isPeriodEnded = $periodObj && (($periodObj->result_end && now()->toDateString() > $periodObj->result_end) || (method_exists($periodObj, 'getPhaseInfo') && $periodObj->getPhaseInfo()['phase'] === 'Selesai / Terpublikasi'));

            $submissionsInGlobalPeriod = ($allSubmissions->get($this->periodeId) ?? collect())
                ->keyBy(fn ($sub) => $sub->result ? $sub->result->kode_satker : '');

            foreach ($satkers as $satker) {
                $sub = $submissionsInGlobalPeriod->get($satker->kode_satker);
                if (!$sub) {
                    $sub = Submission::makeDummy($satker, $periodObj, $this->periodeId);
                } else {
                    $isSatkerFinal = ($sub->status >= 7) || ($sub->result?->published_at !== null) || $isPeriodEnded || ($sub->status == 8);
                    $sub->is_final = $isSatkerFinal;

                    if ($isExternal && !$isSatkerFinal) {
                        $sub->calculated_final = 0.0;
                        $sub->predikat = 'BELUM DINILAI';
                    } else {
                        $sub->calculated_final = $penilaianService->calculateFinalScoreFromSubmission($sub);

                        if ($sub->status == 8) {
                            $sub->predikat = 'TIDAK INFORMATIF';
                        } elseif ($sub->status < 6) {
                            $sub->predikat = 'BELUM DINILAI';
                        } else {
                            $sub->predikat = $penilaianService->getPredikat($sub->calculated_final);
                        }
                    }
                }
                $currentPeriodRecords->push($sub);
            }
        }

        // Find highest scoring satker in current period
        $highestRecord = $penilaianService->sortSubmissions($currentPeriodRecords, $this->periodeId, 'desc')->first();
        $highestKode = $highestRecord && $highestRecord->result ? $highestRecord->result->kode_satker : null;

        if (empty($this->chartKabkotKode)) {
            $this->chartKabkotKode = $highestKode ?? ($satkers->first()?->kode_satker ?? '3501');
        }

        // Sort dropdown satkers numerically by kode_satker ascending (3501, 3502, 3503...)
        $satkers = $satkers->sortBy('kode_satker')->values();

        // Sort by selected field and direction
        if ($this->sortField === 'calculated_final') {
            $allRecords = $penilaianService->sortSubmissions($allRecords, $this->periodeId, $this->sortDirection);
        } elseif ($this->sortDirection === 'asc') {
            $allRecords = $allRecords->sortBy($this->sortField)->values();
        } else {
            $allRecords = $allRecords->sortByDesc($this->sortField)->values();
        }
 
        $totalCount = $currentPeriodRecords->count();

        $allKomentars = \App\Models\KabagKomentar::where('id_periode', $this->periodeId)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        $latestKabagKomentar = $allKomentars->first();
        $komentars = $allKomentars->skip(1);
 
        // Calculate counts for SAQ, Visitasi, and Wawancara evaluation stages
        $countSudahSAQ = $currentPeriodRecords->filter(fn($s) => $s->id_submission !== null && $s->result?->ques_score !== null)->count();
        $countSudahVisit = $currentPeriodRecords->filter(fn($s) => $s->visitasi !== null && $s->visitasi->visit_score !== null)->count();
        $countSudahInterview = $currentPeriodRecords->filter(fn($s) => $s->interview !== null && $s->interview->interview_score !== null)->count();
 
        // --- Apply server-side filters to $allRecords before pagination ---
        $filteredRecords = $allRecords;
 
        // Search filter (by satker name)
        if (!empty($this->search)) {
            $searchLower = strtolower($this->search);
            $filteredRecords = $filteredRecords->filter(function ($sub) use ($searchLower) {
                $nama = strtolower($sub->result->satker->nama_satker ?? '');
                return str_contains($nama, $searchLower);
            });
        }
 
        // Kabkot filter
        if (!empty($this->filterKabkots)) {
            $filteredKabkots = array_map('strtolower', (array) $this->filterKabkots);
            $filteredRecords = $filteredRecords->filter(function ($sub) use ($filteredKabkots) {
                $nama = strtolower($sub->result->satker->nama_satker ?? '');
                return in_array($nama, $filteredKabkots);
            });
        }
 
        // Kode Satker filter
        if (!empty($this->filterKodes)) {
            $filterKodes = (array) $this->filterKodes;
            $filteredRecords = $filteredRecords->filter(function ($sub) use ($filterKodes) {
                $kode = $sub->result->kode_satker ?? '';
                return in_array($kode, $filterKodes);
            });
        }
 
        // Status/predikat filter
        if (!empty($this->filterStatuses)) {
            $filterStatuses = (array) $this->filterStatuses;
            $filteredRecords = $filteredRecords->filter(function ($sub) use ($filterStatuses) {
                return in_array($sub->predikat, $filterStatuses);
            });
        }
 
        $filteredRecords = $filteredRecords->values();
        $filteredTotal = $filteredRecords->count();
 
        // Geographic mapping scores calculation
        $regionMap = [
            'BPS Kota Surabaya'         => 'surabaya',          'BPS Kabupaten Sidoarjo'    => 'surabaya',
            'BPS Kabupaten Gresik'      => 'surabaya',          'BPS Kabupaten Mojokerto'   => 'surabaya',
            'BPS Kota Mojokerto'        => 'surabaya',
            'BPS Kabupaten Malang'      => 'malang',            'BPS Kota Malang'           => 'malang',
            'BPS Kota Batu'             => 'malang',
            'BPS Kabupaten Madiun'      => 'mataraman_barat',   'BPS Kota Madiun'           => 'mataraman_barat',
            'BPS Kabupaten Pacitan'     => 'mataraman_barat',   'BPS Kabupaten Ponorogo'    => 'mataraman_barat',
            'BPS Kabupaten Magetan'     => 'mataraman_barat',   'BPS Kabupaten Ngawi'       => 'mataraman_barat',
            'BPS Kabupaten Trenggalek'  => 'mataraman_barat',   'BPS Kabupaten Nganjuk'     => 'mataraman_barat',
            'BPS Kabupaten Kediri'      => 'mataraman_timur',   'BPS Kota Kediri'           => 'mataraman_timur',
            'BPS Kabupaten Blitar'      => 'mataraman_timur',   'BPS Kota Blitar'           => 'mataraman_timur',
            'BPS Kabupaten Tulungagung' => 'mataraman_timur',   'BPS Kabupaten Jombang'     => 'mataraman_timur',
            'BPS Kabupaten Bojonegoro'  => 'pantura',           'BPS Kabupaten Tuban'       => 'pantura',
            'BPS Kabupaten Lamongan'    => 'pantura',
            'BPS Kabupaten Jember'      => 'tapal_kuda_tengah', 'BPS Kabupaten Lumajang'    => 'tapal_kuda_tengah',
            'BPS Kabupaten Probolinggo' => 'tapal_kuda_tengah', 'BPS Kota Probolinggo'      => 'tapal_kuda_tengah',
            'BPS Kabupaten Pasuruan'    => 'tapal_kuda_tengah', 'BPS Kota Pasuruan'         => 'tapal_kuda_tengah',
            'BPS Kabupaten Banyuwangi'  => 'ujung_timur',       'BPS Kabupaten Bondowoso'   => 'ujung_timur',
            'BPS Kabupaten Situbondo'   => 'ujung_timur',
            'BPS Kabupaten Bangkalan'   => 'madura',            'BPS Kabupaten Sampang'     => 'madura',
            'BPS Kabupaten Pamekasan'   => 'madura',            'BPS Kabupaten Sumenep'     => 'madura',
        ];
 
        $regionScores = [
            'surabaya'         => ['name' => 'Surabaya & Sidoarjo', 'score' => 0.0, 'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'malang'           => ['name' => 'Malang Raya',         'score' => 0.0, 'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'mataraman_barat'  => ['name' => 'Mataraman Barat',     'score' => 0.0, 'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'mataraman_timur'  => ['name' => 'Mataraman Timur',     'score' => 0.0, 'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'pantura'          => ['name' => 'Pantura',             'score' => 0.0, 'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'tapal_kuda_tengah'=> ['name' => 'Tapal Kuda Tengah',  'score' => 0.0, 'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'ujung_timur'      => ['name' => 'Ujung Timur',        'score' => 0.0, 'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            'madura'           => ['name' => 'Wilayah Madura',     'score' => 0.0, 'status' => 'BELUM DINILAI', 'color' => '#94a3b8'],
        ];
 
        $satkerScores = [];
        foreach ($currentPeriodRecords as $sub) {
            $satkerName = $sub->result->satker->nama_satker ?? '';
            $cleanName = trim(str_replace('BPS', '', $satkerName));
            $score = $sub->calculated_final;
            $status = $sub->predikat;
 
            if ($score >= 90)      { $color = '#388E3C'; }
            elseif ($score >= 80)  { $color = '#68B92E'; }
            elseif ($score >= 60)  { $color = '#4C84B0'; }
            elseif ($score >= 40)  { $color = '#F7B750'; }
            else                  { $color = ($score > 0) ? '#E82D1F' : '#94a3b8'; }
 
            $satkerScores[$cleanName] = [
                'score' => $score,
                'status' => $status,
                'color' => $color,
            ];
 
            $regionKey = $regionMap[$satkerName] ?? null;
            if ($regionKey && $score > 0) {
                if (!isset($regionScores[$regionKey]['sum'])) {
                    $regionScores[$regionKey]['sum'] = 0;
                    $regionScores[$regionKey]['count'] = 0;
                }
                $regionScores[$regionKey]['sum'] += $score;
                $regionScores[$regionKey]['count'] += 1;
            }
        }
 
        foreach ($regionScores as $key => &$r) {
            if (isset($r['count']) && $r['count'] > 0) {
                $r['score'] = round($r['sum'] / $r['count'], 2);
                if ($r['score'] >= 90)      { $r['status'] = 'Informatif';        $r['color'] = '#388E3C'; }
                elseif ($r['score'] >= 80)  { $r['status'] = 'Menuju Informatif'; $r['color'] = '#68B92E'; }
                elseif ($r['score'] >= 60)  { $r['status'] = 'Cukup Informatif';  $r['color'] = '#4C84B0'; }
                elseif ($r['score'] >= 40)  { $r['status'] = 'Kurang Informatif'; $r['color'] = '#F7B750'; }
                else                        { $r['status'] = 'Tidak Informatif';  $r['color'] = '#E82D1F'; }
            } else {
                $r['score'] = 0.00;
                $r['status'] = 'BELUM DINILAI';
                $r['color'] = '#94a3b8';
            }
            unset($r['sum'], $r['count']);
        }
        unset($r);
 
        // Manual pagination on filtered records
        $currentPage   = $this->getPage() ?: 1;
        $paginatedItems = $filteredRecords->forPage($currentPage, $this->perPage);
        $submissions = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $filteredTotal,
            $this->perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );
 
        // --- Indicator histogram data ---
        $targetPeriodIdForWeight = (count($selectedPeriodIds) === 1) ? $selectedPeriodIds[0] : $this->periodeId;
        $saqWeights = $penilaianService->getSaqCategoryWeights($targetPeriodIdForWeight);
        $pembobotan = $penilaianService->getPembobotanConfig($targetPeriodIdForWeight);
 
        $allIndicators = \Illuminate\Support\Facades\Cache::remember('master_indicators_all', 3600, function() {
            return MasterIndikator::get();
        });
        $dynamicMaxScores = \Illuminate\Support\Facades\Cache::remember('dynamic_max_scores_by_ind', 3600, function() {
            return MasterPertanyaan::selectRaw('id_indicators, sum(bobot_pertanyaan) as max_score')
                ->groupBy('id_indicators')
                ->pluck('max_score', 'id_indicators')
                ->toArray();
        });
 
        // Filter indicators to only include those active for the current period (has max_score > 0 and assigned weight)
        $indicators = $allIndicators->filter(function($ind) use ($dynamicMaxScores, $saqWeights) {
            $hasMaxScore = ($dynamicMaxScores[$ind->id_indicators] ?? 0) > 0;
            $hasWeight = !empty($saqWeights) ? isset($saqWeights[$ind->id_indicators]) : true;
            return $hasMaxScore && $hasWeight;
        })->values();
 
        // Build a map: kode_satker -> [id_indicators -> score%]
        $indicatorScoresPerKabkot = [];
        $submissionsInGlobalPeriodForInd = $rawSubmissionsCollection
            ->filter(fn($sub) => $sub->result && $sub->result->id_periode == $this->periodeId)
            ->keyBy(fn($s) => $s->result->kode_satker);
 
        foreach ($satkers as $satker) {
            $sub = $submissionsInGlobalPeriodForInd->get($satker->kode_satker);
            $answersByInd = ($sub && $sub->answers) ? $sub->answers->groupBy('id_indicators') : collect();
 
            $indScores = [];
            foreach ($indicators as $ind) {
                $maxScore = $dynamicMaxScores[$ind->id_indicators] ?? 1.0;
                $earned = isset($answersByInd[$ind->id_indicators]) ? $answersByInd[$ind->id_indicators]->sum('answer_score') : 0.0;
                $indScores[$ind->id_indicators] = round(($earned / max($maxScore, 1)) * 100, 2);
            }
            $indicatorScoresPerKabkot[$satker->kode_satker] = $indScores;
        }
 
        return view('livewire.evaluasi.penilaian-monitoring-table', [
            'submissions'             => $submissions,
            'allSubmissions'          => $allRecords,
            'period'                  => $period,
            'periods'                 => $periods,
            'satkers'                 => $satkers,
            'pembobotan'              => $pembobotan,
            'stageOrder'              => $pembobotan->resolvedStageOrder(),
            'latestKabagKomentar'     => $latestKabagKomentar,
            'komentars'               => $komentars,
            'totalCount'              => $totalCount,
            'filteredTotal'           => $filteredTotal,
            'countSudahSAQ'           => $countSudahSAQ,
            'countSudahVisit'         => $countSudahVisit,
            'countSudahInterview'     => $countSudahInterview,
            'regionScores'            => $regionScores,
            'satkerScores'            => $satkerScores,
            'indicators'              => $indicators,
            'indicatorScoresPerKabkot' => $indicatorScoresPerKabkot,
            'chartKabkotKode'         => $this->chartKabkotKode,
        ]);
    }
}
