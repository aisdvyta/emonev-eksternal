<?php

namespace App\Http\Controllers\Penilai;

use App\Helpers\SecureId;
use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\InterviewEvaluator;
use App\Services\EvaluationRules;
use App\Services\ScoreRecorder;
use Illuminate\Http\Request;

class WawancaraController extends Controller
{
    use Concerns;

    public function monitor(Request $request)
    {
        [$periods, $period] = $this->periodSelection($request);
        $q = trim((string) $request->input('q', ''));

        $rows = InterviewEvaluator::query()
            ->where('id_user', $request->user()->id_user)
            ->whereHas('interview.result', function ($r) use ($period, $q) {
                if ($period) {
                    $r->where('id_periode', $period->id_periode);
                }
                if ($q !== '') {
                    $r->whereHas('satker', fn ($s) => $s->where('nama_satker', 'like', '%'.$q.'%'));
                }
            })
            ->with('interview.result.satker')
            ->get()
            ->sortBy(fn ($r) => $r->interview?->interview_at?->timestamp ?? PHP_INT_MAX)
            ->values();

        return view('pages.wawancara.monitor', compact('rows', 'periods', 'period', 'q'));
    }

    public function review(Request $request, EvaluationRules $rules, string $id)
    {
        [$interview, $mine] = $this->resolve($request, $id);

        return view('pages.wawancara.review', [
            'interview' => $interview,
            'mine' => $mine,
            'lock' => $rules->interviewLockReason($interview),
        ]);
    }

    public function save(Request $request, EvaluationRules $rules, ScoreRecorder $recorder, string $id)
    {
        [$interview, $mine] = $this->resolve($request, $id);

        if ($reason = $rules->interviewLockReason($interview)) {
            return back()->with('error', $reason);
        }

        $isDraft = $request->input('action') === 'draft';

        $data = $request->validate([
            'action' => 'required|in:draft,submit',
            'score' => $isDraft ? 'nullable|numeric|min:0|max:100' : 'required|numeric|min:0|max:100',
            'note' => ($isDraft ? 'nullable' : 'required').'|string|max:5000',
        ], [
            'score.required' => 'Nilai wawancara wajib diisi sebelum mengirim penilaian.',
            'score.numeric' => 'Nilai wawancara harus berupa angka.',
            'score.min' => 'Nilai wawancara minimal 0.',
            'score.max' => 'Nilai wawancara maksimal 100.',
            'note.required' => 'Catatan wawancara wajib diisi sebelum mengirim penilaian.',
            'note.max' => 'Catatan wawancara maksimal 5000 karakter.',
        ]);

        $score = isset($data['score']) && $data['score'] !== '' ? round((float) $data['score'], 2) : null;

        $recorder->saveInterview(
            (int) $interview->id_interview,
            (int) $request->user()->id_user,
            $score,
            $data['note'] ?? null,
            $isDraft || $score === null,
        );

        return redirect()->route('penilai-eksternal.monitor-wawancara')->with(
            'success',
            $isDraft
                ? 'Draft penilaian wawancara disimpan.'
                : 'Penilaian wawancara dikirim. Nilai akan diteruskan ke sistem pusat pada sinkronisasi berikutnya.'
        );
    }

    /** @return array{0: Interview, 1: InterviewEvaluator} */
    private function resolve(Request $request, string $id): array
    {
        $interviewId = (int) SecureId::decrypt($id);
        $interview = Interview::query()->with('result.satker', 'result.period', 'result.submission')->findOrFail($interviewId);

        $mine = InterviewEvaluator::query()
            ->where('id_interview', $interview->id_interview)
            ->where('id_user', $request->user()->id_user)
            ->first();

        abort_unless($mine, 403, 'Anda tidak ditugaskan untuk wawancara ini.');

        return [$interview, $mine];
    }
}
