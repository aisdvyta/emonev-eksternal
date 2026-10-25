<?php

namespace App\Http\Controllers\Penilai;

use App\Helpers\SecureId;
use App\Http\Controllers\Controller;
use App\Models\InterviewEvaluator;
use App\Models\Period;
use App\Models\SubmissionEvaluator;
use App\Models\VisitasiEvaluator;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $period = Period::current();

        if (! $period) {
            return view('pages.dashboard', [
                'period' => null, 'assigned' => 0, 'done' => 0, 'pending' => 0,
                'saqCount' => 0, 'upcoming' => collect(), 'waitingSync' => 0,
            ]);
        }

        $visitRows = VisitasiEvaluator::query()
            ->where('id_user', $user->id_user)
            ->whereHas('visitasi.result', fn ($q) => $q->where('id_periode', $period->id_periode))
            ->with('visitasi.result.satker')
            ->get();

        $interviewRows = InterviewEvaluator::query()
            ->where('id_user', $user->id_user)
            ->whereHas('interview.result', fn ($q) => $q->where('id_periode', $period->id_periode))
            ->with('interview.result.satker')
            ->get();

        $isDone = fn ($r) => ! $r->is_draft && $r->score !== null;

        $assigned = $visitRows->count() + $interviewRows->count();
        $done = $visitRows->filter($isDone)->count() + $interviewRows->filter($isDone)->count();
        $waitingSync = $visitRows->where('pending_sync', true)->count() + $interviewRows->where('pending_sync', true)->count();

        $today = today();
        $upcoming = collect();
        foreach ($visitRows as $r) {
            if ($r->visitasi?->visit_at && $r->visitasi->visit_at->gte($today) && ! $r->visitasi->is_final) {
                $upcoming->push([
                    'type' => 'Visitasi',
                    'date' => $r->visitasi->visit_at,
                    'satker' => $r->visitasi->result?->satker?->nama_satker ?? '-',
                    'mode' => $r->visitasi->visit_mode,
                    'url' => route('penilai-eksternal.review-visitasi', SecureId::encrypt($r->id_visit)),
                ]);
            }
        }
        foreach ($interviewRows as $r) {
            if ($r->interview?->interview_at && $r->interview->interview_at->gte($today) && ! $r->interview->is_final) {
                $upcoming->push([
                    'type' => 'Wawancara',
                    'date' => $r->interview->interview_at,
                    'satker' => $r->interview->result?->satker?->nama_satker ?? '-',
                    'mode' => null,
                    'url' => route('penilai-eksternal.review-wawancara', SecureId::encrypt($r->id_interview)),
                ]);
            }
        }

        $saqCount = SubmissionEvaluator::query()
            ->where('id_user', $user->id_user)
            ->whereIn('id_submission', function ($q) use ($period) {
                $q->select('submissions.id_submission')->from('submissions')
                    ->join('result', 'result.id_result', '=', 'submissions.id_result')
                    ->where('result.id_periode', $period->id_periode)
                    ->where('submissions.status', '>=', 3);
            })->count();

        return view('pages.dashboard', [
            'period' => $period,
            'assigned' => $assigned,
            'done' => $done,
            'pending' => $assigned - $done,
            'saqCount' => $saqCount,
            'waitingSync' => $waitingSync,
            'upcoming' => $upcoming->sortBy('date')->take(6)->values(),
        ]);
    }
}
