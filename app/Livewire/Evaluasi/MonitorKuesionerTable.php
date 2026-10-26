<?php
 
namespace App\Livewire\Evaluasi;
 
use App\Models\Submission;
use App\Models\User;
use App\Models\Period;
use App\Models\Result;
use App\Models\Answer;
use App\Models\MasterPertanyaan;
use App\Models\MasterSatker;
use Livewire\Component;
use Livewire\WithPagination;
 
class MonitorKuesionerTable extends Component
{
    use WithPagination;
 
    public $search = '';
    public $perPage = 10;
    public $periodeId;
 
    // Stats variables
    public $totalKabkot;
    public $submittedCount = 0;
    public $avgScore = 0.00;

    // Excel-style column filters
    public $filterTahun = [];       // array of years (string); empty = all
    public $filterKodeSatker = [];  // array of kode_satker
    public $filterNamaSatker = [];  // array of lowercase nama_satker
    public $filterEvaluator = [];   // array of lowercase evaluator names / "-" for unassigned
    public $filterStatus = [];      // status keys
 
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

        // Default filter: show only target period year
        if ($period) {
            $this->filterTahun = [(string) ($period->tahun ?? \Carbon\Carbon::parse($period->periode_start)->year)];
        }

        $this->calculateStats();
    }

    public function calculateStats()
    {
        $this->totalKabkot = \Illuminate\Support\Facades\Cache::rememberForever('total_kabkot', function () {
            return MasterSatker::countKabkot();
        });

        if (!$this->periodeId) {
            $this->submittedCount = 0;
            $this->avgScore = 0.00;
            return;
        }

        $this->submittedCount = Submission::whereHas('result', fn($q) => $q->where('id_periode', $this->periodeId))
            ->whereIn('status', [1, 3, 4, 5, 6, 7, 8])
            ->count();

        $this->avgScore = Result::where('id_periode', $this->periodeId)
            ->whereHas('submission')
            ->avg('ques_score') ?? 0.00;
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
            $this->filterTahun = [(string) $latestPeriod->tahun];
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
 
        // Find the statistics period (overall active or selected $this->periodeId)
        $statPeriod = $periods->firstWhere('id_periode', $this->periodeId);
        if (!$statPeriod && $periods->isNotEmpty()) {
            $statPeriod = $periods->first();
            $this->periodeId = $statPeriod->id_periode;
            $this->calculateStats();
        }
 
        $satkers = \Illuminate\Support\Facades\Cache::remember('satkers_not_3500', 3600, function() {
            return MasterSatker::where('kode_satker', '!=', '3500')->get();
        });
        $allEvaluators = \Illuminate\Support\Facades\Cache::remember('evaluators_with_roles_saq', 3600, function() {
            return User::role(['evaluator', 'super_evaluator', 'penilai_eksternal'])
                ->with('roles')
                ->get()
                ->sortBy(fn($user) => $user->hasRole('super_evaluator') ? 0 : ($user->hasRole('penilai_eksternal') ? 2 : 1))
                ->values();
        });

        // Submissions query starting from master_satker table
        $periodIds = [];
        if (!empty($this->filterTahun)) {
            $selectedYears = (array) $this->filterTahun;
            $periodIds = $periods->filter(function ($p) use ($selectedYears) {
                $startYear = \Carbon\Carbon::parse($p->periode_start)->year;
                return in_array((string) $startYear, $selectedYears) || in_array((string) ($p->tahun ?? ''), $selectedYears);
            })->pluck('id_periode')->toArray();
        } else {
            if ($this->periodeId) {
                $periodIds = [$this->periodeId];
            } else {
                $periodIds = $periods->pluck('id_periode')->toArray();
            }
        }

        $query = \Illuminate\Support\Facades\DB::table('master_satker')
            ->where('master_satker.kode_satker', '!=', '3500')
            ->leftJoin('result', function ($join) use ($periodIds) {
                $join->on('master_satker.kode_satker', '=', 'result.kode_satker')
                     ->whereIn('result.id_periode', $periodIds);
            })
            ->leftJoin('submissions', 'result.id_result', '=', 'submissions.id_result');

        // Search Filter
        if (!empty(trim($this->search))) {
            $searchWildcard = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchWildcard) {
                $q->where('master_satker.nama_satker', 'like', $searchWildcard)
                  ->orWhere('master_satker.kode_satker', 'like', $searchWildcard);
            });
        }

        // Status Filter
        if (!empty($this->filterStatus)) {
            $selectedStatuses = (array) $this->filterStatus;
            $query->where(function ($q) use ($selectedStatuses) {
                foreach ($selectedStatuses as $statusKey) {
                    $q->orWhere(function ($sq) use ($statusKey) {
                        switch ($statusKey) {
                            case 'belum_dimulai':
                                $sq->whereNull('submissions.status')
                                   ->orWhere(function($sq2) {
                                       $sq2->where('submissions.status', 0)
                                           ->whereNotExists(function ($aq) {
                                               $aq->select(\Illuminate\Support\Facades\DB::raw(1))
                                                  ->from('answer')
                                                  ->whereColumn('answer.id_submission', 'submissions.id_submission')
                                                  ->where(function ($q2) {
                                                      $q2->where('answer.answer', 0)
                                                         ->orWhere(function ($q3) {
                                                             $q3->where('answer.answer', 1)
                                                                ->whereNotNull('answer.link_ppid')
                                                                ->where('answer.link_ppid', '!=', '')
                                                                ->whereNotNull('answer.link_drive')
                                                                ->where('answer.link_drive', '!=', '');
                                                         });
                                                  });
                                           });
                                   });
                                break;
                            case 'draft_pengisian':
                                $sq->where('submissions.status', 0)
                                   ->whereExists(function ($aq) {
                                       $aq->select(\Illuminate\Support\Facades\DB::raw(1))
                                          ->from('answer')
                                          ->whereColumn('answer.id_submission', 'submissions.id_submission')
                                          ->where(function ($q2) {
                                              $q2->where('answer.answer', 0)
                                                 ->orWhere(function ($q3) {
                                                     $q3->where('answer.answer', 1)
                                                        ->whereNotNull('answer.link_ppid')
                                                        ->where('answer.link_ppid', '!=', '')
                                                        ->whereNotNull('answer.link_drive')
                                                        ->where('answer.link_drive', '!=', '');
                                                 });
                                          });
                                   });
                                break;
                            case 'draft_penilaian':
                            case 'draft_evaluasi':
                                $sq->whereIn('submissions.status', [3, 4])
                                   ->whereExists(function ($aq) {
                                       $aq->select(\Illuminate\Support\Facades\DB::raw(1))
                                          ->from('answer')
                                          ->whereColumn('answer.id_submission', 'submissions.id_submission')
                                          ->whereNotNull('answer.answer_score');
                                   });
                                break;
                            case 'menunggu_verif':
                                $sq->where('submissions.status', 1)
                                   ->where(function($vq) {
                                       $vq->whereNull('submissions.verified_by')
                                          ->orWhere('submissions.verified_by', '');
                                   });
                                break;
                            case 'draft_verif':
                                $sq->where('submissions.status', 1)
                                   ->whereNotNull('submissions.verified_by')
                                   ->where('submissions.verified_by', '!=', '');
                                break;
                            case 'dikembalikan':
                                $sq->where('submissions.status', 2);
                                break;
                            case 'menunggu_eval':
                                $sq->where('submissions.status', 3)
                                   ->whereNotExists(function ($aq) {
                                       $aq->select(\Illuminate\Support\Facades\DB::raw(1))
                                          ->from('answer')
                                          ->whereColumn('answer.id_submission', 'submissions.id_submission')
                                          ->whereNotNull('answer.answer_score');
                                   });
                                break;
                            case 'lolos':
                            case 'selesai':
                                $sq->whereIn('submissions.status', [5, 6, 7]);
                                break;
                            case 'gugur':
                                $sq->where('submissions.status', 8);
                                break;
                        }
                    });
                }
            });
        }

        // Join scored_answers summary table to avoid repeated correlated subqueries in orderByRaw
        $query->leftJoinSub(
            \Illuminate\Support\Facades\DB::table('answer')
                ->select('id_submission', \Illuminate\Support\Facades\DB::raw('1 as has_scored_answer'))
                ->whereNotNull('answer_score')
                ->groupBy('id_submission'),
            'scored_answers',
            'scored_answers.id_submission',
            '=',
            'submissions.id_submission'
        );

        // Sorting
        $query->orderByRaw("
            CASE
                WHEN submissions.status = 3
                     AND scored_answers.has_scored_answer IS NULL
                     AND EXISTS (SELECT 1 FROM submission_evaluators WHERE submission_evaluators.id_submission = submissions.id_submission) THEN 1
                WHEN submissions.status = 3
                     AND scored_answers.has_scored_answer IS NULL
                     AND NOT EXISTS (SELECT 1 FROM submission_evaluators WHERE submission_evaluators.id_submission = submissions.id_submission) THEN 2
                WHEN submissions.status IN (3,4)
                     AND scored_answers.has_scored_answer IS NOT NULL THEN 3
                WHEN submissions.status = 1 AND submissions.verified_by IS NOT NULL AND submissions.verified_by != '' THEN 4
                WHEN submissions.status = 1 THEN 5
                WHEN submissions.status = 2 THEN 6
                WHEN submissions.status IN (5,6,7) THEN 7
                WHEN submissions.status = 8 THEN 8
                WHEN submissions.status = 0 THEN 9
                WHEN submissions.status IS NULL THEN 9.5
                ELSE 10
            END ASC
        ")
        ->orderBy('master_satker.kode_satker', 'asc');

        if (!empty($this->filterKodeSatker)) {
            $query->whereIn('master_satker.kode_satker', (array) $this->filterKodeSatker);
        }

        if (!empty($this->filterNamaSatker)) {
            $query->whereIn('master_satker.nama_satker', (array) $this->filterNamaSatker);
        }

        if (!empty($this->filterEvaluator)) {
            $filterEvals = array_map('strtolower', (array) $this->filterEvaluator);
            $matchingUserIds = Submission::userIdsFromEvaluatorNames($filterEvals);

            $query->where(function ($q) use ($filterEvals, $matchingUserIds) {
                if (!empty($matchingUserIds)) {
                    $q->whereHas('evaluators', fn($evQ) => $evQ->whereIn('users.id_user', $matchingUserIds));
                }

                if (in_array('-', $filterEvals)) {
                    $q->orWhereDoesntHave('evaluators');
                }
            });
        }

        $query->select([
            'master_satker.kode_satker',
            'master_satker.nama_satker',
            'submissions.id_submission',
            'submissions.id_result',
            'submissions.id_operator',
            'submissions.verified_by',
            'submissions.verified_at',
            'submissions.verification_note',
            'submissions.status',
            'submissions.submitted_at',
            'submissions.evaluated_at',
            'result.ques_score',
            'result.id_periode',
        ]);

        $paginatedRecords = $query->paginate($this->perPage);

        // Convert database records to Submission models
        $submissionsList = Submission::hydrate($paginatedRecords->items());

        // Eager load relations for submissions that exist in DB
        $existingSubmissions = $submissionsList->filter(fn($s) => !is_null($s->id_submission));
        if ($existingSubmissions->isNotEmpty()) {
            $existingSubmissions->load([
                'result.satker',
                'result.period',
                'verifier',
                'evaluators.roles',
                'answers' => fn($q) => $q->select(['id_answer', 'id_submission', 'id_indicators', 'answer', 'link_ppid', 'link_drive', 'answer_score']),
            ]);
        }

        // Batch preload all user instances into Submission::$userCache in 1 query
        Submission::preloadUsers($submissionsList);

        // Fetch operator users for all satkers in one query
        $operatorsBySatker = \Illuminate\Support\Facades\Cache::remember('operators_by_satker', 3600, function() {
            return User::role('operator')->get();
        })->groupBy('kode_satker');

        foreach ($submissionsList as $sub) {
            if (is_null($sub->id_submission)) {
                $sub->id_submission = 'temp_' . $sub->kode_satker;
                $sub->status = -1; // represents "belum dimulai"
                
                $result = new Result([
                    'kode_satker' => $sub->kode_satker,
                    'id_periode' => $this->periodeId ?: ($periodIds[0] ?? null),
                ]);
                $result->setRelation('satker', new MasterSatker([
                    'kode_satker' => $sub->kode_satker,
                    'nama_satker' => $sub->nama_satker,
                ]));
                $result->setRelation('period', $statPeriod);
                $sub->setRelation('result', $result);
                
                // Assign dummy operator for temp row if satker has operators
                $opUser = isset($operatorsBySatker[$sub->kode_satker]) ? $operatorsBySatker[$sub->kode_satker]->first() : null;
                if ($opUser) {
                    $sub->id_operator = [['id' => $opUser->id_user, 'nama' => $opUser->nama_user]];
                    $sub->user = $opUser;
                }
                
                // Set empty relations
                $sub->setRelation('answers', collect());
                $sub->setRelation('verifier', null);
                $sub->kuesionerEvaluators = collect();
                $sub->evaluators = collect();
            }
        }

        $paginatedRecords->setCollection($submissionsList);
        $submissions = $paginatedRecords;
 
        // Fetch indicator max scores dynamically (cached for 24h)
        $dynamicMaxScores = \Illuminate\Support\Facades\Cache::remember('master_questions_max_scores', 86400, function() {
            return MasterPertanyaan::selectRaw('id_indicators, sum(bobot_pertanyaan) as max_score')
                ->groupBy('id_indicators')
                ->pluck('max_score', 'id_indicators')
                ->toArray();
        });
 
        $totalQuestions = MasterPertanyaan::getCachedCount() ?: 1;
 
        // Process submissions properties for template binding
        foreach ($submissions as $sub) {
            $sub->subPeriodYear = $sub->result && $sub->result->period ? $sub->result->period->tahun : date('Y');
 
            if ($sub->status > 0) {
                $sub->evaluated_count = $sub->answers->whereNotNull('answer_score')->count();
                $sub->progress_percent = round(($sub->evaluated_count / $totalQuestions) * 100);
 
                $answersByIndicator = $sub->answers->groupBy('id_indicators');
                $scoresArray = [];
                for ($i = 1; $i <= 6; $i++) {
                    $maxForInd = $dynamicMaxScores[$i] ?? 1.0;
                    $earnedForInd = isset($answersByIndicator[$i]) ? $answersByIndicator[$i]->sum('answer_score') : 0.0;
                    $scoresArray[$i] = round(($earnedForInd / $maxForInd) * 100, 2);
                }
                $sub->indicator_scores = $scoresArray;
            } else {
                $answeredCount = $sub->answers->filter(
                    fn($a) => !is_null($a->answer) &&
                        ((int) $a->answer === 0 ||
                            (!empty(trim($a->link_ppid ?? '')) && !empty(trim($a->link_drive ?? ''))))
                )->count();
                $sub->progress_percent = $totalQuestions > 0 ? round(($answeredCount / $totalQuestions) * 100) : 0;
                $sub->indicator_scores = array_fill(1, 6, 0.00);
            }
        }
 
        return view('livewire.evaluasi.monitor-kuesioner-table', [
            'period'         => $statPeriod,
            'periods'        => $periods,
            'submissions'    => $submissions,
            'totalKabkot'    => $this->totalKabkot,
            'submittedCount' => $this->submittedCount,
            'avgScore'       => $this->avgScore,
            'satkers'        => $satkers,
            'allEvaluators'  => $allEvaluators,
        ]);
    }
}
