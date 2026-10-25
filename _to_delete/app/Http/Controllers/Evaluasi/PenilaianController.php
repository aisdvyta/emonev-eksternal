<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\KabagKomentar;
use App\Models\MasterSatker;
use App\Models\Period;
use App\Models\Submission;
use App\Services\PenilaianService;
use App\Helpers\SecureId;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class PenilaianController extends Controller
{
    protected PenilaianService $penilaianService;

    public function __construct(PenilaianService $penilaianService)
    {
        $this->penilaianService = $penilaianService;
    }

    private function getRedirectRouteName(): string
    {
        $role = Auth::user()?->role?->nama_role;
        if (Route::has('evaluasi.penilaian.index')) {
            return 'evaluasi.penilaian.index';
        }
        if ($role === 'super_evaluator') {
            return 'super-evaluator.penilaian';
        }
        if ($role === 'penilai_eksternal') {
            return 'penilai-eksternal.penilaian';
        }
        if ($role === 'verifikator') {
            return 'verifikator.penilaian';
        }
        if ($role === 'operator') {
            return 'operator.penilaian';
        }
        return 'evaluator.penilaian';
    }

    /**
     * Entry point for all roles to view Penilaian Akhir.
     */
    public function index(Request $request, $id_periode = null)
    {
        $user = Auth::user();
        $userRole = $user?->role?->nama_role;

        // 1. Satker View (Operator & Verifikator)
        if (in_array($userRole, ['operator', 'verifikator'])) {
            $data = $this->penilaianService->getSatkerPenilaianData($user->kode_satker, $id_periode, $request);

            if (!$data['period']) {
                return back()->with('error', 'Tidak ada periode penilaian aktif.');
            }

            return view('pages.evaluasi.penilaian.detail', $data);
        }

        // 2. Penilai Eksternal View (View-only, only finished periods)
        if ($userRole === 'penilai_eksternal') {
            $allPeriods = Period::orderByDesc('periode_start')->get();
            $endedPeriods = $allPeriods->filter(function ($p) {
                return $p->result_end && now()->toDateString() > $p->result_end;
            })->values();

            $selectedPeriodId = $request->input('id_periode') ?? $id_periode;

            if ($selectedPeriodId) {
                if (!is_numeric($selectedPeriodId)) {
                    $selectedPeriodId = SecureId::decrypt($selectedPeriodId);
                }
                $requestedPeriod = $allPeriods->firstWhere('id_periode', (int) $selectedPeriodId);
                $isEnded = $requestedPeriod && $requestedPeriod->result_end && now()->toDateString() > $requestedPeriod->result_end;

                if (!$isEnded) {
                    return redirect()
                        ->route($this->getRedirectRouteName())
                        ->with('error', 'Periode yang sedang berjalan belum memiliki penilaian akhir yang final.');
                }
                $period = $requestedPeriod;
            } else {
                $period = $endedPeriods->first();
            }

            return view('pages.evaluasi.penilaian.monitoring', compact('period', 'endedPeriods'));
        }

        // 3. Evaluator, Super Evaluator, Admin View (Province monitoring)
        $selectedPeriodId = $request->input('id_periode') ?? $id_periode;
        if ($selectedPeriodId && !is_numeric($selectedPeriodId)) {
            $selectedPeriodId = SecureId::decrypt($selectedPeriodId);
        }

        $period = Period::resolvePeriod($selectedPeriodId);

        if (!$period) {
            return back()->with('error', 'Tidak ada periode aktif.');
        }

        return view('pages.evaluasi.penilaian.monitoring', compact('period'));
    }

    /**
     * Alias for legacy role routes: operator.penilaian, verifikator.penilaian, evaluator.penilaian, super-evaluator.penilaian.
     */
    public function penilaian(Request $request, $id_periode = null)
    {
        return $this->index($request, $id_periode);
    }

    /**
     * Export penilaian data to XLS (Excel-compatible HTML table).
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $userRole = $user?->role?->nama_role;

        // Operator & Verifikator hanya boleh mengunduh hasil penilaian satkernya sendiri setelah pengumuman resmi (result_end)
        if (in_array($userRole, ['operator', 'verifikator'])) {
            $periodId = $request->input('id_periode');
            $p = Period::resolvePeriod($periodId);
            if (!$p || !$p->result_end || now()->toDateString() <= $p->result_end) {
                $msg = 'Hasil penilaian akhir periode ini belum resmi diterbitkan.';
                if ($request->has('validate')) {
                    return response()->json(['success' => false, 'message' => $msg]);
                }
                return back()->with('error', $msg);
            }
            $satkerName = $user->masterSatker?->nama_satker;
            if ($satkerName) {
                $request->merge(['kota' => [$satkerName]]);
            }
        }

        if ($userRole === 'penilai_eksternal') {
            $periodId = $request->input('id_periode');
            $p = Period::resolvePeriod($periodId);
            if (!$p || !$p->result_end || now()->toDateString() <= $p->result_end) {
                $msg = 'Periode yang sedang berjalan belum memiliki penilaian akhir yang final.';
                if ($request->has('validate')) {
                    return response()->json(['success' => false, 'message' => $msg]);
                }
                return back()->with('error', $msg);
            }
        }

        return $this->penilaianService->exportPenilaianExcel($request);
    }

    /**
     * AJAX: Get donut chart distribution data for a specific period.
     */
    public function donutData(Request $request)
    {
        $userRole = Auth::user()?->role?->nama_role;
        $periodId = $request->input('id_periode');

        // Penilai eksternal check
        if ($userRole === 'penilai_eksternal' && $periodId) {
            $p = Period::find($periodId);
            if ($p && (!$p->result_end || now()->toDateString() <= $p->result_end)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Periode masih berjalan.',
                    'series'  => [0, 0, 0, 0, 0, 0],
                ]);
            }
        }

        $period = Period::find($periodId);
        if (!$period) {
            return response()->json(['error' => 'Periode tidak ditemukan.'], 404);
        }

        $satkers = MasterSatker::where('kode_satker', '!=', '3500')->get();
        $submissionsInPeriod = Submission::with(['visitasi', 'interview', 'result'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $period->id_periode))
            ->get()
            ->keyBy(function ($sub) {
                return $sub->result ? $sub->result->kode_satker : '';
            });

        $allRecords = collect();
        foreach ($satkers as $satker) {
            $sub = $submissionsInPeriod->get($satker->kode_satker);
            if (!$sub) {
                $dummy = Submission::makeDummy($satker, $period);
                $allRecords->push($dummy);
            } else {
                $sub->calculated_final = $this->penilaianService->calculateFinalScoreFromSubmission($sub);
                if ($sub->status == 8) {
                    $sub->predikat = 'TIDAK INFORMATIF';
                } elseif ($sub->status < 6) {
                    $sub->predikat = 'BELUM DINILAI';
                } else {
                    $sub->predikat = $this->penilaianService->getPredikat($sub->calculated_final);
                }
                $allRecords->push($sub);
            }
        }

        $totalCount    = $allRecords->count();
        $countInf      = $allRecords->filter(fn($s) => $s->predikat === 'INFORMATIF' && in_array($s->status, [6, 7]))->count();
        $countMenuju   = $allRecords->filter(fn($s) => $s->predikat === 'MENUJU INFORMATIF' && in_array($s->status, [6, 7]))->count();
        $countCukup    = $allRecords->filter(fn($s) => $s->predikat === 'CUKUP INFORMATIF' && in_array($s->status, [6, 7]))->count();
        $countKurang   = $allRecords->filter(fn($s) => $s->predikat === 'KURANG INFORMATIF' && in_array($s->status, [6, 7]))->count();
        $countTidak    = $allRecords->filter(fn($s) => ($s->predikat === 'TIDAK INFORMATIF' && in_array($s->status, [6, 7])) || $s->status == 8)->count();
        $countBelum    = $allRecords->filter(fn($s) => $s->status < 6)->count();

        $pct = fn($c) => $totalCount > 0 ? round(($c / $totalCount) * 100, 1) : 0;

        return response()->json([
            'tahun' => Carbon::parse($period->periode_start)->year,
            'total' => $totalCount,
            'items' => [
                ['color' => '#388E3C', 'label' => 'INFORMATIF',       'count' => $countInf,    'pct' => $pct($countInf)],
                ['color' => '#68B92E', 'label' => 'MENUJU INFORMATIF', 'count' => $countMenuju, 'pct' => $pct($countMenuju)],
                ['color' => '#4C84B0', 'label' => 'CUKUP INFORMATIF',  'count' => $countCukup,  'pct' => $pct($countCukup)],
                ['color' => '#F7B750', 'label' => 'KURANG INFORMATIF', 'count' => $countKurang, 'pct' => $pct($countKurang)],
                ['color' => '#E82D1F', 'label' => 'TIDAK INFORMATIF',  'count' => $countTidak,  'pct' => $pct($countTidak)],
                ['color' => '#94a3b8', 'label' => 'BELUM DINILAI',     'count' => $countBelum,  'pct' => $pct($countBelum)],
            ],
        ]);
    }

    /**
     * Publish results & save recommendations by Super Evaluator / Admin.
     */
    public function publish(Request $request, $id)
    {
        $id = is_numeric($id) ? $id : SecureId::decrypt($id);

        return DB::transaction(function () use ($id) {
            $submission = Submission::with(['result.satker'])->lockForUpdate()->findOrFail($id);
            Gate::authorize('update', $submission);

            abort_unless(in_array($submission->status, [5, 6]), 409, 'Hanya submission yang telah menyelesaikan tahap evaluasi/wawancara (status Lolos / Wawancara Selesai) yang dapat dipublikasikan.');

            $result = $submission->result;
            if ($result) {
                $finalScore = $this->penilaianService->calculateFinalScore(
                    $result->ques_score ?? 0,
                    $result->visit_score ?? 0,
                    $result->interview_score ?? 0
                );
                $result->final_score = $finalScore;
                $result->index_label = $this->penilaianService->getPredikat($finalScore);
                $result->published_at = now();
                $result->save();
            }

            // Change status to 7 (Completed / Published)
            $submission->update(['status' => 7]);

            return redirect()->route($this->getRedirectRouteName())->with('success', 'Hasil penilaian akhir BPS ' . ($submission->result?->satker?->nama_satker ?? '') . ' berhasil dipublikasikan.');
        });
    }

    /**
     * Simpan / update komentar catatan untuk satu periode (Super Evaluator).
     */
    public function saveKomentar(Request $request, $id)
    {
        $request->validate([
            'komentar' => 'required|string|min:5|max:5000',
        ]);

        $period = Period::findOrFail(SecureId::decrypt($id));

        KabagKomentar::create([
            'id_periode' => $period->id_periode,
            'id_user'    => Auth::id(),
            'komentar'   => $request->input('komentar'),
            'created_at' => now(),
        ]);

        return redirect()
            ->route($this->getRedirectRouteName(), ['id_periode' => $period->id_periode])
            ->with('success', 'Catatan berhasil disimpan ke riwayat.');
        }
}
