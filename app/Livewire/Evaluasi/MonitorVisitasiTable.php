<?php
 
namespace App\Livewire\Evaluasi;
 
use App\Models\MasterSatker;
use App\Models\Submission;
use App\Models\User;
use App\Models\Period;
use App\Models\Visitasi;
use Livewire\Component;
use Livewire\WithPagination;
 
class MonitorVisitasiTable extends Component
{
    use WithPagination;
 
    public $search = '';
    public $perPage = 10;
    public $periodeId;
 
    // Excel-style column filters
    public $filterTahun = [];       // array of years (string); empty = all (semua periode)
    public $filterKodeSatker = [];  // array of kode_satker
    public $filterNamaSatker = [];  // array of lowercase nama_satker
    public $filterEvaluator = [];   // array of lowercase evaluator names
    public $filterStatus = [];      // array of status keys: not_started, draft, submitted
    public $filterMode = [];        // array of mode keys: offline, online_meeting, online_upload, none
 
    // Stats variables
    public $totalLolos = 0;
    public $visitedCount = 0;
    public $notVisitedCount = 0;
 
    public function mount($periodeId = null)
    {
        if ($periodeId) {
            $this->periodeId = $periodeId;
            $period = Period::resolvePeriod($periodeId);
        } else {
            $latestPeriod = Period::getActivePeriod();
            $this->periodeId = $latestPeriod?->id_periode;
            $period = $latestPeriod;
        }

        // Default: show only target period year
        if ($period) {
            $this->filterTahun = [(string) ($period->tahun ?? \Carbon\Carbon::parse($period->periode_start)->year)];
        }

        $this->calculateStats();
    }

