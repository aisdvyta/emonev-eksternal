<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\MasterPembobotan;
use App\Models\Period;
use App\Models\Result;
use App\Models\Submission;
use App\Models\User;
use App\Services\EvaluationService;
use App\Services\PenilaianService;
use App\Helpers\ExcelExportHelper;
use App\Helpers\SecureId;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class WawancaraController extends Controller
{
    protected PenilaianService $penilaianService;
    protected EvaluationService $evaluationService;

    public function __construct(PenilaianService $penilaianService, EvaluationService $evaluationService)
    {
        $this->penilaianService = $penilaianService;
        $this->evaluationService = $evaluationService;
    }

    private function getMonitoringRouteName(): string
    {
        $role = Auth::user()?->role?->nama_role;
        if (Route::has('evaluasi.wawancara.monitoring')) {
            return 'evaluasi.wawancara.monitoring';
        }
        if ($role === 'super_evaluator') {
            return 'super-evaluator.monitor-wawancara';
        }
        if ($role === 'penilai_eksternal') {
            return 'penilai-eksternal.monitor-wawancara';
        }
        return 'evaluator.monitor-wawancara';
    }

    /**
     * Entry point for Satker (Operator/Verifikator) and fallback for monitoring.
     */
    public function wawancara(Request $request, $id_periode = null)
    {
        $user = Auth::user();
        $userRole = $user?->role?->nama_role;

        // Evaluator / Super Evaluator / Penilai Eksternal redirect to monitorWawancara
        if (in_array($userRole, ['evaluator', 'super_evaluator', 'penilai_eksternal', 'admin'])) {
            return $this->monitorWawancara($request);
        }

        // Operator & Verifikator logic
        if ($id_periode) {
            if (!is_numeric($id_periode)) {
                $id_periode = SecureId::decrypt($id_periode);
            }
        }

        if (!$id_periode) {
            $periods = Period::getAvailablePeriodsForUser($user);
            $submissionsCollection = Submission::with(['result', 'visitasi.evaluators.user.roles', 'interview.evaluators.user.roles'])
                ->whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker))
                ->get();
            Submission::preloadUsers($submissionsCollection);
            $submissions = $submissionsCollection->keyBy(fn($sub) => $sub->result ? $sub->result->id_periode : 0);

            return view('pages.evaluasi.wawancara.monitoring-satker', compact('periods', 'submissions'));
        }

        $period = Period::findOrFail($id_periode);
        $submission = Submission::whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker)->where('id_periode', $period->id_periode))
            ->first();

        $isGugur = $submission && $submission->status == 8;

        if (!$submission || (!$submission->interview && !$isGugur)) {
            $redirectRoute = Route::has('evaluasi.wawancara.index') ? 'evaluasi.wawancara.index' : ($userRole === 'verifikator' ? 'verifikator.monitoring-wawancara' : 'operator.wawancara');
            return redirect()->route($redirectRoute)->with('error', 'Wawancara belum diinisialisasi atau Anda tidak lolos threshold.');
        }

        $interview = $submission->interview;

        return view('pages.evaluasi.wawancara.detail', compact('period', 'submission', 'interview', 'isGugur'));
    }

    /**
     * Alias for Verifikator legacy route
     */
    public function monitoringWawancara(Request $request, $id_periode = null)
    {
        return $this->wawancara($request, $id_periode);
    }

    /**
     * Show Wawancara Monitoring page for Evaluator, Super Evaluator, and Penilai Eksternal.
     */
    public function monitorWawancara(Request $request)
    {
        $selectedPeriodId = $request->input('id_periode');
        $period = Period::resolvePeriod($selectedPeriodId);

        if (!$period) {
            return back()->with('error', 'Tidak ada periode aktif.');
        }

        // Fetch Internal Evaluators for assignment dropdown (cached)
        $evaluators = Cache::remember('evaluators_with_roles_wawancara_internal', 3600, function() {
            return User::role(['evaluator', 'super_evaluator'])
                ->with('roles')
                ->get()
                ->sortBy(fn($user) => $user->hasRole('super_evaluator') ? 0 : 1)
                ->values();
        });

        // Fetch External Evaluators
        $externalEvaluators = Cache::remember('evaluators_with_roles_wawancara_external', 3600, function() {
            return User::role('penilai_eksternal')
                ->with('roles')
                ->get()
                ->sortBy('nama_user')
                ->values();
        });

        // Get interviews for Calendar
        $interviews = Interview::with(['result.satker', 'result.submission', 'evaluators.user.roles'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $period->id_periode))
            ->whereNotNull('interview_at')
            ->get();

        // Resolve stage order to dynamically determine prerequisites
        $wawancaraRequiresVisitasi = MasterPembobotan::wawancaraRequiresVisitasi($period->id_periode);

        // Get all eligible submissions for modal selection
        $allEligibleSubmissionsQuery = Submission::with(['result.satker', 'interview.evaluators.user.roles'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $period->id_periode))
            ->whereIn('status', [5, 6, 7]);

        if ($wawancaraRequiresVisitasi) {
            $allEligibleSubmissionsQuery->where(function ($q) {
                $q->whereHas('visitasi', function ($sq) {
                    $sq->whereNotNull('visit_score');
                })->orWhereHas('interview', function ($iq) {
                    $iq->whereNotNull('interview_at')
                       ->orWhereNotNull('interview_score')
                       ->orWhereHas('evaluators');
                });
            });
        }

        $allEligibleSubmissions = $allEligibleSubmissionsQuery->get();

        // Preload interviewer users (dengan roles)
        Interview::preloadInterviewerUsers($interviews);

        // Preload operator users dari submissions yang di-embed
        $submissionsFromInterviews = $interviews->map(fn($i) => $i->result?->submission)->filter();
        Submission::preloadUsers($submissionsFromInterviews->concat($allEligibleSubmissions));

        // Format calendar events
        $calendarEvents = $interviews->map(function ($i) {
            $satker = $i->result?->satker;
            $namaSatker = $satker ? ('(' . $satker->kode_satker . ') ' . $satker->nama_satker) : 'BPS Kab/Kota';
            return [
                'title' => $namaSatker,
                'start' => $i->interview_at ? Carbon::parse($i->interview_at)->toIso8601String() : null,
                'color' => $i->interview_score !== null ? '#1e6091' : '#e67e22',
            ];
        })->filter(fn($e) => !empty($e['start']))->values();

        return view('pages.evaluasi.wawancara.monitoring', compact(
            'period',
            'evaluators',
            'externalEvaluators',
            'interviews',
            'allEligibleSubmissions',
            'calendarEvents'
        ));
    }

    /**
     * Save/Update Schedule for a Wawancara (Interview) record.
     */
    public function saveSchedule(Request $request, $id)
    {
        $sub = Submission::where('id_result', $id)->first();
        $targetPeriodId = $sub ? $sub->result?->id_periode : null;

        $interview = Interview::where('id_result', $id)->first();
        if (!$interview && $targetPeriodId) {
            $interview = Interview::where('id_interview', $id)
                ->whereHas('result', fn($q) => $q->where('id_periode', $targetPeriodId))
                ->first();
        }

        $period = $interview->period ?? ($sub ? $sub->result?->period : Period::getActivePeriod());

        if (!$request->has('id_interviewer') && $request->has('interviewer_1_id')) {
            $request->merge([
                'id_interviewer' => array_filter([
                    $request->input('interviewer_1_id'),
                    $request->input('interviewer_2_id'),
                    $request->input('interviewer_3_id'),
                ])
            ]);
        }

        $request->validate([
            'interview_at' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($period) {
                    $valCarbon = Carbon::parse($value)->startOfDay();
                    if ($period && $period->interview_start && $valCarbon->lt(Carbon::parse($period->interview_start)->startOfDay())) {
                        $fail('Tanggal wawancara tidak boleh kurang dari tanggal mulai tahapan wawancara (' . Carbon::parse($period->interview_start)->translatedFormat('d M Y') . ').');
                    }
                    if ($period && $period->interview_end && $valCarbon->gt(Carbon::parse($period->interview_end)->endOfDay())) {
                        $fail('Tanggal wawancara tidak boleh lebih dari tanggal selesai tahapan wawancara (' . Carbon::parse($period->interview_end)->translatedFormat('d M Y') . ').');
                    }
                }
            ],
            'id_interviewer' => 'required|array',
            'id_interviewer.*' => 'exists:users,id_user',
            'id_external_interviewer' => 'nullable|array',
            'id_external_interviewer.*' => 'exists:users,id_user',
            'external_interviewer_id' => 'nullable',
            'meets_link' => 'nullable|url',
        ]);

        $selectedInterviewers = (array) $request->input('id_interviewer', []);
        $externalInterviewerIds = (array) ($request->input('id_external_interviewer') ?? $request->input('external_interviewer_ids') ?? $request->input('external_interviewer_id') ?? []);
        $externalInterviewerIds = array_values(array_filter($externalInterviewerIds));

        // Sync interview_data JSON array
        $currentData = (array) ($interview->interview_data ?? []);
        $newData = [];
        foreach ($selectedInterviewers as $userId) {
            $existing = collect($currentData)->first(fn($item) => isset($item['id_user']) && $item['id_user'] == $userId && empty($item['is_external']));
            if ($existing) {
                $newData[] = $existing;
            } else {
                $newData[] = [
                    'id_user' => (int) $userId,
                    'score' => null,
                    'note' => null,
                    'is_draft' => true
                ];
            }
        }

        // Add external interviewers if selected
        foreach ($externalInterviewerIds as $extId) {
            $existingExt = collect($currentData)->first(function ($item) use ($extId) {
                return (isset($item['is_external']) && $item['is_external']) 
                    && (isset($item['id_user']) && (string) $item['id_user'] === (string) $extId);
            });

            if ($existingExt) {
                $newData[] = $existingExt;
            } else {
                $extUser = User::find($extId);
                if ($extUser) {
                    $newData[] = [
                        'id_user' => (int) $extUser->id_user,
                        'name' => $extUser->name,
                        'instansi' => '',
                        'score' => null,
                        'note' => null,
                        'is_draft' => true,
                        'is_external' => true,
                    ];
                }
            }
        }

        $meetLink = $request->input('link_meet_interview', $request->input('meets_link'));
        $interview->update([
            'interview_at' => $request->input('interview_at'),
            'link_meet_interview' => $meetLink,
            'meets_link' => $meetLink,
        ]);

        // Sync ke tabel interview_evaluators
        $allAssignedUserIds = collect($newData)->pluck('id_user')->filter()->map(fn($id) => (int)$id)->all();
        \App\Models\InterviewEvaluator::where('id_interview', $interview->id_interview)
            ->whereNotIn('id_user', $allAssignedUserIds)
            ->delete();

        foreach ($newData as $item) {
            $uId = $item['id_user'] ?? null;
            if ($uId && User::where('id_user', (int)$uId)->exists()) {
                \App\Models\InterviewEvaluator::updateOrCreate(
                    [
                        'id_interview' => $interview->id_interview,
                        'id_user'      => (int)$uId,
                    ],
                    [
                        'score'    => isset($item['score']) && $item['score'] !== '' ? floatval($item['score']) : null,
                        'catatan'  => $item['note'] ?? $item['catatan'] ?? null,
                        'is_draft' => (bool) ($item['is_draft'] ?? true),
                    ]
                );

                if (!empty($item['name']) || !empty($item['instansi'])) {
                    $uUpdates = [];
                    if (!empty($item['name'])) $uUpdates['name'] = $item['name'];
                    if (!empty($item['instansi'])) $uUpdates['instansi'] = $item['instansi'];
                    User::where('id_user', (int)$uId)->update($uUpdates);
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jadwal wawancara berhasil disimpan.',
            ]);
        }

        return back()->with('success', 'Jadwal wawancara berhasil disimpan.');
    }

    /**
     * Save/Update Schedule for multiple Wawancara records in batch.
     */
    public function saveScheduleBatch(Request $request)
    {
        $interviewIds = $request->input('interview_ids');
        if (!is_array($interviewIds)) {
            $interviewIds = array_filter(explode(',', (string) $interviewIds));
        }
        $interviewIds = array_values(array_filter((array) $interviewIds));
        $request->merge(['interview_ids' => $interviewIds]);

        $idInterviewer = $request->input('id_interviewer');
        if (!is_array($idInterviewer) || empty($idInterviewer)) {
            $idInterviewer = array_values(array_filter([
                $request->input('interviewer_1_id'),
                $request->input('interviewer_2_id'),
                $request->input('interviewer_3_id'),
            ]));
            $request->merge(['id_interviewer' => $idInterviewer]);
        }

        $firstResultId = !empty($interviewIds) ? $interviewIds[0] : null;
        $firstSub = $firstResultId ? Submission::with('result.period')->where('id_result', $firstResultId)->first() : null;
        $period = $firstSub?->result?->period ?? Period::getActivePeriod();

        $request->validate([
            'interview_ids' => 'required|array|min:1',
            'interview_at' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($period) {
                    $valCarbon = Carbon::parse($value)->startOfDay();
                    if ($period && $period->interview_start && $valCarbon->lt(Carbon::parse($period->interview_start)->startOfDay())) {
                        $fail('Tanggal wawancara tidak boleh kurang dari tanggal mulai tahapan wawancara (' . Carbon::parse($period->interview_start)->translatedFormat('d M Y') . ').');
                    }
                    if ($period && $period->interview_end && $valCarbon->gt(Carbon::parse($period->interview_end)->endOfDay())) {
                        $fail('Tanggal wawancara tidak boleh lebih dari tanggal selesai tahapan wawancara (' . Carbon::parse($period->interview_end)->translatedFormat('d M Y') . ').');
                    }
                }
            ],
            'id_interviewer' => 'required|array|min:1',
            'id_interviewer.*' => 'exists:users,id_user',
            'id_external_interviewer' => 'nullable|array',
            'id_external_interviewer.*' => 'exists:users,id_user',
            'external_interviewer_id' => 'nullable',
            'meets_link' => 'nullable|url',
        ], [
            'interview_ids.required' => 'Pilih minimal satu Kabupaten/Kota.',
            'interview_ids.min' => 'Pilih minimal satu Kabupaten/Kota.',
            'id_interviewer.required' => 'Pilih minimal satu pewawancara.',
            'id_interviewer.min' => 'Pilih minimal satu pewawancara.',
            'id_interviewer.*.exists' => 'Pewawancara tidak valid.',
            'meets_link.url' => 'Tautan wawancara harus berupa URL yang valid (http/https).',
        ]);

        $selectedInterviewers = (array) $request->input('id_interviewer', []);
        $externalInterviewerIds = (array) ($request->input('id_external_interviewer') ?? $request->input('external_interviewer_ids') ?? $request->input('external_interviewer_id') ?? []);
        $externalInterviewerIds = array_values(array_filter($externalInterviewerIds));
        $extUsersMap = !empty($externalInterviewerIds) ? User::whereIn('id_user', $externalInterviewerIds)->get()->keyBy('id_user') : collect();

        $allPotentialUserIds = array_unique(array_merge(
            array_map('intval', $selectedInterviewers),
            array_map('intval', $externalInterviewerIds)
        ));
        $validUserIds = User::whereIn('id_user', $allPotentialUserIds)->pluck('id_user')->flip()->toArray();

        $submissionsMap = Submission::with('result')->whereIn('id_result', $interviewIds)->get()->keyBy('id_result');
        $interviewsMap = Interview::whereIn('id_result', $interviewIds)->get()->keyBy('id_result');

        foreach ($interviewIds as $idVal) {
            $sub = $submissionsMap->get($idVal);
            $targetPeriodId = $sub ? $sub->result?->id_periode : ($period ? $period->id_periode : null);

            $interview = $interviewsMap->get($idVal);
            if (!$interview && $targetPeriodId) {
                $interview = Interview::where('id_interview', $idVal)
                    ->whereHas('result', fn($q) => $q->where('id_periode', $targetPeriodId))
                    ->first();
            }

            if (!$interview) {
                $interview = Interview::create([
                    'id_result' => $idVal,
                ]);
            }
            
            // Sync interview_data JSON array
            $currentData = (array) ($interview->interview_data ?? []);
            $newData = [];
            foreach ($selectedInterviewers as $userId) {
                $existing = collect($currentData)->first(fn($item) => isset($item['id_user']) && $item['id_user'] == $userId && empty($item['is_external']));
                if ($existing) {
                    $newData[] = $existing;
                } else {
                    $newData[] = [
                        'id_user' => (int) $userId,
                        'score' => null,
                        'note' => null,
                        'is_draft' => true
                    ];
                }
            }

            // Add external interviewers if selected
            foreach ($externalInterviewerIds as $extId) {
                $existingExt = collect($currentData)->first(function ($item) use ($extId) {
                    return (isset($item['is_external']) && $item['is_external']) 
                        && (isset($item['id_user']) && (string) $item['id_user'] === (string) $extId);
                });

                if ($existingExt) {
                    $newData[] = $existingExt;
                } else {
                    $extUser = $extUsersMap->get($extId) ?? User::find($extId);
                    if ($extUser) {
                        $newData[] = [
                            'id_user' => (int) $extUser->id_user,
                            'name' => $extUser->name,
                            'instansi' => '',
                            'score' => null,
                            'note' => null,
                            'is_draft' => true,
                            'is_external' => true,
                        ];
                    }
                }
            }

            $meetLink = $request->input('link_meet_interview', $request->input('meets_link'));
            $interview->update([
                'interview_at' => $request->input('interview_at'),
                'link_meet_interview' => $meetLink,
                'meets_link' => $meetLink,
            ]);

            // Sync ke tabel interview_evaluators
            $allAssignedUserIds = collect($newData)->pluck('id_user')->filter()->map(fn($id) => (int)$id)->all();
            \App\Models\InterviewEvaluator::where('id_interview', $interview->id_interview)
                ->whereNotIn('id_user', $allAssignedUserIds)
                ->delete();

            foreach ($newData as $item) {
                $uId = $item['id_user'] ?? null;
                if ($uId && isset($validUserIds[(int)$uId])) {
                    \App\Models\InterviewEvaluator::updateOrCreate(
                        [
                            'id_interview' => $interview->id_interview,
                            'id_user'      => (int)$uId,
                        ],
                        [
                            'score'    => isset($item['score']) && $item['score'] !== '' ? floatval($item['score']) : null,
                            'catatan'  => $item['note'] ?? $item['catatan'] ?? null,
                            'is_draft' => (bool) ($item['is_draft'] ?? true),
                        ]
                    );

                    if (!empty($item['name']) || !empty($item['instansi'])) {
                        $uUpdates = [];
                        if (!empty($item['name'])) $uUpdates['name'] = $item['name'];
                        if (!empty($item['instansi'])) $uUpdates['instansi'] = $item['instansi'];
                        User::where('id_user', (int)$uId)->update($uUpdates);
                    }
                }
            }
        }

        $totalSaved = count($interviewIds);
        $msg = 'Jadwal wawancara berhasil disimpan untuk ' . $totalSaved . ' Kab/Kota.';
        Session::flash('toast', [
            'type' => 'success',
            'message' => $msg,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Show Wawancara review page for a specific interview.
     */
    public function reviewWawancara($id)
    {
        $id = is_numeric($id) ? $id : SecureId::decrypt($id);
        if (!$id) {
            abort(404, 'Data wawancara tidak ditemukan.');
        }

        $interview = Interview::with([
            'result.satker',
            'result.period',
            'result.submission',
            'evaluators.user.roles',
        ])->findOrFail($id);

        $currentUser = Auth::user();
        $userRole = $currentUser?->role?->nama_role;
        $data = $interview->interview_data ?? [];
        $currentInterviewerData = collect($data)->first(fn($item) => (string)($item['id_user'] ?? '') === (string)$currentUser->id_user);

        if (!$currentInterviewerData && !in_array($userRole, ['super_evaluator', 'admin'])) {
            return redirect()->route($this->getMonitoringRouteName())->with('error', 'Anda tidak ditugaskan sebagai pewawancara untuk satker ini.');
        }

        // Prepare personal evaluator object
        $interviewEvaluator = (object) [
            'score' => $currentInterviewerData['score'] ?? null,
            'note' => $currentInterviewerData['note'] ?? '',
            'is_draft' => $currentInterviewerData['is_draft'] ?? true,
        ];

        return view('pages.evaluasi.wawancara.review', compact('interview', 'interviewEvaluator'));
    }

    /**
     * Save/Submit Wawancara review.
     */
    public function saveReview(Request $request, $id)
    {
        $id = is_numeric($id) ? $id : SecureId::decrypt($id);
        if (!$id) {
            abort(404, 'Data wawancara tidak ditemukan.');
        }

        return DB::transaction(function () use ($request, $id) {
            $interview = Interview::with(['result.period', 'result.submission'])->lockForUpdate()->findOrFail($id);
            $submission = $interview->submission;

            // Status/tenggat/jadwal dicek EvaluationService (aturan sama dengan jalur sinkronisasi satelit).
            if ($reason = $this->evaluationService->interviewLockReason($interview)) {
                return back()->with('error', $reason);
            }

        $data = (array) ($interview->interview_data ?? []);
        $currentUserId = Auth::user()?->id_user;
        
        $isInternalMe = collect($data)->contains(function ($item) use ($currentUserId) {
            return isset($item['id_user']) && (string) $item['id_user'] === (string) $currentUserId && empty($item['is_external']);
        });
        $isExternalMe = collect($data)->contains(function ($item) use ($currentUserId) {
            return isset($item['id_user']) && (string) $item['id_user'] === (string) $currentUserId && !empty($item['is_external']);
        });
        $isSuperEval = Auth::user()?->role?->nama_role === 'super_evaluator';

        if (!$isInternalMe && !$isExternalMe && !$isSuperEval) {
            return back()->with('error', 'Anda tidak ditugaskan sebagai pewawancara untuk satker ini.');
        }

        $isDraftAction = $request->input('action') === 'draft';

        // Validasi jika user internal men-submit nilai
        if ($isInternalMe && !$isDraftAction) {
            $request->validate([
                'score' => 'required|numeric|min:0|max:100',
                'note'  => 'required|string|max:5000',
            ], [
                'score.required' => 'Nilai wawancara wajib diisi sebelum mengirim penilaian.',
                'score.numeric'  => 'Nilai wawancara harus berupa angka.',
                'note.required'   => 'Catatan wawancara wajib diisi sebelum mengirim penilaian.',
                'note.max'        => 'Catatan wawancara maksimal 5000 karakter.',
            ]);
        }

        // Validasi jika user eksternal men-submit nilai
        if ($isExternalMe && !$isDraftAction) {
            $extInput = $request->input('external_evaluators', []);
            $myExtItem = collect($extInput)->first(function ($item) use ($currentUserId) {
                return isset($item['id_user']) && (string) $item['id_user'] === (string) $currentUserId;
            });
            
            if (!$myExtItem && $request->has('external_scores')) {
                $extUserIds = $request->input('external_user_ids', []);
                $extScores = $request->input('external_scores', []);
                $extNotes = $request->input('external_notes', []);
                $myIdx = array_search((string) $currentUserId, array_map('strval', $extUserIds));
                if ($myIdx !== false) {
                    $myExtItem = [
                        'score' => $extScores[$myIdx] ?? null,
                        'note' => $extNotes[$myIdx] ?? null,
                    ];
                }
            }

            if (!$myExtItem || !isset($myExtItem['score']) || $myExtItem['score'] === '' || $myExtItem['score'] === null) {
                return back()->with('error', 'Nilai wawancara wajib diisi sebelum mengirim penilaian.')->withInput();
            }
            if (empty(trim((string) ($myExtItem['note'] ?? '')))) {
                return back()->with('error', 'Catatan wawancara wajib diisi sebelum mengirim penilaian.')->withInput();
            }
        }

        // Update JSON data
        $data = (array) ($interview->interview_data ?? []);

        // 1. Process regular internal interviewer score if isInternalMe
        if ($isInternalMe) {
            $scoreInput = $request->input('score');
            $scoreVal = ($scoreInput !== null && $scoreInput !== '') ? floatval($scoreInput) : null;
            $noteVal = $request->input('note');
            $isDraft = $isDraftAction || ($scoreVal === null);
            
            $found = false;
            foreach ($data as &$item) {
                if (isset($item['id_user']) && (string) $item['id_user'] === (string) $currentUserId && empty($item['is_external'])) {
                    $item['score'] = $scoreVal;
                    $item['note'] = $noteVal;
                    $item['is_draft'] = $isDraft;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data[] = [
                    'id_user' => (int) $currentUserId,
                    'score' => $scoreVal,
                    'note' => $noteVal,
                    'is_draft' => $isDraft
                ];
            }
        }

        // 2. Process external interviewer scores
        if ($request->has('external_evaluators') || $request->has('external_scores') || $request->has('external_names') || $request->has('external_instansis')) {
            $existingExternals = collect($data)->filter(fn($item) => isset($item['is_external']) && $item['is_external'])->values();
            $data = collect($data)->filter(fn($item) => !(isset($item['is_external']) && $item['is_external']))->toArray();

            $externalInputs = $request->input('external_evaluators');
            if (is_array($externalInputs) && !empty($externalInputs)) {
                foreach ($externalInputs as $idx => $in) {
                    $origExt = $existingExternals->get($idx);
                    $userId = !empty($in['id_user']) ? (int) $in['id_user'] : ($origExt['id_user'] ?? null);
                    
                    $isThisMe = ($userId && (string) $userId === (string) $currentUserId);
                    $canEditThis = $isSuperEval || $isThisMe;

                    if ($canEditThis) {
                        $name = trim($in['name'] ?? ($origExt['name'] ?? 'Penilai Eksternal'));
                        $instansi = trim($in['instansi'] ?? ($origExt['instansi'] ?? ''));
                        $scoreInput = $in['score'] ?? null;
                        $score = ($scoreInput !== null && $scoreInput !== '') ? floatval($scoreInput) : null;
                        $note = mb_substr(isset($in['note']) ? trim((string) $in['note']) : '', 0, 5000);
                        $isItemDraft = $isDraftAction ? true : ($score === null);
                    } else {
                        $name = $origExt['name'] ?? 'Penilai Eksternal';
                        $instansi = $origExt['instansi'] ?? '';
                        $score = $origExt['score'] ?? null;
                        $note = $origExt['note'] ?? '';
                        $isItemDraft = $origExt['is_draft'] ?? true;
                    }

                    $data[] = [
                        'id_user' => $userId,
                        'name' => $name ?: 'Penilai Eksternal',
                        'instansi' => $instansi,
                        'score' => $score,
                        'note' => $note,
                        'is_external' => true,
                        'is_draft' => $isItemDraft,
                    ];
                }
            } else {
                $extUserIds = $request->input('external_user_ids', []);
                $extNames = $request->input('external_names', []);
                $extInstansis = $request->input('external_instansis', []);
                $extScores = $request->input('external_scores', []);
                $extNotes = $request->input('external_notes', []);

                $count = max(count($extNames), count($existingExternals), count($extUserIds));
                for ($idx = 0; $idx < $count; $idx++) {
                    $origExt = $existingExternals->get($idx);
                    $userId = !empty($extUserIds[$idx]) ? (int) $extUserIds[$idx] : ($origExt['id_user'] ?? null);
                    
                    $isThisMe = ($userId && (string) $userId === (string) $currentUserId);
                    $canEditThis = $isSuperEval || $isThisMe;

                    if ($canEditThis) {
                        $name = isset($extNames[$idx]) ? trim((string) $extNames[$idx]) : ($origExt['name'] ?? 'Penilai Eksternal');
                        $instansi = isset($extInstansis[$idx]) ? trim((string) $extInstansis[$idx]) : ($origExt['instansi'] ?? '');
                        $scoreInput = $extScores[$idx] ?? null;
                        $score = ($scoreInput !== null && $scoreInput !== '') ? floatval($scoreInput) : null;
                        $note = isset($extNotes[$idx]) ? trim((string) $extNotes[$idx]) : '';
                        $isItemDraft = $isDraftAction ? true : ($score === null);
                    } else {
                        $name = $origExt['name'] ?? 'Penilai Eksternal';
                        $instansi = $origExt['instansi'] ?? '';
                        $score = $origExt['score'] ?? null;
                        $note = $origExt['note'] ?? '';
                        $isItemDraft = $origExt['is_draft'] ?? true;
                    }

                    $data[] = [
                        'id_user' => $userId,
                        'name' => $name ?: 'Penilai Eksternal',
                        'instansi' => $instansi,
                        'score' => $score,
                        'note' => $note,
                        'is_external' => true,
                        'is_draft' => $isItemDraft,
                    ];
                }
            }
        }

        // Sync ke tabel interview_evaluators
        foreach ($data as $item) {
            $uId = $item['id_user'] ?? null;
            if ($uId && User::where('id_user', (int)$uId)->exists()) {
                \App\Models\InterviewEvaluator::updateOrCreate(
                    [
                        'id_interview' => $interview->id_interview,
                        'id_user'      => (int)$uId,
                    ],
                    [
                        'score'    => isset($item['score']) && $item['score'] !== '' ? floatval($item['score']) : null,
                        'catatan'  => $item['note'] ?? $item['catatan'] ?? null,
                        'is_draft' => (bool) ($item['is_draft'] ?? false),
                    ]
                );

                if (!empty($item['name']) || !empty($item['instansi'])) {
                    $uUpdates = [];
                    if (!empty($item['name'])) $uUpdates['name'] = $item['name'];
                    if (!empty($item['instansi'])) $uUpdates['instansi'] = $item['instansi'];
                    User::where('id_user', (int)$uId)->update($uUpdates);
                }
            }
        }

        // Super evaluators saving without isMe doesn't change their own draft, they just update external scores
        $isMe = $isInternalMe || $isExternalMe;
        $finalizeRequested = $request->input('action') === 'submit' || ($isSuperEval && !$isMe);

        // Rata-rata, finalisasi, status, dan syncResult ada di EvaluationService.
        $result = $this->evaluationService->finalizeInterview($interview, $submission, $data, $finalizeRequested);

        return redirect()->route($this->getMonitoringRouteName())->with('success', $result->message);
        });
    }

    /**
     * Export Wawancara ke Excel (Single satker untuk Operator/Verifikator, Multi satker untuk Evaluator).
     */
    public function exportWawancara(Request $request)
    {
        $user = Auth::user();
        $userRole = $user?->role?->nama_role;

        if ($userRole === 'penilai_eksternal') {
            return back()->with('error', 'Penilai Eksternal hanya dapat mengunduh rekap hasil akhir pada halaman Penilaian Akhir setelah periode selesai.');
        }

        // Operator & Verifikator: single satker export
        if (in_array($userRole, ['operator', 'verifikator'])) {
            return $this->exportWawancaraSingleSatker($request, $user);
        }

        // Evaluator / Super Evaluator / Penilai Eksternal: multi satker export
        return $this->exportWawancaraMultiSatker($request);
    }

    /**
     * Export Wawancara single satker (Operator / Verifikator)
     */
    protected function exportWawancaraSingleSatker(Request $request, $user)
    {
        $tahun = $request->input('tahun');
        $idPeriode = $request->input('id_periode');

        if ($idPeriode) {
            $period = Period::find($idPeriode);
        } elseif ($tahun) {
            $period = Period::whereYear('periode_start', $tahun)->orderByDesc('periode_start')->first();
        } else {
            $period = Period::getActivePeriod();
        }

        if (!$period) {
            return back()->with('error', 'Periode evaluasi tidak ditemukan.');
        }

        $submission = Submission::whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker)->where('id_periode', $period->id_periode))
            ->with(['result.satker', 'interview'])
            ->first();

        if (!$submission || $submission->status < 6) {
            return back()->with('error', 'Data Wawancara untuk tahun ' . $period->tahun . ' belum tersedia atau belum lolos kualifikasi.');
        }

        $interview = $submission->interview;
        $interviewEnd = $period->interview_end ? Carbon::parse($period->interview_end)->endOfDay() : null;
        $isInterviewReleased = !$interviewEnd || now()->greaterThan($interviewEnd);

        $satkerName = $user->masterSatker->nama_satker ?? $submission->user->masterSatker->nama_satker ?? 'BPS Kab/Kota';
        $periodYear = $period->tahun ?? date('Y');
        $filename = 'rekap_wawancara_' . str_replace(' ', '_', strtolower($satkerName)) . '_' . $periodYear . '_' . now()->format('Ymd_His') . '.xls';

        $interviewDate = ($interview && $interview->interview_at) ? Carbon::parse($interview->interview_at)->translatedFormat('d F Y') : 'Belum Dijadwalkan';
        $meetsLink = $interview ? $interview->meets_link : '-';
        $interviewScore = ($interview && $interview->interview_score !== null && $isInterviewReleased) ? number_format($interview->interview_score, 2) : '-';

        $html = '<table border="1" style="font-family:Arial;font-size:11pt;border-collapse:collapse;">';
        $html .= '<tr><td colspan="4" style="font-weight:bold;font-size:14pt;text-align:center;background-color:#184D77;color:#ffffff;padding:10px;">Rekap Sesi Wawancara - KIP</td></tr>';
        $html .= '<tr><td style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Unit Kerja / Satker:</td><td colspan="3" style="border:1px solid #9e9e9e;">' . htmlspecialchars($satkerName) . '</td></tr>';
        $html .= '<tr><td style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Tahun Periode:</td><td colspan="3" style="border:1px solid #9e9e9e;">' . $periodYear . '</td></tr>';
        $html .= '<tr><td style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Jadwal Wawancara:</td><td colspan="3" style="border:1px solid #9e9e9e;">' . htmlspecialchars($interviewDate) . '</td></tr>';
        $html .= '<tr><td style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Link Meeting Room:</td><td colspan="3" style="border:1px solid #9e9e9e;">' . ($meetsLink && $meetsLink !== '-' ? '<a href="' . htmlspecialchars($meetsLink) . '">' . htmlspecialchars($meetsLink) . '</a>' : '-') . '</td></tr>';
        $html .= '<tr><td style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Nilai Rata-rata Wawancara:</td><td colspan="3" style="border:1px solid #9e9e9e;font-weight:bold;">' . $interviewScore . '</td></tr>';
        $html .= '<tr><td colspan="4" style="padding:4px;border:none;"></td></tr>';

        $cols = ['No', 'Petugas Evaluator / Penilai', 'Nilai Evaluator', 'Catatan Evaluator'];
        $html .= '<tr>';
        foreach ($cols as $col) {
            $html .= '<td style="font-weight:bold;background-color:#E8F0FE;text-align:center;padding:6px;border:1px solid #9e9e9e;">' . $col . '</td>';
        }
        $html .= '</tr>';

        $interviewers = $interview ? $interview->interviewers : [];
        if (!empty($interviewers)) {
            $iCounter = 1;
            foreach ($interviewers as $ev) {
                $evName = $ev->user->name ?? '-';
                $evRole = $ev->user->role->nama_role ?? '-';
                $evScore = ($ev->score !== null && $isInterviewReleased) ? number_format($ev->score, 2) : '-';
                $evNote = ($isInterviewReleased) ? ($ev->note ?: '-') : '-';

                $html .= '<tr>';
                $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . $iCounter++ . '</td>';
                $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($evName) . ' (' . htmlspecialchars($evRole) . ')</td>';
                $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;font-weight:bold;">' . $evScore . '</td>';
                $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($evNote) . '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="4" style="text-align:center;padding:8px;border:1px solid #e0e0e0;">Belum ada penilai / evaluator terdaftar.</td></tr>';
        }

        $html .= '</table>';

        return ExcelExportHelper::download($filename, 'Rekap Wawancara', $html);
    }

    /**
     * Export Wawancara multi satker (Evaluator / Super Evaluator / Penilai Eksternal)
     */
    protected function exportWawancaraMultiSatker(Request $request)
    {
        $selectedPeriodId = $request->input('id_periode');
        $period = Period::resolvePeriod($selectedPeriodId);

        if (!$period) {
            return back()->with('error', 'Tidak ada periode aktif.');
        }

        $interviews = Interview::with(['result.satker', 'result.submission'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $period->id_periode))
            ->get()
            ->sortBy(fn($i) => $i->result?->satker?->kode_satker ?? '');

        // Filter by kota[] if provided
        $filterKotaArr = $request->input('kota', []);
        if (empty($filterKotaArr)) {
            $msg = 'Pilih minimal satu Kabupaten/Kota untuk diunduh.';
            if ($request->has('validate')) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return back()->with('error', $msg);
        }

        $filterKotaLower = array_map('strtolower', $filterKotaArr);
        $interviews = $interviews->filter(
            fn($i) => in_array(strtolower($i->result?->satker?->nama_satker ?? ''), $filterKotaLower)
        );

        if ($interviews->isEmpty()) {
            $msg = 'Tidak ada data Kabupaten/Kota yang cocok dengan filter.';
            if ($request->has('validate')) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return back()->with('error', $msg);
        }

        $gradedCount = $interviews->filter(fn($i) => $i->interview_score !== null)->count();
        if ($gradedCount === 0) {
            $msg = 'Kabupaten/Kota yang dipilih belum selesai dinilai pada tahap wawancara.';
            if ($request->has('validate')) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return back()->with('error', $msg);
        }

        if ($request->has('validate')) {
            return response()->json(['success' => true]);
        }

        $styleTitle = [
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '184D77']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $styleInfoLabel = [
            'font'    => ['bold' => true],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9E9E9E']]],
        ];
        $styleInfoValue = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9E9E9E']]],
        ];
        $styleColHeader = [
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F0FE']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9E9E9E']]],
        ];
        $styleCellCenter = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
        ];
        $styleCellLeft = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Wawancara');

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'Rekap Monitoring Wawancara - KIP');
        $sheet->getStyle('A1')->applyFromArray($styleTitle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $infoRows = [
            ['Periode', $period->nama ?? (string) $period->tahun],
            ['Tahun',   (string) ($period->tahun ?? date('Y'))],
        ];
        $r = 2;
        foreach ($infoRows as [$label, $value]) {
            $sheet->mergeCells('A' . $r . ':B' . $r);
            $sheet->mergeCells('C' . $r . ':I' . $r);
            $sheet->setCellValue('A' . $r, $label);
            $sheet->setCellValueExplicit('C' . $r, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->getStyle('A' . $r . ':B' . $r)->applyFromArray($styleInfoLabel);
            $sheet->getStyle('C' . $r . ':I' . $r)->applyFromArray($styleInfoValue);
            $r++;
        }
        $r++; // spacer

        $cols = [
            'A' => 'No',
            'B' => 'Kode Satker',
            'C' => 'Nama Satker',
            'D' => 'Pewawancara',
            'E' => 'Tanggal Wawancara',
            'F' => 'Status',
            'G' => 'Nilai Wawancara',
            'H' => 'Rata-rata Skor',
            'I' => 'Catatan',
        ];
        foreach ($cols as $col => $label) {
            $sheet->setCellValue($col . $r, $label);
            $sheet->getStyle($col . $r)->applyFromArray($styleColHeader);
        }
        $sheet->getRowDimension($r)->setRowHeight(30);
        $r++;

        $no = 1;
        foreach ($interviews as $int) {
            $satker    = $int->result?->satker;
            $data      = collect($int->interview_data ?? []);

            $names = $data->map(function ($ev) {
                if (!empty($ev['is_external'])) {
                    return ($ev['name'] ?? '') . ' (Eksternal)';
                }
                $user = User::find($ev['id_user'] ?? null);
                return $user?->name ?? $user?->nama_user ?? '';
            })->filter()->implode(', ');
            $names = $names ?: '-';

            $tanggal = $int->interview_at ? Carbon::parse($int->interview_at)->translatedFormat('d M Y') : '-';

            $allSubmitted  = $data->isNotEmpty() && $data->every(fn($ev) => isset($ev['score']) && !($ev['is_draft'] ?? true));
            $anyScored     = $data->some(fn($ev) => isset($ev['score']));
            if ($allSubmitted) {
                $status = 'Sudah Dinilai';
            } elseif ($anyScored) {
                $status = 'Draft Penilaian';
            } else {
                $status = 'Belum Dinilai';
            }

            $nilaiWaw = $int->interview_score !== null ? number_format($int->interview_score, 2) : '-';

            $submittedScores = $data->filter(fn($ev) => isset($ev['score']) && !($ev['is_draft'] ?? true))->pluck('score');
            $avgIndividual   = $submittedScores->isNotEmpty() ? number_format($submittedScores->average(), 2) : '-';

            $notes = $data->map(fn($ev) => trim($ev['note'] ?? ''))->filter()->implode(' | ');
            $notes = $notes ?: '-';

            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValueExplicit('B' . $r, $satker?->kode_satker ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $r, $satker?->nama_satker ?? '-');
            $sheet->setCellValue('D' . $r, $names);
            $sheet->setCellValue('E' . $r, $tanggal);
            $sheet->setCellValue('F' . $r, $status);
            $sheet->setCellValue('G' . $r, $nilaiWaw);
            $sheet->setCellValue('H' . $r, $avgIndividual);
            $sheet->setCellValue('I' . $r, $notes);

            $sheet->getStyle('A' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('B' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('C' . $r)->applyFromArray($styleCellLeft);
            $sheet->getStyle('D' . $r)->applyFromArray($styleCellLeft);
            $sheet->getStyle('E' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('F' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('G' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('H' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('I' . $r)->applyFromArray($styleCellLeft);
            $sheet->getRowDimension($r)->setRowHeight(-1);
            $r++;
        }

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(40);

        $filename = 'rekap_wawancara_' . ($period->tahun ?? date('Y')) . '_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
