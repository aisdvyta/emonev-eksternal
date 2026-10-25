<?php

namespace App\Livewire\Evaluasi;

use App\Helpers\RegionMapHelper;
use App\Models\MasterIndikator;
use App\Models\MasterLink;
use App\Models\MasterPertanyaan;
use App\Models\MasterSatker;
use App\Models\Period;
use App\Models\Submission;
use App\Services\PenilaianService;
use Livewire\Component;

class SaqReviewSection extends Component
{
    public $periodeId;
    public $selectedTahun;
    public $chartKabkotKode;
    public $driveReviewLink = '';
    public $isEditingDrive = false;

    public function mount($periodeId = null)
    {
        if ($periodeId) {
            $this->periodeId = $periodeId;
            $period = Period::resolvePeriod($periodeId);
        } else {
            $period = Period::getActivePeriod();
            $this->periodeId = $period?->id_periode;
        }

        if ($period) {
            $this->selectedTahun = (string) ($period->tahun ?? \Carbon\Carbon::parse($period->periode_start)->year);
        }

        $this->syncDriveReviewLink();
    }

    public function updatedSelectedTahun($year)
    {
        $periods = Period::orderByDesc('periode_start')->get();
        $period = $periods->first(function ($p) use ($year) {
            $y = $p->tahun ?? ($p->periode_start ? (string) \Carbon\Carbon::parse($p->periode_start)->year : null);
            return (string) $y === (string) $year;
        });

        if ($period) {
            $this->periodeId = $period->id_periode;
        } else {
            $this->periodeId = null;
        }
        $this->syncDriveReviewLink();
        $this->dispatch('saqDataUpdated');
    }

    public function setChartKabkotKode($kode)
    {
        $this->chartKabkotKode = $kode;
    }

    public function syncDriveReviewLink()
    {
        if ($this->periodeId) {
            $masterLink = MasterLink::where('id_periode', $this->periodeId)->first();
            $this->driveReviewLink = $masterLink?->link_drive_review_saq ?? '';
        } else {
            $this->driveReviewLink = '';
        }
    }

    public function saveDriveReviewLink()
    {
        $role = auth()->user()?->role?->nama_role;
        if (!in_array($role, ['super_evaluator', 'evaluator', 'admin'])) {
            return;
        }

        $this->validate([
            'driveReviewLink' => ['nullable', 'regex:/^https?:\/\//i', 'url'],
        ], [
            'driveReviewLink.regex' => 'Isikan Link dengan benar',
            'driveReviewLink.url' => 'Isikan Link dengan benar',
        ]);

        if ($this->periodeId) {
            MasterLink::updateOrCreate(
                ['id_periode' => $this->periodeId],
                ['link_drive_review_saq' => $this->driveReviewLink]
            );

            $this->isEditingDrive = false;
            session()->flash('drive_link_status', 'Tautan Google Drive Review SAQ berhasil disimpan.');
        }
    }

