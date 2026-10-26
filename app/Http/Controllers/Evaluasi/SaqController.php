<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\MasterIndikator;
use App\Models\MasterPertanyaan;
use App\Models\MasterSatker;
use App\Models\Period;
use App\Models\Result;
use App\Models\Submission;
use App\Models\User;
use App\Services\PenilaianService;
use App\Helpers\ExcelExportHelper;
use App\Helpers\SAQExportHelper;
use App\Helpers\SAQImportHelper;
use App\Helpers\SecureId;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SaqController extends Controller
{
    protected PenilaianService $penilaianService;

    public function __construct(PenilaianService $penilaianService)
    {
        $this->penilaianService = $penilaianService;
    }

    private function getRoutePrefix(): string
    {
        $role = Auth::user()?->role?->nama_role;
        if ($role === 'super_evaluator') {
            return 'super-evaluator';
        }
        if ($role === 'evaluator') {
            return 'evaluator';
        }
        if ($role === 'verifikator') {
            return 'verifikator';
        }
        if ($role === 'penilai_eksternal') {
            return 'penilai-eksternal';
        }
        return 'operator';
    }

    /**
     * Unified entry point for SAQ domain.
     */
    public function index(Request $request, $id_periode = null)
    {
        $user = Auth::user();
        $userRole = $user?->role?->nama_role;

        // 1. OPERATOR
        if ($userRole === 'operator') {
            // Decrypt id_periode jika ada
            if ($id_periode && !is_numeric($id_periode)) {
                $id_periode = SecureId::decrypt($id_periode);
            }

            // Jika id_periode kosong, tampilkan daftar monitoring satker
            if (!$id_periode) {
                $periods = Period::getAvailablePeriodsForUser($user);
                $submissionsCollection = Submission::with(['answers', 'result'])
                    ->whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker))
                    ->get();
                Submission::preloadUsers($submissionsCollection);
                $submissions = $submissionsCollection->keyBy(fn($sub) => $sub->result ? $sub->result->id_periode : 0);

                return view('pages.evaluasi.saq.monitoring-satker', compact('periods', 'submissions'));
            }

            $periodId = $id_periode;
            $period = Period::findOrFail($periodId);

            // Get or create result & submission
            $result = Result::firstOrCreate([
                'kode_satker' => $user->kode_satker,
                'id_periode' => $period->id_periode,
            ]);

            $submission = Submission::with('result')->firstOrCreate([
                'id_result' => $result->id_result,
            ], [
                'id_operator' => [['id' => $user->id_user, 'nama' => $user->nama_user]],
                'status' => 0, // Draft
            ]);

            // Sync operator if not present (keyed by id)
            $operators = (array) $submission->id_operator;
            $existingIds = array_column($operators, 'id');
            if (!in_array($user->id_user, $existingIds)) {
                $operators[] = ['id' => $user->id_user, 'nama' => $user->nama_user];
                $submission->update(['id_operator' => $operators]);
            }

            // Check if SAQ evaluation stage date has passed
            $evalEnd = $period->eval_end ? Carbon::parse($period->eval_end)->endOfDay() : null;
            $isReleased = !$evalEnd || now()->greaterThan($evalEnd);

            // Whether the submission has been evaluated (status >= 4) and release date has passed
            $isEvaluated = in_array($submission->status, [4, 5, 6, 7]) && $isReleased;

            // Load indicators, sub-indicators, and questions
            $indicators = MasterIndikator::getOrderedForPeriod($periodId, [
                'subIndikators.pertanyaans' => function ($query) {
                    $query->orderBy('order_no');
                }
            ]);

            // Get current answers
            $answers = Answer::where('id_submission', $submission->id_submission)
                ->get()
                ->keyBy('id_question');

            // Calculate progress percentage
            $progressData = $this->penilaianService->getAnswerProgress($submission);
            $totalQuestions = $progressData['total'];
            $answeredCount = $progressData['answered'];
            $progressPercent = $progressData['percent'];

            $dynamicMaxScores = $this->penilaianService->getDynamicMaxScores();
            $indicatorScores = $this->penilaianService->calculateIndicatorScores($submission);

            // Current active tab (default is first indicator in ordered list)
            $activeTab = $request->has('tab') ? (int) $request->input('tab') : (int) ($indicators->first()?->id_indicators ?? 1);

            return view('pages.evaluasi.saq.pengisian', compact(
                'submission',
                'period',
                'indicators',
                'answers',
                'progressPercent',
                'totalQuestions',
                'answeredCount',
                'activeTab',
                'dynamicMaxScores',
                'indicatorScores',
                'isEvaluated'
            ));
        }

        // 2. VERIFIKATOR (Kasubag Kabkot)
        if ($userRole === 'verifikator') {
            $rawParam = $request->input('submission') ?? $request->input('id_periode') ?? $id_periode;
            $decryptedId = null;
            if ($rawParam) {
                $decryptedId = is_numeric($rawParam) ? (int)$rawParam : SecureId::decrypt($rawParam);
            }

            $submission = null;
            if ($rawParam && $decryptedId) {
                $isExplicitPeriod = ($request->route() && $request->route()->hasParameter('id_periode')) || $request->filled('id_periode');
                $isExplicitSubmission = ($request->route() && $request->route()->hasParameter('submission')) || $request->filled('submission');

                // 1. Jika parameter adalah Period ID (baik eksplisit route id_periode, atau Period valid)
                if ($isExplicitPeriod || (!$isExplicitSubmission && Period::where('id_periode', $decryptedId)->exists())) {
                    $targetPeriod = Period::find($decryptedId);
                    if ($targetPeriod) {
                        $submission = Submission::with(['result.satker', 'result.period'])
                            ->whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker)->where('id_periode', $targetPeriod->id_periode))
                            ->first();

                        if (!$submission) {
                            // Periode valid tapi operator satker belum mengisi, tampilkan monitoring satker dengan pemberitahuan
                            $periods = Period::getAvailablePeriodsForUser($user);
                            $operators = User::role('operator')
                                ->where('kode_satker', $user->kode_satker)
                                ->get();

                            $submissionsList = Submission::with(['answers', 'result'])
                                ->whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker))
                                ->get();

                            Submission::preloadUsers($submissionsList);

                            $submissions = collect();
                            foreach ($submissionsList as $sub) {
                                if ($sub->result) {
                                    $submissions->put($sub->result->id_periode, $sub);
                                }
                            }

                            return view('pages.evaluasi.saq.monitoring-satker', compact('periods', 'operators', 'submissions'))
                                ->with('toast', [
                                    'type'    => 'info',
                                    'message' => 'Operator belum memulai pengisian kuesioner SAQ untuk periode yang dipilih.',
                                ]);
                        }
                    }
                }

                // 2. Jika bukan periode atau eksplisit submission
                if (!$submission) {
                    $targetSubmission = Submission::with(['result.satker', 'result.period'])->find($decryptedId);
                    if ($targetSubmission) {
                        // IDOR Protection: Verifikator hanya boleh melihat submission satkernya sendiri
                        if ((string)$targetSubmission->result?->kode_satker !== (string)$user->kode_satker && !$user->hasAnyRole(['admin', 'super_evaluator'])) {
                            abort(403, 'Akses ditolak. Kuesioner ini bukan milik satuan kerja Anda.');
                        }
                        $submission = $targetSubmission;
                    } elseif (!$isExplicitPeriod) {
                        abort(404, 'Data kuesioner tidak ditemukan.');
                    }
                }
            }

            // Jika tanpa parameter, tampilkan halaman monitoring satker
            if (!$submission) {
                $periods = Period::getAvailablePeriodsForUser($user);
                $operators = User::role('operator')
                    ->where('kode_satker', $user->kode_satker)
                    ->get();
                
                $submissionsList = Submission::with(['answers', 'result'])
                    ->whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker))
                    ->get();

                Submission::preloadUsers($submissionsList);

                $submissions = collect();
                foreach ($submissionsList as $sub) {
                    if ($sub->result) {
                        $submissions->put($sub->result->id_periode, $sub);
                    }
                }

                return view('pages.evaluasi.saq.monitoring-satker', compact('periods', 'operators', 'submissions'));
            }

            Submission::preloadUsers([$submission]);

            // Ensure user is authorized to view this submission (IDOR protection)
            Gate::authorize('view', $submission);

            $period = $submission->result?->period ?? Period::getActivePeriod();

            // Load indicators, sub-indicators, and questions
            $indicators = MasterIndikator::getOrderedForPeriod($submission->result?->id_periode, [
                'subIndikators.pertanyaans' => function ($query) {
                    $query->orderBy('order_no');
                }
            ]);

            // Get current answers
            $answers = Answer::where('id_submission', $submission->id_submission)
                ->get()
                ->keyBy('id_question');

            // Total questions
            $totalQuestions = MasterPertanyaan::getCachedCount() ?: 1;
            $answeredCount = $answers->filter(function ($a) {
                if (is_null($a->answer)) return false;
                if ((int)$a->answer === 1 || $a->answer === true) {
                    return !empty(trim($a->link_ppid ?? '')) && !empty(trim($a->link_drive ?? ''));
                }
                return true;
            })->count();

            // Active tab
            $activeTab = $request->has('tab') ? (int) $request->input('tab') : (int) ($indicators->first()?->id_indicators ?? 1);

            return view('pages.evaluasi.saq.verifikasi', compact(
                'submission',
                'period',
                'indicators',
                'answers',
                'totalQuestions',
                'answeredCount',
                'activeTab'
            ));
        }

        // 3. EVALUATOR, SUPER EVALUATOR, ADMIN
        $selectedPeriodId = $request->input('id_periode') ?? $id_periode;
        if ($selectedPeriodId && !is_numeric($selectedPeriodId)) {
            $selectedPeriodId = SecureId::decrypt($selectedPeriodId);
        }

        $period = Period::resolvePeriod($selectedPeriodId);
        $periods = \Illuminate\Support\Facades\Cache::remember('periods_desc', 3600, function() {
            return Period::orderByDesc('periode_start')->get();
        });

        $evaluators = \Illuminate\Support\Facades\Cache::remember('evaluators_with_roles_saq', 3600, function() {
            return User::role(['evaluator', 'super_evaluator', 'penilai_eksternal'])
                ->with('roles')
                ->get()
                ->sortBy(fn($user) => $user->hasRole('super_evaluator') ? 0 : ($user->hasRole('penilai_eksternal') ? 2 : 1))
                ->values();
        });

        $submissions = collect();
        if ($period) {
            $submissions = Submission::with(['result.satker', 'answers', 'evaluators.roles', 'verifier.roles'])
                ->whereHas('result', fn($q) => $q->where('id_periode', $period->id_periode))
                ->where('status', '>=', 1)
                ->get();

            Submission::preloadUsers($submissions);
        }

        if ($userRole === 'penilai_eksternal') {
            return view('pages.evaluasi.saq.review-eksternal', compact('period', 'periods'));
        }

        return view('pages.evaluasi.saq.monitoring', compact('period', 'periods', 'evaluators', 'submissions'));
    }

    /**
     * Alias for Operator SAQ route: operator.kues-saq
     */
    public function kuesSaq(Request $request, $id_periode = null)
    {
        return $this->index($request, $id_periode);
    }

    /**
     * Alias for Verifikator SAQ route: verifikator.verifikasi-saq
     */
    public function verifikasiSaq(Request $request, $submission = null)
    {
        return $this->index($request, $submission);
    }

    /**
     * Alias for Evaluator SAQ route: evaluator.monitor-kues, super-evaluator.monitor-kues
     */
    public function monitorKues(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Save SAQ questionnaire answers as draft or submit them (Operator).
     */

    /**
     * Download pre-filled Excel template for SAQ Operator.
     */
    public function downloadTemplateSaq(Request $request)
    {
        $user = Auth::user();
        $idPeriode = $request->input('id_periode');
        $tahun = $request->input('tahun');

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

        $satker = $user->masterSatker;
        if (!$satker) {
            return back()->with('error', 'Satker pengguna tidak ditemukan.');
        }

        $submission = Submission::whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker)->where('id_periode', $period->id_periode))
            ->with(['result.satker', 'answers'])
            ->first();

        // Load indicators, sub-indicators, and questions
        $indicators = MasterIndikator::getOrderedForPeriod($period->id_periode, [
            'subIndikators.pertanyaans' => function ($query) {
                $query->orderBy('order_no');
            }
        ]);

        $answers = $submission ? $submission->answers->keyBy('id_question') : collect();

        return SAQImportHelper::generateTemplate($period, $indicators, $answers, $submission, $satker);
    }

    /**
     * Download blank SAQ template publicly without login (for initial preparation).
     */
    public function downloadPublicTemplateSaq(Request $request)
    {
        $idPeriode = $request->input('id_periode');
        $tahun = $request->input('tahun');

        if ($idPeriode) {
            $period = Period::find($idPeriode);
        } elseif ($tahun) {
            $period = Period::whereYear('periode_start', $tahun)->orderByDesc('periode_start')->first();
        } else {
            $period = Period::getActivePeriod();
        }

        if (!$period) {
            $period = Period::orderByDesc('periode_start')->first();
        }

        if (!$period) {
            return back()->with('error', 'Periode evaluasi kuesioner belum tersedia.');
        }

        // Load indicators, sub-indicators, and questions
        $indicators = MasterIndikator::getOrderedForPeriod($period->id_periode, [
            'subIndikators.pertanyaans' => function ($query) {
                $query->orderBy('order_no');
            }
        ]);

        return SAQImportHelper::generatePublicTemplate($period, $indicators);
    }

    /**
     * Import answers from Excel template for SAQ Operator.
     */
    public function importAnswersSaq(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:5120',
            'id_submission' => 'nullable',
        ]);

        $user = Auth::user();
        $submissionId = $request->input('id_submission');

        if ($submissionId) {
            $submission = Submission::with('result.period')->findOrFail($submissionId);
        } else {
            $period = Period::getActivePeriod();
            if (!$period) {
                return response()->json(['success' => false, 'message' => 'Tidak ada periode KIP aktif.'], 422);
            }
            $submission = Submission::whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker)->where('id_periode', $period->id_periode))
                ->with('result.period')
                ->first();
        }

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'Submission Kuesioner SAQ tidak ditemukan.'], 404);
        }

        // Authorize via SubmissionPolicy
        Gate::authorize('update', $submission);

        // Check if evaluation stage has passed
        $period = $submission->result?->period ?? Period::getActivePeriod();
        $evalEnd = $period?->eval_end ? Carbon::parse($period->eval_end)->endOfDay() : null;
        if ($evalEnd && now()->greaterThan($evalEnd)) {
            return response()->json([
                'success' => false,
                'message' => 'Tahap pengisian kuesioner SAQ telah berakhir. Data tidak dapat diubah.',
            ], 403);
        }

        // Check if submission is editable (status 0: draft, or status 2: returned)
        if (!in_array($submission->status, [0, 2])) {
            return response()->json([
                'success' => false,
                'message' => 'Kuesioner telah disubmit dan tidak dapat diubah.',
            ], 422);
        }

        $file = $request->file('excel_file');

        try {
            // Load indicators and questions for this period
            $indicators = MasterIndikator::getOrderedForPeriod($period->id_periode, [
                'subIndikators.pertanyaans' => function ($query) {
                    $query->orderBy('order_no');
                }
            ]);

            $parseResult = SAQImportHelper::parseAnswers($file->getRealPath(), $indicators);
            $parsedAnswers = $parseResult['answers'];

            if (empty($parsedAnswers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada jawaban valid yang dapat dipetakan dari file Excel. Pastikan mengisi kolom Jawaban Operator dengan "Ya" atau "Tidak".',
                ], 422);
            }

            // Save answers in a transaction
            DB::transaction(function () use ($submission, $parsedAnswers) {
                $existingAnswers = Answer::where('id_submission', $submission->id_submission)
                    ->get()
                    ->keyBy('id_question');

                foreach ($parsedAnswers as $ansData) {
                    $existing = $existingAnswers->get($ansData['id_question']);
                    if ($existing) {
                        $existing->id_indicators = $ansData['id_indicators'];
                        $existing->answer        = $ansData['answer'];
                        $existing->link_ppid     = $ansData['link_ppid'];
                        $existing->link_drive    = $ansData['link_drive'];
                        $existing->save();
                    } else {
                        Answer::create([
                            'id_submission' => $submission->id_submission,
                            'id_question'   => $ansData['id_question'],
                            'id_indicators' => $ansData['id_indicators'],
                            'answer'        => $ansData['answer'],
                            'link_ppid'     => $ansData['link_ppid'],
                            'link_drive'    => $ansData['link_drive'],
                        ]);
                    }
                }

                $submission->update([
                    'draft_saved_at' => now(),
                ]);
            });

            $savedCount = count($parsedAnswers);
            $msg = "Berhasil mengimpor {$savedCount} jawaban kuesioner SAQ sebagai draft. ({$parseResult['answered_ya']} Ya, {$parseResult['answered_tidak']} Tidak).";

            if (!empty($parseResult['warnings'])) {
                $msg .= ' Peringatan: Terdapat ' . count($parseResult['warnings']) . ' catatan format URL yang perlu diperiksa.';
            }

            return response()->json([
                'success'        => true,
                'message'        => $msg,
                'saved_count'    => $savedCount,
                'answered_ya'    => $parseResult['answered_ya'],
                'answered_tidak' => $parseResult['answered_tidak'],
                'warnings'       => $parseResult['warnings'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file Excel: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function saveKues(Request $request)
    {
        $submissionId = $request->input('id_submission');
        $isFinalSubmit = $request->input('action') === 'submit';

        // Validate link URL formats
        $questions = MasterPertanyaan::all();
        $errors = [];

        foreach ($questions as $q) {
            $inputName = 'q'.$q->id_master_pertanyaan;
            if ($request->input($inputName) === 'Ya') {
                $linkPpid = $request->input($inputName.'_ppid');
                $linkDrive = $request->input($inputName.'_drive');

                if ($isFinalSubmit) {
                    if (empty($linkPpid) || !preg_match('/^https?:\/\//i', $linkPpid)) {
                        $errors[] = 'Link PPID untuk Pertanyaan ' . $q->order_no . ' wajib diisi dengan URL valid (diawali http:// atau https://).';
                    }
                    if (empty($linkDrive) || !preg_match('/^https?:\/\//i', $linkDrive)) {
                        $errors[] = 'Link Drive untuk Pertanyaan ' . $q->order_no . ' wajib diisi dengan URL valid (diawali http:// atau https://).';
                    }
                } else {
                    if (!empty($linkPpid) && !preg_match('/^https?:\/\//i', $linkPpid)) {
                        $errors[] = 'Link PPID untuk Pertanyaan ' . $q->order_no . ' harus berupa URL valid (diawali http:// atau https://).';
                    }
                    if (!empty($linkDrive) && !preg_match('/^https?:\/\//i', $linkDrive)) {
                        $errors[] = 'Link Drive untuk Pertanyaan ' . $q->order_no . ' harus berupa URL valid (diawali http:// atau https://).';
                    }
                }
            }
        }

        if (!empty($errors)) {
            if ($request->ajax() || $request->wantsJson() || $request->input('ajax') == 1) {
                return response()->json([
                    'success' => false,
                    'message' => $errors[0],
                    'errors' => $errors
                ], 422);
            }
            return back()->withInput()->with('error', $errors[0]);
        }

        $formattedTime = null;

        $result = DB::transaction(function () use ($submissionId, $request, $questions, $isFinalSubmit, &$formattedTime) {
            $submission = Submission::with('result.period')->lockForUpdate()->findOrFail($submissionId);

            // Ensure user is authorized to update this submission (IDOR protection via SubmissionPolicy)
            Gate::authorize('update', $submission);

            // Check if evaluation stage has passed
            $period = $submission->result?->period ?? Period::getActivePeriod();
            $evalEnd = $period?->eval_end ? Carbon::parse($period->eval_end)->endOfDay() : null;
            if ($evalEnd && now()->greaterThan($evalEnd)) {
                return ['error' => 'Tahap pengisian dan evaluasi data kuesioner SAQ telah berakhir.', 'code' => 403];
            }

            // Check if submission is editable
            if (!in_array($submission->status, [0, 2])) {
                return ['error' => 'Kuesioner telah dikirim dan tidak dapat diubah.', 'code' => 409];
            }

            $existingAnswers = Answer::where('id_submission', $submission->id_submission)
                ->get()
                ->keyBy('id_question');

            foreach ($questions as $q) {
                $inputName = 'q'.$q->id_master_pertanyaan;
                if ($request->has($inputName)) {
                    $answerVal = $request->input($inputName);
                    $linkPpid = $request->input($inputName.'_ppid');
                    $linkDrive = $request->input($inputName.'_drive');

                    $isYes = $answerVal === 'Ya';
                    $newPpid = $isYes ? $linkPpid : null;
                    $newDrive = $isYes ? $linkDrive : null;

                    $existing = $existingAnswers->get($q->id_master_pertanyaan);
                    if ($existing) {
                        $isDirty = ($existing->answer !== $isYes)
                            || ($existing->link_ppid !== $newPpid)
                            || ($existing->link_drive !== $newDrive)
                            || ($existing->id_indicators !== $q->id_indicators);

                        if ($isDirty) {
                            $existing->id_indicators = $q->id_indicators;
                            $existing->answer = $isYes;
                            $existing->link_ppid = $newPpid;
                            $existing->link_drive = $newDrive;
                            $existing->save();
                        }
                    } else {
                        Answer::create([
                            'id_submission' => $submission->id_submission,
                            'id_question'   => $q->id_master_pertanyaan,
                            'id_indicators' => $q->id_indicators,
                            'answer'        => $isYes,
                            'link_ppid'     => $newPpid,
                            'link_drive'    => $newDrive,
                        ]);
                    }
                }
            }

            // Handle Draft vs Submit
            if ($isFinalSubmit) {
                $submission->update([
                    'status' => 1,
                    'submitted_at' => now(),
                ]);
            } else {
                $submission->update([
                    'draft_saved_at' => now(),
                ]);
                $formattedTime = Carbon::parse($submission->draft_saved_at)->isToday()
                    ? 'Hari ini, '.Carbon::parse($submission->draft_saved_at)->format('H:i')
                    : Carbon::parse($submission->draft_saved_at)->translatedFormat('d M Y, H:i');
            }

            return ['success' => true];
        });

        if (isset($result['error'])) {
            if ($request->ajax() || $request->wantsJson() || $request->input('ajax') == 1) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], $result['code'] ?? 400);
            }
            return back()->with('error', $result['error']);
        }

        if ($isFinalSubmit) {
            if ($request->ajax() || $request->wantsJson() || $request->input('ajax') == 1) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kuesioner SAQ berhasil dikirim ke Kasubag untuk verifikasi.',
                ]);
            }

            return redirect()->route('operator.kues-saq', ['tab' => $request->input('tab')])
                ->with('success', 'Kuesioner SAQ berhasil dikirim ke Kasubag untuk verifikasi.');
        }

        if ($request->ajax() || $request->wantsJson() || $request->input('ajax') == 1) {
            return response()->json([
                'success' => true,
                'message' => 'Jawaban berhasil disimpan sebagai draft otomatis.',
                'draft_saved_at' => $formattedTime,
            ]);
        }

        return redirect()->route('operator.kues-saq', ['tab' => $request->input('tab')])
            ->with('success', 'Jawaban berhasil disimpan sebagai draft.');
    }

    /**
     * Save draft verification note & notes per question without changing submission status (Verifikator).
     */
    public function saveDraft(Request $request, $id)
    {
        $id = is_numeric($id) ? $id : SecureId::decrypt($id);
        $user = Auth::user();

        return DB::transaction(function () use ($id, $request, $user) {
            $submission = Submission::with('result.period')->lockForUpdate()->findOrFail($id);

            // Ensure user is authorized to update this submission (IDOR protection)
            Gate::authorize('update', $submission);

            // Guard status: hanya submission yang sedang menunggu verifikasi (status = 1) yang boleh disimpan draft verifikasinya
            abort_unless($submission->status === 1, 409, 'Kuesioner tidak dalam status menunggu verifikasi.');

            // Check if evaluation stage has passed
            $period = $submission->result?->period ?? Period::getActivePeriod();
            $evalEnd = $period?->eval_end ? Carbon::parse($period->eval_end)->endOfDay() : null;
            if ($evalEnd && now()->greaterThan($evalEnd)) {
                return redirect()->route('verifikator.index')->with('error', 'Tahap evaluasi data telah berakhir. Draft verifikasi tidak dapat disimpan lagi.');
            }

            $note = $request->input('verification_note');

            $submission->update([
                'verified_by' => $user->id_user,
                'verified_at' => now(),
                'verification_note' => $note,
            ]);

            // Simpan catatan per soal jika ada
            $verifikatorNotes = $request->input('verifikator_note', []);
            $this->syncVerifikatorNotes($submission, $verifikatorNotes);

            return redirect()->back()->with('success', 'Draft verifikasi berhasil disimpan.');
        });
    }

    /**
     * Process verification decision (Setujui / Kembalikan) (Verifikator).
     */
    public function processDecision(Request $request, $id)
    {
        $request->validate([
            'verification_note' => 'required|string|max:5000',
            'decision'          => 'required|in:approve,return',
        ]);

        $id = is_numeric($id) ? $id : SecureId::decrypt($id);
        $user = Auth::user();

        return DB::transaction(function () use ($id, $request, $user) {
            $submission = Submission::with('result.period')->lockForUpdate()->findOrFail($id);

            // Ensure user is authorized to update this submission (IDOR protection)
            Gate::authorize('update', $submission);

            // Guard status: hanya submission berstatus menunggu verifikasi (status = 1) yang bisa disetujui / dikembalikan
            abort_unless($submission->status === 1, 409, 'Kuesioner tidak dalam status menunggu verifikasi.');

            // Check if evaluation stage has passed
            $period = $submission->result?->period ?? Period::getActivePeriod();
            $evalEnd = $period?->eval_end ? Carbon::parse($period->eval_end)->endOfDay() : null;
            if ($evalEnd && now()->greaterThan($evalEnd)) {
                return redirect()->route('verifikator.index')->with('error', 'Tahap evaluasi data telah berakhir. Verifikasi kuesioner SAQ tidak dapat diproses lagi.');
            }

            $decision = $request->input('decision');
            $note = $request->input('verification_note');

            if ($decision === 'approve') {
                // status = 3 (Verified) — catatan verifikator per soal diabaikan dan dibersihkan
                $submission->update([
                    'status' => 3,
                    'verified_by' => $user->id_user,
                    'verified_at' => now(),
                    'verification_note' => $note,
                ]);
                Answer::where('id_submission', $submission->id_submission)->update(['verifikator_note' => null]);
                $msg = 'Kuesioner SAQ berhasil disetujui.';
            } else {
                // status = 2 (Returned) — simpan catatan verifikator per soal
                $submission->update([
                    'status' => 2,
                    'verified_by' => $user->id_user,
                    'verified_at' => now(),
                    'verification_note' => $note,
                ]);

                // Simpan catatan per soal (hanya yang tidak kosong)
                $verifikatorNotes = $request->input('verifikator_note', []);
                $this->syncVerifikatorNotes($submission, $verifikatorNotes);

                $msg = 'Kuesioner SAQ berhasil dikembalikan ke operator.';
            }

            return redirect()->route('verifikator.index')->with('success', $msg);
        });
    }

    /**
     * Simpan catatan verifikator per soal secara efisien (tanpa N+1 queries).
     */
    private function syncVerifikatorNotes(Submission $submission, array $verifikatorNotes): void
    {
        if (empty($verifikatorNotes)) {
            return;
        }

        $questionIds = array_keys($verifikatorNotes);
        $questionsMap = MasterPertanyaan::whereIn('id_master_pertanyaan', $questionIds)
            ->pluck('id_indicators', 'id_master_pertanyaan');

        $existingAnswers = Answer::where('id_submission', $submission->id_submission)
            ->whereIn('id_question', $questionIds)
            ->get()
            ->keyBy('id_question');

        DB::transaction(function () use ($submission, $verifikatorNotes, $questionsMap, $existingAnswers) {
            foreach ($verifikatorNotes as $questionId => $verifNote) {
                $trimmed = trim((string) $verifNote);
                $existing = $existingAnswers->get($questionId);

                if ($trimmed !== '') {
                    if ($existing) {
                        if ($existing->verifikator_note !== $trimmed) {
                            $existing->verifikator_note = $trimmed;
                            $existing->save();
                        }
                    } else {
                        Answer::create([
                            'id_submission'    => $submission->id_submission,
                            'id_question'      => $questionId,
                            'id_indicators'    => $questionsMap->get($questionId),
                            'verifikator_note' => $trimmed,
                        ]);
                    }
                } else {
                    if ($existing && $existing->verifikator_note !== null) {
                        $existing->verifikator_note = null;
                        $existing->save();
                    }
                }
            }
        });
    }

    /**
     * Show SAQ review page for a specific submission (Evaluator & Super Evaluator).
     */
    public function reviewKues(Request $request, $submission)
    {
        $submissionId = is_numeric($submission) ? $submission : SecureId::decrypt($submission);
        if (!$submissionId) {
            return redirect()->route($this->getRoutePrefix() . '.monitor-kues')->with('error', 'Submission ID tidak ditentukan.');
        }

        $submission = Submission::with(['result.satker', 'result.period', 'evaluators'])->findOrFail($submissionId);

        // Otorisasi Policy: Memeriksa status >= 3 dan memastikan penugasan evaluator / super_evaluator / admin
        Gate::authorize('review', $submission);

        // Load indicators, sub-indicators, and questions
        $indicators = MasterIndikator::getOrderedForPeriod($submission->result?->id_periode, [
            'subIndikators.pertanyaans' => function ($query) {
                $query->orderBy('order_no');
            }
        ]);

        // Get answers
        $answers = Answer::where('id_submission', $submission->id_submission)
            ->get()
            ->keyBy('id_question');

        $dynamicMaxScores = $this->penilaianService->getDynamicMaxScores();

        // Active tab
        $activeTab = (int) $request->input('tab', $indicators->first()->id_indicators ?? 1);

        return view('pages.evaluasi.saq.review', compact(
            'submission',
            'indicators',
            'answers',
            'activeTab',
            'dynamicMaxScores'
        ));
    }

    /**
     * Save evaluation review (draft or submit) (Evaluator & Super Evaluator).
     */
    public function saveReview(Request $request)
    {
        $submissionId = $request->input('id_submission');
        $submission = Submission::with(['evaluators'])->findOrFail($submissionId);

        // Otorisasi Policy: Memeriksa status >= 3 dan memastikan penugasan evaluator / super_evaluator / admin
        Gate::authorize('review', $submission);

        if ($submission->status == 8) {
            return back()->with('error', 'Kuesioner berstatus gugur dan tidak dapat dievaluasi.');
        }

        $period = $submission->period;
        $evalStart = $period?->eval_start ? Carbon::parse($period->eval_start)->startOfDay() : null;
        if ($evalStart && now()->startOfDay()->lt($evalStart)) {
            return back()->with('error', 'Tahapan evaluasi kuesioner belum dimulai. Penilaian baru dapat dilakukan mulai tanggal ' . $evalStart->translatedFormat('d M Y') . '.');
        }

        // Check eval_end deadline
        $evalEnd = $period?->eval_end ? Carbon::parse($period->eval_end)->endOfDay() : null;
        if ($evalEnd && now()->greaterThan($evalEnd)) {
            return back()->with('error', 'Batas waktu pengisian/pengeditan nilai evaluasi kuesioner periode ini telah berakhir pada tanggal ' . $evalEnd->translatedFormat('d M Y') . '.');
        }

        $questions = MasterPertanyaan::all();

        // Save review scores in a transaction
        DB::transaction(function () use ($submission, $request, $questions) {
            $isFinalSubmit = $request->input('action') === 'submit';

            // Pre-load all existing answers for this submission in 1 query
            $existingAnswers = Answer::where('id_submission', $submission->id_submission)
                ->get()
                ->keyBy('id_question');

            foreach ($questions as $q) {
                $scoreInput = 'q' . $q->id_master_pertanyaan . '_score';
                $noteInput  = 'q' . $q->id_master_pertanyaan . '_note';

                if (!$request->has($scoreInput)) {
                    continue;
                }

                $rawScore = trim($request->input($scoreInput, ''));
                $noteVal  = mb_substr(trim($request->input($noteInput, '')), 0, 2000);

                if (!$isFinalSubmit && $rawScore === '') {
                    continue;
                }

                $scoreVal = floatval(str_replace(',', '.', $rawScore));
                $scoreVal = max(0, min($scoreVal, $q->bobot_pertanyaan));

                $existing = $existingAnswers->get($q->id_master_pertanyaan);

                if ($existing) {
                    $existingScore = $existing->answer_score !== null ? floatval($existing->answer_score) : null;
                    $existingNote = $existing->evaluator_note;
                    $newNote = $noteVal ?: null;

                    if ($existingScore !== null && abs($existingScore - $scoreVal) < 0.00001 && $existingNote === $newNote) {
                        continue;
                    }

                    $existing->answer_score   = $scoreVal;
                    $existing->evaluator_note = $newNote;
                    $existing->evaluator_at   = now();
                    $existing->save();
                } else {
                    Answer::create([
                        'id_submission'  => $submission->id_submission,
                        'id_question'    => $q->id_master_pertanyaan,
                        'id_indicators'  => $q->id_indicators,
                        'answer_score'   => $scoreVal,
                        'evaluator_note' => $noteVal ?: null,
                        'evaluator_at'   => now(),
                    ]);
                }
            }
        });

        // Calculate SAQ Score
        $saqScore = $this->penilaianService->calculateSaqScore($submission);

        // If action is submit (final evaluation)
        if ($request->input('action') === 'submit') {
            $saqCounts = $this->penilaianService->getSaqEvaluationCounts($submission);
            if ($saqCounts['total'] > 0 && $saqCounts['evaluated'] < $saqCounts['total']) {
                $remaining = max(0, $saqCounts['total'] - $saqCounts['evaluated']);
                return redirect()->route('evaluator.review-kues', [
                    'submission' => SecureId::encrypt($submission->id_submission),
                    'tab' => $request->input('tab')
                ])->with('error', 'Tidak dapat mengirim penilaian. Masih ada ' . $remaining . ' pertanyaan yang belum dinilai. Harap lengkapi semua penilaian terlebih dahulu.');
            }

            foreach ($questions as $q) {
                $noteInput = $request->input('q' . $q->id_master_pertanyaan . '_note');
                if (trim($noteInput ?? '') === '') {
                    return redirect()->route($this->getRoutePrefix() . '.review-kues', [
                        'submission' => SecureId::encrypt($submission->id_submission),
                        'tab' => $request->input('tab')
                    ])->with('error', 'Tidak dapat mengirim penilaian. Semua catatan evaluator harus diisi.');
                }
            }

            $msg = $this->penilaianService->finalizeSaqEvaluation($submission, $saqScore);

            if ($request->ajax() || $request->wantsJson() || $request->input('ajax') == 1) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'saq_score' => $saqScore,
                ]);
            }

            return redirect()->route($this->getRoutePrefix() . '.monitor-kues')->with('success', $msg);
        }

        // Just saving draft
        Result::updateOrCreate(
            ['id_result' => $submission->id_result],
            ['ques_score' => $saqScore]
        );

        $submission->touch('evaluated_at');

        if ($request->ajax() || $request->wantsJson() || $request->input('ajax') == 1) {
            return response()->json([
                'success' => true,
                'message' => 'Hasil evaluasi disimpan sebagai draft otomatis.',
                'saq_score' => $saqScore,
            ]);
        }

        return redirect()->route($this->getRoutePrefix() . '.review-kues', [
            'submission' => SecureId::encrypt($submission->id_submission),
            'tab' => $request->input('tab')
        ])->with('success', 'Hasil evaluasi disimpan sebagai draft. Rerata SAQ sementara: ' . $saqScore);
    }

    /**
     * Batch assign evaluators to submissions (Super Evaluator & Admin).
     */
    public function saveScheduleBatch(Request $request)
    {
        $request->validate([
            'submission_ids' => 'required|array',
            'submission_ids.*' => 'exists:submissions,id_submission',
            'evaluator_ids' => 'required|array',
            'evaluator_ids.*' => 'exists:users,id_user',
        ]);

        $submissionIds = $request->input('submission_ids');
        $evaluatorIds = $request->input('evaluator_ids');

        $submissions = Submission::whereIn('id_submission', $submissionIds)->get();
        foreach ($submissions as $sub) {
            $sub->evaluators()->sync($evaluatorIds);
        }

        $msg = 'Evaluator kuesioner berhasil diatur untuk ' . count($submissionIds) . ' Kab/Kota.';
        session()->flash('toast', [
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
     * Unified export action for SAQ.
     */
    public function export(Request $request)
    {
        $userRole = Auth::user()?->role?->nama_role;

        if (in_array($userRole, ['operator', 'verifikator'])) {
            return $this->exportSaq($request);
        }

        if ($request->has('submission')) {
            return $this->exportKues($request, $request->input('submission'));
        }

        return $this->exportKuesBatch($request);
    }

    /**
     * Export SAQ questionnaire answers for Operator / Verifikator to Excel.
     */
    public function exportSaq(Request $request)
    {
        $user = Auth::user();
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
            ->with(['result.satker', 'answers'])
            ->first();

        if ($submission) {
            Submission::preloadUsers([$submission]);
        }

        if (!$submission || $submission->status < 1) {
            return back()->with('error', 'Kuesioner SAQ untuk tahun ' . $period->tahun . ' belum selesai diisi/disubmit.');
        }

        // Load indicators, sub-indicators, and questions
        $indicators = MasterIndikator::getOrderedForPeriod($period->id_periode, [
            'subIndikators.pertanyaans' => function ($query) {
                $query->orderBy('order_no');
            }
        ]);

        // Get answers
        $answers = Answer::where('id_submission', $submission->id_submission)
            ->get()
            ->keyBy('id_question');

        $evalEnd = $period->eval_end ? Carbon::parse($period->eval_end)->endOfDay() : null;
        $isReleased = !$evalEnd || now()->greaterThan($evalEnd);

        $satkerName = $user->masterSatker->nama_satker ?? $submission->user->masterSatker->nama_satker ?? 'BPS Kab/Kota';
        $periodYear = $period->tahun ?? date('Y');
        $filename = 'rekap_saq_' . str_replace(' ', '_', strtolower($satkerName)) . '_' . $periodYear . '_' . now()->format('Ymd_His') . '.xls';

        $html = '<table border="1" style="font-family:Arial;font-size:11pt;border-collapse:collapse;">';

        // Header Info
        $html .= '<tr><td colspan="12" style="font-weight:bold;font-size:14pt;text-align:center;background-color:#184D77;color:#ffffff;padding:10px;">Rekap Pengisian Kuesioner SAQ - KIP</td></tr>';
        $html .= '<tr><td colspan="2" style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Unit Kerja / Satker:</td><td colspan="10" style="border:1px solid #9e9e9e;">' . htmlspecialchars($satkerName) . '</td></tr>';
        $html .= '<tr><td colspan="2" style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Tahun Periode:</td><td colspan="10" style="border:1px solid #9e9e9e;">' . $periodYear . '</td></tr>';
        $html .= '<tr><td colspan="2" style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Nilai SAQ:</td><td colspan="10" style="border:1px solid #9e9e9e;">' . (($submission->result?->ques_score !== null && $isReleased) ? number_format($submission->result->ques_score, 2) : '-') . '</td></tr>';
        $html .= '<tr><td colspan="12" style="padding:4px;border:none;"></td></tr>';

        // Column Titles
        $cols = ['No', 'Indikator', 'Sub Indikator', 'Pertanyaan', 'Deskripsi', 'Dokumen yang Diperlukan', 'Jawaban Operator', 'Link PPID', 'Link Drive', 'Nilai Evaluator', 'Catatan Evaluator', 'Nilai Maksimal'];
        $html .= '<tr>';
        foreach ($cols as $col) {
            $html .= '<td style="font-weight:bold;background-color:#E8F0FE;text-align:center;padding:6px;border:1px solid #9e9e9e;">' . $col . '</td>';
        }
        $html .= '</tr>';

        $qCounter = 1;
        foreach ($indicators as $ind) {
            foreach ($ind->subIndikators as $subInd) {
                foreach ($subInd->pertanyaans as $q) {
                    $ans = $answers->get($q->id_master_pertanyaan);
                    $val = $ans ? ($ans->answer ? 'Ya' : 'Tidak') : '-';
                    $ppidVal = $ans ? $ans->link_ppid : '';
                    $driveVal = $ans ? $ans->link_drive : '';
                    
                    $earnedScore = ($ans && $ans->answer_score !== null && $isReleased) ? number_format($ans->answer_score, 2) : '-';
                    $evaluatorNote = ($ans && $isReleased) ? $ans->evaluator_note : '';

                    $docText = trim((string)($q->doc_requirement ?? ''));
                    $contohLinks = [];
                    if (!empty(trim((string)($q->link_drive ?? '')))) {
                        $contohLinks[] = "Link Drive: " . trim((string)$q->link_drive);
                    }
                    if (!empty(trim((string)($q->link_ppid ?? '')))) {
                        $contohLinks[] = "Link PPID: " . trim((string)$q->link_ppid);
                    }
                    $cellParts = [];
                    if (!empty($docText)) {
                        $cellParts[] = nl2br(htmlspecialchars($docText));
                    }
                    if (!empty($contohLinks)) {
                        $cellParts[] = "<b>Contoh Link:</b><br>" . implode("<br>", array_map('htmlspecialchars', $contohLinks));
                    }
                    $dokumenHtml = !empty($cellParts) ? implode("<br><br>", $cellParts) : '-';

                    $html .= '<tr>';
                    $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . $qCounter++ . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($ind->nama_indikator) . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($subInd->nama_sub_indikator) . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($q->ques_text) . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($q->ques_description ?? '-') . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . $dokumenHtml . '</td>';
                    $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . $val . '</td>';
                    
                    // Link PPID
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;word-break:break-all;">';
                    if ($ppidVal) {
                        $html .= '<a href="' . htmlspecialchars($ppidVal) . '">' . htmlspecialchars($ppidVal) . '</a>';
                    } else {
                        $html .= '-';
                    }
                    $html .= '</td>';

                    // Link Drive
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;word-break:break-all;">';
                    if ($driveVal) {
                        $html .= '<a href="' . htmlspecialchars($driveVal) . '">' . htmlspecialchars($driveVal) . '</a>';
                    } else {
                        $html .= '-';
                    }
                    $html .= '</td>';

                    $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;font-weight:bold;">' . $earnedScore . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($evaluatorNote ?: '-') . '</td>';
                    $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . number_format($q->bobot_pertanyaan, 2) . '</td>';
                    $html .= '</tr>';
                }
            }
        }

        $html .= '</table>';

        return ExcelExportHelper::download($filename, 'Rekap SAQ', $html);
    }

    /**
     * Export single submission SAQ details to XLS (Evaluator).
     */
    public function exportKues(Request $request, $submission)
    {
        $submissionId = is_numeric($submission) ? $submission : SecureId::decrypt($submission);
        if (!$submissionId) {
            return back()->with('error', 'Submission ID tidak ditentukan.');
        }

        $submission = Submission::with(['result.satker', 'result', 'evaluators'])->findOrFail($submissionId);

        // Otorisasi Policy: Memeriksa status >= 3 dan memastikan penugasan evaluator / super_evaluator / admin
        Gate::authorize('review', $submission);

        // Load indicators, sub-indicators, and questions
        $indicators = MasterIndikator::getOrderedForPeriod($submission->result?->id_periode, [
            'subIndikators.pertanyaans' => function ($query) {
                $query->orderBy('order_no');
            }
        ]);

        // Get answers
        $answers = Answer::where('id_submission', $submission->id_submission)
            ->get()
            ->keyBy('id_question');

        return SAQExportHelper::downloadSingle($submission, $indicators, $answers);
    }

    /**
     * Batch export multiple submissions SAQ details to XLSX (1 sheet per kab/kota).
     */
    public function exportKuesBatch(Request $request)
    {
        $selectedPeriodId = $request->input('id_periode');
        $period = Period::resolvePeriod($selectedPeriodId);

        if (!$period) {
            $msg = 'Tidak ada periode aktif.';
            if ($request->has('validate')) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return back()->with('error', $msg);
        }

        $satkers = MasterSatker::where('kode_satker', '!=', '3500')->get();

        // Query all submissions in this period
        $submissionsInPeriod = Submission::with(['result.satker', 'result', 'answers', 'evaluators'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $period->id_periode))
            ->get();

        Submission::preloadUsers($submissionsInPeriod);

        $submissionsInPeriod = $submissionsInPeriod->keyBy(fn($sub) => $sub->result ? $sub->result->kode_satker : '');

        $records = collect();

        $statusLabels = [
            'belum_dimulai'   => 'Belum Dimulai',
            'draft_pengisian' => 'Draft Pengisian',
            'menunggu_verif'  => 'Menunggu Verif',
            'draft_verif'     => 'Draft Verif',
            'dikembalikan'    => 'Dikembalikan',
            'menunggu_eval'   => 'Menunggu Eval',
            'draft_penilaian' => 'Draft Penilaian',
            'lolos'           => 'Lolos',
            'gugur'           => 'Gugur',
        ];

        foreach ($satkers as $satker) {
            $sub = $submissionsInPeriod->get($satker->kode_satker);

            if (!$sub) {
                $statusVal = 'belum_dimulai';
            } else {
                if ($sub->status === 0) {
                    $answeredCount = $sub->answers->filter(fn($a) => !is_null($a->answer) && ((int)$a->answer === 0 || (!empty(trim($a->link_ppid ?? '')) && !empty(trim($a->link_drive ?? '')))))->count();
                    $statusVal = $answeredCount > 0 ? 'draft_pengisian' : 'belum_dimulai';
                } elseif ($sub->status === 1) {
                    $statusVal = !empty($sub->verified_by) ? 'draft_verif' : 'menunggu_verif';
                } elseif ($sub->status === 2) {
                    $statusVal = 'dikembalikan';
                } elseif ($sub->status === 3) {
                    $hasDraftScore = $sub->answers->whereNotNull('answer_score')->count() > 0;
                    $statusVal = $hasDraftScore ? 'draft_penilaian' : 'menunggu_eval';
                } elseif ($sub->status === 4) {
                    $statusVal = 'draft_penilaian';
                } elseif (in_array($sub->status, [5, 6, 7])) {
                    $statusVal = 'lolos';
                } elseif ($sub->status === 8) {
                    $statusVal = 'gugur';
                } else {
                    $statusVal = 'belum_dimulai';
                }
            }

            $records->push((object)[
                'satker'     => $satker,
                'submission' => $sub,
                'status_val' => $statusVal,
            ]);
        }

        $filterKotaArr = $request->input('kota', []);
        if (!empty($filterKotaArr)) {
            $filterKotaLower = array_map('strtolower', $filterKotaArr);
            $records = $records->filter(
                fn($r) => in_array(strtolower($r->satker->nama_satker ?? ''), $filterKotaLower)
            );
        }

        $filterStatusArr = $request->input('status', []);
        if (!empty($filterStatusArr)) {
            $records = $records->filter(fn($r) => in_array($r->status_val, $filterStatusArr));
        }

        if ($records->isEmpty()) {
            $msg = 'Tidak ada data Kabupaten/Kota yang cocok dengan filter.';
            if ($request->has('validate')) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return back()->with('error', $msg);
        }

        if ($request->has('validate')) {
            return response()->json(['success' => true]);
        }

        // Load all questions, sub-indicators, indicators
        $indicators = MasterIndikator::getOrderedForPeriod($period->id_periode, [
            'subIndikators.pertanyaans' => function ($query) {
                $query->orderBy('order_no');
            }
        ]);

        return SAQExportHelper::downloadBatch($period, $records, $indicators, $statusLabels);
    }
}
