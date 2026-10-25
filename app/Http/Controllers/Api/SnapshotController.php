<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Services\SnapshotImporter;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SnapshotController extends Controller
{
    public function store(Request $request, SnapshotImporter $importer)
    {
        $payload = $request->json()->all();

        if (! is_array($payload) || ! isset($payload['data']) || ! is_array($payload['data'])) {
            SyncLog::record('snapshot', false, 'payload tidak valid', $request->ip());

            return response()->json(['ok' => false, 'error' => 'invalid_payload'], 422);
        }

        if (isset($payload['mode']) && ! in_array($payload['mode'], ['full', 'delta'], true)) {
            return response()->json(['ok' => false, 'error' => 'invalid_mode'], 422);
        }

        try {
            $summary = $importer->import($payload);
        } catch (InvalidArgumentException $e) {
            SyncLog::record('snapshot', false, $e->getMessage(), $request->ip());

            return response()->json(['ok' => false, 'error' => 'invalid_payload', 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            SyncLog::record('snapshot', false, 'kesalahan internal: '.class_basename($e), $request->ip());

            return response()->json(['ok' => false, 'error' => 'server_error'], 500);
        }

        SyncLog::record(
            'snapshot',
            true,
            ($payload['mode'] ?? 'delta').' '.json_encode($summary),
            $request->ip()
        );

        return response()->json([
            'ok' => true,
            'sync_id' => $payload['sync_id'] ?? null,
            'summary' => $summary,
        ]);
    }
}
