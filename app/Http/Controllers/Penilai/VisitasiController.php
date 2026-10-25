<?php

namespace App\Http\Controllers\Penilai;

use App\Helpers\SecureId;
use App\Http\Controllers\Controller;
use App\Models\Visitasi;
use App\Models\VisitasiEvaluator;
use App\Services\EvaluationRules;
use App\Services\ScoreRecorder;
use Illuminate\Http\Request;

class VisitasiController extends Controller
{
    use Concerns;

    public function monitor(Request $request)
    {
        [$periods, $period] = $this->periodSelection($request);
        $q = trim((string) $request->input('q', ''));

        $rows = VisitasiEvaluator::query()
            ->where('id_user', $request->user()->id_user)
            ->whereHas('visitasi.result', function ($r) use ($period, $q) {
                if ($period) {
                    $r->where('id_periode', $period->id_periode);
                }
                if ($q !== '') {
                    $r->whereHas('satker', fn ($s) => $s->where('nama_satker', 'like', '%'.$q.'%'));
                }
            })
            ->with('visitasi.result.satker')
            ->get()
            ->sortBy(fn ($r) => $r->visitasi?->visit_at?->timestamp ?? PHP_INT_MAX)
            ->values();

        return view('pages.visitasi.monitor', compact('rows', 'periods', 'period', 'q'));
    }

    public function review(Request $request, EvaluationRules $rules, string $id)
    {
        [$visitasi, $mine] = $this->resolve($request, $id);

        return view('pages.visitasi.review', [
            'visitasi' => $visitasi,
            'mine' => $mine,
            'lock' => $rules->visitasiLockReason($visitasi),
        ]);
    }

    public function save(Request $request, EvaluationRules $rules, ScoreRecorder $recorder, string $id)
    {
        [$visitasi, $mine] = $this->resolve($request, $id);

        if ($reason = $rules->visitasiLockReason($visitasi)) {
            return back()->with('error', $reason);
        }

        $data = $request->validate([
            'nilai_visitasi' => 'required|numeric|min:0|max:100',
            'catatan_visitasi' => 'required|string|max:5000',
            'action' => 'required|in:draft,submit',
        ], [
            'nilai_visitasi.required' => 'Nilai visitasi wajib diisi.',
            'nilai_visitasi.numeric' => 'Nilai visitasi harus berupa angka.',
            'nilai_visitasi.min' => 'Nilai visitasi minimal 0.',
            'nilai_visitasi.max' => 'Nilai visitasi maksimal 100.',
            'catatan_visitasi.required' => 'Catatan visitasi wajib diisi.',
            'catatan_visitasi.max' => 'Catatan visitasi maksimal 5000 karakter.',
        ]);

        $isDraft = $data['action'] === 'draft';
        $recorder->saveVisitasi(
            $visitasi->id_visit,
            (int) $request->user()->id_user,
            round((float) $data['nilai_visitasi'], 2),
            $data['catatan_visitasi'],
            $isDraft,
        );

        return redirect()->route('penilai-eksternal.monitor-visitasi')->with(
            'success',
            $isDraft
                ? 'Draft penilaian visitasi disimpan.'
                : 'Penilaian visitasi dikirim. Nilai akan diteruskan ke sistem pusat pada sinkronisasi berikutnya.'
        );
    }

    /** @return array{0: Visitasi, 1: VisitasiEvaluator} */
    private function resolve(Request $request, string $id): array
    {
        $visitId = (string) SecureId::decrypt($id);
        $visitasi = Visitasi::query()->with('result.satker', 'result.period', 'result.submission')->findOrFail($visitId);

        $mine = VisitasiEvaluator::query()
            ->where('id_visit', $visitasi->id_visit)
            ->where('id_user', $request->user()->id_user)
            ->first();

        abort_unless($mine, 403, 'Anda tidak ditugaskan untuk visitasi ini.');

        return [$visitasi, $mine];
    }
}