    public function render()
    {
        $periods = Period::orderByDesc('periode_start')->get();
        $availableYears = $periods->map(function ($p) {
            return $p->tahun ?? ($p->periode_start ? (string) \Carbon\Carbon::parse($p->periode_start)->year : null);
        })->filter()->unique()->values()->toArray();

        $currentPeriod = $this->periodeId ? Period::find($this->periodeId) : Period::getActivePeriod();

        $satkers = \Illuminate\Support\Facades\Cache::remember('satkers_not_3500_by_kode', 3600, function () {
            return MasterSatker::where('kode_satker', '!=', '3500')->orderBy('kode_satker')->get();
        });

        $satkers = $satkers->sortBy('kode_satker')->values();

        if (!$this->chartKabkotKode) {
            $this->chartKabkotKode = $satkers->first()?->kode_satker ?? '3501';
        }

        $penilaianService = app(PenilaianService::class);

        // 1. Indikator dan skor capaian SAQ per Kab/Kota
        $allIndicators = \Illuminate\Support\Facades\Cache::remember('master_indicators_all', 3600, function () {
            return MasterIndikator::get();
        });

        $dynamicMaxScores = \Illuminate\Support\Facades\Cache::remember('dynamic_max_scores_by_ind', 3600, function () {
            return MasterPertanyaan::selectRaw('id_indicators, sum(bobot_pertanyaan) as max_score')
                ->groupBy('id_indicators')
                ->pluck('max_score', 'id_indicators')
                ->toArray();
        });

        $saqWeights = $currentPeriod ? $penilaianService->getSaqCategoryWeights($currentPeriod->id_periode) : [];

        $indicators = $allIndicators->filter(function ($ind) use ($dynamicMaxScores, $saqWeights) {
            $hasMaxScore = ($dynamicMaxScores[$ind->id_indicators] ?? 0) > 0;
            $hasWeight = !empty($saqWeights) ? isset($saqWeights[$ind->id_indicators]) : true;
            return $hasMaxScore && $hasWeight;
        })->values();

        // Ambil submission untuk periode ini
        $submissionsInPeriod = collect();
        $answersSummaryBySubmission = collect();

        if ($currentPeriod) {
            $submissionsInPeriod = Submission::with([
                    'result' => fn($q) => $q->select(['id_result', 'kode_satker', 'ques_score', 'id_periode'])
                ])
                ->whereHas('result', fn($q) => $q->where('id_periode', $currentPeriod->id_periode))
                ->get()
                ->keyBy(fn($s) => $s->result?->kode_satker);

            $submissionIds = $submissionsInPeriod->pluck('id_submission')->filter()->values();

            if ($submissionIds->isNotEmpty()) {
                $answersSummaryBySubmission = \App\Models\Answer::whereIn('id_submission', $submissionIds)
                    ->selectRaw('id_submission, id_indicators, sum(answer_score) as earned')
                    ->groupBy('id_submission', 'id_indicators')
                    ->get()
                    ->groupBy('id_submission');
            }
        }

        // Kalkulasi skor per indikator per Kab/Kota
        $indicatorScoresPerKabkot = [];
        foreach ($satkers as $satker) {
            $sub = $submissionsInPeriod->get($satker->kode_satker);

            // Penilaian SAQ dianggap selesai jika status final (5, 6, 7, 8) dan nilai ques_score sudah terisi
            $isFinished = false;
            if ($sub && in_array((int) $sub->status, [5, 6, 7, 8]) && $sub->result?->ques_score !== null) {
                $isFinished = true;
            }

            $earnedByIndicator = ($isFinished && isset($answersSummaryBySubmission[$sub->id_submission]))
                ? $answersSummaryBySubmission[$sub->id_submission]->pluck('earned', 'id_indicators')
                : collect();

            $indScores = [];
            foreach ($indicators as $ind) {
                if (!$isFinished) {
                    $indScores[$ind->id_indicators] = 0.0;
                } else {
                    $maxScore = $dynamicMaxScores[$ind->id_indicators] ?? 1.0;
                    $earned = (float) ($earnedByIndicator[$ind->id_indicators] ?? 0.0);
                    $indScores[$ind->id_indicators] = round(($earned / max($maxScore, 1.0)) * 100, 2);
                }
            }
            $indicatorScoresPerKabkot[$satker->kode_satker] = $indScores;
        }

        // 2. Data Skor dan Status Lolos/Gugur SAQ untuk Peta
        // Sesuai tema app.css:
        // Lolos: hijau (#388E3C)
        // Gugur: merah2 (#E82D1F)
        // Belum dinilai: abumuda (#D1D5D8)
        $COLOR_LOLOS = '#388E3C';
        $COLOR_GUGUR = '#E82D1F';
        $COLOR_BELUM = '#D1D5D8';

        $satkerScores = [];
        $satkerRawScores = [];
        $countLolos = 0;
        $countGugur = 0;
        $countBelum = 0;

        foreach ($satkers as $satker) {
            $sub = $submissionsInPeriod->get($satker->kode_satker);
            $satkerName = $satker->nama_satker;
            $cleanName = trim(str_replace('BPS', '', $satkerName));

            $score = 0.0;
            $status = 0;
            $isFinished = false;

            if ($sub && in_array((int) $sub->status, [5, 6, 7, 8]) && $sub->result?->ques_score !== null) {
                $score = (float) $sub->result->ques_score;
                $status = (int) $sub->status;
                $isFinished = true;
            }

            // Aturan KKM SAQ: Threshold = 80
            // Jika penilaian sudah final/selesai:
            if ($isFinished) {
                if ($status === 8 || $score < 80) {
                    $statusText = 'Gugur';
                    $color = $COLOR_GUGUR;
                    $countGugur++;
                } else {
                    $statusText = 'Lolos';
                    $color = $COLOR_LOLOS;
                    $countLolos++;
                }
            } else {
                $statusText = 'Belum Dinilai';
                $color = $COLOR_BELUM;
                $countBelum++;
            }

            $justDistrict = trim(str_replace(['Kabupaten', 'Kota', 'BPS'], '', $satkerName));
            $dataItem = [
                'score' => $score,
                'status' => $statusText,
                'color' => $color,
            ];

            $satkerScores[$cleanName] = $dataItem;
            $satkerScores[$satkerName] = $dataItem;
            $satkerScores[$justDistrict] = $dataItem;
            $satkerScores['Kabupaten ' . $justDistrict] = $dataItem;
            $satkerScores['Kota ' . $justDistrict] = $dataItem;

            if ($score > 0) {
                $satkerRawScores[$satkerName] = $score;
            }
        }

        $regionScores = RegionMapHelper::buildRegionScores($satkerRawScores);

        return view('livewire.evaluasi.saq-review-section', [
            'period' => $currentPeriod,
            'periods' => $periods,
            'availableYears' => $availableYears,
            'satkers' => $satkers,
            'indicators' => $indicators,
            'indicatorScoresPerKabkot' => $indicatorScoresPerKabkot,
            'satkerScores' => $satkerScores,
            'regionScores' => $regionScores,
            'countLolos' => $countLolos,
            'countGugur' => $countGugur,
            'countBelum' => $countBelum,
        ]);
    }
}
