<?php
namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MasterIndikator;
use App\Models\MasterPembobotan;
use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PembobotanController extends Controller
{
    protected function getRedirectRoute(Request $request): string
    {
        $currentRoute = $request->route()?->getName() ?? '';
        if (str_contains($currentRoute, 'super-evaluator')) {
            return 'super-evaluator.manage-pembobotan';
        }
        return 'master.pembobotan.index';
    }

    public function index(Request $request) { return $this->managePembobotan($request); }
    public function save(Request $request) { return $this->storePembobotan($request); }

    /**
     * Tampilkan halaman pengelolaan Master Pembobotan.
     */
    public function managePembobotan(Request $request)
    {
        if (\Illuminate\Support\Facades\Auth::user()?->role?->nama_role === 'evaluator') {
            abort(403, 'Akses ditolak. Master Pembobotan hanya dapat diakses oleh Super Evaluator.');
        }

        $selectedPeriodId = $request->input('id_periode');
        $period = Period::resolvePeriod($selectedPeriodId);

        $periods = Cache::remember('periods_desc', 3600, function() {
            return Period::orderByDesc('periode_start')->get();
        });

        if (!$period) {
            return back()->with('error', 'Tidak ada periode aktif.');
        }

        $pembobotan = MasterPembobotan::firstOrCreate(
            ['id_periode' => $period->id_periode],
            [
                'weight_saq'       => 30.00,
                'weight_visitasi'  => 40.00,
                'weight_wawancara' => 30.00,
                'threshold_saq'    => 80.00,
            ]
        );

        $indicators = MasterIndikator::get();

        $savedIndWeights = $pembobotan->bobot_indikator ?? [];
        if (empty($savedIndWeights) && $indicators->isNotEmpty()) {
            $count = $indicators->count();
            $defaultWeight = round(100.0 / $count, 2);
            $accum = 0;
            $savedIndWeights = [];
            foreach ($indicators as $idx => $ind) {
                if ($idx === $count - 1) {
                    $savedIndWeights[$ind->id_indicators] = round(100.0 - $accum, 2);
                } else {
                    $savedIndWeights[$ind->id_indicators] = $defaultWeight;
                    $accum += $defaultWeight;
                }
            }
        }

        $stageOrder = $pembobotan->resolvedStageOrder();
        $indicatorOrder = $pembobotan->resolvedIndicatorOrder();

        $today = now()->toDateString();
        $isPastPeriod = $period && $period->periode_end && ($today > $period->periode_end);
        $isPeriodActive = $period && $period->periode_start && ($today >= $period->periode_start && (!$period->periode_end || $today <= $period->periode_end));
        $hasActivePeriod = $isPastPeriod || $isPeriodActive;
        $canReorderStages = !$isPeriodActive && !$isPastPeriod;
        $activePeriod = $period;

        return view('pages.master.pembobotan', compact(
            'period',
            'periods',
            'pembobotan',
            'indicators',
            'savedIndWeights',
            'stageOrder',
            'indicatorOrder',
            'activePeriod',
            'hasActivePeriod',
            'isPastPeriod',
            'isPeriodActive',
            'canReorderStages'
        ));
    }

    /**
     * Simpan perubahan pembobotan tahapan akhir, KKM SAQ, bobot indikator, dan urutan indikator.
     */
    public function storePembobotan(Request $request)
    {
        if (\Illuminate\Support\Facades\Auth::user()?->role?->nama_role === 'evaluator') {
            abort(403, 'Akses ditolak. Master Pembobotan hanya dapat diakses oleh Super Evaluator.');
        }

        $periodId = $request->input('id_periode');
        $targetPeriod = Period::find($periodId);

        if ($targetPeriod && $targetPeriod->periode_end && ($targetPeriod->periode_end < now()->toDateString())) {
            return back()->with('error', 'Master pembobotan tidak dapat diubah karena periode evaluasi telah berakhir.');
        }

        $request->validate([
            'id_periode'        => 'required|exists:periods,id_periode',
            'weight_saq'        => 'required|numeric|min:0|max:100',
            'weight_visitasi'   => 'required|numeric|min:0|max:100',
            'weight_wawancara'  => 'required|numeric|min:0|max:100',
            'threshold_saq'     => 'required|numeric|min:0|max:100',
            'indicator_weights' => 'required|array',
            'indicator_weights.*' => 'required|numeric|min:0|max:100',
            'stage_order'       => 'required|array|size:3',
            'stage_order.*'     => 'required|string|in:saq,visitasi,wawancara',
            'indicator_order'   => 'nullable|array',
            'indicator_order.*' => 'integer',
        ], [
            'weight_saq.required'       => 'Bobot SAQ wajib diisi.',
            'weight_visitasi.required'  => 'Bobot Visitasi wajib diisi.',
            'weight_wawancara.required' => 'Bobot Wawancara wajib diisi.',
            'threshold_saq.required'    => 'KKM SAQ per Indikator wajib diisi.',
            'indicator_weights.required'=> 'Bobot Indikator wajib diisi.',
            'stage_order.required'      => 'Urutan tahapan wajib diisi.',
            'stage_order.size'          => 'Urutan tahapan harus berisi tepat 3 tahapan.',
        ]);

                $stageOrderInput = $request->input('stage_order', []);
        if (count(array_unique($stageOrderInput)) !== 3) {
            return back()->withInput()->with('error', 'Setiap tahapan harus unik â€” tidak boleh ada tahapan yang sama dalam urutan.');
        }

        $today = now()->toDateString();
        $isPeriodActive = $targetPeriod && $targetPeriod->periode_start && ($today >= $targetPeriod->periode_start && (!$targetPeriod->periode_end || $today <= $targetPeriod->periode_end));
        $existingPembobotan = MasterPembobotan::where('id_periode', $periodId)->first();
        $existingStageOrder = $existingPembobotan ? $existingPembobotan->resolvedStageOrder() : MasterPembobotan::getDefaultStageOrder();

        if ($isPeriodActive && !empty($stageOrderInput)) {
            if (array_values($stageOrderInput) !== array_values($existingStageOrder)) {
                return back()->withInput()->with('error', 'Urutan tahapan kegiatan tidak dapat dipindahkan karena periode evaluasi sedang aktif. Anda hanya dapat mengubah besar nilai pembobotan.');
            }
        }

        $wSaq   = floatval($request->input('weight_saq'));
        $wVis   = floatval($request->input('weight_visitasi'));
        $wWaw   = floatval($request->input('weight_wawancara'));
        $threshold = floatval($request->input('threshold_saq'));

        $totalStage = round($wSaq + $wVis + $wWaw, 2);
        if (abs($totalStage - 100.0) > 0.01) {
            return back()->withInput()->with('error', 'Total Bobot Tahapan Evaluasi Akhir (SAQ + Visitasi + Wawancara) harus tepat 100%. Total saat ini: ' . $totalStage . '%');
        }

        $indWeights = $request->input('indicator_weights', []);
        $totalInd = round(array_sum(array_map('floatval', $indWeights)), 2);
        if (abs($totalInd - 100.0) > 0.01) {
            return back()->withInput()->with('error', 'Total Bobot Indikator SAQ harus tepat 100%. Total saat ini: ' . $totalInd . '%');
        }

        $indicatorOrderInput = $request->input('indicator_order', array_keys($indWeights));
        $formattedIndWeights = [];
        foreach ($indicatorOrderInput as $indId) {
            if (isset($indWeights[$indId])) {
                $formattedIndWeights[(string) $indId] = floatval($indWeights[$indId]);
            }
        }
        foreach ($indWeights as $indId => $val) {
            if (!isset($formattedIndWeights[(string) $indId])) {
                $formattedIndWeights[(string) $indId] = floatval($val);
            }
        }

        $newStageOrder = array_values($stageOrderInput);

        MasterPembobotan::updateOrCreate(
            ['id_periode' => $periodId],
            [
                'weight_saq'       => $wSaq,
                'weight_visitasi'  => $wVis,
                'weight_wawancara' => $wWaw,
                'threshold_saq'    => $threshold,
                'bobot_indikator'  => $formattedIndWeights,
                'stage_order'      => $newStageOrder,
            ]
        );

        if ($targetPeriod) {
            $targetPeriod->realignStageDates($newStageOrder);
        }

        Cache::forget('periods_desc');
        Cache::forget('saq_category_weights_from_db');
        Cache::forget('saq_category_weights_' . $periodId);
        Cache::forget('pembobotan_config_' . $periodId);
        Cache::forget('pembobotan_stage_order_' . $periodId);
        Cache::forget('pembobotan_indicator_order_' . $periodId);
        Cache::forget('indicators_tree');
        Cache::forget('dynamic_max_scores_by_ind');
        Cache::forget('op_sidebar_sub_' . $periodId);

        $msg = 'Pengaturan Master Pembobotan & KKM berhasil disimpan.';

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route($this->getRedirectRoute($request), ['id_periode' => $periodId])
            ->with('success', $msg);
    }
}
