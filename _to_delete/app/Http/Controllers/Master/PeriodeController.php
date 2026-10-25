<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Models\MasterPembobotan;
use App\Helpers\SecureId;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PeriodeController extends Controller
{
    protected function getRoutePrefix(): string
    {
        $currentRoute = request()->route()?->getName() ?? '';
        if (str_contains($currentRoute, 'super-evaluator')) {
            return 'super-evaluator';
        }
        return 'master.periode';
    }

    /**
     * Show periods management page.
     */
    public function managePeriode()
    {
        $periods = Period::orderBy('periode_start', 'desc')->get();

        $today = now()->toDateString();
        $totalPeriode   = $periods->count();
        $periodeAktif   = $periods->filter(fn($p) => $p->periode_start <= $today && $p->periode_end >= $today)->count();
        $periodeSelesai = $periods->filter(fn($p) => $p->periode_end < $today)->count();

        return view('pages.master.periode', compact('periods', 'totalPeriode', 'periodeAktif', 'periodeSelesai'));
    }

    public function index() { return $this->managePeriode(); }
    public function store(Request $request) { return $this->storePeriode($request); }
    public function update(Request $request, $id) { return $this->updatePeriode($request, $id); }
    public function destroy($id) { return $this->deletePeriode($id); }

    /**
     * Resolve ordered 5 stage keys from a 3-item stage_order.
     */
    private function resolveStageKeys(array $stageOrder): array
    {
        $keys = [];
        foreach ($stageOrder as $stg) {
            if ($stg === 'saq') {
                $keys[] = 'saq';
                $keys[] = 'eval';
            } elseif ($stg === 'visitasi') {
                $keys[] = 'visit';
            } elseif ($stg === 'wawancara') {
                $keys[] = 'interview';
            }
        }
        $keys[] = 'result';
        return $keys;
    }

    /**
     * Calculate default stage dates sequentially based on global start & end dates and stage_order.
     */
    private function calculateDefaultStages($startStr, $endStr, ?int $periodId = null)
    {
        if (!$startStr || !$endStr) {
            return [];
        }

        $start    = Carbon::parse($startStr);
        $end      = Carbon::parse($endStr);
        $diffDays = max(1, $start->diffInDays($end));

        $stageOrder = MasterPembobotan::getActiveStageOrder($periodId);
        $orderedKeys = $this->resolveStageKeys($stageOrder);
        $totalStages = count($orderedKeys);
        $daysPerStage = max(1, (int) floor($diffDays / $totalStages));

        $result = [];
        $currentDate = $start->copy();

        foreach ($orderedKeys as $idx => $stageKey) {
            $isLast = ($idx === $totalStages - 1);
            $sStart = $currentDate->copy();
            $sEnd   = $isLast ? $end->copy() : $sStart->copy()->addDays($daysPerStage - 1);

            $result[$stageKey . '_start'] = $sStart->toDateString();
            $result[$stageKey . '_end']   = $sEnd->toDateString();

            $currentDate = $sEnd->copy()->addDay();
        }

        return $result;
    }

    /**
     * Validate that stage dates follow sequential chronological order and do not overlap.
     */
    private function validateStageOverlaps(Request $request, array $stages, array $stageOrder = ['saq', 'visitasi', 'wawancara'], ?string $globalStart = null, ?string $globalEnd = null)
    {
        $orderedKeys = $this->resolveStageKeys($stageOrder);

        // 1. Validasi urutan kronologis antar-tahapan sesuai urutan aktif
        for ($i = 0; $i < count($orderedKeys); $i++) {
            $key = $orderedKeys[$i];
            if (!isset($stages[$key])) continue;

            $s = $stages[$key]['start'];
            $e = $stages[$key]['end'];
            $name = $stages[$key]['name'];

            if ($s && $e && $e < $s) {
                $msg = sprintf('Tanggal selesai untuk tahapan "%s" tidak boleh lebih awal dari tanggal mulai.', $name);
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
            }

            if ($i === 0 && $globalStart && $s && $s < $globalStart) {
                $msg = sprintf(
                    'Tanggal mulai tahapan ke-1 ("%s") tidak boleh mendahului tanggal mulai periode (%s).',
                    $name,
                    Carbon::parse($globalStart)->translatedFormat('d M Y')
                );
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
            }

            if ($i > 0) {
                $prevKey = $orderedKeys[$i - 1];
                $prevS   = $stages[$prevKey]['start'] ?? null;
                $prevE   = $stages[$prevKey]['end'] ?? null;
                $prevName = $stages[$prevKey]['name'] ?? $prevKey;

                if ($s && $prevE && $s <= $prevE) {
                    $msg = sprintf(
                        'Tahapan ke-%d ("%s") tidak boleh dimulai sebelum atau bersamaan dengan Tahapan ke-%d ("%s") selesai (harus setelah %s).',
                        $i + 1,
                        $name,
                        $i,
                        $prevName,
                        Carbon::parse($prevE)->translatedFormat('d M Y')
                    );
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withInput()->with('error', $msg);
                }

                if ($s && $prevS && $s <= $prevS) {
                    $msg = sprintf(
                        'Tahapan ke-%d ("%s") tidak boleh dimulai sebelum Tahapan ke-%d ("%s").',
                        $i + 1,
                        $name,
                        $i,
                        $prevName
                    );
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withInput()->with('error', $msg);
                }
            }

            if ($i === count($orderedKeys) - 1 && $globalEnd && $e && $e > $globalEnd) {
                $msg = sprintf(
                    'Tanggal selesai tahapan terakhir ("%s") tidak boleh melebihi tanggal selesai periode (%s).',
                    $name,
                    Carbon::parse($globalEnd)->translatedFormat('d M Y')
                );
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
            }
        }

        // 2. Pemeriksaan pairwise overlap
        $stageKeys = array_keys($stages);
        for ($i = 0; $i < count($stageKeys); $i++) {
            for ($j = $i + 1; $j < count($stageKeys); $j++) {
                $key1 = $stageKeys[$i];
                $key2 = $stageKeys[$j];
                $s1 = $stages[$key1]['start'];
                $e1 = $stages[$key1]['end'];
                $s2 = $stages[$key2]['start'];
                $e2 = $stages[$key2]['end'];

                if ($s1 && $e1 && $s2 && $e2 && $s1 <= $e2 && $s2 <= $e1) {
                    $msg = sprintf(
                        'Gagal memproses periode: Tahapan "%s" bertabrakan dengan tahapan "%s" (%s — %s vs %s — %s).',
                        $stages[$key1]['name'],
                        $stages[$key2]['name'],
                        Carbon::parse($s1)->translatedFormat('d M Y'),
                        Carbon::parse($e1)->translatedFormat('d M Y'),
                        Carbon::parse($s2)->translatedFormat('d M Y'),
                        Carbon::parse($e2)->translatedFormat('d M Y'),
                    );

                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withInput()->with('error', $msg);
                }
            }
        }
        return null;
    }

    /**
     * Store a new period.
     */
    public function storePeriode(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end'   => 'required|date|after_or_equal:start',
        ]);

        $start = $request->input('start');
        $end   = $request->input('end');

        // Auto-sinkronisasi start dan end agar melingkupi seluruh tahapan jika tahapan diperluas
        $stagesList = ['saq', 'eval', 'visit', 'interview', 'result'];
        $allStageStarts = array_filter(array_map(fn($k) => $request->input($k . '_start'), $stagesList));
        $allStageEnds   = array_filter(array_map(fn($k) => $request->input($k . '_end'), $stagesList));

        if (!empty($allStageStarts)) {
            $minStageStart = min($allStageStarts);
            if (!$start || $minStageStart < $start) {
                $start = $minStageStart;
                $request->merge(['start' => $start]);
            }
        }
        if (!empty($allStageEnds)) {
            $maxStageEnd = max($allStageEnds);
            if (!$end || $maxStageEnd > $end) {
                $end = $maxStageEnd;
                $request->merge(['end' => $end]);
            }
        }

        $overlap = Period::where(fn($q) => $q->where('periode_start', '<=', $end)->where('periode_end', '>=', $start))->first();
        if ($overlap) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'start' => 'Jadwal periode ini bertabrakan dengan ' . $overlap->nama . ' (' . Carbon::parse($overlap->periode_start)->translatedFormat('d M Y') . ' — ' . Carbon::parse($overlap->periode_end)->translatedFormat('d M Y') . ').'
            ]);
        }

        if (!$request->filled('saq_start')) {
            $request->merge($this->calculateDefaultStages($start, $end));
        }

        $request->validate([
            'saq_start'       => 'required|date|after_or_equal:start|before_or_equal:end',
            'saq_end'         => 'required|date|after_or_equal:saq_start|before_or_equal:end',
            'eval_start'      => 'required|date|after_or_equal:start|before_or_equal:end',
            'eval_end'        => 'required|date|after_or_equal:eval_start|before_or_equal:end',
            'visit_start'     => 'required|date|after_or_equal:start|before_or_equal:end',
            'visit_end'       => 'required|date|after_or_equal:visit_start|before_or_equal:end',
            'interview_start' => 'required|date|after_or_equal:start|before_or_equal:end',
            'interview_end'   => 'required|date|after_or_equal:interview_start|before_or_equal:end',
            'result_start'    => 'required|date|after_or_equal:start|before_or_equal:end',
            'result_end'      => 'required|date|after_or_equal:result_start|before_or_equal:end',
        ]);

        $stages = [
            'saq'       => ['name' => 'Pengisian SAQ',           'start' => $request->input('saq_start'),       'end' => $request->input('saq_end')],
            'eval'      => ['name' => 'Evaluasi Data',           'start' => $request->input('eval_start'),      'end' => $request->input('eval_end')],
            'visit'     => ['name' => 'Visitasi & Verif. Faktual', 'start' => $request->input('visit_start'),   'end' => $request->input('visit_end')],
            'interview' => ['name' => 'Wawancara',               'start' => $request->input('interview_start'), 'end' => $request->input('interview_end')],
            'result'    => ['name' => 'Penilaian Akhir',         'start' => $request->input('result_start'),    'end' => $request->input('result_end')],
        ];

        $prevPeriod = Period::orderBy('periode_start', 'desc')->first();
        $prevPem = $prevPeriod ? MasterPembobotan::where('id_periode', $prevPeriod->id_periode)->first() : null;
        $stageOrder = $prevPem ? $prevPem->resolvedStageOrder() : ['saq', 'visitasi', 'wawancara'];

        if ($err = $this->validateStageOverlaps($request, $stages, $stageOrder, $start, $end)) {
            return $err;
        }

        $newPeriod = Period::create([
            'periode_start'    => $request->input('start'),
            'periode_end'      => $request->input('end'),
            'saq_start'        => $request->input('saq_start'),
            'saq_end'          => $request->input('saq_end'),
            'eval_start'       => $request->input('eval_start'),
            'eval_end'         => $request->input('eval_end'),
            'visit_start'      => $request->input('visit_start'),
            'visit_end'        => $request->input('visit_end'),
            'interview_start'  => $request->input('interview_start'),
            'interview_end'    => $request->input('interview_end'),
            'result_start'     => $request->input('result_start'),
            'result_end'       => $request->input('result_end'),
        ]);

        // Auto-copy pembobotan dari periode sebelumnya
        $prevPeriod = Period::where('id_periode', '!=', $newPeriod->id_periode)
            ->orderBy('periode_start', 'desc')
            ->first();

        $prevPem = $prevPeriod ? MasterPembobotan::where('id_periode', $prevPeriod->id_periode)->first() : null;

        MasterPembobotan::create([
            'id_periode'       => $newPeriod->id_periode,
            'weight_saq'       => $prevPem ? $prevPem->weight_saq : 30.00,
            'weight_visitasi'  => $prevPem ? $prevPem->weight_visitasi : 40.00,
            'weight_wawancara' => $prevPem ? $prevPem->weight_wawancara : 30.00,
            'threshold_saq'    => $prevPem ? $prevPem->threshold_saq : 80.00,
            'bobot_indikator'  => $prevPem ? $prevPem->bobot_indikator : null,
            'stage_order'      => $prevPem ? $prevPem->resolvedStageOrder() : ['saq', 'visitasi', 'wawancara'],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Periode berhasil ditambahkan.']);
        }

        $redirectRoute = str_contains($request->route()?->getName() ?? '', 'super-evaluator') ? 'super-evaluator.manage-jadwal' : 'master.periode.index';
        return redirect()->route($redirectRoute)->with('success', 'Periode berhasil ditambahkan.');
    }

    /**
     * Update an existing period.
     */
    public function updatePeriode(Request $request, $id)
    {
        $request->validate([
            'start' => 'required|date',
            'end'   => 'required|date|after_or_equal:start',
        ]);

        $start = $request->input('start');
        $end   = $request->input('end');

        // Auto-sinkronisasi start dan end agar melingkupi seluruh tahapan jika tahapan diperluas
        $stagesList = ['saq', 'eval', 'visit', 'interview', 'result'];
        $allStageStarts = array_filter(array_map(fn($k) => $request->input($k . '_start'), $stagesList));
        $allStageEnds   = array_filter(array_map(fn($k) => $request->input($k . '_end'), $stagesList));

        if (!empty($allStageStarts)) {
            $minStageStart = min($allStageStarts);
            if (!$start || $minStageStart < $start) {
                $start = $minStageStart;
                $request->merge(['start' => $start]);
            }
        }
        if (!empty($allStageEnds)) {
            $maxStageEnd = max($allStageEnds);
            if (!$end || $maxStageEnd > $end) {
                $end = $maxStageEnd;
                $request->merge(['end' => $end]);
            }
        }

        $overlap = Period::where(fn($q) => $q->where('periode_start', '<=', $end)->where('periode_end', '>=', $start))
            ->where('id_periode', '!=', $id)
            ->first();
        if ($overlap) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'start' => 'Jadwal periode ini bertabrakan dengan ' . $overlap->nama . ' (' . Carbon::parse($overlap->periode_start)->translatedFormat('d M Y') . ' — ' . Carbon::parse($overlap->periode_end)->translatedFormat('d M Y') . ').'
            ]);
        }

        $period = Period::findOrFail($id);

        if (!$request->filled('saq_start')) {
            $request->merge($this->calculateDefaultStages($start, $end));
        }

        $request->validate([
            'saq_start'       => 'required|date|after_or_equal:start|before_or_equal:end',
            'saq_end'         => 'required|date|after_or_equal:saq_start|before_or_equal:end',
            'eval_start'      => 'required|date|after_or_equal:start|before_or_equal:end',
            'eval_end'        => 'required|date|after_or_equal:eval_start|before_or_equal:end',
            'visit_start'     => 'required|date|after_or_equal:start|before_or_equal:end',
            'visit_end'       => 'required|date|after_or_equal:visit_start|before_or_equal:end',
            'interview_start' => 'required|date|after_or_equal:start|before_or_equal:end',
            'interview_end'   => 'required|date|after_or_equal:interview_start|before_or_equal:end',
            'result_start'    => 'required|date|after_or_equal:start|before_or_equal:end',
            'result_end'      => 'required|date|after_or_equal:result_start|before_or_equal:end',
        ]);

        $stages = [
            'saq'       => ['name' => 'Pengisian SAQ',             'start' => $request->input('saq_start'),       'end' => $request->input('saq_end')],
            'eval'      => ['name' => 'Evaluasi Data',             'start' => $request->input('eval_start'),      'end' => $request->input('eval_end')],
            'visit'     => ['name' => 'Visitasi & Verif. Faktual', 'start' => $request->input('visit_start'),     'end' => $request->input('visit_end')],
            'interview' => ['name' => 'Wawancara',                 'start' => $request->input('interview_start'), 'end' => $request->input('interview_end')],
            'result'    => ['name' => 'Penilaian Akhir',           'start' => $request->input('result_start'),    'end' => $request->input('result_end')],
        ];

        $stageOrder = MasterPembobotan::getActiveStageOrder((int) $id);
        if ($err = $this->validateStageOverlaps($request, $stages, $stageOrder, $start, $end)) {
            return $err;
        }

        $period->update([
            'periode_start'   => $request->input('start'),
            'periode_end'     => $request->input('end'),
            'saq_start'       => $request->input('saq_start'),
            'saq_end'         => $request->input('saq_end'),
            'eval_start'      => $request->input('eval_start'),
            'eval_end'        => $request->input('eval_end'),
            'visit_start'     => $request->input('visit_start'),
            'visit_end'       => $request->input('visit_end'),
            'interview_start' => $request->input('interview_start'),
            'interview_end'   => $request->input('interview_end'),
            'result_start'    => $request->input('result_start'),
            'result_end'      => $request->input('result_end'),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Periode berhasil diperbarui.']);
        }

        $redirectRoute = str_contains($request->route()?->getName() ?? '', 'super-evaluator') ? 'super-evaluator.manage-jadwal' : 'master.periode.index';
        return redirect()->route($redirectRoute)->with('success', 'Periode berhasil diperbarui.');
    }

    /**
     * Delete a period.
     */
    public function deletePeriode($id)
    {
        Period::findOrFail($id)->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Periode berhasil dihapus.']);
        }

        $redirectRoute = str_contains(request()->route()?->getName() ?? '', 'super-evaluator') ? 'super-evaluator.manage-jadwal' : 'master.periode.index';
        return redirect()->route($redirectRoute)->with('success', 'Periode berhasil dihapus.');
    }

    /**
     * Update a specific stage's date range for a period.
     */
    public function updateStageJadwal(Request $request, $id)
    {
        $id = SecureId::decrypt($id);
        $request->validate([
            'stage_key'  => 'required|in:saq,eval,visit,interview,result',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $period   = Period::findOrFail($id);
        $key      = $request->input('stage_key');
        $newStart = $request->input('start_date');
        $newEnd   = $request->input('end_date');

        if ($period->periode_start && $newStart < $period->periode_start) {
            $msg = 'Gagal memperbarui jadwal: Tanggal mulai tidak boleh mendahului tanggal mulai periode (' . Carbon::parse($period->periode_start)->translatedFormat('d M Y') . ').';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('master.periode.index')->withInput()->with('error', $msg);
        }

        if ($period->periode_end && $newEnd > $period->periode_end) {
            $msg = 'Gagal memperbarui jadwal: Tanggal selesai tidak boleh melebihi tanggal selesai periode (' . Carbon::parse($period->periode_end)->translatedFormat('d M Y') . ').';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('master.periode.index')->withInput()->with('error', $msg);
        }

        $stages = [
            'saq'       => ['name' => 'Pengisian SAQ',             'start' => $period->saq_start,       'end' => $period->saq_end],
            'eval'      => ['name' => 'Evaluasi Data',             'start' => $period->eval_start,      'end' => $period->eval_end],
            'visit'     => ['name' => 'Visitasi & Verif. Faktual', 'start' => $period->visit_start,     'end' => $period->visit_end],
            'interview' => ['name' => 'Wawancara',                 'start' => $period->interview_start, 'end' => $period->interview_end],
            'result'    => ['name' => 'Penilaian Akhir',           'start' => $period->result_start,    'end' => $period->result_end],
        ];
        $stages[$key]['start'] = $newStart;
        $stages[$key]['end']   = $newEnd;

        $stageOrder = MasterPembobotan::getActiveStageOrder($period->id_periode);
        if ($err = $this->validateStageOverlaps($request, $stages, $stageOrder, $period->periode_start, $period->periode_end)) {
            return $err;
        }

        $period->update([
            $key . '_start' => $newStart,
            $key . '_end'   => $newEnd,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Jadwal tahapan berhasil diperbarui.']);
        }

        return redirect()->route('master.periode.index')->with('success', 'Jadwal tahapan berhasil diperbarui.');
    }

    /**
     * Show stage schedule management page for a specific period.
     */
    public function manageAturJadwal(Request $request, $id = null)
    {
        return redirect()->route('master.periode.index')->with('info', 'Halaman atur jadwal telah disatukan.');
    }
}