    public function calculateStats()
    {
        if (!$this->periodeId) {
            $this->totalLolos = 0;
            $this->visitedCount = 0;
            $this->notVisitedCount = 0;
            return;
        }

        $visitasiRequiresWawancara = \App\Models\MasterPembobotan::visitasiRequiresWawancara($this->periodeId);

        $subQuery = Submission::whereHas('result', fn($q) => $q->where('id_periode', $this->periodeId))
            ->whereIn('status', [5, 6, 7]);

        if ($visitasiRequiresWawancara) {
            $subQuery->where(function ($q) {
                $q->whereHas('interview', function ($iq) {
                    $iq->whereNotNull('interview_score');
                })->orWhereHas('visitasi', function ($vq) {
                    $vq->whereNotNull('visit_at')
                       ->orWhereNotNull('visit_score')
                       ->orWhereHas('evaluators');
                });
            });
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $isEksternal = $user?->role?->nama_role === 'penilai_eksternal';
        $userId = $user?->id_user;

        if ($isEksternal && $userId) {
            $subQuery->whereHas('visitasi.evaluators', fn($ev) => $ev->where('id_user', $userId));

            $myVisitsQuery = Visitasi::with('evaluators')
                ->whereHas('result', fn($q) => $q->where('id_periode', $this->periodeId))
                ->whereHas('evaluators', fn($ev) => $ev->where('id_user', $userId))
                ->get();

            $this->totalLolos = $myVisitsQuery->count();
            $this->visitedCount = $myVisitsQuery->filter(function($v) use ($userId) {
                $myEval = $v->evaluators->firstWhere('id_user', $userId);
                return $myEval && $myEval->score !== null && !$myEval->is_draft;
            })->count();
            $this->notVisitedCount = max(0, $this->totalLolos - $this->visitedCount);
            return;
        }

        $this->totalLolos = $subQuery->count();

        $visitedQuery = Visitasi::whereHas('result', fn($q) => $q->where('id_periode', $this->periodeId))
            ->whereNotNull('visit_score');

        $this->visitedCount = $visitedQuery->count();

        $this->notVisitedCount = max(0, $this->totalLolos - $this->visitedCount);
    }

    public function updatedPeriodeId()
    {
        $this->calculateStats();
        $this->resetPage();
    }
 
    public function updatingSearch()
    {
        $this->resetPage();
    }
 
    public function updatingPerPage()
    {
        $this->resetPage();
    }
 
    public function updatingFilterTahun()
    {
        $this->resetPage();
    }
 
    public function updatingFilterKodeSatker()
    {
        $this->resetPage();
    }
 
    public function updatingFilterNamaSatker()
    {
        $this->resetPage();
    }
 
    public function updatingFilterEvaluator()
    {
        $this->resetPage();
    }
 
    public function updatingFilterStatus()
    {
        $this->resetPage();
    }
 
    public function updatingFilterMode()
    {
        $this->resetPage();
    }
 
    public function setFilterTahun(array $years)
    {
        $this->filterTahun = $years;
        $this->resetPage();
    }
 
    public function setFilterKodeSatker(array $kodes)
    {
        $this->filterKodeSatker = $kodes;
        $this->resetPage();
    }
 
    public function setFilterNamaSatker(array $names)
    {
        $this->filterNamaSatker = $names;
        $this->resetPage();
    }
 
    public function setFilterEvaluator(array $names)
    {
        $this->filterEvaluator = $names;
        $this->resetPage();
    }
 
    public function setFilterStatus(array $statuses)
    {
        $this->filterStatus = $statuses;
        $this->resetPage();
    }
 
    public function setFilterMode(array $modes)
    {
        $this->filterMode = $modes;
        $this->resetPage();
    }
 
    public function resetFilters()
    {
        $this->search = '';
        $this->filterKodeSatker = [];
        $this->filterNamaSatker = [];
        $this->filterEvaluator = [];
        $this->filterStatus = [];
        $this->filterMode = [];
        
        $latestPeriod = Period::getActivePeriod();
        
        if ($latestPeriod) {
            $this->filterTahun = [(string) ($latestPeriod->tahun ?? \Carbon\Carbon::parse($latestPeriod->periode_start)->year)];
            $this->periodeId = $latestPeriod->id_periode;
        } else {
            $this->filterTahun = [];
        }
 
        $this->calculateStats();
        $this->resetPage();
    }
 
    public function render()
    {
        $periods = \Illuminate\Support\Facades\Cache::remember('periods_desc', 3600, function() {
            return Period::orderByDesc('periode_start')->get();
        });
        $satkers = \Illuminate\Support\Facades\Cache::remember('satkers_not_3500', 3600, function() {
            return MasterSatker::where('kode_satker', '!=', '3500')->orderBy('nama_satker')->get();
        });
        $period = $periods->firstWhere('id_periode', $this->periodeId);
 
        if (!$period && $periods->isNotEmpty()) {
            $period = $periods->first();
            $this->periodeId = $period->id_periode;
            $this->calculateStats();
        }
 
        $targetPeriodId = $period?->id_periode ?? $this->periodeId;
        $visitasiRequiresWawancara = \App\Models\MasterPembobotan::visitasiRequiresWawancara($targetPeriodId);

        // Build query — filter by selected years
        $query = Submission::with(['result.satker', 'visitasi.evaluators.user.roles', 'result.period'])
            ->from('submissions')
            ->whereIn('submissions.status', [5, 6, 7]);

        if ($visitasiRequiresWawancara) {
            $query->where(function ($q) {
                $q->whereHas('interview', function ($iq) {
                    $iq->whereNotNull('interview_score');
                })->orWhereHas('visitasi', function ($vq) {
                    $vq->whereNotNull('visit_at')
                       ->orWhereNotNull('visit_score')
                       ->orWhereHas('evaluators');
                });
            });
        }

        // Penilai Eksternal: Hanya tampilkan satker yang ditugaskan kepada user yang sedang login
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user?->role?->nama_role === 'penilai_eksternal' && $user->id_user) {
            $userId = $user->id_user;
            $query->whereHas('visitasi.evaluators', fn($ev) => $ev->where('id_user', $userId));
        }
        if (!empty($this->filterTahun)) {
            $selectedYears = (array) $this->filterTahun;
            $periodIds = $periods->filter(function ($p) use ($selectedYears) {
                $startYear = \Carbon\Carbon::parse($p->periode_start)->year;
                return in_array((string) $startYear, $selectedYears) || in_array((string) ($p->tahun ?? ''), $selectedYears);
            })->pluck('id_periode')->toArray();

            $query->whereHas('result', fn($q) => $q->whereIn('id_periode', $periodIds));
        }

        if (!empty(trim($this->search))) {
            $searchWildcard = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchWildcard) {
                $q->whereHas('result.satker', function ($sq) use ($searchWildcard) {
                    $sq->where('nama_satker', 'like', $searchWildcard)
                       ->orWhere('kode_satker', 'like', $searchWildcard);
                });
            });
        }

        // Status column filter
        if (!empty($this->filterStatus)) {
            $selectedStatuses = (array) $this->filterStatus;
            $query->where(function ($q) use ($selectedStatuses) {
                foreach ($selectedStatuses as $statusKey) {
                    $q->orWhere(function ($sq) use ($statusKey) {
                        if ($statusKey === 'submitted') {
                            $sq->whereHas('visitasi', function ($vq) {
                                $vq->whereNotNull('visit_score');
                            });
                        } elseif ($statusKey === 'draft') {
                            $sq->whereHas('visitasi', function ($vq) {
                                $vq->whereNull('visit_score')
                                  ->whereHas('evaluators');
                            });
                        } elseif ($statusKey === 'not_started') {
                            $sq->where(function ($inner) {
                                $inner->whereDoesntHave('visitasi')
                                  ->orWhereHas('visitasi', function ($vq) {
                                      $vq->whereNull('visit_score')
                                        ->whereDoesntHave('evaluators');
                                  });
                            });
                        }
                    });
                }
            });
        }

        // Mode column filter
        if (!empty($this->filterMode)) {
            $selectedModes = (array) $this->filterMode;
            $query->where(function ($q) use ($selectedModes) {
                foreach ($selectedModes as $modeKey) {
                    $q->orWhere(function ($sq) use ($modeKey) {
                        if ($modeKey === 'online_upload') {
                            $sq->whereHas('visitasi', function ($vq) {
                                $vq->where('visit_mode', 'online_upload');
                            });
                        } elseif ($modeKey === 'online_meeting') {
                            $sq->whereHas('visitasi', function ($vq) {
                                $vq->where('visit_mode', 'online_meeting')
                                   ->where(function ($inner) {
                                       $inner->whereNotNull('visit_at')
                                             ->orWhereHas('evaluators');
                                   });
                            });
                        } elseif ($modeKey === 'offline') {
                            $sq->whereHas('visitasi', function ($vq) {
                                $vq->where(function ($inner) {
                                    $inner->where('visit_mode', 'offline')
                                          ->orWhereNull('visit_mode');
                                })->where(function ($inner) {
                                    $inner->whereNotNull('visit_at')
                                          ->orWhereHas('evaluators');
                                });
                            });
                        } elseif ($modeKey === 'none') {
                            $sq->where(function ($inner) {
                                $inner->whereDoesntHave('visitasi')
                                  ->orWhereHas('visitasi', function ($vq) {
                                      $vq->where('visit_mode', '!=', 'online_upload')
                                        ->whereNull('visit_at')
                                        ->whereDoesntHave('evaluators');
                                  });
                            });
                        }
                    });
                }
            });
        }
 
        // Apply filters at DB level before pagination
        if (!empty($this->filterKodeSatker)) {
            $query->whereHas('result.satker', function ($q) {
                $q->whereIn('kode_satker', (array) $this->filterKodeSatker);
            });
        }
 
        if (!empty($this->filterNamaSatker)) {
            $query->whereHas('result.satker', function ($q) {
                $q->whereIn('nama_satker', (array) $this->filterNamaSatker);
            });
        }
 
        if (!empty($this->filterEvaluator)) {
            $filterEvals = array_map('strtolower', (array) $this->filterEvaluator);
            $matchingUserIds = Submission::userIdsFromEvaluatorNames($filterEvals);

            $query->whereHas('visitasi.evaluators', fn($ev) => $ev->whereIn('id_user', $matchingUserIds));
        }
 
        // Sorting
        $query->leftJoin('visitasi', 'submissions.id_result', '=', 'visitasi.id_result')
              ->select('submissions.*')
              ->orderByRaw("
                  CASE
                      WHEN visitasi.visit_score IS NOT NULL                                                   THEN 3
                      WHEN (visitasi.visit_at IS NOT NULL OR visitasi.visit_mode = 'online_upload') AND visitasi.visit_score IS NULL THEN 1
                      WHEN visitasi.visit_at IS NULL OR visitasi.id_visit IS NULL                             THEN 2
                      ELSE 4
                  END ASC
              ")
              ->orderBy('submissions.id_submission', 'asc');
 
        $submissions = $query->paginate($this->perPage);

        // Preload operator users ke cache agar getUserAttribute() tidak N+1
        Submission::preloadUsers($submissions->getCollection());

        // Preload evaluator users (dengan roles) dari visitasi yang terload — cegah N+1 getEvaluatorsAttribute()
        $loadedVisitasis = $submissions->getCollection()->map(fn($s) => $s->visitasi)->filter();
        Visitasi::preloadEvaluatorUsers($loadedVisitasis);

        // Fetch all Evaluators for assignment dropdown (cached)
        $evaluators = \Illuminate\Support\Facades\Cache::remember('evaluators_with_roles_visitasi', 3600, function() {
            return User::role(['evaluator', 'super_evaluator', 'penilai_eksternal'])
                ->with('roles')
                ->get()
                ->sortBy(fn($user) => $user->hasRole('super_evaluator') ? 0 : ($user->hasRole('penilai_eksternal') ? 2 : 1))
                ->values();
        });
 
        return view('livewire.evaluasi.monitor-visitasi-table', [
            'submissions'     => $submissions,
            'period'          => $period,
            'periods'         => $periods,
            'satkers'         => $satkers,
            'evaluators'      => $evaluators,
            'totalLolos'      => $this->totalLolos,
            'visitedCount'    => $this->visitedCount,
            'notVisitedCount' => $this->notVisitedCount,
        ]);
    }
}
