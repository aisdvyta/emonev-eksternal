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

class KuesionerController extends Controller
{
    protected function getRedirectRoute(Request $request): string
    {
        $currentRoute = $request->route()?->getName() ?? '';
        if (str_contains($currentRoute, 'super-evaluator')) {
            return 'super-evaluator.manage-kuesioner';
        }
        if (str_contains($currentRoute, 'evaluator')) {
            return 'evaluator.manage-kuesioner';
        }
        return 'master.kuesioner.index';
    }

    public function index(Request $request) { return $this->manageKuesioner($request); }
    public function store(Request $request) { return $this->storeKuesioner($request); }
    public function update(Request $request, $id) { return $this->updateKuesioner($request, $id); }
    public function destroy($id) { return $this->deleteKuesioner($id); }
    public function import(Request $request) { return $this->importKuesioner($request); }

    /**
     * Show master questionnaire management page.
     */
    public function manageKuesioner(Request $request)
    {
        $hasActivePeriod = Period::hasActivePeriod();
        $activePeriod    = Period::getActivePeriod();

        // Eager-load all indicators, sub-indicators, and questions in 3 optimized queries total
        $indicators = MasterIndikator::getOrderedForPeriod($activePeriod?->id_periode, [
            'subIndikators.pertanyaans' => function ($query) {
                $query->orderBy('order_no');
            }
        ]);

        $tabInput = $request->input('tab');
        if ($tabInput) {
            if (is_numeric($tabInput)) {
                $activeTab = (int) $tabInput;
            } else {
                $activeTab = (int) SecureId::decrypt($tabInput);
            }
        } else {
            $activeTab = (int) ($indicators->first()?->id_indicators ?? 1);
        }

        $activeInd = $indicators->firstWhere('id_indicators', $activeTab) ?? $indicators->first();

        $questionsData        = [];
        $subIndicatorsMapping = [];
        $indicatorsData       = [];

        foreach ($indicators as $ind) {
            $subIndicatorsMapping[$ind->nama_indikator] = $ind->subIndikators->pluck('nama_sub_indikator')->toArray();
            $totalIndSoal = 0;

            foreach ($ind->subIndikators as $sub) {
                $totalIndSoal += $sub->pertanyaans->count();
                foreach ($sub->pertanyaans as $q) {
                    $questionsData[$q->id_master_pertanyaan] = [
                        'id'         => $q->id_master_pertanyaan,
                        'indicator'  => $ind->nama_indikator,
                        'sub'        => $sub->nama_sub_indikator,
                        'weight'     => $q->bobot_pertanyaan,
                        'question'   => $q->ques_text,
                        'deskripsi'  => $q->ques_description ?? '',
                        'dokumen'    => $q->doc_requirement ?? '',
                        'link_drive' => $q->link_drive ?? '',
                        'link_ppid'  => $q->link_ppid ?? '',
                    ];
                }
            }

            $indicatorsData[] = [
                'id'    => $ind->id_indicators,
                'name'  => $ind->nama_indikator,
                'count' => $totalIndSoal,
            ];
        }

        return view('pages.master.kuesioner', compact(
            'indicators', 'activeTab', 'activeInd', 'subIndicatorsMapping',
            'questionsData', 'indicatorsData', 'hasActivePeriod', 'activePeriod'
        ));
    }

    /**
     * Store a new question.
     */
    public function storeKuesioner(Request $request)
    {
        if (Period::hasActivePeriod()) {
            return redirect()
                ->route($this->getRedirectRoute($request))
                ->with('error', 'Master Pertanyaan tidak dapat ditambah karena terdapat periode KIP yang sedang aktif.');
        }

        $urlRule = $this->validateSecureUrl();

        $request->validate([
            'indicator'    => 'required|string|max:255',
            'sub_indicator'=> 'required|string|max:255',
            'weight'       => 'required|numeric|min:0',
            'question'     => 'required|string|max:2000',
            'deskripsi'    => 'nullable|string|max:5000',
            'dokumen'      => 'nullable|string|max:5000',
            'link_drive'   => 'nullable|string|max:2000',
            'link_ppid'    => 'nullable|string|max:2000',
        ]);

        $ind = MasterIndikator::where('nama_indikator', $request->input('indicator'))->firstOrFail();
        $sub = MasterSubIndikator::where('id_indicators', $ind->id_indicators)
            ->where('nama_sub_indikator', $request->input('sub_indicator'))
            ->firstOrFail();

        $maxOrder = MasterPertanyaan::max('order_no') ?? 0;

        MasterPertanyaan::create([
            'order_no'         => $maxOrder + 1,
            'id_indicators'    => $ind->id_indicators,
            'id_sub_indicators'=> $sub->id_sub_indicators,
            'ques_text'        => $request->input('question'),
            'ques_description' => $request->input('deskripsi'),
            'doc_requirement'  => $request->filled('dokumen') ? $request->input('dokumen') : null,
            'link_drive'       => $request->filled('link_drive') ? $request->input('link_drive') : null,
            'link_ppid'        => $request->filled('link_ppid') ? $request->input('link_ppid') : null,
            'bobot_pertanyaan' => $request->input('weight'),
        ]);

        return redirect()
            ->route($this->getRedirectRoute($request), ['tab' => SecureId::encrypt($ind->id_indicators)])
            ->with('success', 'Pertanyaan berhasil ditambahkan.');
    }

