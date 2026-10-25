<?php

namespace App\Http\Controllers\Penilai;

use App\Helpers\SecureId;
use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\MasterIndikator;
use App\Models\Submission;
use Illuminate\Http\Request;

/**
 * Kuesioner SAQ untuk penilai eksternal: HANYA LIHAT. Tidak ada rute simpan/ubah.
 * Skor dan catatan evaluator tidak pernah dikirim ke satelit.
 */
class SaqController extends Controller
{
    use Concerns;

    public function monitor(Request $request)
    {
        [$periods, $period] = $this->periodSelection($request);
        $q = trim((string) $request->input('q', ''));

        $rows = Submission::query()
            ->where('status', '>=', Submission::STATUS_VERIFIED)
            ->whereIn('id_submission', fn ($s) => $s->select('id_submission')->from('submission_evaluators')
                ->where('id_user', $request->user()->id_user))
            ->whereHas('result', function ($r) use ($period, $q) {
                if ($period) {
                    $r->where('id_periode', $period->id_periode);
                }
                if ($q !== '') {
                    $r->whereHas('satker', fn ($s) => $s->where('nama_satker', 'like', '%'.$q.'%'));
                }
            })
            ->with('result.satker')
            ->orderBy('id_submission')
            ->get();

        return view('pages.saq.monitor', compact('rows', 'periods', 'period', 'q'));
    }

    public function review(Request $request, string $submission)
    {
        $id = (int) SecureId::decrypt($submission);

        $sub = Submission::query()->with('result.satker', 'result.period')->findOrFail($id);

        // Hanya yang ditugaskan, dan hanya submission yang sudah lolos verifikasi.
        $assigned = $sub->evaluators()->where('id_user', $request->user()->id_user)->exists();
        abort_unless($assigned && $sub->status >= Submission::STATUS_VERIFIED, 403);

        $indicators = MasterIndikator::query()->with('subIndikators.pertanyaans')->orderBy('id_indicators')->get();
        $answers = Answer::query()->where('id_submission', $sub->id_submission)->get()->keyBy('id_question');

        return view('pages.saq.review', compact('sub', 'indicators', 'answers'));
    }
}
