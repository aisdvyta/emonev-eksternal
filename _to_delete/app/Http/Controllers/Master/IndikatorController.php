<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MasterIndikator;
use App\Models\MasterPembobotan;
use App\Models\Answer;
use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IndikatorController extends Controller
{
    protected function getRedirectRoute(Request $request): string
    {
        $currentRoute = $request->route()?->getName() ?? '';
        if (str_contains($currentRoute, 'super-evaluator')) {
            return 'super-evaluator.manage-indikator';
        }
        if (str_contains($currentRoute, 'evaluator')) {
            return 'evaluator.manage-indikator';
        }
        return 'master.indikator.index';
    }

    public function index(Request $request) { return $this->manageIndikator($request); }
    public function store(Request $request) { return $this->storeIndikator($request); }
    public function update(Request $request, $id) { return $this->updateIndikator($request, $id); }
    public function destroy($id) { return $this->deleteIndikator($id); }

    /**
     * Display master indicator list.
     */
    public function manageIndikator(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 20, 50])) {
            $perPage = 10;
        }

        $hasActivePeriod = Period::hasActivePeriod();
        $activePeriod    = Period::getActivePeriod();

        $order = MasterPembobotan::getActiveIndicatorOrder($activePeriod?->id_periode);
        $query = MasterIndikator::withCount(['subIndikators', 'pertanyaan']);
        if (!empty($order)) {
            $orderStr = implode(',', array_map('intval', $order));
            $query->orderByRaw("FIELD(id_indicators, {$orderStr})");
        }

        $indicators = $query->paginate($perPage)->withQueryString();

        return view('pages.master.indikator', compact('indicators', 'hasActivePeriod', 'activePeriod'));
    }

    /**
     * Store a new indicator.
     */
    public function storeIndikator(Request $request)
    {
        if (Period::hasActivePeriod()) {
            return redirect()
                ->route($this->getRedirectRoute($request))
                ->with('error', 'Master data Indikator tidak dapat ditambah karena terdapat periode KIP yang sedang aktif.');
        }

        $request->validate([
            'nama_indikator'      => 'required|string|max:255',
            'deskripsi_indikator' => 'nullable|string|max:5000',
        ]);

        MasterIndikator::create([
            'nama_indikator'      => Str::title(trim($request->input('nama_indikator'))),
            'deskripsi_indikator' => $request->input('deskripsi_indikator'),
        ]);

        Cache::forget('saq_category_weights_from_db');
        Cache::forget('indicators_tree');
        Cache::forget('master_indicators_all');
        Cache::forget('dynamic_max_scores_by_ind');

        return redirect()
            ->route($this->getRedirectRoute($request))
            ->with('success', 'Indikator berhasil ditambahkan.');
    }

    /**
     * Update an indicator.
     */
    public function updateIndikator(Request $request, $id)
    {
        if (Period::hasActivePeriod()) {
            return redirect()
                ->route($this->getRedirectRoute($request))
                ->with('error', 'Master data Indikator tidak dapat diubah karena terdapat periode KIP yang sedang aktif.');
        }

        $request->validate([
            'nama_indikator'      => 'required|string|max:255',
            'deskripsi_indikator' => 'nullable|string|max:5000',
        ]);

        $indicator = MasterIndikator::findOrFail($id);
        $indicator->update([
            'nama_indikator'      => Str::title(trim($request->input('nama_indikator'))),
            'deskripsi_indikator' => $request->input('deskripsi_indikator'),
        ]);

        Cache::forget('saq_category_weights_from_db');
        Cache::forget('indicators_tree');
        Cache::forget('master_indicators_all');
        Cache::forget('dynamic_max_scores_by_ind');

        return redirect()
            ->route($this->getRedirectRoute($request))
            ->with('success', 'Indikator berhasil diperbarui.');
    }

    /**
     * Delete an indicator.
     */
    public function deleteIndikator(Request $request, $id)
    {
        if (Period::hasActivePeriod()) {
            return redirect()
                ->route($this->getRedirectRoute($request))
                ->with('error', 'Master data Indikator tidak dapat dihapus karena terdapat periode KIP yang sedang aktif.');
        }

        $indicator = MasterIndikator::findOrFail($id);

        $hasAnswers = Answer::where('id_indicators', $id)->exists();
        if ($hasAnswers) {
            return redirect()
                ->route($this->getRedirectRoute($request))
                ->with('error', 'Indikator tidak dapat dihapus karena sudah memiliki data pengisian SAQ.');
        }

        $indicator->delete();

        Cache::forget('saq_category_weights_from_db');
        Cache::forget('indicators_tree');
        Cache::forget('master_indicators_all');
        Cache::forget('dynamic_max_scores_by_ind');

        return redirect()
            ->route($this->getRedirectRoute($request))
            ->with('success', 'Indikator berhasil dihapus.');
    }
}