    /**
     * Update an existing question.
     */
    public function updateKuesioner(Request $request, $id)
    {
        if (Period::hasActivePeriod()) {
            return redirect()
                ->route($this->getRedirectRoute($request))
                ->with('error', 'Master Pertanyaan tidak dapat diubah karena terdapat periode KIP yang sedang aktif.');
        }

        $request->validate([
            'indicator'    => 'required|string|max:255',
            'sub_indicator'=> 'required|string|max:255',
            'weight'       => 'required|numeric|min:0',
            'question'     => 'required|string|max:2000',
            'deskripsi'    => 'nullable|string|max:5000',
            'dokumen'      => 'nullable|string|max:5000',
            'link_drive'   => 'nullable|string|max:2000',
            'link_ppid'    => 'nullable|string|max:2000',
        ]);

        $question = MasterPertanyaan::findOrFail($id);
        $ind      = MasterIndikator::where('nama_indikator', $request->input('indicator'))->firstOrFail();
        $sub      = MasterSubIndikator::where('id_indicators', $ind->id_indicators)
            ->where('nama_sub_indikator', $request->input('sub_indicator'))
            ->firstOrFail();

        $question->update([
            'id_indicators'    => $ind->id_indicators,
            'id_sub_indicators'=> $sub->id_sub_indicators,
            'ques_text'        => $request->input('question'),
            'ques_description' => $request->input('deskripsi'),
            'doc_requirement'  => $request->filled('dokumen') ? $request->input('dokumen') : null,
            'link_drive'       => $request->filled('link_drive') ? $request->input('link_drive') : null,
            'link_ppid'        => $request->filled('link_ppid') ? $request->input('link_ppid') : null,
            'bobot_pertanyaan' => $request->input('weight'),
        ]);

        return redirect()
            ->route($this->getRedirectRoute($request), ['tab' => SecureId::encrypt($ind->id_indicators)])
            ->with('success', 'Pertanyaan berhasil diperbarui.');
    }

    /**
     * Delete a question.
     */
    public function deleteKuesioner(Request $request, $id)
    {
        if (Period::hasActivePeriod()) {
            return redirect()
                ->route($this->getRedirectRoute($request))
                ->with('error', 'Master Pertanyaan tidak dapat dihapus karena terdapat periode KIP yang sedang aktif.');
        }

        $question = MasterPertanyaan::findOrFail($id);

        $hasAnswers = Answer::where('id_question', $id)->exists();
        if ($hasAnswers) {
            return redirect()
                ->route($this->getRedirectRoute($request), ['tab' => SecureId::encrypt($question->id_indicators)])
                ->with('error', 'Pertanyaan tidak dapat dihapus karena sudah memiliki data pengisian SAQ.');
        }

        $tab = $question->id_indicators;
        $question->delete();

        return redirect()
            ->route($this->getRedirectRoute($request), ['tab' => SecureId::encrypt($tab)])
            ->with('success', 'Pertanyaan berhasil dihapus.');
    }

