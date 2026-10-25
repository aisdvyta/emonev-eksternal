<?php

namespace App\Http\Controllers\Dokumen;

use App\Http\Controllers\Controller;
use App\Models\SkSertifikat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DokumenDownloadController extends Controller
{
    /**
     * Download or preview a certificate securely using its UUID.
     */
    public function download(Request $request, $uuid)
    {
        $skSertifikat = SkSertifikat::where('uuid', $uuid)->firstOrFail();

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        $submission = $skSertifikat->submission;
        if (! $submission) {
            // Sertifikat Global
            $linkSertifikat = 'uploads/sertifikat-global/' . $skSertifikat->uuid . '.pdf';
            $filePath = storage_path('app/' . $linkSertifikat);

            if (! file_exists($filePath)) {
                abort(404, 'Berkas sertifikat tidak ditemukan di server.');
            }

            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $year = $skSertifikat->period->tahun ?? '';
            $downloadName = 'Sertifikat Evaluasi KIP ' . $year . '.' . $extension;
        } else {
            // Sertifikat Kabkot/Satker
            $submissionOwner = $submission->user;
            if (! $submissionOwner) {
                abort(404, 'Pemilik berkas tidak ditemukan.');
            }

            // Authorization: operators and verifikators can only access certificates of their own satker (IDOR protection)
            \Illuminate\Support\Facades\Gate::authorize('downloadSertifikat', $submission);

            $linkSertifikat = 'uploads/sertifikat/' . $skSertifikat->uuid . '.pdf';
            $filePath = storage_path('app/' . $linkSertifikat);

            if (! file_exists($filePath)) {
                abort(404, 'Berkas sertifikat tidak ditemukan di server.');
            }

            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $satkerName = $submissionOwner->masterSatker->nama_satker ?? 'Satker Tidak Diketahui';
            $year = $skSertifikat->period->tahun ?? '';
            $downloadName = 'Sertifikat Evaluasi KIP ' . $year . '_' . $satkerName . '.' . $extension;
        }

        // If 'download=1' query param is passed, trigger download. Otherwise, preview inline.
        if ($request->has('download')) {
            return response()->download($filePath, $downloadName);
        }

        // Preview inline
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'zip' => 'application/zip',
        ];
        $contentType = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';

        return response()->file($filePath, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
        ]);
    }

    /**
     * Download or preview a SK file securely using its UUID.
     */
    public function downloadSk(Request $request, $uuid)
    {
        $skSertifikat = SkSertifikat::where('uuid', $uuid)->firstOrFail();

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        $submission = $skSertifikat->submission;
        if (! $submission) {
            // SK Global
            $linkSk = 'uploads/sk-global/' . $skSertifikat->uuid . '.pdf';
            $filePath = storage_path('app/' . $linkSk);

            if (! file_exists($filePath)) {
                abort(404, 'Berkas SK tidak ditemukan di server.');
            }

            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $year = $skSertifikat->period->tahun ?? '';
            $downloadName = 'SK Evaluasi KIP ' . $year . '.' . $extension;
        } else {
            // SK Kabkot/Satker
            $submissionOwner = $submission->user;
            if (! $submissionOwner) {
                abort(404, 'Pemilik berkas tidak ditemukan.');
            }

            // Authorization: operators and verifikators can only access SK files of their own satker (IDOR protection)
            \Illuminate\Support\Facades\Gate::authorize('downloadSertifikat', $submission);

            $linkSk = 'uploads/sk/' . $skSertifikat->uuid . '.pdf';
            $filePath = storage_path('app/' . $linkSk);

            if (! file_exists($filePath)) {
                abort(404, 'Berkas SK tidak ditemukan di server.');
            }

            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $satkerName = $submissionOwner->masterSatker->nama_satker ?? 'Satker Tidak Diketahui';
            $year = $skSertifikat->period->tahun ?? '';
            $downloadName = 'SK Evaluasi KIP ' . $year . '_' . $satkerName . '.' . $extension;
        }

        // If 'download=1' query param is passed, trigger download. Otherwise, preview inline.
        if ($request->has('download')) {
            return response()->download($filePath, $downloadName);
        }

        // Preview inline
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'zip' => 'application/zip',
        ];
        $contentType = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';

        return response()->file($filePath, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
        ]);
    }
}
