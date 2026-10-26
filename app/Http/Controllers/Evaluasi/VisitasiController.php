<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\MasterPembobotan;
use App\Models\Period;
use App\Models\Submission;
use App\Models\User;
use App\Models\Visitasi;
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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class VisitasiController extends Controller
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
        if (Route::has('evaluasi.visitasi.monitoring')) {
            return 'evaluasi.visitasi.monitoring';
        }
        if ($role === 'super_evaluator') {
            return 'super-evaluator.monitor-visitasi';
        }
        if ($role === 'penilai_eksternal') {
            return 'penilai-eksternal.monitor-visitasi';
        }
        return 'evaluator.monitor-visitasi';
    }

    /**
     * Entry point for Satker (Operator/Verifikator) and fallback for monitoring.
     */
    public function visitasi(Request $request, $id_periode = null)
    {
        $user = Auth::user();
        $userRole = $user?->role?->nama_role;

        // Evaluator, Super Evaluator & Penilai Eksternal redirect to monitoring
        if (in_array($userRole, ['evaluator', 'super_evaluator', 'penilai_eksternal', 'admin'])) {
            return $this->monitorVisitasi($request);
        }

        // Decrypt id_periode from URL
        if ($id_periode) {
            if (!is_numeric($id_periode)) {
                $id_periode = SecureId::decrypt($id_periode);
            }
        }

        // If id_periode is not present, render the monitoring list
        if (!$id_periode) {
            $periods = Period::getAvailablePeriodsForUser($user);
            $submissionsCollection = Submission::with(['result', 'visitasi.evaluators.user.roles', 'interview.evaluators.user.roles'])
                ->whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker))
                ->get();
            Submission::preloadUsers($submissionsCollection);
            $submissions = $submissionsCollection->keyBy(fn($sub) => $sub->result ? $sub->result->id_periode : 0);

            return view('pages.evaluasi.visitasi.monitoring-satker', compact('periods', 'submissions'));
        }

        $periodId = $id_periode;
        $period = Period::findOrFail($periodId);

        $submission = Submission::whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker)->where('id_periode', $period->id_periode))
            ->first();

        // Operator/Verifikator who failed SAQ (status=8) can still see the visitasi page
        $isGugur = $submission && $submission->status == 8;

        if (!$submission || (!$submission->visitasi && !$isGugur)) {
            $redirectRoute = Route::has('evaluasi.visitasi.index')
                ? 'evaluasi.visitasi.index'
                : ($userRole === 'verifikator' ? 'verifikator.monitoring-visitasi' : 'operator.visitasi');
            return redirect()->route($redirectRoute)->with('error', 'Visitasi belum diinisialisasi atau Anda tidak lolos threshold.');
        }

        $visitasi = $submission->visitasi;

        return view('pages.evaluasi.visitasi.detail', compact('period', 'submission', 'visitasi', 'isGugur'));
    }

    /**
     * Alias for Verifikator monitoring route.
     */
    public function monitoringVisitasi(Request $request, $id_periode = null)
    {
        return $this->visitasi($request, $id_periode);
    }

    /**
     * Show Visitasi Monitoring page for Evaluator / Super Evaluator.
     */
    public function monitorVisitasi(Request $request)
    {
        $selectedPeriodId = $request->input('id_periode');
        $period = Period::resolvePeriod($selectedPeriodId);

        if (!$period) {
            return back()->with('error', 'Tidak ada periode aktif.');
        }

        // Fetch all Evaluators for assignment dropdown (cached)
        $evaluators = Cache::remember('evaluators_with_roles_visitasi', 3600, function() {
            return User::role(['evaluator', 'super_evaluator', 'penilai_eksternal'])
                ->with('roles')
                ->get()
                ->sortBy(fn($user) => $user->hasRole('super_evaluator') ? 0 : ($user->hasRole('penilai_eksternal') ? 2 : 1))
                ->values();
        });

        // Resolve stage order to dynamically determine prerequisites
        $visitasiRequiresWawancara = MasterPembobotan::visitasiRequiresWawancara($period->id_periode);

        // Get all visitations for Calendar & modal selection (1 DB query)
        $allVisitationsQuery = Visitasi::with(['result.satker', 'result.submission', 'evaluators.user.roles'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $period->id_periode));

        if ($visitasiRequiresWawancara) {
            $allVisitationsQuery->where(function ($q) {
                $q->whereHas('result.interview', function ($iq) {
                    $iq->whereNotNull('interview_score');
                })->orWhereNotNull('visit_at')
                  ->orWhereNotNull('visit_score')
                  ->orWhereHas('evaluators');
            });
        }

        $allVisitations = $allVisitationsQuery->get();

        // Derive scheduled visitations for Calendar in memory
        $visitations = $allVisitations->filter(fn($v) => $v->visit_at !== null)->values();

        // Preload evaluator users from all visitasi
        Visitasi::preloadEvaluatorUsers($allVisitations);

        // Preload operator users from submissions
        $submissionsFromVisitasi = $allVisitations->map(fn($v) => $v->result?->submission)->filter();
        Submission::preloadUsers($submissionsFromVisitasi);

        return view('pages.evaluasi.visitasi.monitoring', compact(
            'period',
            'evaluators',
            'visitations',
            'allVisitations'
        ));
    }

    /**
     * Save link bukti dukung visitasi oleh Operator.
     */
    public function saveLinkBuktiVisitasi(Request $request)
    {
        $request->validate([
            'link_buktidukung' => 'nullable|url|max:500',
        ]);

        $user = Auth::user();

        $periodId = $request->input('id_periode');
        if ($periodId) {
            if (!is_numeric($periodId)) {
                $periodId = SecureId::decrypt($periodId);
            }
            $period = Period::find($periodId);
        }

        if (empty($period)) {
            $period = Period::getActivePeriod();
        }

        $submission = Submission::whereHas('result', fn($q) => $q->where('kode_satker', $user->kode_satker)->where('id_periode', $period->id_periode))
            ->firstOrFail();

        $visitasi = $submission->visitasi;
        if (!$visitasi) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Data visitasi tidak ditemukan.'], 404);
            }

            return back()->with('error', 'Data visitasi tidak ditemukan.');
        }

        $isPast = false;
        if ($visitasi->visit_mode === 'online_upload' || !$visitasi->visit_at) {
            $uploadDeadline = $period->visit_end ? Carbon::parse($period->visit_end)->subDays(3)->toDateString() : null;
            if ($uploadDeadline && now()->toDateString() > $uploadDeadline) {
                $isPast = true;
            }
        } else {
            if ($visitasi->visit_at && now()->toDateString() > $visitasi->visit_at) {
                $isPast = true;
            }
        }

        if ($isPast) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tautan bukti dukung tidak dapat diubah karena telah melewati batas H-3 sebelum periode visitasi berakhir.',
                ], 403);
            }

            return back()->with('error', 'Tautan bukti dukung tidak dapat diubah karena telah melewati batas H-3 sebelum periode visitasi berakhir.');
        }

        $visitasi->update([
            'link_buktidukung' => $request->input('link_buktidukung'),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Link bukti dukung visitasi berhasil disimpan.',
            ]);
        }

        return back()->with('success', 'Link bukti dukung visitasi berhasil disimpan.');
    }

    /**
     * Save/Update Schedule for a single Visitasi record.
     */
    public function saveSchedule(Request $request, $id)
    {
        $visitasi = Visitasi::findOrFail($id);
        $period = $visitasi->period;
        $visitMode = $request->input('visit_mode', 'offline');

        $rules = [
            'visit_mode'    => 'required|in:offline,online_meeting,online_upload',
            'id_visitor'    => 'required|array',
            'id_visitor.*'  => 'exists:users,id_user',
        ];

        if (in_array($visitMode, ['offline', 'online_meeting'])) {
            $rules['visit_at'] = [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($period) {
                    if ($period && $period->visit_start && $value < $period->visit_start) {
                        $fail('Tanggal visitasi tidak boleh kurang dari tanggal mulai tahapan visitasi (' . Carbon::parse($period->visit_start)->translatedFormat('d M Y') . ').');
                    }
                    if ($period && $period->visit_end && $value > $period->visit_end) {
                        $fail('Tanggal visitasi tidak boleh lebih dari tanggal selesai tahapan visitasi (' . Carbon::parse($period->visit_end)->translatedFormat('d M Y') . ').');
                    }
                }
            ];
        }

        if ($visitMode === 'online_meeting') {
            $rules['link_meeting'] = 'required|url';
        }

        $request->validate($rules);

        $selectedEvaluators = $request->input('id_visitor');

        // Sync visit_data JSON array
        $currentData = $visitasi->visit_data ?? [];
        $newData = [];
        foreach ($selectedEvaluators as $userId) {
            $existing = collect($currentData)->firstWhere('id_user', $userId);
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

        $visitAt = in_array($visitMode, ['offline', 'online_meeting'])
            ? $request->input('visit_at')
            : null;

        $meetLink = $visitMode === 'online_meeting' ? $request->input('link_meet_visitasi', $request->input('link_meeting')) : null;

        DB::transaction(function () use ($visitasi, $visitMode, $meetLink, $visitAt, $newData) {
            $visitasi->update([
                'visit_mode'          => $visitMode,
                'link_meet_visitasi'  => $meetLink,
                'link_meeting'        => $meetLink,
                'visit_at'            => $visitAt,
            ]);

            // Sync ke tabel visitasi_evaluators
            $allAssignedUserIds = collect($newData)->pluck('id_user')->filter()->map(fn($id) => (int)$id)->all();
            \App\Models\VisitasiEvaluator::where('id_visit', $visitasi->id_visit)
                ->whereNotIn('id_user', $allAssignedUserIds)
                ->delete();

            foreach ($newData as $item) {
                $uId = $item['id_user'] ?? null;
                if ($uId && User::where('id_user', (int)$uId)->exists()) {
                    \App\Models\VisitasiEvaluator::updateOrCreate(
                        [
                            'id_visit' => $visitasi->id_visit,
                            'id_user'  => (int)$uId,
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
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jadwal visitasi berhasil disimpan.',
            ]);
        }

        return back()->with('success', 'Jadwal visitasi berhasil disimpan.');
    }

    /**
     * Save/Update Schedule for multiple Visitasi records in batch.
     */
    public function saveScheduleBatch(Request $request)
    {
        $visitIds = $request->input('visit_ids');
        $visitMode = $request->input('visit_mode', 'offline');

        $firstVisit = ($visitIds && is_array($visitIds)) ? Visitasi::find($visitIds[0]) : null;
        if ($firstVisit) {
            $period = $firstVisit->period;
        } else {
            $period = Period::getActivePeriod();
        }

        $rules = [
            'visit_ids'    => 'required|array',
            'visit_ids.*'  => 'exists:visitasi,id_visit',
            'visit_mode'   => 'required|in:offline,online_meeting,online_upload',
            'id_visitor'   => 'required|array',
            'id_visitor.*' => 'exists:users,id_user',
        ];

        if (in_array($visitMode, ['offline', 'online_meeting'])) {
            $rules['visit_at'] = [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($period) {
                    if ($period && $period->visit_start && $value < $period->visit_start) {
                        $fail('Tanggal visitasi tidak boleh kurang dari tanggal mulai tahapan visitasi (' . Carbon::parse($period->visit_start)->translatedFormat('d M Y') . ').');
                    }
                    if ($period && $period->visit_end && $value > $period->visit_end) {
                        $fail('Tanggal visitasi tidak boleh lebih dari tanggal selesai tahapan visitasi (' . Carbon::parse($period->visit_end)->translatedFormat('d M Y') . ').');
                    }
                }
            ];
        }

        if ($visitMode === 'online_meeting') {
            $rules['link_meeting'] = 'required|url';
        }

        $request->validate($rules);

        $visitIds = $request->input('visit_ids');
        $visitAt = in_array($visitMode, ['offline', 'online_meeting'])
            ? $request->input('visit_at')
            : null;
        $linkMeeting = $visitMode === 'online_meeting' ? $request->input('link_meeting') : null;
        $selectedEvaluators = (array) $request->input('id_visitor');
        $validUserIds = User::whereIn('id_user', array_map('intval', $selectedEvaluators))->pluck('id_user')->flip()->toArray();

        $visitasis = Visitasi::whereIn('id_visit', $visitIds)->get();

        DB::transaction(function () use ($visitasis, $selectedEvaluators, $linkMeeting, $visitMode, $visitAt, $validUserIds) {
            foreach ($visitasis as $visitasi) {
                $currentData = $visitasi->visit_data ?? [];
                $newData = [];
                foreach ($selectedEvaluators as $userId) {
                    $existing = collect($currentData)->firstWhere('id_user', $userId);
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

                $meetLink = $linkMeeting;
                $visitasi->update([
                    'visit_mode'          => $visitMode,
                    'link_meet_visitasi'  => $meetLink,
                    'link_meeting'        => $meetLink,
                    'visit_at'            => $visitAt,
                ]);

                // Sync ke tabel visitasi_evaluators
                $allAssignedUserIds = collect($newData)->pluck('id_user')->filter()->map(fn($id) => (int)$id)->all();
                \App\Models\VisitasiEvaluator::where('id_visit', $visitasi->id_visit)
                    ->whereNotIn('id_user', $allAssignedUserIds)
                    ->delete();

                foreach ($newData as $item) {
                    $uId = $item['id_user'] ?? null;
                    if ($uId && isset($validUserIds[(int)$uId])) {
                        \App\Models\VisitasiEvaluator::updateOrCreate(
                            [
                                'id_visit' => $visitasi->id_visit,
                                'id_user'  => (int)$uId,
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
        });

        $msg = 'Jadwal visitasi berhasil disimpan untuk ' . count($visitIds) . ' Kab/Kota.';
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
     * Show Visitasi review page for a specific visitasi.
     */
    public function reviewVisitasi($id)
    {
        $id = SecureId::decrypt($id);
        $visitasi = Visitasi::with([
            'result.satker',
            'result.period',
            'result.submission',
            'evaluators.user.roles',
        ])->findOrFail($id);

        $currentUser = Auth::user();
        $data = $visitasi->visit_data ?? [];
        $currentEvaluatorData = collect($data)->first(fn($item) => (string)($item['id_user'] ?? '') === (string)$currentUser->id_user);

        if (!$currentEvaluatorData && Auth::user()?->role?->nama_role !== 'super_evaluator') {
            return redirect()->route($this->getMonitoringRouteName())->with('error', 'Anda tidak ditugaskan sebagai verifikator/evaluator untuk visitasi ini.');
        }

        $visitasiEvaluator = (object) [
            'score' => $currentEvaluatorData['score'] ?? null,
            'note' => $currentEvaluatorData['note'] ?? '',
            'is_draft' => $currentEvaluatorData['is_draft'] ?? true,
        ];

        return view('pages.evaluasi.visitasi.review', compact('visitasi', 'visitasiEvaluator'));
    }

    /**
     * Save/Submit Visitasi review.
     * Aturan simpan/finalisasi ada di EvaluationService (dipakai juga oleh sinkronisasi satelit).
     */
    public function saveReview(Request $request, $id)
    {
        $id = is_numeric($id) ? $id : SecureId::decrypt($id);

        $request->validate([
            'nilai_visitasi'   => 'required|numeric|min:0|max:100',
            'catatan_visitasi' => 'required|string|max:5000',
        ]);

        $action = $request->input('action');

        $result = $this->evaluationService->saveVisitasiScore(
            (string) $id,
            (int) Auth::user()->id_user,
            floatval($request->input('nilai_visitasi')),
            $request->input('catatan_visitasi'),
            $action === 'draft',
            $action === 'submit',
        );

        if (!$result->ok) {
            return back()->with('error', $result->message);
        }

        return redirect()->route($this->getMonitoringRouteName())->with('success', $result->message);
    }

    /**
     * Export rekap visitasi ke Excel.
     * Evaluator/Super Evaluator: multi-satker XLSX spreadsheet.
     * Operator/Verifikator: single-satker XLS table export.
     */
    public function exportVisitasi(Request $request)
    {
        $user = Auth::user();
        $userRole = $user?->role?->nama_role;

        if ($userRole === 'penilai_eksternal') {
            return back()->with('error', 'Penilai Eksternal hanya dapat mengunduh rekap hasil akhir pada halaman Penilaian Akhir setelah periode selesai.');
        }

        // Operator & Verifikator export
        if (in_array($userRole, ['operator', 'verifikator'])) {
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
                ->with(['result.satker', 'visitasi'])
                ->first();

            if (!$submission || $submission->status < 5) {
                return back()->with('error', 'Data Visitasi Lapangan untuk tahun ' . $period->tahun . ' belum tersedia atau belum lolos kualifikasi.');
            }

            $visitasi = $submission->visitasi;
            $visitEnd = $period->visit_end ? Carbon::parse($period->visit_end)->endOfDay() : null;
            $isVisitReleased = !$visitEnd || now()->greaterThan($visitEnd);

            $satkerName = $user->masterSatker->nama_satker ?? $submission->user->masterSatker->nama_satker ?? 'BPS Kab/Kota';
            $periodYear = $period->tahun ?? date('Y');
            $filename = 'rekap_visitasi_' . str_replace(' ', '_', strtolower($satkerName)) . '_' . $periodYear . '_' . now()->format('Ymd_His') . '.xls';

            $visitModeMap = [
                'onsite' => 'Kunjungan Lapangan (Onsite)',
                'offline' => 'Kunjungan Lapangan (Onsite)',
                'online_meeting' => 'Sesi Wawancara Online (Meeting)',
                'online_upload' => 'Upload Bukti Dukung Online'
            ];
            $modeText = $visitasi ? ($visitModeMap[$visitasi->visit_mode] ?? $visitasi->visit_mode ?? '-') : '-';
            $visitDate = ($visitasi && $visitasi->visit_at) ? Carbon::parse($visitasi->visit_at)->translatedFormat('d F Y, H:i') . ' WIB' : 'Belum Dijadwalkan';
            $visitScore = ($visitasi && $visitasi->visit_score !== null && $isVisitReleased) ? number_format($visitasi->visit_score, 2) : '-';
            $visitNote = ($visitasi && $isVisitReleased) ? ($visitasi->visit_note ?: '-') : '-';

            $html = '<table border="1" style="font-family:Arial;font-size:11pt;border-collapse:collapse;">';
            $html .= '<tr><td colspan="5" style="font-weight:bold;font-size:14pt;text-align:center;background-color:#184D77;color:#ffffff;padding:10px;">Rekap Visitasi & Verifikasi Faktual - KIP</td></tr>';
            $html .= '<tr><td style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Unit Kerja / Satker:</td><td colspan="4" style="border:1px solid #9e9e9e;">' . htmlspecialchars($satkerName) . '</td></tr>';
            $html .= '<tr><td style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Tahun Periode:</td><td colspan="4" style="border:1px solid #9e9e9e;">' . $periodYear . '</td></tr>';
            $html .= '<tr><td style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Mode Visitasi:</td><td colspan="4" style="border:1px solid #9e9e9e;">' . htmlspecialchars($modeText) . '</td></tr>';
            $html .= '<tr><td style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Jadwal Visitasi:</td><td colspan="4" style="border:1px solid #9e9e9e;">' . htmlspecialchars($visitDate) . '</td></tr>';
            $html .= '<tr><td style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Nilai Visitasi:</td><td colspan="4" style="border:1px solid #9e9e9e;font-weight:bold;">' . $visitScore . '</td></tr>';
            $html .= '<tr><td style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Catatan Evaluator:</td><td colspan="4" style="border:1px solid #9e9e9e;">' . htmlspecialchars($visitNote) . '</td></tr>';
            $html .= '<tr><td colspan="5" style="padding:4px;border:none;"></td></tr>';

            $cols = ['No', 'Tautan Bukti Dukung', 'Status Bukti Dukung', 'Nilai Visitasi', 'Catatan'];
            $html .= '<tr>';
            foreach ($cols as $col) {
                $html .= '<td style="font-weight:bold;background-color:#E8F0FE;text-align:center;padding:6px;border:1px solid #9e9e9e;">' . $col . '</td>';
            }
            $html .= '</tr>';

            $linkBukti = $visitasi ? $visitasi->link_buktidukung : '-';
            $html .= '<tr>';
            $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">1</td>';
            $html .= '<td style="padding:5px;border:1px solid #e0e0e0;word-break:break-all;">' . ($linkBukti && $linkBukti !== '-' ? '<a href="' . htmlspecialchars($linkBukti) . '">' . htmlspecialchars($linkBukti) . '</a>' : '-') . '</td>';
            $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . ($linkBukti && $linkBukti !== '-' ? 'Sudah Diunggah' : 'Belum Ada') . '</td>';
            $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;font-weight:bold;">' . $visitScore . '</td>';
            $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($visitNote) . '</td>';
            $html .= '</tr>';
            $html .= '</table>';

            return ExcelExportHelper::download($filename, 'Rekap Visitasi', $html);
        }

        // Evaluator / Super Evaluator export
        $selectedPeriodId = $request->input('id_periode');
        $period = Period::resolvePeriod($selectedPeriodId);

        if (!$period) {
            return back()->with('error', 'Tidak ada periode aktif.');
        }

        $visitasis = Visitasi::with(['result.satker', 'result.submission'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $period->id_periode))
            ->get()
            ->sortBy(fn($v) => $v->result?->satker?->kode_satker ?? '');

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
        $visitasis = $visitasis->filter(
            fn($v) => in_array(strtolower($v->result?->satker?->nama_satker ?? ''), $filterKotaLower)
        );

        if ($visitasis->isEmpty()) {
            $msg = 'Tidak ada data Kabupaten/Kota yang cocok dengan filter.';
            if ($request->has('validate')) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return back()->with('error', $msg);
        }

        $gradedCount = $visitasis->filter(fn($v) => $v->visit_score !== null)->count();
        if ($gradedCount === 0) {
            $msg = 'Kabupaten/Kota yang dipilih belum selesai dinilai pada tahap visitasi.';
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
        $sheet->setTitle('Rekap Visitasi');

        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'Rekap Monitoring Visitasi - KIP');
        $sheet->getStyle('A1')->applyFromArray($styleTitle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $infoRows = [
            ['Periode', $period->nama ?? (string) $period->tahun],
            ['Tahun',   (string) ($period->tahun ?? date('Y'))],
        ];
        $r = 2;
        foreach ($infoRows as [$label, $value]) {
            $sheet->mergeCells('A' . $r . ':B' . $r);
            $sheet->mergeCells('C' . $r . ':J' . $r);
            $sheet->setCellValue('A' . $r, $label);
            $sheet->setCellValueExplicit('C' . $r, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->getStyle('A' . $r . ':B' . $r)->applyFromArray($styleInfoLabel);
            $sheet->getStyle('C' . $r . ':J' . $r)->applyFromArray($styleInfoValue);
            $r++;
        }
        $r++; // spacer

        $cols = [
            'A' => 'No', 'B' => 'Kode Satker', 'C' => 'Nama Satker',
            'D' => 'Petugas Visitasi', 'E' => 'Tanggal Visitasi',
            'F' => 'Mode Visitasi', 'G' => 'Status',
            'H' => 'Nilai Visitasi', 'I' => 'Catatan Evaluator', 'J' => 'Link Bukti',
        ];
        foreach ($cols as $col => $label) {
            $sheet->setCellValue($col . $r, $label);
            $sheet->getStyle($col . $r)->applyFromArray($styleColHeader);
        }
        $sheet->getRowDimension($r)->setRowHeight(30);
        $r++;

        $no = 1;
        foreach ($visitasis as $v) {
            $satker       = $v->result?->satker;
            $petugasNames = $v->evaluators->pluck('user.name')->filter()->implode(', ') ?: '-';
            $tanggal      = $v->visit_at ? Carbon::parse($v->visit_at)->translatedFormat('d M Y') : '-';
            $modeMap      = ['offline' => 'Offline', 'online_meeting' => 'Online Meeting', 'online_upload' => 'Online Upload'];
            $mode         = $modeMap[$v->visit_mode ?? ''] ?? ($v->visit_mode ?? '-');
            $status       = $v->visit_score !== null ? 'Sudah Dinilai' : ($v->visit_at ? 'Dijadwalkan' : 'Belum Dijadwalkan');
            $nilai        = $v->visit_score !== null ? number_format($v->visit_score, 2) : '-';

            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValueExplicit('B' . $r, $satker?->kode_satker ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $r, $satker?->nama_satker ?? '-');
            $sheet->setCellValue('D' . $r, $petugasNames);
            $sheet->setCellValue('E' . $r, $tanggal);
            $sheet->setCellValue('F' . $r, $mode);
            $sheet->setCellValue('G' . $r, $status);
            $sheet->setCellValue('H' . $r, $nilai);
            $sheet->setCellValue('I' . $r, $v->visit_note ?: '-');
            $sheet->setCellValue('J' . $r, $v->link_bukti ?: '-');

            $sheet->getStyle('A' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('B' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('C' . $r)->applyFromArray($styleCellLeft);
            $sheet->getStyle('D' . $r)->applyFromArray($styleCellLeft);
            $sheet->getStyle('E' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('F' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('G' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('H' . $r)->applyFromArray($styleCellCenter);
            $sheet->getStyle('I' . $r)->applyFromArray($styleCellLeft);
            $sheet->getStyle('J' . $r)->applyFromArray($styleCellLeft);
            $sheet->getRowDimension($r)->setRowHeight(-1);
            $r++;
        }

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(30);
        $sheet->getColumnDimension('J')->setWidth(30);

        $filename = 'rekap_visitasi_' . ($period->tahun ?? date('Y')) . '_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