    /**
     * Import questions from parsed Excel JSON data.
     */
    public function importKuesioner(Request $request)
    {
        if (Period::hasActivePeriod()) {
            return response()->json([
                'success' => false,
                'message' => 'Master Pertanyaan tidak dapat di-import karena terdapat periode KIP yang sedang aktif.'
            ], 422);
        }

        $request->validate([
            'questions'                 => 'required|array',
            'questions.*.indikator'     => 'required|string|max:255',
            'questions.*.sub_indikator' => 'required|string|max:255',
            'questions.*.pertanyaan'    => 'required|string|max:2000',
            'questions.*.deskripsi'     => 'nullable|string|max:5000',
            'questions.*.link_drive'    => 'nullable|string|max:2000',
            'questions.*.link_ppid'     => 'nullable|string|max:2000',
            'questions.*.dokumen'       => 'nullable|string|max:2000',
            'questions.*.bobot'         => 'required|numeric|min:0',
        ]);

        $questionsData = $request->input('questions');
        $importedCount = 0;
        $skippedCount  = 0;
        $errors        = [];

        $maxOrder = MasterPertanyaan::max('order_no') ?? 0;
        $indicatorsMap = MasterIndikator::with('subIndikators')->get()->keyBy(fn($item) => strtolower(trim($item->nama_indikator)));

        foreach ($questionsData as $index => $data) {
            $rowNumber = $index + 2;

            $indName = trim($data['indikator'] ?? '');
            $subName = trim($data['sub_indikator'] ?? '');
            $quesText = trim($data['pertanyaan'] ?? '');
            $deskripsi = trim($data['deskripsi'] ?? '');
            $linkDrive = trim($data['link_drive'] ?? '');
            $linkPpid  = trim($data['link_ppid'] ?? '');
            $dokumen = trim($data['dokumen'] ?? '');
            $scores = floatval($data['bobot'] ?? 2.5);

            $ind = $indicatorsMap->get(strtolower($indName));
            if (!$ind) {
                $errors[] = "Baris {$rowNumber}: Indikator '{$indName}' tidak ditemukan.";
                $skippedCount++;
                continue;
            }

            $sub = $ind->subIndikators->first(fn($s) => strtolower(trim($s->nama_sub_indikator)) === strtolower($subName));
            if (!$sub) {
                $errors[] = "Baris {$rowNumber}: Sub-Indikator '{$subName}' tidak sesuai dengan indikator '{$indName}'.";
                $skippedCount++;
                continue;
            }

            if (empty($quesText)) {
                $errors[] = "Baris {$rowNumber}: Isi pertanyaan kosong.";
                $skippedCount++;
                continue;
            }

            $maxOrder++;
            MasterPertanyaan::create([
                'order_no'          => $maxOrder,
                'id_indicators'     => $ind->id_indicators,
                'id_sub_indicators' => $sub->id_sub_indicators,
                'ques_text'         => $quesText,
                'ques_description'  => $deskripsi,
                'doc_requirement'   => !empty($dokumen) ? $dokumen : null,
                'link_drive'        => !empty($linkDrive) ? $linkDrive : null,
                'link_ppid'         => !empty($linkPpid) ? $linkPpid : null,
                'bobot_pertanyaan'  => $scores,
            ]);

            $importedCount++;
        }

        return response()->json([
            'success'  => true,
            'imported' => $importedCount,
            'skipped'  => $skippedCount,
            'errors'   => $errors,
            'message'  => "Berhasil mengimpor {$importedCount} pertanyaan. " . ($skippedCount > 0 ? "{$skippedCount} pertanyaan dilewati." : '')
        ]);
    }

    /**
     * Validasi format dan keamanan link (URL http/https).
     */
    private function validateSecureUrl(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if ($value === null || $value === '') {
                return;
            }

            $trimmed = trim((string) $value);

            // 1. Wajib diawali http:// atau https://
            if (!preg_match('/^https?:\/\//i', $trimmed)) {
                $fail('Isikan Link dengan benar.');
                return;
            }

            // 2. Proteksi kontrol karakter, null byte, dan header injection
            if (preg_match('/[\x00-\x1F\x7F]/', $trimmed)) {
                $fail('Isikan Link dengan benar.');
                return;
            }

            // 3. Validasi struktur URL standar
            if (!filter_var($trimmed, FILTER_VALIDATE_URL)) {
                $fail('Isikan Link dengan benar.');
                return;
            }

            // 4. Pastikan skema strictly http/https dan host terisi
            $parsed = parse_url($trimmed);
            if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), ['http', 'https'], true) || empty($parsed['host'])) {
                $fail('Isikan Link dengan benar.');
                return;
            }
        };
    }
}
