<?php

namespace App\Http\Controllers\Dokumen;

use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Models\Result;
use App\Models\Submission;
use App\Models\User;
use App\Models\SkSertifikat;
use App\Helpers\SecureId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkSertifikatController extends Controller
{
    /**
     * Tentukan route prefix redirect sesuai konteks request/role.
     */
    protected function getRoutePrefix(Request $request): string
    {
        $currentRoute = $request->route()?->getName() ?? '';
        if (str_contains($currentRoute, 'super-evaluator')) {
            return 'super-evaluator';
        }
        if (str_contains($currentRoute, 'evaluator')) {
            return 'evaluator';
        }
        return 'dokumen';
    }

    /**
     * Halaman SK & Sertifikat utama (adaptif: jika evaluator diarahkan ke manage-sk, jika operator/verifikator ke list unduh).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $roleName = $user?->role?->nama_role;

        if (in_array($roleName, ['evaluator', 'super_evaluator'])) {
            return $this->manageSk($request);
        }

        return $this->skSertifikat($request);
    }

    /**
     * Tampilkan halaman unduh SK & Sertifikat untuk Operator & Verifikator.
     */
    public function skSertifikat(Request $request)
    {
        $user = Auth::user();
        $satkerCode = $user?->kode_satker;

        // Operator di satker yang sama (jika user adalah verifikator)
        $operator = null;
        if ($user?->role?->nama_role === 'verifikator') {
            $operator = User::role('operator')
                ->where('kode_satker', $satkerCode)
                ->first();
        }

        $periods = Period::with('skSertifikat')->orderByDesc('periode_start')->get();

        $submissions = collect();
        if ($satkerCode) {
            $submissions = Submission::with(['result', 'skSertifikat'])
                ->whereHas('result', fn($q) => $q->where('kode_satker', $satkerCode))
                ->get()
                ->keyBy(fn($sub) => $sub->result ? $sub->result->id_periode : 0);
        }

        return view('pages.dokumen.sk-sertifikat', compact('periods', 'submissions', 'operator'));
    }

    /**
     * Tampilkan halaman manajemen SK & Sertifikat untuk Evaluator / Super Evaluator.
     */
    public function manageSk(Request $request, $id_periode = null)
    {
        $periods = Period::with('skSertifikat')->orderByDesc('periode_start')->get();

        if ($id_periode) {
            if (!is_numeric($id_periode)) {
                $id_periode = SecureId::decrypt($id_periode);
            }
        }

        $selectedPeriodId = $id_periode;
        $period = Period::resolvePeriod($selectedPeriodId);

        if (!$period) {
            return back()->with('error', 'Tidak ada periode aktif.');
        }

        $period->loadMissing('skSertifikat');

        $operators = User::with(['masterSatker', 'submissions' => function ($q) use ($period) {
            $q->whereHas('result', fn($sq) => $sq->where('id_periode', $period->id_periode))->with(['result', 'skSertifikat']);
        }])->role('operator')->get();

        return view('pages.dokumen.manage-sk', compact('period', 'periods', 'operators'));
    }

    /**
     * Simpan / Perbarui file SK & Sertifikat per Satker.
     */
    public function saveSk(Request $request)
    {
        $request->validate([
            'id_periode'      => 'required|exists:periods,id_periode',
            'id_user'         => 'required|exists:users,id_user',
            'link_sk'         => 'nullable|file|mimes:pdf|max:10240',
            'link_sertifikat' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $periodId = $request->input('id_periode');
        $userId   = $request->input('id_user');

        $userObj = User::findOrFail($userId);
        $result  = Result::firstOrCreate([
            'kode_satker' => $userObj->kode_satker,
            'id_periode'  => $periodId,
        ]);
        $submission = Submission::firstOrCreate(
            ['id_result' => $result->id_result],
            ['id_operator' => [['id' => $userObj->id_user, 'nama' => $userObj->nama_user]], 'status' => 0]
        );

        $skSertifikat = SkSertifikat::firstOrCreate([
            'id_submission' => $submission->id_submission,
            'id_periode'    => $periodId,
        ]);

        if ($request->hasFile('link_sk')) {
            $this->storeFile($request->file('link_sk'), storage_path('app/uploads/sk'), $skSertifikat->uuid);
        }

        if ($request->hasFile('link_sertifikat')) {
            $this->storeFile($request->file('link_sertifikat'), storage_path('app/uploads/sertifikat'), $skSertifikat->uuid);
        }

        $skSertifikat->save();

        $prefix = $this->getRoutePrefix($request);
        $routeName = ($prefix === 'dokumen') ? 'dokumen.manage-sk' : ($prefix . '.manage-sk');

        return redirect()
            ->route($routeName, ['id_periode' => SecureId::encrypt($periodId)])
            ->with('success', 'File berhasil diunggah dan disimpan untuk Satker tersebut.');
    }

    /**
     * Hapus file SK atau Sertifikat per Satker.
     */
    public function deleteSk(Request $request)
    {
        $request->validate([
            'id_periode' => 'required|exists:periods,id_periode',
            'id_user'    => 'required|exists:users,id_user',
            'type'       => 'required|in:sk,sertifikat',
        ]);

        $periodId = $request->input('id_periode');
        $userId   = $request->input('id_user');
        $type     = $request->input('type');

        $userObj   = User::findOrFail($userId);
        $submission = Submission::whereHas('result', fn($q) => $q->where('kode_satker', $userObj->kode_satker)->where('id_periode', $periodId))->first();

        if ($submission) {
            $skSertifikat = SkSertifikat::where('id_submission', $submission->id_submission)->first();
            if ($skSertifikat) {
                $dir      = $type === 'sk' ? 'sk' : 'sertifikat';
                $filePath = storage_path("app/uploads/{$dir}/" . $skSertifikat->uuid . '.pdf');
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                $msg = $type === 'sk' ? 'File SK berhasil dihapus.' : 'File Sertifikat berhasil dihapus.';
                if (!$skSertifikat->has_sk && !$skSertifikat->has_sertifikat) {
                    $skSertifikat->delete();
                }
            } else {
                $msg = 'Data berkas tidak ditemukan.';
            }
        } else {
            $msg = 'Data pengajuan tidak ditemukan.';
        }

        $prefix = $this->getRoutePrefix($request);
        $routeName = ($prefix === 'dokumen') ? 'dokumen.manage-sk' : ($prefix . '.manage-sk');

        return redirect()
            ->route($routeName, ['id_periode' => SecureId::encrypt($periodId)])
            ->with('success', $msg);
    }

    /**
     * [SIMPLE MODE] Upload file SK global (satu file untuk semua kabkot).
     */
    public function saveSimpleFile(Request $request)
    {
        $request->validate([
            'id_periode'  => 'required|exists:periods,id_periode',
            'type'        => 'required|in:sk',
            'file_upload' => 'required|file|mimes:pdf|max:20480',
        ]);

        $periodId = $request->input('id_periode');
        $file     = $request->file('file_upload');

        $skSertifikat = SkSertifikat::firstOrCreate([
            'id_periode'    => $periodId,
            'id_submission' => null,
        ]);

        $this->storeFile($file, storage_path('app/uploads/sk-global'), $skSertifikat->uuid);
        $skSertifikat->save();

        $prefix = $this->getRoutePrefix($request);
        $routeName = ($prefix === 'dokumen') ? 'dokumen.manage-sk' : ($prefix . '.manage-sk');

        return redirect()
            ->route($routeName, ['id_periode' => SecureId::encrypt($periodId)])
            ->with('success', 'File SK berhasil diunggah untuk periode ini.');
    }

    /**
     * [SIMPLE MODE] Hapus file SK global.
     */
    public function deleteSimpleFile(Request $request)
    {
        $request->validate([
            'id_periode' => 'required|exists:periods,id_periode',
            'type'       => 'required|in:sk',
        ]);

        $periodId     = $request->input('id_periode');
        $skSertifikat = SkSertifikat::where('id_periode', $periodId)->whereNull('id_submission')->first();

        if ($skSertifikat) {
            $filePath = storage_path('app/uploads/sk-global/' . $skSertifikat->uuid . '.pdf');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            if (!$skSertifikat->has_sk && !$skSertifikat->has_sertifikat) {
                $skSertifikat->delete();
            }
        }

        $prefix = $this->getRoutePrefix($request);
        $routeName = ($prefix === 'dokumen') ? 'dokumen.manage-sk' : ($prefix . '.manage-sk');

        return redirect()
            ->route($routeName, ['id_periode' => SecureId::encrypt($periodId)])
            ->with('success', 'File SK berhasil dihapus.');
    }

    /**
     * Tampilkan halaman pengelolaan link buku panduan.
     */
    public function managePanduan(Request $request)
    {
        $linkPanduan = $this->readPanduanLink();
        return view('pages.dokumen.manage-panduan', compact('linkPanduan'));
    }

    /**
     * Simpan pembaruan link buku panduan secara global.
     */
    public function savePanduan(Request $request)
    {
        $request->validate([
            'link_panduan' => ['nullable', 'regex:/^https?:\/\//i', 'url', 'max:500'],
        ], [
            'link_panduan.regex' => 'Isikan Link dengan benar',
            'link_panduan.url' => 'Isikan Link dengan benar',
        ]);

        $url = trim($request->input('link_panduan', ''));
        file_put_contents($this->panduanFilePath(), $url);

        // Simpan ke master_link di database
        $periods = Period::all();
        foreach ($periods as $p) {
            \App\Models\MasterLink::updateOrCreate(
                ['id_periode' => $p->id_periode],
                ['link_panduan' => $url]
            );
        }

        $prefix = $this->getRoutePrefix($request);
        $routeName = ($prefix === 'dokumen') ? 'dokumen.manage-panduan' : ($prefix . '.manage-panduan');

        return redirect()
            ->route($routeName)
            ->with('success', 'Link panduan berhasil disimpan ke database.');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Simpan file unggahan ke direktori tujuan dengan nama file UUID.
     */
    private function storeFile(\Illuminate\Http\UploadedFile $file, string $destDir, string $uuid): void
    {
        $filename = $uuid . '.pdf';
        $file->move($destDir, $filename);
    }

    private function panduanFilePath(): string
    {
        return storage_path('app/panduan_url.txt');
    }

    private function readPanduanLink(): string
    {
        $activePeriod = Period::getActivePeriod();
        $link = $activePeriod?->masterLink?->link_panduan;
        if ($link) {
            return $link;
        }

        $path = $this->panduanFilePath();
        if (file_exists($path)) {
            return trim(file_get_contents($path));
        }
        return env('PANDUAN_DRIVE_URL', '');
    }
}
