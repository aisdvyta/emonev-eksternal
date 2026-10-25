<?php
 
namespace App\Livewire\Evaluasi;
 
use App\Models\MasterSatker;
use App\Models\Submission;
use App\Models\User;
use App\Models\Period;
use App\Models\Result;
use App\Models\Interview;
use App\Models\MasterPembobotan;
use Livewire\Component;
use Livewire\WithPagination;
 
class MonitorWawancaraTable extends Component
{
    use WithPagination;
 
    public $search = '';
    public $perPage = 10;
    public $periodeId;
 
    // Excel-style column filters
    public $filterTahun = [];       // array of years (string); empty = all
    public $filterKodeSatker = [];  // array of kode_satker
    public $filterNamaSatker = [];  // array of lowercase nama_satker
    public $filterEvaluator = [];   // array of lowercase evaluator names
    public $filterStatus = [];      // array of status keys: belum_dinilai, draft, sudah_dinilai
 
    // Stats variables
    public $totalLolos = 0;
    public $completedCount = 0;
    public $notCompletedCount = 0;
 
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
            $this->completedCount = 0;
            $this->notCompletedCount = 0;
            return;
        }

        $wawancaraRequiresVisitasi = MasterPembobotan::wawancaraRequiresVisitasi($this->periodeId);

        $subQuery = Submission::whereHas('result', fn($q) => $q->where('id_periode', $this->periodeId))
            ->whereIn('status', [5, 6, 7]);

        if ($wawancaraRequiresVisitasi) {
            $subQuery->where(function ($q) {
                $q->whereHas('visitasi', function ($vq) {
                    $vq->whereNotNull('visit_score');
                })->orWhereHas('interview', function ($iq) {
                    $iq->whereNotNull('interview_at')
                       ->orWhereNotNull('interview_score')
                       ->orWhereHas('evaluators');
                });
            });
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $isEksternal = $user?->role?->nama_role === 'penilai_eksternal';
        $userId = $user?->id_user;

        if ($isEksternal && $userId) {
            $subQuery->whereHas('interview.evaluators', fn($ev) => $ev->where('id_user', $userId));

            $mySubmissions = $subQuery->with(['interview.evaluators'])->get();
            $this->totalLolos = $mySubmissions->count();
            $this->completedCount = $mySubmissions->filter(function($sub) use ($userId) {
                $myEval = $sub->interview?->evaluators->firstWhere('id_user', $userId);
                return $myEval && $myEval->score !== null && !$myEval->is_draft;
            })->count();
            $this->notCompletedCount = max(0, $this->totalLolos - $this->completedCount);
            return;
        }

        $this->totalLolos = $subQuery->count();

        $completedQuery = Submission::whereHas('result', fn($q) => $q->where('id_periode', $this->periodeId))
            ->whereIn('status', [5, 6, 7])
            ->whereHas('interview', fn($q) => $q->whereNotNull('interview_score'));

        $this->completedCount = $completedQuery->count();

        $this->notCompletedCount = max(0, $this->totalLolos - $this->completedCount);
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
 
    public function resetFilters()
    {
        $this->search = '';
        $this->filterKodeSatker = [];
        $this->filterNamaSatker = [];
        $this->filterEvaluator = [];
        $this->filterStatus = [];
        
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
        $wawancaraRequiresVisitasi = MasterPembobotan::wawancaraRequiresVisitasi($targetPeriodId);

        $query = Submission::with(['result.satker', 'interview.evaluators.user.roles', 'result.period'])
            ->select('submissions.*')
            ->leftJoin('interview', 'submissions.id_result', '=', 'interview.id_result')
            ->whereIn('submissions.status', [5, 6, 7]);

        if ($wawancaraRequiresVisitasi) {
            $query->where(function ($q) {
                $q->whereHas('visitasi', function ($vq) {
                    $vq->whereNotNull('visit_score');
                })->orWhereHas('interview', function ($iq) {
                    $iq->whereNotNull('interview_at')
                       ->orWhereNotNull('interview_score')
                       ->orWhereHas('evaluators');
                });
            });
        }

        // Penilai Eksternal: Hanya tampilkan satker yang ditugaskan kepada user yang sedang login
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user?->role?->nama_role === 'penilai_eksternal' && $user->id_user) {
            $userId = $user->id_user;
            $query->whereHas('interview.evaluators', fn($ev) => $ev->where('id_user', $userId));
        }
 
        // Filter by selected years in DB
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
                        if ($statusKey === 'sudah_dinilai') {
                            $sq->whereHas('interview', function ($iq) {
                                $iq->whereNotNull('interview_score');
                            });
                        } elseif ($statusKey === 'draft') {
                            $sq->whereHas('interview', function ($iq) {
                                $iq->whereNull('interview_score')
                                  ->whereHas('evaluators');
                            });
                        } elseif ($statusKey === 'belum_dinilai') {
                            $sq->where(function ($inner) {
                                $inner->whereDoesntHave('interview')
                                  ->orWhereHas('interview', function ($iq) {
                                      $iq->whereNull('interview_score')
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

            $query->whereHas('interview.evaluators', fn($ev) => $ev->whereIn('id_user', $matchingUserIds));
        }
 
        $query->orderByRaw("
            CASE 
                WHEN interview.interview_at IS NOT NULL AND interview.interview_score IS NULL THEN 1
                WHEN interview.interview_at IS NULL THEN 2
                ELSE 3
            END ASC
        ");
        $query->orderBy('submissions.id_submission', 'asc');
        $submissions = $query->paginate($this->perPage);
 
        // Preload operator users ke cache agar getUserAttribute() tidak N+1
        Submission::preloadUsers($submissions->getCollection());
 
        // Preload interviewer users (dengan roles) dari interviews yang terload — cegah N+1 getInterviewersAttribute()
        $loadedInterviews = $submissions->getCollection()->map(fn($s) => $s->interview)->filter();
        Interview::preloadInterviewerUsers($loadedInterviews);
 
        // Fetch all Evaluators for assignment dropdown (cached)
        $evaluators = \Illuminate\Support\Facades\Cache::remember('evaluators_with_roles_wawancara', 3600, function() {
            return User::role(['evaluator', 'super_evaluator', 'penilai_eksternal'])
                ->with('roles')
                ->get()
                ->sortBy(fn($user) => $user->hasRole('super_evaluator') ? 0 : ($user->hasRole('penilai_eksternal') ? 2 : 1))
                ->values();
        });
 
        return view('livewire.evaluasi.monitor-wawancara-table', [
            'submissions'       => $submissions,
            'period'            => $period,
            'periodeId'         => $this->periodeId,
            'periods'           => $periods,
            'satkers'           => $satkers,
            'evaluators'        => $evaluators,
            'totalLolos'        => $this->totalLolos,
            'completedCount'    => $this->completedCount,
            'notCompletedCount' => $this->notCompletedCount,
            'filterTahun'       => $this->filterTahun,
            'filterKodeSatker'  => $this->filterKodeSatker,
            'filterNamaSatker'  => $this->filterNamaSatker,
            'filterEvaluator'   => $this->filterEvaluator,
            'filterStatus'      => $this->filterStatus,
        ]);
    }
}
