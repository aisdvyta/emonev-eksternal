<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InterviewEvaluator;
use App\Models\VisitasiEvaluator;

class PingController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'ok' => true,
            'app' => 'emonev-eksternal',
            'api_version' => 1,
            'time' => now()->toIso8601String(),
            'pending_scores' => VisitasiEvaluator::where('pending_sync', true)->count()
                + InterviewEvaluator::where('pending_sync', true)->count(),
        ]);
    }
}
