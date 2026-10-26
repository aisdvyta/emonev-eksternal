<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Interview;
use App\Models\KabagKomentar;
use App\Models\MasterIndikator;
use App\Models\MasterPertanyaan;
use App\Models\MasterSatker;
use App\Models\Period;
use App\Models\Result;
use App\Models\Submission;
use App\Models\User;
use App\Models\Visitasi;
use App\Helpers\RegionMapHelper;
use App\Helpers\SecureId;
use Illuminate\Http\Request;

class PenilaianService
{
    /**
     * Dapatkan konfigurasi pembobotan tahapan & KKM untuk periode tertentu (atau periode aktif/default).
     */
    public function getPembobotanConfig(?int $periodId = null): \App\Models\MasterPembobotan
    {
        $resolvedPeriodId = $periodId;
        if (!$resolvedPeriodId) {
            $activePeriod = Period::getActivePeriod();
            $resolvedPeriodId = $activePeriod?->id_periode;
        }

        $cacheKey = 'pembobotan_config_' . ($resolvedPeriodId ?? 'default');

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($resolvedPeriodId) {
            if ($resolvedPeriodId) {
                $pem = \App\Models\MasterPembobotan::where('id_periode', $resolvedPeriodId)->first();
                if ($pem) {
                    return $pem;
                }
            }

            $pem = \App\Models\MasterPembobotan::first();
            if ($pem) {
                return $pem;
            }

            // Default fallback if table is empty
            $default = new \App\Models\MasterPembobotan();
            $default->weight_saq = 30.00;
            $default->weight_visitasi = 40.00;
            $default->weight_wawancara = 30.00;
            $default->threshold_saq = 80.00;
            return $default;
        });
    }

    /**
     * Dapatkan bobot per kategori/indikator untuk SAQ dari MasterPembobotan periode tertentu.
     * @return array<int, float>
     */
    public function getSaqCategoryWeights(?int $periodId = null): array
    {
        $cacheKey = 'saq_category_weights_' . ($periodId ?? 'default');
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function() use ($periodId) {
            $config = $this->getPembobotanConfig($periodId);
            $weights = $config->bobot_indikator ?? [];

            $indicators = MasterIndikator::getCachedIds();
            $result = [];
            $assignedTotal = 0.0;

            foreach ($indicators as $indId) {
                if (isset($weights[$indId]) || isset($weights[(string) $indId])) {
                    $w = floatval($weights[$indId] ?? $weights[(string) $indId]);
                    $result[$indId] = $w;
                    $assignedTotal += $w;
                }
            }

            // Fallback jika belum ada bobot tersimpan sama sekali
            if ($assignedTotal === 0.0 && count($indicators) > 0) {
                $even = round(100.0 / count($indicators), 2);
                foreach ($indicators as $indId) {
                    $result[$indId] = $even;
                }
            }

            return $result;
        });
    }

    /**
     * Dapatkan skor maksimal per indikator SAQ secara dinamis dari MasterPertanyaan.
     * @return array<int, float>
     */
    public function getDynamicMaxScores(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('dynamic_max_scores_by_ind', 3600, function() {
            return MasterPertanyaan::selectRaw('id_indicators, sum(bobot_pertanyaan) as max_score')
                ->groupBy('id_indicators')
                ->pluck('max_score', 'id_indicators')
                ->toArray();
        });
    }

    /**
     * Hitung persentase nilai per indikator untuk suatu submission.
     * @return array<int, float>
     */
    public function calculateIndicatorScores(?Submission $submission): array
    {
        $dynamicMaxScores = $this->getDynamicMaxScores();

        $earnedScores = [];
        if ($submission && $submission->id_submission) {
            $earnedScores = Answer::where('id_submission', $submission->id_submission)
                ->selectRaw('id_indicators, sum(answer_score) as earned_score')
                ->groupBy('id_indicators')
                ->pluck('earned_score', 'id_indicators')
                ->toArray();
        }

        $periodId = $submission?->result?->id_periode;
        $saqWeights = $this->getSaqCategoryWeights($periodId);
        $scores = [];
        foreach (array_keys($saqWeights) as $indId) {
            $maxScore = $dynamicMaxScores[$indId] ?? 1.0;
            $earned = $earnedScores[$indId] ?? 0.0;
            $scores[$indId] = round(($earned / max($maxScore, 1.0)) * 100, 2);
        }

        return $scores;
    }

    /**
     * Hitung persentase nilai per indikator untuk semua satker dalam satu periode.
     * @return array<string, array<int, float>> key: kode_satker => [id_indicators => percentage]
     */
    public function calculateIndicatorScoresPerSatker(int $periodId): array
    {
        $dynamicMaxScores = $this->getDynamicMaxScores();
        $satkers = \Illuminate\Support\Facades\Cache::remember('satkers_not_3500', 3600, function() {
            return \App\Models\MasterSatker::where('kode_satker', '!=', '3500')->get();
        });
        $saqWeights = $this->getSaqCategoryWeights($periodId);
        $indicators = array_filter(array_keys($saqWeights), function($indId) use ($dynamicMaxScores) {
            return ($dynamicMaxScores[$indId] ?? 0) > 0;
        });

        $allSubmissions = Submission::with(['answers', 'result'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $periodId))
            ->get()
            ->keyBy(fn($s) => $s->result ? $s->result->kode_satker : '');

        $resultMap = [];
        foreach ($satkers as $satker) {
            $sub = $allSubmissions->get($satker->kode_satker);
            $answersByInd = $sub ? $sub->answers->groupBy('id_indicators') : collect();

            $indScores = [];
            foreach ($indicators as $indId) {
                $maxScore = $dynamicMaxScores[$indId] ?? 1.0;
                $earned = isset($answersByInd[$indId]) ? $answersByInd[$indId]->sum('answer_score') : 0.0;
                $indScores[$indId] = round(($earned / max($maxScore, 1.0)) * 100, 2);
            }
            $resultMap[$satker->kode_satker] = $indScores;
        }

        return $resultMap;
    }

    /**
     * Hitung skor SAQ secara terbobot per kategori.
     */
    public function calculateSaqScore(Submission $submission, ?array $indicatorScores = null): float
    {
        $scores = $indicatorScores ?? $this->calculateIndicatorScores($submission);
        $periodId = $submission->result?->id_periode;
        $saqWeights = $this->getSaqCategoryWeights($periodId);
        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($saqWeights as $indId => $weight) {
            $pct = $scores[$indId] ?? 0.0;
            $weightedSum += $pct * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight === 0.0) {
            return 0.0;
        }

        // Normalisasi ke skala 100
        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Periksa apakah nilai total skor SAQ memenuhi ambang batas KKM (default 80%).
     * Kelulusan dihitung berdasarkan total nilai SAQ keseluruhan (>= KKM),
     * bukan per indikator lagi (meskipun ada indikator < threshold, tetap lolos selama total SAQ >= threshold).
     */
    public function checkSaqKkmPerIndicator(Submission $submission, ?array $indicatorScores = null, ?float $saqScore = null): bool
    {
        $periodId = $submission->result?->id_periode;
        $config = $this->getPembobotanConfig($periodId);
        $threshold = $config->threshold_saq ?? 80.0;

        $totalSaq = $saqScore ?? $this->calculateSaqScore($submission, $indicatorScores);

        return $totalSaq >= $threshold;
    }

    /**
     * Periksa apakah nilai total SAQ memenuhi standar KKM.
     */
    public function checkSaqKkm(Submission $submission, ?float $saqScore = null): bool
    {
        return $this->checkSaqKkmPerIndicator($submission, null, $saqScore);
    }

    /**
     * Finalisasi evaluasi SAQ (cek KKM total SAQ, update status Lolos/Gugur, auto inisialisasi Visitasi & Interview).
     */
    public function finalizeSaqEvaluation(Submission $submission, float $saqScore): string
    {
        $periodId = $submission->result?->id_periode;
        $config = $this->getPembobotanConfig($periodId);
        $threshold = $config->threshold_saq ?? 80.0;

        $submission->update([
            'evaluated_at' => now(),
        ]);

        Result::updateOrCreate([
            'id_result' => $submission->id_result
        ], [
            'ques_score' => $saqScore,
        ]);

        // Cek kelulusan berdasarkan nilai total SAQ keseluruhan (>= KKM)
        $isLolosKkm = $saqScore >= $threshold;
        if ($isLolosKkm) {
            // Lolos threshold -> status 5 (Visitasi)
            $submission->update(['status' => 5]);

            // Auto initialize Visitasi
            Visitasi::updateOrCreate([
                'id_result' => $submission->id_result,
            ], [
                'visit_mode' => 'offline',
            ]);

            // Auto initialize Interview
            $evalList = User::role('evaluator')->limit(3)->get();
            $interview = Interview::updateOrCreate([
                'id_result' => $submission->id_result,
            ]);
            foreach ($evalList as $ev) {
                \App\Models\InterviewEvaluator::updateOrCreate([
                    'id_interview' => $interview->id_interview,
                    'id_user' => $ev->id_user,
                ], [
                    'score' => null,
                    'catatan' => null,
                    'is_draft' => true,
                ]);
            }

            return 'Evaluasi SAQ berhasil difinalisasi. Nilai SAQ: ' . $saqScore . ' (Lolos KKM SAQ (>= ' . $threshold . '), lanjut ke Visitasi & Wawancara).';
        } else {
            // Gugur -> status 8 (Gugur)
            $submission->update(['status' => 8]);

            $weightSaqRatio = ($config->weight_saq ?? 30.0) / 100.0;

            Result::updateOrCreate([
                'id_result' => $submission->id_result
            ], [
                'ques_score' => $saqScore,
                'final_score' => $saqScore * $weightSaqRatio,
                'index_label' => 'Gugur',
                'published_at' => now(),
            ]);

            return 'Evaluasi SAQ berhasil difinalisasi. Nilai SAQ: ' . $saqScore . ' (Tidak memenuhi KKM SAQ (>= ' . $threshold . '), dinyatakan Gugur).';
        }
    }

    /**
     * Hitung rata-rata skor wawancara dari semua interviewer.
     * Mengembalikan null jika belum ada interviewer yang menilai.
     */
    public function calculateInterviewScore(?Interview $interview): ?float
    {
        if (!$interview) {
            return null;
        }

        if ($interview->relationLoaded('evaluators') || $interview->evaluators()->exists()) {
            $scores = $interview->evaluators
                ->filter(fn($ev) => !is_null($ev->score) && !$ev->is_draft)
                ->pluck('score');

            return $scores->count() > 0 ? round((float) $scores->average(), 2) : null;
        }

        return null;
    }

    /**
     * Hitung rata-rata skor visitasi dari semua evaluator yang ditugaskan.
     * Mengembalikan null jika belum ada evaluator yang menilai.
     */
    public function calculateVisitasiScore(?Visitasi $visitasi): ?float
    {
        if (!$visitasi) {
            return null;
        }

        if ($visitasi->relationLoaded('evaluators') || $visitasi->evaluators()->exists()) {
            $scores = $visitasi->evaluators
                ->filter(fn($ev) => !is_null($ev->score) && !$ev->is_draft)
                ->pluck('score');

            return $scores->count() > 0 ? round((float) $scores->average(), 2) : null;
        }

        return null;
    }

    /**
     * Hitung final score secara dinamis sesuai bobot tahapan pada periode tertentu.
     */
    public function calculateFinalScore(float $saqScore, float $visitScore, float $interviewScore, ?int $periodId = null): float
    {
        $config = $this->getPembobotanConfig($periodId);
        $wSaq = ($config->weight_saq ?? 30.0) / 100.0;
        $wVisit = ($config->weight_visitasi ?? 40.0) / 100.0;
        $wInterview = ($config->weight_wawancara ?? 30.0) / 100.0;

        return round(
            ($saqScore * $wSaq) +
            ($visitScore * $wVisit) +
            ($interviewScore * $wInterview),
            2
        );
    }

    /**
     * Hitung final score dari submission secara lengkap.
     */
    public function calculateFinalScoreFromSubmission(Submission $submission): float
    {
        $periodId = $submission->result?->id_periode;
        $saqScore = $submission->result?->ques_score ?? 0;
        $visitScore = $submission->visitasi ? ($submission->visitasi->visit_score ?? 0) : 0;
        $interviewScore = $submission->interview ? ($submission->interview->interview_score ?? 0) : 0;

        return $this->calculateFinalScore($saqScore, $visitScore, $interviewScore, $periodId);
    }

    /**
     * Tentukan predikat berdasarkan final score.
     */
    public function getPredikat(float $score): string
    {
        if ($score >= 90) {
            return 'INFORMATIF';
        } elseif ($score >= 80) {
            return 'MENUJU INFORMATIF';
        } elseif ($score >= 60) {
            return 'CUKUP INFORMATIF';
        } elseif ($score >= 40) {
            return 'KURANG INFORMATIF';
        } else {
            return 'TIDAK INFORMATIF';
        }
    }

    /**
     * Dapatkan jumlah total pertanyaan dan pertanyaan yang sudah dinilai untuk sebuah submission.
     * @return array{total: int, evaluated: int}
     */
    public function getSaqEvaluationCounts(Submission $submission): array
    {
        $total = MasterPertanyaan::getCachedCount();
        $evaluated = Answer::where('id_submission', $submission->id_submission)
            ->whereNotNull('answer_score')
            ->count();

        return [
            'total'     => $total,
            'evaluated' => $evaluated,
        ];
    }

    /**
     * Cek apakah semua pertanyaan SAQ sudah dinilai oleh evaluator.
     */
    public function isSaqFullyEvaluated(Submission $submission): bool
    {
        $counts = $this->getSaqEvaluationCounts($submission);
        if ($counts['total'] === 0) {
            return true;
        }

        return $counts['evaluated'] >= $counts['total'];
    }

    /**
     * Hitung jumlah pertanyaan SAQ yang belum dinilai.
     */
    public function countUnevaluatedSaqQuestions(Submission $submission): int
    {
        $counts = $this->getSaqEvaluationCounts($submission);
        return max(0, $counts['total'] - $counts['evaluated']);
    }

    /**
     * Dapatkan progress pengisian kuesioner SAQ untuk submission tertentu.
     * @return array{total: int, answered: int, percent: int}
     */
    public function getAnswerProgress(?Submission $submission): array
    {
        $total = MasterPertanyaan::getCachedCount() ?: 1;
        if (!$submission || !$submission->id_submission) {
            return [
                'total'    => $total,
                'answered' => 0,
                'percent'  => 0,
            ];
        }

        $answered = Answer::where('id_submission', $submission->id_submission)
            ->where(function ($q) {
                $q->where('answer', 0)
                  ->orWhere(function ($q2) {
                      $q2->where('answer', 1)
                         ->whereNotNull('link_ppid')
                         ->where('link_ppid', '!=', '')
                         ->whereNotNull('link_drive')
                         ->where('link_drive', '!=', '');
                  });
            })
            ->count();

        $percent = (int) round(($answered / $total) * 100);

        return [
            'total'    => $total,
            'answered' => $answered,
            'percent'  => min(100, $percent),
        ];
    }

    /**
     * Cek apakah semua evaluator visitasi sudah memberikan nilai.
     */
    public function isVisitasiFullyScored(?Visitasi $visitasi): bool
    {
        if (!$visitasi) {
            return false;
        }

        if ($visitasi->relationLoaded('evaluators') || $visitasi->evaluators()->exists()) {
            $evaluators = $visitasi->evaluators;
            return $evaluators->count() > 0 && $evaluators->every(fn($ev) => !is_null($ev->score) && !$ev->is_draft);
        }

        return false;
    }

    /**
     * Cek apakah semua pewawancara sudah memberikan nilai.
     */
    public function isInterviewFullyScored(?Interview $interview): bool
    {
        if (!$interview) {
            return false;
        }

        if ($interview->relationLoaded('evaluators') || $interview->evaluators()->exists()) {
            $evaluators = $interview->evaluators;
            return $evaluators->count() > 0 && $evaluators->every(fn($ev) => !is_null($ev->score) && !$ev->is_draft);
        }

        return false;
    }

    /**
     * Sinkronisasi semua score ke Result table dan kembalikan instance Result.
     */
    public function syncResult(Submission $submission): Result
    {
        $submission->loadMissing(['result']);
        if ($submission->result) {
            $submission->result->loadMissing(['visitasi', 'interview']);
        }

        $saqScore = $submission->result?->ques_score ?? 0;
        $visitScore = $submission->visitasi ? ($submission->visitasi->visit_score ?? 0) : 0;
        $interviewScore = $submission->interview ? ($submission->interview->interview_score ?? 0) : 0;

        $finalScore = $this->calculateFinalScore($saqScore, $visitScore, $interviewScore);
        $predikat = $submission->status < 6
            ? 'BELUM DINILAI'
            : ($submission->status == 8 ? 'TIDAK INFORMATIF' : $this->getPredikat($finalScore));

        $result = $submission->result;
        if ($result) {
            $result->update([
                'ques_score' => $saqScore,
                'visit_score' => $visitScore,
                'interview_score' => $interviewScore,
                'final_score' => $finalScore,
                'index_label' => $predikat,
            ]);
        }

        return $result;
    }

    /**
     * Export Laporan Penilaian Akhir (XLS HTML-as-Excel) terpusat untuk ResultController, KabagController, & SubmissionController.
     */
    public function exportPenilaianExcel(\Illuminate\Http\Request $request)
    {
        $selectedPeriodId = $request->input('id_periode');
        $period = \App\Models\Period::resolvePeriod($selectedPeriodId);

        if (!$period) {
            return back()->with('error', 'Tidak ada periode aktif.');
        }

        $satkers = \App\Models\MasterSatker::where('kode_satker', '!=', '3500')->get();

        $submissionsInPeriod = Submission::with(['result.satker', 'result', 'visitasi', 'interview'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $period->id_periode))
            ->get()
            ->keyBy(fn($sub) => $sub->result ? $sub->result->kode_satker : '');

        $allRecords = collect();

        foreach ($satkers as $satker) {
            $sub = $submissionsInPeriod->get($satker->kode_satker);

            if (!$sub) {
                $sub = Submission::makeDummy($satker, $period);
            } else {
                $sub->calculated_final = $this->calculateFinalScoreFromSubmission($sub);
                $sub->predikat = $sub->status == 8
                    ? 'TIDAK INFORMATIF'
                    : ($sub->status < 6 ? 'BELUM DINILAI' : $this->getPredikat($sub->calculated_final));
            }
            $allRecords->push($sub);
        }

        // Filter kota
        $filterKotaArr = $request->input('kota', []);
        if (!empty($filterKotaArr)) {
            $filterKotaLower = array_map('strtolower', $filterKotaArr);
            $allRecords = $allRecords->filter(fn($s) =>
                in_array(strtolower($s->result->satker->nama_satker ?? ''), $filterKotaLower)
            );
        }

        // Filter predikat
        $filterPredikatArr = $request->input('predikat', []);
        if (!empty($filterPredikatArr)) {
            $allRecords = $allRecords->filter(fn($s) => in_array($s->predikat, $filterPredikatArr));
        }

        $allRecords = $this->sortSubmissions($allRecords, $period->id_periode, 'desc');

        if ($allRecords->isEmpty()) {
            $predikats = array_map(fn($p) => '"' . ucwords(strtolower($p)) . '"', $filterPredikatArr);
            $predikatStr = implode(' atau ', $predikats);
            $msg = 'Tidak ada Kabupaten/Kota yang memiliki status ' . ($predikatStr ?: 'tersebut') . ' pada tahun ' . $period->tahun . '.';
            
            if ($request->has('validate')) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return back()->with('error', $msg);
        }

        $gradedCount = $allRecords->filter(fn($s) => $s->status >= 6 || $s->status == 8)->count();
        if ($gradedCount === 0) {
            $msg = 'Kabupaten/Kota yang dipilih belum selesai dinilai pada periode tahun ' . $period->tahun . '.';
            
            if ($request->has('validate')) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return back()->with('error', $msg);
        }

        if ($request->has('validate')) {
            return response()->json(['success' => true]);
        }

        $filename = 'laporan_penilaian_' . $period->tahun . '_' . now()->format('Ymd_His') . '.xls';

        $pembobotan = $this->getPembobotanConfig($period->id_periode);
        $wSaq = (float) ($pembobotan->weight_saq ?? 30);
        $wVisit = (float) ($pembobotan->weight_visitasi ?? 40);
        $wWawancara = (float) ($pembobotan->weight_wawancara ?? 30);
        $stageOrder = $pembobotan->resolvedStageOrder();

        $cols = ['No', 'Kabupaten/Kota', 'Tahun'];
        foreach ($stageOrder as $stageKey) {
            if ($stageKey === 'saq') {
                $cols[] = "Nilai SAQ ({$wSaq}%)";
            } elseif ($stageKey === 'visitasi') {
                $cols[] = "Nilai Visitasi ({$wVisit}%)";
            } elseif ($stageKey === 'wawancara') {
                $cols[] = "Nilai Wawancara ({$wWawancara}%)";
            }
        }
        $cols[] = 'Nilai Akhir';
        $cols[] = 'Skala Penilaian';

        $html = '<table border="1" style="font-family:Arial;font-size:11pt;border-collapse:collapse;">';

        $html .= '<tr><td colspan="8" style="font-weight:bold;font-size:13pt;text-align:center;background-color:#184D77;color:#ffffff;padding:10px;">Laporan Penilaian Akhir KIP - Tahun ' . $period->tahun . '</td></tr>';
        $html .= '<tr><td colspan="8" style="padding:4px;"></td></tr>';

        $html .= '<tr>';
        foreach ($cols as $col) {
            $html .= '<td style="font-weight:bold;background-color:#E8F0FE;text-align:center;padding:6px;border:1px solid #9e9e9e;">' . $col . '</td>';
        }
        $html .= '</tr>';

        foreach ($allRecords as $i => $sub) {
            $satker = $sub->result->satker ?? null;
            $iScore = $this->calculateInterviewScore($sub->interview) ?? 0;
            $qScore = $sub->result?->ques_score ?? 0;
            $vScore = $sub->visitasi ? ($sub->visitasi->visit_score ?? 0) : 0;
            $predikatColor = match($sub->predikat) {
                'INFORMATIF'        => '#388E3C',
                'MENUJU INFORMATIF' => '#68B92E',
                'CUKUP INFORMATIF'  => '#4C84B0',
                'KURANG INFORMATIF' => '#F7B750',
                'TIDAK INFORMATIF'  => '#E82D1F',
                default             => '#f3f4f6',
            };
            $rowBg = $i % 2 === 0 ? '#ffffff' : '#f9fafb';
            $html .= '<tr style="background-color:' . $rowBg . ';">';
            $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . ($i + 1) . '</td>';
            $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($satker->nama_satker ?? '-') . '</td>';
            $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . $period->tahun . '</td>';
            foreach ($stageOrder as $stageKey) {
                if ($stageKey === 'saq') {
                    $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . number_format($qScore, 2) . '</td>';
                } elseif ($stageKey === 'visitasi') {
                    $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . number_format($vScore, 2) . '</td>';
                } elseif ($stageKey === 'wawancara') {
                    $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . number_format($iScore, 2) . '</td>';
                }
            }
            $html .= '<td style="text-align:center;font-weight:bold;padding:5px;border:1px solid #e0e0e0;">' . number_format($sub->calculated_final, 2) . '</td>';
            $html .= '<td style="text-align:center;background-color:' . $predikatColor . ';font-weight:bold;padding:5px;border:1px solid #e0e0e0;">' . $sub->predikat . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return \App\Helpers\ExcelExportHelper::download($filename, 'Laporan Penilaian', $html);
    }

    /**
     * Dapatkan data lengkap laporan penilaian akhir untuk satu satker (dipakai oleh Operator & Verifikator).
     *
     * @param string $kodeSatker Kode satker target
     * @param mixed $id_periode ID periode (encrypted/decrypted/null)
     * @param Request|null $request Request object untuk parameter filter grafik
     * @return array Data array untuk dikirim ke Compact View
     */
    public function getSatkerPenilaianData(string $kodeSatker, $id_periode = null, ?Request $request = null): array
    {
        $request = $request ?? request();

        $today = now()->toDateString();
        $allPeriods = Period::getAvailablePeriodsForUser();

        // Periode yang sudah selesai / terpublikasi / atau memiliki hasil terbit untuk satker ini (status >= 7)
        $completedPeriods = $allPeriods->filter(function ($p) use ($today, $kodeSatker) {
            return ($p->result_end && $today > $p->result_end)
                || ($p->getPhaseInfo()['phase'] === 'Selesai / Terpublikasi')
                || $p->results()->where('kode_satker', $kodeSatker)->whereHas('submission', fn($q) => $q->where('status', '>=', 7))->exists();
        })->values();

        // Pilihan periode untuk Penilaian Akhir: utamakan periode yang sudah selesai
        $periods = $completedPeriods->isNotEmpty() ? $completedPeriods : $allPeriods;

        $selectedPeriodId = $id_periode;
        if ($selectedPeriodId) {
            if (!is_numeric($selectedPeriodId)) {
                $selectedPeriodId = SecureId::decrypt($selectedPeriodId);
            }
        }
        if ($selectedPeriodId) {
            $period = $allPeriods->firstWhere('id_periode', $selectedPeriodId);
        } else {
            $period = $periods->first();
        }

        if (!$period) {
            return [
                'period' => null,
                'periods' => collect(),
                'submission' => null,
                'result' => null,
                'finalScore' => 0,
                'predikat' => 'TIDAK INFORMATIF',
                'rank' => '-',
                'allResults' => collect(),
                'avgProv' => 0,
                'indicators' => collect(),
                'indicatorScores' => [],
                'regionScores' => [],
                'satkerScores' => [],
                'chartYears' => [],
                'chartScores' => [],
                'chartAvgs' => [],
                'latestKabagKomentar' => null,
                'kabagKomentar' => null,
                'isPublished' => false,
                'availableYears' => [],
                'chartStartYear' => null,
                'chartEndYear' => null,
                'satkers' => collect(),
                'indicatorScoresPerKabkot' => [],
                'chartKabkotKode' => $kodeSatker,
                'pembobotan' => $this->getPembobotanConfig(null),
            ];
        }

        $submission = Submission::with(['visitasi', 'interview', 'result'])
            ->whereHas('result', fn($q) => $q->where('kode_satker', $kodeSatker)->where('id_periode', $period->id_periode))
            ->first();

        $isPeriodEnded = ($period->result_end && now()->toDateString() > $period->result_end)
            || ($period->getPhaseInfo()['phase'] === 'Selesai / Terpublikasi');

        $isPublished = ($submission && $submission->result && $submission->status >= 7) || $isPeriodEnded;

        $result = $submission ? $submission->result : null;
        $finalScore = $result ? ($result->final_score ?? 0) : 0;
        $predikat = $result ? ($result->index_label ?? 'TIDAK INFORMATIF') : 'TIDAK INFORMATIF';

        // Calculate rank
        $allResults = Result::where('id_periode', $period->id_periode)
            ->whereHas('submission', fn($q) => $q->where('status', '>=', 7))
            ->get();
        $allResults = $this->sortSubmissions($allResults, $period->id_periode, 'desc');

        $rank = '-';
        if ($isPublished && $submission) {
            foreach ($allResults as $index => $res) {
                if ($res->id_result == $submission->id_result) {
                    $rank = $index + 1;
                    break;
                }
            }
        }
        $avgProv = count($allResults) > 0 ? round($allResults->avg('final_score'), 2) : 0;

        $ascPeriods = $allPeriods->sortBy('periode_start');

        $availableYears = $ascPeriods
            ->filter(function($p) use ($period) {
                return !$period || $p->tahun <= $period->tahun;
            })
            ->pluck('tahun', 'tahun')
            ->toArray();

        $chartStartYear = $request->query('chart_start_year');
        $chartEndYear = $request->query('chart_end_year');

        if ($period) {
            $currentPeriodYear = $period->tahun;
            if (empty($chartEndYear) || $chartEndYear > $currentPeriodYear) {
                $chartEndYear = $currentPeriodYear;
            }
            if (empty($chartStartYear) || $chartStartYear > $currentPeriodYear) {
                $keys = array_keys($availableYears);
                if (count($keys) > 0) {
                    $chartStartYear = count($keys) >= 5 ? $keys[count($keys) - 5] : $keys[0];
                }
            }
        }

        if (!empty($chartStartYear) && !empty($chartEndYear) && $chartStartYear > $chartEndYear) {
            $chartEndYear = $chartStartYear;
        }

        $filteredChartPeriods = $ascPeriods;
        if (!empty($chartStartYear)) {
            $filteredChartPeriods = $filteredChartPeriods->filter(fn($p) => $p->tahun >= $chartStartYear);
        }
        if (!empty($chartEndYear)) {
            $filteredChartPeriods = $filteredChartPeriods->filter(fn($p) => $p->tahun <= $chartEndYear);
        }

        $chartYears = [];
        $chartScores = [];
        $chartAvgs = [];

        $periodIds = $filteredChartPeriods->pluck('id_periode')->toArray();

        $subInPeriods = Submission::with('result')
            ->whereHas('result', fn($q) => $q->where('kode_satker', $kodeSatker)->whereIn('id_periode', $periodIds))
            ->where('status', '>=', 7)
            ->get()
            ->keyBy(fn($s) => $s->result->id_periode);

        $avgScoresPerPeriod = Result::whereIn('id_periode', $periodIds)
            ->whereHas('submission', fn($q) => $q->where('status', '>=', 7))
            ->selectRaw('id_periode, AVG(final_score) as avg_score')
            ->groupBy('id_periode')
            ->pluck('avg_score', 'id_periode');

        foreach ($filteredChartPeriods as $p) {
            $chartYears[] = (string) $p->tahun;
            $subInP = $subInPeriods->get($p->id_periode);
            $chartScores[] = $subInP && $subInP->result ? round($subInP->result->final_score, 2) : 0;
            $chartAvgs[] = round($avgScoresPerPeriod->get($p->id_periode) ?? 0, 2);
        }

        // Group answers by indicator to calculate score per indicator
        $satkers = \Illuminate\Support\Facades\Cache::remember('satkers_not_3500', 3600, function() {
            return MasterSatker::where('kode_satker', '!=', '3500')->get();
        });
        $saqWeights = $this->getSaqCategoryWeights($period->id_periode);
        $dynamicMaxScores = $this->getDynamicMaxScores();
        $allIndicators = \Illuminate\Support\Facades\Cache::remember('indicators_tree', 3600, function() {
            return MasterIndikator::with(['subIndikators.pertanyaans'])->get();
        });
        $indicators = $allIndicators->filter(function($ind) use ($dynamicMaxScores, $saqWeights) {
            $hasMaxScore = ($dynamicMaxScores[$ind->id_indicators] ?? 0) > 0;
            $hasWeight = !empty($saqWeights) ? isset($saqWeights[$ind->id_indicators]) : true;
            return $hasMaxScore && $hasWeight;
        })->values();
        $indicatorScoresPerKabkot = $this->calculateIndicatorScoresPerSatker($period->id_periode);
        $chartKabkotKode = $kodeSatker;
        $indicatorScores = $indicatorScoresPerKabkot[$chartKabkotKode] ?? [];

        $submissions = Submission::with(['result.satker', 'visitasi', 'interview'])
            ->whereHas('result', fn($q) => $q->where('id_periode', $period->id_periode))
            ->get();

        $satkerScores = [];
        $satkerRawScores = [];
        foreach ($submissions as $sub) {
            $satkerName = $sub->result->satker->nama_satker ?? '';
            $cleanName = trim(str_replace('BPS', '', $satkerName));

            $score = $this->calculateFinalScoreFromSubmission($sub);
            $status = $score > 0 ? $this->getPredikat($score) : 'BELUM DINILAI';
            $color = RegionMapHelper::scoreToColor($score);

            $satkerScores[$cleanName] = [
                'score' => $score,
                'status' => $status,
                'color' => $color,
            ];

            if ($score > 0) {
                $satkerRawScores[$satkerName] = $score;
            }
        }

        $regionScores = RegionMapHelper::buildRegionScores($satkerRawScores);

        $latestKabagKomentar = KabagKomentar::where('id_periode', $period->id_periode)
            ->with('user')
            ->orderByDesc('created_at')
            ->first();
        $kabagKomentar = $latestKabagKomentar;

        $pembobotan = $this->getPembobotanConfig($period->id_periode);

        return compact(
            'period',
            'periods',
            'submission',
            'result',
            'finalScore',
            'predikat',
            'rank',
            'allResults',
            'avgProv',
            'indicators',
            'indicatorScores',
            'regionScores',
            'satkerScores',
            'chartYears',
            'chartScores',
            'chartAvgs',
            'latestKabagKomentar',
            'kabagKomentar',
            'isPublished',
            'availableYears',
            'chartStartYear',
            'chartEndYear',
            'satkers',
            'indicatorScoresPerKabkot',
            'chartKabkotKode',
            'pembobotan'
        );
    }

    /**
     * Mengurutkan koleksi Submission atau Result dengan multi-tier sorting (Tie-Breaker).
     *
     * Prioritas Pengurutan:
     * 1. Nilai Akhir (calculated_final / final_score)
     * 2. Tie-Breaker: Tahapan dengan bobot 0% (misal jika Visitasi 0%, nilai Visitasi jadi penentu jika Nilai Akhir sama)
     * 3. Tahapan lainnya berdasarkan bobot tertinggi / urutan tahapan
     * 4. Kode Satker ascending (penentu akhir yang deterministik)
     *
     * @param \Illuminate\Support\Collection $submissions
     * @param int|\App\Models\MasterPembobotan|null $periodId
     * @param string $direction 'desc'|'asc'
     * @return \Illuminate\Support\Collection
     */
    public function sortSubmissions(\Illuminate\Support\Collection $submissions, $periodId = null, string $direction = 'desc'): \Illuminate\Support\Collection
    {
        $config = $periodId instanceof \App\Models\MasterPembobotan
            ? $periodId
            : $this->getPembobotanConfig(is_numeric($periodId) ? (int)$periodId : null);
        $wSaq = (float)($config->weight_saq ?? 30.0);
        $wVisit = (float)($config->weight_visitasi ?? 40.0);
        $wWawancara = (float)($config->weight_wawancara ?? 30.0);
        $stageOrder = $config->resolvedStageOrder();

        $weights = [
            'saq' => $wSaq,
            'visitasi' => $wVisit,
            'wawancara' => $wWawancara,
        ];

        // Pisahkan tahapan berbobot 0% dan > 0%
        $zeroWeightStages = [];
        $nonZeroStages = [];
        foreach ($stageOrder as $stg) {
            if (($weights[$stg] ?? 0.0) <= 0.001) {
                $zeroWeightStages[] = $stg;
            } else {
                $nonZeroStages[] = $stg;
            }
        }

        // Sort nonZeroStages berdasarkan bobot terbesar
        usort($nonZeroStages, function($a, $b) use ($weights) {
            return ($weights[$b] ?? 0) <=> ($weights[$a] ?? 0);
        });

        $tieBreakerOrder = array_merge($zeroWeightStages, $nonZeroStages);

        return $submissions->sort(function ($a, $b) use ($direction, $tieBreakerOrder) {
            $finalA = (float) ($a->calculated_final ?? ($a->result?->final_score ?? ($a->final_score ?? 0.0)));
            $finalB = (float) ($b->calculated_final ?? ($b->result?->final_score ?? ($b->final_score ?? 0.0)));

            if (abs($finalA - $finalB) > 0.0001) {
                return $direction === 'asc' ? ($finalA <=> $finalB) : ($finalB <=> $finalA);
            }

            $getStageScore = function ($item, string $stageKey): float {
                if ($item instanceof \App\Models\Result) {
                    return match ($stageKey) {
                        'saq' => (float) ($item->ques_score ?? 0.0),
                        'visitasi' => (float) ($item->visit_score ?? 0.0),
                        'wawancara' => (float) ($item->interview_score ?? 0.0),
                        default => 0.0,
                    };
                }

                return match ($stageKey) {
                    'saq' => (float) ($item->result?->ques_score ?? 0.0),
                    'visitasi' => (float) ($item->visitasi?->visit_score ?? ($item->result?->visit_score ?? 0.0)),
                    'wawancara' => (float) ($item->interview?->interview_score ?? ($item->result?->interview_score ?? ($this->calculateInterviewScore($item->interview) ?? 0.0))),
                    default => 0.0,
                };
            };

            foreach ($tieBreakerOrder as $stageKey) {
                $scoreA = $getStageScore($a, $stageKey);
                $scoreB = $getStageScore($b, $stageKey);

                if (abs($scoreA - $scoreB) > 0.0001) {
                    return $direction === 'asc' ? ($scoreA <=> $scoreB) : ($scoreB <=> $scoreA);
                }
            }

            $kodeA = $a->result?->kode_satker ?? ($a->kode_satker ?? '');
            $kodeB = $b->result?->kode_satker ?? ($b->kode_satker ?? '');

            return $kodeA <=> $kodeB;
        })->values();
    }
}

