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
use App\Services\PenilaianService;
use Livewire\Component;
use Livewire\WithPagination;
 
class PenilaianDetailTable extends Component
{
    use WithPagination;
 
    public $userSatkerKode = '';
 
    public $periodeId;
    public $perPage = 10;
 
    // Server-side filter properties
    public $search = '';
    public $filterKabkots = [];   // array of satker names (lowercase)
    public $filterKodes = [];     // array of satker kodes
    public $filterPeriodes = [];  // array of period IDs
    public $filterStatuses = [];  // array of predikat values e.g. ['INFORMATIF', 'BELUM DINILAI']
 
    // Selected kabkot kode for line chart (single selection)
    public $chartKabkotKode = '';
 
    public function mount($periodeId, $userSatkerKode = null)
    {
        $this->periodeId = $periodeId;
        $this->userSatkerKode = $userSatkerKode ?: (auth()->user()?->kode_satker ?? auth()->user()?->masterSatker?->kode_satker ?? '');
    }
 
    public function updatingPeriodeId()
    {
        $this->resetPage();
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
     * Called from JS when user selects a specific kabkot for the chart.
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
        $periods = Period::getAvailablePeriodsForUser();
        $period = $periods->firstWhere('id_periode', $this->periodeId) ?? $periods->first();
        if ($period) {
            $this->periodeId = $period->id_periode;
        }
        $allPeriods = $periods;
 
        $satkers = \Illuminate\Support\Facades\Cache::remember('satkers_not_3500', 3600, function() {
            return MasterSatker::where('kode_satker', '!=', '3500')->get();
        });
 
        $selectedPeriodIds = !empty($this->filterPeriodes) ? $this->filterPeriodes : [$this->periodeId];
        $periodsMap = $periods->keyBy('id_periode');
 
        $chartPeriodIds = $allPeriods->pluck('id_periode')->toArray();
        $allPeriodIdsToFetch = array_values(array_unique(array_merge($selectedPeriodIds, [$this->periodeId], $chartPeriodIds)));
 
        $rawSubmissionsCollection = Submission::with([
            'result.satker',
            'result.period',
            'evaluators',
            'visitasi.evaluators.user.roles',
            'interview.evaluators.user.roles',
        ])
            ->whereHas('result', fn($q) => $q->whereIn('id_periode', $allPeriodIdsToFetch))
            ->get();
 
        Submission::preloadUsers($rawSubmissionsCollection);
 
        $allSubmissions = $rawSubmissionsCollection->groupBy(fn($sub) => $sub->result ? $sub->result->id_periode : 0);
 
        $allRecords = collect();
 
        foreach ($selectedPeriodIds as $pId) {
            $submissionsInPeriod = ($allSubmissions->get($pId) ?? collect())
                ->keyBy(fn ($sub) => $sub->result ? $sub->result->kode_satker : '');
 
            foreach ($satkers as $satker) {
                $sub = $submissionsInPeriod->get($satker->kode_satker);
 
                if (!$sub) {
                    $sub = new Submission();
                    $sub->id_submission    = null;
                    $sub->status           = 0;
                    $sub->calculated_final = 0.0;
                    $sub->predikat         = 'BELUM DINILAI';
 
                    $resultMock = new Result();
                    $resultMock->kode_satker = $satker->kode_satker;
                    $resultMock->id_periode  = $pId;
                    $resultMock->setRelation('satker', $satker);
                    $resultMock->setRelation('period', $periodsMap->get($pId));
 
                    $sub->setRelation('result', $resultMock);
                    $sub->setRelation('visitasi', null);
                    $sub->setRelation('interview', null);
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
 
                $allRecords->push($sub);
            }
        }
 
        // Sort descending by final score with tie-breaker logic
        $allRecords = $penilaianService->sortSubmissions($allRecords, $this->periodeId, 'desc');
 
        // Calculate target page for userSatkerKode dynamically on first load
        if ($this->userSatkerKode && $this->getPage() == 1) {
            foreach ($allRecords as $index => $rec) {
                $kode = $rec->result ? $rec->result->kode_satker : '';
                if ($kode == $this->userSatkerKode) {
                    $targetPage = (int) ceil(($index + 1) / $this->perPage);
                    if ($targetPage > 1) {
                        $this->setPage($targetPage);
                    }
                    break;
                }
            }
        }
 
        // Reuse allRecords if selectedPeriodIds === [$this->periodeId]
        if (count($selectedPeriodIds) === 1 && $selectedPeriodIds[0] == $this->periodeId) {
            $currentPeriodRecords = $allRecords;
        } else {
            $currentPeriodRecords = collect();
            $submissionsInGlobalPeriod = ($allSubmissions->get($this->periodeId) ?? collect())
                ->keyBy(fn ($sub) => $sub->result ? $sub->result->kode_satker : '');
 
            foreach ($satkers as $satker) {
                $sub = $submissionsInGlobalPeriod->get($satker->kode_satker);
                if (!$sub) {
                    $sub = new Submission();
                    $sub->id_submission    = null;
                    $sub->status           = 0;
                    $sub->calculated_final = 0.0;
                    $sub->predikat         = 'BELUM DINILAI';
 
                    $resultMock = new Result();
                    $resultMock->kode_satker = $satker->kode_satker;
                    $resultMock->id_periode  = $this->periodeId;
                    $resultMock->setRelation('satker', $satker);
                    $resultMock->setRelation('period', $periodsMap->get($this->periodeId));
 
                    $sub->setRelation('result', $resultMock);
                    $sub->setRelation('visitasi', null);
                    $sub->setRelation('interview', null);
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
                $currentPeriodRecords->push($sub);
            }
        }
 
        // Find highest scoring satker in current period
        $highestRecord = $penilaianService->sortSubmissions($currentPeriodRecords, $this->periodeId, 'desc')->first();
        $highestKode = $highestRecord && $highestRecord->result ? $highestRecord->result->kode_satker : null;

        if (empty($this->chartKabkotKode) && $highestKode) {
            $this->chartKabkotKode = $highestKode;
        }
 
        // Sort dropdown satkers numerically by kode_satker ascending (3501, 3502, 3503...)
        $satkers = $satkers->sortBy('kode_satker')->values();
 
        // --- Counts for donut chart (always on current global period records, unfiltered) ---
        $countInformatif = $currentPeriodRecords
            ->filter(fn($s) => $s->predikat === 'INFORMATIF' && in_array($s->status, [6, 7]))
            ->count();
        $countMenuju = $currentPeriodRecords
            ->filter(fn($s) => $s->predikat === 'MENUJU INFORMATIF' && in_array($s->status, [6, 7]))
            ->count();
        $countCukup = $currentPeriodRecords
            ->filter(fn($s) => $s->predikat === 'CUKUP INFORMATIF' && in_array($s->status, [6, 7]))
            ->count();
        $countKurang = $currentPeriodRecords
            ->filter(fn($s) => $s->predikat === 'KURANG INFORMATIF' && in_array($s->status, [6, 7]))
            ->count();
        $countTidak = $currentPeriodRecords
            ->filter(
                fn($s) => ($s->predikat === 'TIDAK INFORMATIF' && in_array($s->status, [6, 7])) || $s->status == 8,
            )
            ->count();
        $countBelum = $currentPeriodRecords->filter(fn($s) => $s->status < 6)->count();
        $totalCount = $currentPeriodRecords->count();
 
        $pctInformatif = $totalCount > 0 ? round(($countInformatif / $totalCount) * 100, 1) : 0;
        $pctMenuju = $totalCount > 0 ? round(($countMenuju / $totalCount) * 100, 1) : 0;
        $pctCukup = $totalCount > 0 ? round(($countCukup / $totalCount) * 100, 1) : 0;
        $pctKurang = $totalCount > 0 ? round(($countKurang / $totalCount) * 100, 1) : 0;
        $pctTidak = $totalCount > 0 ? round(($countTidak / $totalCount) * 100, 1) : 0;
        $pctBelum = $totalCount > 0 ? round(($countBelum / $totalCount) * 100, 1) : 0;
 
        $latestKabagKomentar = \App\Models\KabagKomentar::where('id_periode', $this->periodeId)
            ->with('user')
            ->orderByDesc('created_at')
            ->first();
 
        $chartYears = [];
        $chartAvgs = [];
        $allResultsGlobally = $rawSubmissionsCollection
            ->pluck('result')
            ->filter()
            ->groupBy('id_periode');
 
        foreach ($allPeriods as $p) {
            $chartYears[] = (string) $p->tahun;
            $resInP = $allResultsGlobally->get($p->id_periode) ?? collect();
            $chartAvgs[] = $resInP->count() > 0 ? round($resInP->avg('final_score'), 2) : 0;
        }
 
        // Map satker scores across all periods
        $satkerScoresMap = [];
        foreach ($satkers as $satker) {
            $scores = [];
            foreach ($allPeriods as $p) {
                $subsInP = $allSubmissions->get($p->id_periode) ?? collect();
                $sub = $subsInP->first(function($s) use ($satker) {
                    return $s->result && $s->result->kode_satker === $satker->kode_satker;
                });
                $satkerScore = 0;
                if ($sub) {
                    $satkerScore = $penilaianService->calculateFinalScoreFromSubmission($sub);
                }
                $scores[] = $satkerScore;
            }
            $satkerScoresMap[$satker->kode_satker] = $scores;
        }
 
        // Dispatch the updated event to sync Echarts on frontend
        $this->dispatch('periode-updated', [
            'total' => $totalCount,
            'items' => [
                ['count' => $countInformatif, 'pct' => $pctInformatif, 'label' => 'INFORMATIF', 'color' => '#388E3C'],
                ['count' => $countMenuju, 'pct' => $pctMenuju, 'label' => 'MENUJU INFORMATIF', 'color' => '#68B92E'],
                ['count' => $countCukup, 'pct' => $pctCukup, 'label' => 'CUKUP INFORMATIF', 'color' => '#4C84B0'],
                ['count' => $countKurang, 'pct' => $pctKurang, 'label' => 'KURANG INFORMATIF', 'color' => '#F7B750'],
                ['count' => $countTidak, 'pct' => $pctTidak, 'label' => 'TIDAK INFORMATIF', 'color' => '#E82D1F'],
                ['count' => $countBelum, 'pct' => $pctBelum, 'label' => 'BELUM DINILAI', 'color' => '#94a3b8'],
            ]
        ]);
 
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
            $filteredKabkots = array_map('strtolower', $this->filterKabkots);
            $filteredRecords = $filteredRecords->filter(function ($sub) use ($filteredKabkots) {
                $nama = strtolower($sub->result->satker->nama_satker ?? '');
                return in_array($nama, $filteredKabkots);
            });
        }
 
        // Kode Satker filter
        if (!empty($this->filterKodes)) {
            $filterKodes = $this->filterKodes;
            $filteredRecords = $filteredRecords->filter(function ($sub) use ($filterKodes) {
                $kode = $sub->result->kode_satker ?? '';
                return in_array($kode, $filterKodes);
            });
        }
 
        // Status/predikat filter
        if (!empty($this->filterStatuses)) {
            $filterStatuses = $this->filterStatuses;
            $filteredRecords = $filteredRecords->filter(function ($sub) use ($filterStatuses) {
                return in_array($sub->predikat, $filterStatuses);
            });
        }
 
        $filteredRecords = $filteredRecords->values();
        $filteredTotal = $filteredRecords->count();
 
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
 
        $targetPeriodIdForWeight = (!empty($this->filterPeriodes) && count($this->filterPeriodes) === 1) ? $this->filterPeriodes[0] : $this->periodeId;
        $pembobotan = $penilaianService->getPembobotanConfig($targetPeriodIdForWeight);

        return view('livewire.evaluasi.penilaian-detail-table', [
            'submissions'    => $submissions,
            'allSubmissions' => $allRecords,
            'period'         => $period,
            'periods'        => $periods,
            'satkers'        => $satkers,
            'pembobotan'     => $pembobotan,
            'stageOrder'     => $pembobotan->resolvedStageOrder(),
            'latestKabagKomentar' => $latestKabagKomentar,
            'countInformatif' => $countInformatif,
            'countMenuju' => $countMenuju,
            'countCukup' => $countCukup,
            'countKurang' => $countKurang,
            'countTidak' => $countTidak,
            'countBelum' => $countBelum,
            'totalCount' => $totalCount,
            'filteredTotal' => $filteredTotal,
            'pctInformatif' => $pctInformatif,
            'pctMenuju' => $pctMenuju,
            'pctCukup' => $pctCukup,
            'pctKurang' => $pctKurang,
            'pctTidak' => $pctTidak,
            'pctBelum' => $pctBelum,
            'chartYears' => $chartYears,
            'chartAvgs' => $chartAvgs,
            'satkerScoresMap' => $satkerScoresMap,
            'countSudahSAQ' => $countSudahSAQ,
            'countSudahVisit' => $countSudahVisit,
            'countSudahInterview' => $countSudahInterview,
        ]);
    }
}
