<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MasterIndikator;
use App\Models\MasterSubIndikator;
use App\Models\MasterPertanyaan;
use App\Models\Answer;
use App\Models\Period;
use App\Helpers\SecureId;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubIndikatorController extends Controller
{
    protected function getRedirectRoute(Request $request): string
    {
        $currentRoute = $request->route()?->getName() ?? '';
        if (str_contains($currentRoute, 'super-evaluator')) {
            return 'super-evaluator.manage-sub-indikator';
        }
        if (str_contains($currentRoute, 'evaluator')) {
            return 'evaluator.manage-sub-indikator';
        }
        return 'master.sub-indikator.index';
    }

    public function index(Request $request) { return $this->manageSubIndikator($request); }
    public function store(Request $request) { return $this->storeSubIndikator($request); }
    public function update(Request $request, $id) { return $this->updateSubIndikator($request, $id); }
    public function destroy($id) { return $this->deleteSubIndikator($id); }

    /**
     * Display master sub-indicator list tabbed by indicator.
     */
    public function manageSubIndikator(Request $request)
    {
        $hasActivePeriod = Period::hasActivePeriod();
        $activePeriod    = Period::getActivePeriod();

        $indicators = MasterIndikator::getOrderedForPeriod($activePeriod?->id_periode)->loadCount('subIndikators');

        $tabInput = $request->input('tab') ?? $request->input('id_indicators');
        if ($tabInput) {
            if (is_numeric($tabInput)) {
                $activeTab = (int) $tabInput;
            } else {
                $activeTab = (int) SecureId::decrypt($tabInput);
            }
        } else {
            $activeTab = (int) ($indicators->first()?->id_indicators ?? 1);
        }

        $activeInd = MasterIndikator::find($activeTab);
        if (!$activeInd) {
            $activeInd = $indicators->first();
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 20, 50])) {
            $perPage = 10;
        }

        $subIndicators = MasterSubIndikator::with('indikator')
            ->withCount('pertanyaan')
            ->where('id_indicators', $activeTab)
            ->orderBy('id_sub_indicators', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('pages.master.sub-indikator', compact(
            'indicators', 'activeTab', 'activeInd', 'subIndicators', 'hasActivePeriod', 'activePeriod'
        ));
    }

    /**
     * Store a new sub-indicator.
     */
    public function storeSubIndikator(Request $request)
    {
        if (Period::hasActivePeriod()) {
            return redirect()
                ->route($this->getRedirectRoute($request))
                ->with('error', 'Master data Sub-Indikator tidak dapat ditambah karena terdapat periode KIP yang sedang aktif.');
        }

        $request->validate([
            'id_indicators'      => 'required|exists:master_indikator,id_indicators',
            'nama_sub_indikator' => 'required|string|max:255',
        ]);

        $sub = MasterSubIndikator::create([
            'id_indicators'      => $request->input('id_indicators'),
            'nama_sub_indikator' => Str::title(trim($request->input('nama_sub_indikator'))),
        ]);

        return redirect()
            ->route($this->getRedirectRoute($request), ['tab' => SecureId::encrypt($sub->id_indicators)])
            ->with('success', 'Sub-Indikator berhasil ditambahkan.');
    }

    /**
     * Update a sub-indicator.
     */
    public function updateSubIndikator(Request $request, $id)
    {
        if (Period::hasActivePeriod()) {
            return redirect()
                ->route($this->getRedirectRoute($request))
                ->with('error', 'Master data Sub-Indikator tidak dapat diubah karena terdapat periode KIP yang sedang aktif.');
        }

        $request->validate([
            'id_indicators'      => 'required|exists:master_indikator,id_indicators',
            'nama_sub_indikator' => 'required|string|max:255',
        ]);

        $subIndicator = MasterSubIndikator::findOrFail($id);
        $subIndicator->update([
            'id_indicators'      => $request->input('id_indicators'),
            'nama_sub_indikator' => Str::title(trim($request->input('nama_sub_indikator'))),
        ]);

        return redirect()
            ->route($this->getRedirectRoute($request), ['tab' => SecureId::encrypt($subIndicator->id_indicators)])
            ->with('success', 'Sub-Indikator berhasil diperbarui.');
    }

    /**
     * Delete a sub-indicator.
     */
    public function deleteSubIndikator(Request $request, $id)
    {
        if (Period::hasActivePeriod()) {
            return redirect()
                ->route($this->getRedirectRoute($request))
                ->with('error', 'Master data Sub-Indikator tidak dapat dihapus karena terdapat periode KIP yang sedang aktif.');
        }

        $subIndicator = MasterSubIndikator::findOrFail($id);
        $idInd = $subIndicator->id_indicators;

        $questionIds = MasterPertanyaan::where('id_sub_indicators', $id)->pluck('id_master_pertanyaan');
        $hasAnswers = Answer::whereIn('id_question', $questionIds)->exists();

        if ($hasAnswers) {
            return redirect()
                ->route($this->getRedirectRoute($request), ['tab' => SecureId::encrypt($idInd)])
                ->with('error', 'Sub-Indikator tidak dapat dihapus karena pertanyaannya sudah memiliki data pengisian SAQ.');
        }

        $subIndicator->delete();

        return redirect()
            ->route($this->getRedirectRoute($request), ['tab' => SecureId::encrypt($idInd)])
            ->with('success', 'Sub-Indikator berhasil dihapus.');
    }
}
