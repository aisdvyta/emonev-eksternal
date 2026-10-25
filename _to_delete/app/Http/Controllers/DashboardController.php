<?php

namespace App\Http\Controllers;

use App\Helpers\RegionMapHelper;
use App\Helpers\SecureId;
use App\Models\Answer;
use App\Models\Interview;
use App\Models\MasterPembobotan;
use App\Models\MasterPertanyaan;
use App\Models\MasterSatker;
use App\Models\Period;
use App\Models\Submission;
use App\Models\User;
use App\Models\Visitasi;
use App\Services\PenilaianService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected PenilaianService $penilaianService;

    public function __construct(PenilaianService $penilaianService)
    {
        $this->penilaianService = $penilaianService;
    }

    /**
     * Tampilkan Dashboard Terpadu berdasarkan role user yang sedang login.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $role = strtolower($user->role?->nama_role ?? 'operator');

        return match ($role) {
            'admin'             => $this->dashboardAdmin(),
            'super_evaluator'   => $this->dashboardEvaluator($user, isSuperEvaluator: true),
            'evaluator'         => $this->dashboardEvaluator($user, isSuperEvaluator: false),
            'operator'          => $this->dashboardOperator($user),
            'verifikator'       => $this->dashboardVerifikator($user),
            'penilai_eksternal' => $this->dashboardPenilaiEksternal($user),
            default             => view('pages.dashboard.index', [
                'role'      => $role,
                'pageTitle' => 'Beranda - HaloKIP',
            ]),
        };
    }

    /**
     * Dashboard untuk role Admin.
     */
    protected function dashboardAdmin()
    {
        $totalUsers  = User::count();
        $totalByRole = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->selectRaw('roles.name, COUNT(*) as total')
            ->groupBy('roles.name')
            ->pluck('total', 'name');

        $stats = [
            'total'       => $totalUsers,
            'operator'    => $totalByRole->get('operator', 0),
            'verifikator' => $totalByRole->get('verifikator', 0),
            'evaluator'   => $totalByRole->get('evaluator', 0),
            'kabag'       => $totalByRole->get('kabag', 0),
            'admin'       => $totalByRole->get('admin', 0),
        ];

        return view('pages.dashboard.index', [
            'role'      => 'admin',
            'pageTitle' => 'Beranda Admin - HaloKIP',
            'stats'     => $stats,
        ]);
    }

    /**
     * Dashboard untuk Evaluator dan Super Evaluator.
     */
    protected function dashboardEvaluator(User $user, bool $isSuperEvaluator)
    {
        $period = Period::getActivePeriod();

        if (!$period) {
            return view('pages.dashboard.index', [
                'role'      => $isSuperEvaluator ? 'super_evaluator' : 'evaluator',
                'pageTitle' => $isSuperEvaluator ? 'Beranda Super Evaluator - HaloKIP' : 'Beranda Evaluator - HaloKIP',
                'period'    => null,
            ])->with('error', 'Tidak ada periode aktif.');
        }

        $periodeId = $period->id_periode;
        $currentUserId = $user->id_user;
        $agenda = [];

        // Agenda Visitasi
        $upcomingVisitsQuery = Visitasi::with('result.satker')
            ->whereHas('result', fn($q) => $q->where('id_periode', $periodeId))
            ->whereNotNull('visit_at')
            ->where('visit_at', '>=', now()->toDateString())
            ->orderBy('visit_at')
            ->limit(4);

        if (!$isSuperEvaluator) {
            $upcomingVisitsQuery->whereHas('evaluators', fn($q) => $q->where('id_user', $currentUserId));
        }
        $upcomingVisits = $upcomingVisitsQuery->get();

        foreach ($upcomingVisits as $v) {
            $agenda[] = [
                'date'      => Carbon::parse($v->visit_at)->translatedFormat('d M'),
                'day'       => Carbon::parse($v->visit_at)->translatedFormat('D'),
                'event'     => 'Visitasi — ' . ($v->result->satker->nama_satker ?? 'Satker'),
                'color'     => 'biru2',
                'time'      => '09:00 WIB',
                'timestamp' => Carbon::parse($v->visit_at)->timestamp,
                'url'       => route('evaluasi.visitasi.review', SecureId::encrypt($v->id_visit)),
            ];
        }

        // Agenda Wawancara
        $upcomingInterviewsQuery = Interview::with('result.satker')
            ->whereHas('result', fn($q) => $q->where('id_periode', $periodeId))
            ->whereNotNull('interview_at')
            ->where('interview_at', '>=', now()->toDateString())
            ->orderBy('interview_at')
            ->limit(4);

        if (!$isSuperEvaluator) {
            $upcomingInterviewsQuery->whereHas('evaluators', fn($q) => $q->where('id_user', $currentUserId));
        }
        $upcomingInterviews = $upcomingInterviewsQuery->get();

        foreach ($upcomingInterviews as $i) {
            $agenda[] = [
                'date'      => Carbon::parse($i->interview_at)->translatedFormat('d M'),
                'day'       => Carbon::parse($i->interview_at)->translatedFormat('D'),
                'event'     => 'Wawancara — ' . ($i->result->satker->nama_satker ?? 'Satker'),
                'color'     => 'hijau',
                'time'      => Carbon::parse($i->interview_at)->format('H:i') . ' WIB',
                'timestamp' => Carbon::parse($i->interview_at)->timestamp,
                'url'       => route('evaluasi.wawancara.review', SecureId::encrypt($i->id_interview)),
            ];
        }

        usort($agenda, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        $agenda = array_slice($agenda, 0, 4);

        // Submissions & Scoring
        $allSubmissions = Submission::with(['result.satker', 'result', 'visitasi.evaluators.user.roles', 'interview.evaluators.user.roles'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $periodeId))
            ->get();
        Submission::preloadUsers($allSubmissions);

        $satkerScores = [];
        $validScores = [];
        $satkerRawScores = [];

        foreach ($allSubmissions as $sub) {
            $satkerName = $sub->result->satker->nama_satker ?? '';
            $cleanName = trim(str_replace('BPS', '', $satkerName));

            $score = $this->penilaianService->calculateFinalScoreFromSubmission($sub);
            $status = $score > 0 ? $this->penilaianService->getPredikat($score) : 'BELUM DINILAI';
            $color = RegionMapHelper::scoreToColor($score);

            $satkerScores[$cleanName] = [
                'score'  => $score,
                'status' => $status,
                'color'  => $color,
            ];

            if ($score > 0) {
                $validScores[] = $score;
                $satkerRawScores[$satkerName] = $score;
            }
        }

        $regionScores = RegionMapHelper::buildRegionScores($satkerRawScores);
        $avgScore = count($validScores) > 0 ? round(array_sum($validScores) / count($validScores), 2) : 0;

        $phaseInfo = $period->getPhaseInfo($allSubmissions);
        $phase = $phaseInfo['phase'];
        $progressPercent = $phaseInfo['stepperPercent'];

        $lolosCount     = $allSubmissions->whereIn('status', [5, 6, 7])->count();
        $gugurCount     = $allSubmissions->where('status', 8)->count();
        $totalEvaluated = $lolosCount + $gugurCount;

        $visitasiScheduled = $allSubmissions->filter(function($s) {
            return $s->visitasi && $s->visitasi->visit_at !== null;
        })->count();

        $visitasiEvaluated = $allSubmissions->filter(function($s) {
            return $s->visitasi && $s->visitasi->visit_score !== null;
        })->count();

        $totalKabkot = MasterSatker::countKabkot();

        return view('pages.dashboard.index', [
            'role'              => $isSuperEvaluator ? 'super_evaluator' : 'evaluator',
            'pageTitle'         => $isSuperEvaluator ? 'Beranda Super Evaluator - HaloKIP' : 'Beranda Evaluator - HaloKIP',
            'period'            => $period,
            'avgScore'          => $avgScore,
            'agenda'            => $agenda,
            'regionScores'      => $regionScores,
            'satkerScores'      => $satkerScores,
            'phase'             => $phase,
            'progressPercent'   => $progressPercent,
            'lolosCount'        => $lolosCount,
            'gugurCount'        => $gugurCount,
            'totalEvaluated'    => $totalEvaluated,
            'totalKabkot'       => $totalKabkot,
            'visitasiScheduled' => $visitasiScheduled,
            'visitasiEvaluated' => $visitasiEvaluated,
        ]);
    }

    /**
     * Dashboard untuk role Operator.
     */
    protected function dashboardOperator(User $user)
    {
        $period = Period::getActivePeriod();

        if (!$period) {
            return view('pages.dashboard.index', [
                'role'      => 'operator',
                'pageTitle' => 'Beranda Operator - HaloKIP',
                'period'    => null,
            ])->with('error', 'Tidak ada periode penilaian aktif.');
        }

        $submission = Submission::whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker)->where('id_periode', $period->id_periode))
            ->with(['result', 'visitasi', 'interview'])
            ->first();

        $saqScore = 0;
        $progressPercent = 0;
        $status = 0;

        if ($submission) {
            $status = $submission->status;
            $saqScore = $submission->result?->ques_score ?? 0;

            $progressData = $this->penilaianService->getAnswerProgress($submission);
            $totalQuestions = $progressData['total'];
            $answeredCount = $progressData['answered'];
            $progressPercent = $progressData['percent'];
        }

        $visitasi = $submission?->visitasi;
        $interview = $submission?->interview;

        $agenda = [];

        if ($visitasi && $visitasi->visit_at && Carbon::parse($visitasi->visit_at)->endOfDay()->isFuture()) {
            $vDate = Carbon::parse($visitasi->visit_at);
            $agenda[] = [
                'date'      => $vDate->translatedFormat('d M'),
                'day'       => $vDate->translatedFormat('D'),
                'event'     => 'Visitasi Lapangan',
                'color'     => 'biru2',
                'timestamp' => $vDate->timestamp,
                'url'       => route('evaluasi.visitasi.index', ['id_periode' => SecureId::encrypt($period->id_periode)]),
            ];
        }

        if ($interview && $interview->interview_at && Carbon::parse($interview->interview_at)->endOfDay()->isFuture()) {
            $iDate = Carbon::parse($interview->interview_at);
            $agenda[] = [
                'date'      => $iDate->translatedFormat('d M'),
                'day'       => $iDate->translatedFormat('D'),
                'event'     => 'Wawancara',
                'color'     => 'hijau',
                'timestamp' => $iDate->timestamp,
                'url'       => route('evaluasi.wawancara.index', ['id_periode' => SecureId::encrypt($period->id_periode)]),
            ];
        }

        usort($agenda, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        $agenda = array_slice($agenda, 0, 4);

        $prevPeriod = Period::whereYear('periode_start', $period->tahun - 1)->first();
        $prevResultScore = 88.50;
        $prevResultLabel = 'INFORMATIF';
        if ($prevPeriod) {
            $prevSub = Submission::whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker)->where('id_periode', $prevPeriod->id_periode))->first();
            if ($prevSub && $prevSub->result && $prevSub->result->published_at) {
                $prevResultScore = $prevSub->result->final_score ?? $prevSub->result->ques_score ?? 88.50;
                $prevResultLabel = $prevSub->result->index_label ?? 'INFORMATIF';
            }
        }

        return view('pages.dashboard.index', [
            'role'            => 'operator',
            'pageTitle'       => 'Beranda Operator - HaloKIP',
            'period'          => $period,
            'submission'      => $submission,
            'status'          => $status,
            'saqScore'        => $saqScore,
            'progressPercent' => $progressPercent,
            'prevResultScore' => $prevResultScore,
            'prevResultLabel' => $prevResultLabel,
            'visitasi'        => $visitasi,
            'interview'       => $interview,
            'agenda'          => $agenda,
        ]);
    }

    /**
     * Dashboard untuk role Verifikator.
     */
    protected function dashboardVerifikator(User $user)
    {
        $activePeriod = Period::getActivePeriod();

        $operators = collect();
        $agenda = [];
        $steps = [];
        $phase = 'Pengisian Kuesioner';
        $stepperPercent = 0;

        if ($activePeriod) {
            $totalQuestions = MasterPertanyaan::getCachedCount() ?: 1;

            $submission = Submission::whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker)->where('id_periode', $activePeriod->id_periode))
                ->with(['result', 'visitasi', 'interview'])
                ->first();

            $answeredCount = 0;
            $status = 0;
            if ($submission) {
                $status = $submission->status;
                $progressData = $this->penilaianService->getAnswerProgress($submission);
                $answeredCount = $progressData['answered'];

                $visitasi = $submission->visitasi;
                $interview = $submission->interview;

                if ($visitasi && $visitasi->visit_at && Carbon::parse($visitasi->visit_at)->endOfDay()->isFuture()) {
                    $vDate = Carbon::parse($visitasi->visit_at);
                    $agenda[] = [
                        'date'      => $vDate->translatedFormat('d M'),
                        'day'       => $vDate->translatedFormat('D'),
                        'event'     => 'Visitasi Lapangan',
                        'color'     => 'biru2',
                        'timestamp' => $vDate->timestamp,
                        'url'       => route('evaluasi.penilaian.index', ['id_periode' => SecureId::encrypt($activePeriod->id_periode)]),
                    ];
                }

                if ($interview && $interview->interview_at && Carbon::parse($interview->interview_at)->endOfDay()->isFuture()) {
                    $iDate = Carbon::parse($interview->interview_at);
                    $agenda[] = [
                        'date'      => $iDate->translatedFormat('d M'),
                        'day'       => $iDate->translatedFormat('D'),
                        'event'     => 'Wawancara',
                        'color'     => 'hijau',
                        'timestamp' => $iDate->timestamp,
                        'url'       => route('evaluasi.penilaian.index', ['id_periode' => SecureId::encrypt($activePeriod->id_periode)]),
                    ];
                }

                $operators = $submission->operators;
            } else {
                $operators = User::role('operator')
                    ->where('kode_satker', $user->kode_satker)
                    ->get();
            }

            foreach ($operators as $op) {
                if ($submission) {
                    $op->submission = $submission;
                    $op->answered_count = $answeredCount;
                    $op->progress_percent = $totalQuestions > 0 ? round(($answeredCount / $totalQuestions) * 100) : 0;
                } else {
                    $op->submission = null;
                    $op->answered_count = 0;
                    $op->progress_percent = 0;
                }
            }

            $activePeriod->is_active = now()->between($activePeriod->periode_start, $activePeriod->periode_end);
            usort($agenda, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

            $fmtDate = fn($s, $e) => ($s && $e) ? Carbon::parse($s)->translatedFormat('d M Y') . ' – ' . Carbon::parse($e)->translatedFormat('d M Y') : 'Menunggu Jadwal';

            $phaseInfo = $activePeriod->getPhaseInfo();
            $phase = $phaseInfo['phase'];
            $stepperPercent = $phaseInfo['stepperPercent'];

            $evalEndObj = $activePeriod->eval_end ? Carbon::parse($activePeriod->eval_end)->endOfDay() : null;
            $isEvalExpired = $evalEndObj && now()->greaterThan($evalEndObj);

            $dashStageOrder = MasterPembobotan::getActiveStageOrder($activePeriod->id_periode);

            $stepMap = [
                'saq' => [
                    [
                        'key'         => 'saq_fill',
                        'label'       => 'Pengisian Kuesioner',
                        'icon'        => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z',
                        'date'        => $fmtDate($activePeriod->saq_start, $activePeriod->saq_end),
                        'actionUrl'   => route('evaluasi.saq.index'),
                        'actionLabel' => $isEvalExpired ? 'Lihat SAQ' : 'Verifikasi SAQ',
                    ],
                    [
                        'key'         => 'saq_eval',
                        'label'       => 'Evaluasi Data',
                        'icon'        => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                        'date'        => $fmtDate($activePeriod->eval_start, $activePeriod->eval_end),
                        'actionUrl'   => route('evaluasi.penilaian.index'),
                        'actionLabel' => 'Penilaian',
                    ],
                ],
                'visitasi' => [
                    [
                        'key'         => 'visit',
                        'label'       => 'Visitasi Lapangan',
                        'icon'        => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
                        'date'        => $fmtDate($activePeriod->visit_start, $activePeriod->visit_end),
                        'actionUrl'   => route('evaluasi.penilaian.index'),
                        'actionLabel' => 'Penilaian',
                    ],
                ],
                'wawancara' => [
                    [
                        'key'         => 'interview',
                        'label'       => 'Wawancara',
                        'icon'        => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                        'date'        => $fmtDate($activePeriod->interview_start, $activePeriod->interview_end),
                        'actionUrl'   => route('evaluasi.penilaian.index'),
                        'actionLabel' => 'Penilaian',
                    ],
                ],
            ];

            $rawSteps = [];
            foreach ($dashStageOrder as $stgKey) {
                if (isset($stepMap[$stgKey])) {
                    foreach ($stepMap[$stgKey] as $stepItem) {
                        $rawSteps[] = $stepItem;
                    }
                }
            }
            $rawSteps[] = [
                'key'         => 'result',
                'label'       => 'Penilaian Akhir',
                'icon'        => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
                'date'        => $fmtDate($activePeriod->result_start, $activePeriod->result_end),
                'actionUrl'   => route('evaluasi.penilaian.index'),
                'actionLabel' => 'Hasil Akhir',
            ];

            $activeStageIndex = null;
            if ($phase === 'Selesai / Terpublikasi') {
                $activeStageIndex = count($rawSteps);
            } else {
                foreach ($rawSteps as $idx => $stg) {
                    if ($stg['label'] === $phase || 
                        ($stg['key'] === 'saq_fill' && $phase === 'Pengisian Kuesioner') ||
                        ($stg['key'] === 'saq_eval' && in_array($phase, ['Evaluasi Kuesioner', 'Evaluasi Data', 'Evaluasi & Penilaian'])) ||
                        ($stg['key'] === 'visit' && $phase === 'Visitasi Lapangan') ||
                        ($stg['key'] === 'interview' && in_array($phase, ['Wawancara & Presentasi', 'Presentasi & Wawancara', 'Wawancara'])) ||
                        ($stg['key'] === 'result' && in_array($phase, ['Sidang Penilaian Akhir', 'Penilaian Akhir']))) {
                        $activeStageIndex = $idx;
                        break;
                    }
                }
            }
            if ($activeStageIndex === null) {
                $activeStageIndex = 0;
            }

            $steps = [];
            foreach ($rawSteps as $idx => $stg) {
                $isActive = ($idx === $activeStageIndex && $phase !== 'Selesai / Terpublikasi');
                $isDone = ($idx < $activeStageIndex || $phase === 'Selesai / Terpublikasi');

                if ($status == 8 && $idx > 0) {
                    $statusText = 'Gugur';
                    $colorText = 'danger';
                } else {
                    $statusText = $isDone ? 'Selesai' : ($isActive ? ($idx === 0 ? 'Sedang Berlangsung' : 'Aktif') : 'Belum Dimulai');
                    $colorText = $isDone ? 'done' : ($isActive ? 'active' : 'pending');
                }

                $steps[] = array_merge($stg, [
                    'status' => $statusText,
                    'color'  => $colorText,
                    'active' => $isActive,
                ]);
            }
        }

        return view('pages.dashboard.index', [
            'role'           => 'verifikator',
            'pageTitle'      => 'Beranda Verifikator - HaloKIP',
            'activePeriod'   => $activePeriod,
            'operators'      => $operators,
            'agenda'         => $agenda,
            'steps'          => $steps,
            'phase'          => $phase,
            'stepperPercent' => $stepperPercent,
        ]);
    }

    /**
     * Dashboard untuk role Penilai Eksternal.
     */
    protected function dashboardPenilaiEksternal(User $user)
    {
        $period = Period::getActivePeriod();

        if (!$period) {
            return view('pages.dashboard.index', [
                'role'               => 'penilai_eksternal',
                'pageTitle'          => 'Beranda Penilai Eksternal - HaloKIP',
                'period'             => null,
                'assignedCount'      => 0,
                'completedCount'     => 0,
                'pendingCount'       => 0,
                'upcomingInterviews' => collect(),
            ]);
        }

        $currentUser = $user;
        $periodeId   = $period->id_periode;

        $allInterviews = Interview::with(['result.satker', 'result.submission', 'evaluators'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $periodeId))
            ->whereNotNull('interview_at')
            ->whereHas('evaluators', fn($evQ) => $evQ->where('id_user', (int)$currentUser->id_user))
            ->get();

        $assignedCount  = $allInterviews->count();
        $completedCount = $allInterviews->filter(function ($interview) use ($currentUser) {
            $myEval = $interview->evaluators->firstWhere('id_user', $currentUser->id_user);
            return $myEval && $myEval->score !== null && !$myEval->is_draft;
        })->count();
        $pendingCount   = $assignedCount - $completedCount;

        $upcomingInterviews = $allInterviews
            ->filter(fn($i) => $i->interview_at && Carbon::parse($i->interview_at)->startOfDay()->gte(now()->startOfDay()))
            ->sortBy('interview_at')
            ->take(5)
            ->values();

        $agenda = [];
        foreach ($upcomingInterviews as $i) {
            $agenda[] = [
                'date'      => Carbon::parse($i->interview_at)->translatedFormat('d M'),
                'day'       => Carbon::parse($i->interview_at)->translatedFormat('D'),
                'event'     => 'Wawancara — ' . ($i->result?->satker?->nama_satker ?? 'Satker'),
                'color'     => 'hijau',
                'time'      => Carbon::parse($i->interview_at)->format('H:i') . ' WIB',
                'timestamp' => Carbon::parse($i->interview_at)->timestamp,
                'url'       => route('evaluasi.wawancara.review', SecureId::encrypt($i->id_interview)),
            ];
        }

        $phaseInfo       = $period->getPhaseInfo();
        $phase           = $phaseInfo['phase'];
        $progressPercent = $phaseInfo['stepperPercent'];

        return view('pages.dashboard.index', [
            'role'               => 'penilai_eksternal',
            'pageTitle'          => 'Beranda Penilai Eksternal - HaloKIP',
            'period'             => $period,
            'assignedCount'      => $assignedCount,
            'completedCount'     => $completedCount,
            'pendingCount'       => $pendingCount,
            'upcomingInterviews' => $upcomingInterviews,
            'agenda'             => $agenda,
            'phase'              => $phase,
            'progressPercent'    => $progressPercent,
        ]);
    }
}
