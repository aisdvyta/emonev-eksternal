<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\MasterSatker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserController extends Controller
{
    /**
     * Tentukan route tujuan redirect sesuai konteks request.
     */
    protected function getRedirectRoute(Request $request): string
    {
        $currentRoute = $request->route()?->getName() ?? '';
        if (str_contains($currentRoute, 'super-evaluator')) {
            return 'super-evaluator.users';
        }
        if (str_contains($currentRoute, 'admin')) {
            return 'admin.users';
        }
        return 'users.index';
    }

    /**
     * Tampilkan halaman manajemen user.
     */
    public function users(Request $request)
    {
        $roles   = Role::all();
        $satkers = MasterSatker::orderBy('kode_satker')->get();

        return view('pages.master.user', compact('roles', 'satkers'));
    }

    public function index(Request $request) { return $this->users($request); }
    public function store(Request $request) { return $this->storeUser($request); }
    public function update(Request $request, $id) { return $this->updateUser($request, $id); }
    public function destroy(Request $request, $id) { return $this->destroyUser($request, $id); }
    public function import(Request $request) { return $this->importUser($request); }

    /**
     * Cari profil pegawai BPS via SSO REST API (Fase 6)
     */
    public function searchPegawai(Request $request, \App\Services\Auth\SsoService $ssoService)
    {
        $query = trim($request->input('nip') ?? ($request->input('query') ?? ''));

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'NIP, email, atau username harus diisi.',
            ], 422);
        }

        try {
            if (str_contains($query, '@')) {
                $data = $ssoService->getPegawaiByEmail($query);
            } elseif (preg_match('/^[0-9]+$/', $query)) {
                $data = $ssoService->getPegawaiByNip($query);
            } else {
                $data = $ssoService->getPegawaiByUsername($query);
            }

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pegawai: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simpan user baru.
     */
    public function storeUser(Request $request)
    {
        $role = Role::find($request->id_role);
        $isExternal = $role && str_contains(strtolower($role->name), 'eksternal');

        $request->validate([
            'nama_user'   => 'nullable|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'id_role'     => 'required|exists:roles,id',
            'kode_satker' => 'nullable|exists:master_satker,kode_satker',
            'password'    => $isExternal ? 'required|string|min:6' : 'nullable|string|min:6',
            'nip_panjang' => 'nullable|string|max:18',
            'nip_pendek'  => 'nullable|string|max:9',
            'foto_url'    => 'nullable|string|max:255',
        ], [
            'password.required' => 'Password wajib diisi untuk pengguna dengan role Penilai Eksternal.',
            'password.min'      => 'Password minimal 6 karakter.',
        ]);

        $namaUser = trim($request->nama_user ?? '');
        if (empty($namaUser)) {
            $namaUser = explode('@', $request->email)[0];
        }

        $passwordRaw = trim($request->password ?? '');
        if (empty($passwordRaw)) {
            $passwordRaw = \Illuminate\Support\Str::random(16);
        }

        DB::transaction(function () use ($request, $namaUser, $passwordRaw) {
            $user = User::create([
                'nama_user'   => $namaUser,
                'email'       => $request->email,
                'kode_satker' => $request->kode_satker ?: null,
                'password'    => Hash::make($passwordRaw),
                'nip_panjang' => $request->nip_panjang ?: null,
                'nip_pendek'  => $request->nip_pendek ?: null,
                'foto_url'    => $request->foto_url ?: null,
            ]);

            $user->assignRole((int) $request->id_role);
        });

        return redirect()->route($this->getRedirectRoute($request))->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Update data user.
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nama_user'   => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $id . ',id_user',
            'id_role'     => 'required|exists:roles,id',
            'kode_satker' => 'nullable|exists:master_satker,kode_satker',
            'nip_panjang' => 'nullable|string|max:18',
            'nip_pendek'  => 'nullable|string|max:9',
            'foto_url'    => 'nullable|string|max:255',
        ]);

        $data = [
            'nama_user'   => $request->nama_user,
            'email'       => $request->email,
            'kode_satker' => $request->kode_satker ?: null,
            'nip_panjang' => $request->nip_panjang,
            'nip_pendek'  => $request->nip_pendek,
            'foto_url'    => $request->foto_url,
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6']);
            $data['password'] = Hash::make($request->password);
        }

        DB::transaction(function () use ($user, $data, $request) {
            $user->update($data);
            $user->syncRoles([(int) $request->id_role]);
        });

        return redirect()->route($this->getRedirectRoute($request))->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Hapus user.
     */
    public function destroyUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route($this->getRedirectRoute($request))->with('success', 'User berhasil dihapus.');
    }

    /**
     * Import users dari file Excel.
     */
    public function importUser(Request $request)
    {
        // 1. Jika request berasal dari Modal UI Interaktif (JSON Payload)
        if ($request->isJson() || $request->has('users')) {
            $request->validate([
                'users' => 'required|array|min:1',
                'users.*.nama_user'   => 'nullable|string|max:255',
                'users.*.email'       => 'required|email',
                'users.*.password'    => 'nullable|string',
                'users.*.nip_panjang' => 'nullable|string',
                'users.*.nip_pendek'  => 'nullable|string',
                'users.*.kode_satker' => 'nullable|string',
                'users.*.role'        => 'required|string',
            ]);

            $usersData = $request->input('users');
            $successCount = 0;
            $skipCount = 0;

            DB::beginTransaction();
            try {
                $satkerMap = MasterSatker::pluck('kode_satker')->flip()->toArray();
                $roles = Role::all();
                $roleMap = [];
                foreach ($roles as $r) {
                    $roleMap[strtolower($r->name)] = $r->id;
                    $roleMap[str_replace(' ', '_', strtolower($r->name))] = $r->id;
                }

                $inputEmails = array_map(fn($u) => strtolower(trim($u['email'] ?? '')), $usersData);
                $existingEmails = User::whereIn('email', array_filter(array_unique($inputEmails)))
                    ->pluck('email')
                    ->map(fn($e) => strtolower(trim($e)))
                    ->flip()
                    ->toArray();

                foreach ($usersData as $u) {
                    $email = trim($u['email'] ?? '');
                    if (!$email) {
                        $skipCount++;
                        continue;
                    }

                    $lowerEmail = strtolower($email);
                    if (isset($existingEmails[$lowerEmail])) {
                        $skipCount++;
                        continue;
                    }
                    $existingEmails[$lowerEmail] = true;

                    $rawRole = strtolower(trim($u['role'] ?? ''));
                    $normalizedRole = str_replace(' ', '_', $rawRole);
                    $roleId = $roleMap[$normalizedRole] ?? ($roleMap[$rawRole] ?? null);

                    if (!$roleId) {
                        $skipCount++;
                        continue;
                    }

                    $kodeSatker = trim($u['kode_satker'] ?? '');
                    $validSatker = ($kodeSatker && isset($satkerMap[$kodeSatker])) ? $kodeSatker : null;
                    $pwd = !empty($u['password']) ? $u['password'] : 'password123';

                    $user = User::create([
                        'nama_user'   => !empty($u['nama_user']) ? trim($u['nama_user']) : explode('@', $email)[0],
                        'email'       => $email,
                        'password'    => Hash::make($pwd),
                        'kode_satker' => $validSatker,
                        'nip_panjang' => !empty($u['nip_panjang']) ? trim($u['nip_panjang']) : null,
                        'nip_pendek'  => !empty($u['nip_pendek']) ? trim($u['nip_pendek']) : null,
                    ]);

                    $user->assignRole($roleId);
                    $successCount++;
                }

                DB::commit();

                return response()->json([
                    'success'  => true,
                    'message'  => "Berhasil mengimpor {$successCount} user." . ($skipCount > 0 ? " ({$skipCount} baris dilewati karena duplikat/invalid)" : ""),
                    'imported' => $successCount,
                    'skipped'  => $skipCount,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memproses import data: ' . $e->getMessage()
                ], 500);
            }
        }

        // 2. Fallback jika upload file Excel secara langsung (Form multipart)
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        $file = $request->file('excel_file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);

            if (count($rows) < 2) {
                return redirect()->route($this->getRedirectRoute($request))->with('error', 'File Excel kosong atau format tidak sesuai.');
            }

            $successCount = 0;
            $skipCount = 0;

            DB::beginTransaction();

            $satkerMap = MasterSatker::pluck('kode_satker')->flip()->toArray();
            $roleMap   = Role::pluck('id', 'name')->toArray();

            $emailsInSheet = [];
            for ($i = 1; $i < count($rows); $i++) {
                if (!empty($rows[$i][3])) {
                    $emailsInSheet[] = strtolower(trim((string)$rows[$i][3]));
                }
            }
            $existingEmails = !empty($emailsInSheet)
                ? User::whereIn('email', array_unique($emailsInSheet))->pluck('email')->map(fn($e) => strtolower(trim($e)))->flip()->toArray()
                : [];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                $nipPanjang = !empty($row[0]) ? trim((string)$row[0]) : null;
                $nipPendek  = !empty($row[1]) ? trim((string)$row[1]) : null;
                $nama       = !empty($row[2]) ? trim((string)$row[2]) : null;
                $email      = !empty($row[3]) ? trim((string)$row[3]) : null;
                $roleName   = !empty($row[4]) ? strtolower(trim((string)$row[4])) : null;
                $kodeSatker = !empty($row[5]) ? trim((string)$row[5]) : null;
                $password   = !empty($row[6]) ? trim((string)$row[6]) : null;

                if (!$email) {
                    $skipCount++;
                    continue;
                }

                $lowerEmail = strtolower($email);
                if (isset($existingEmails[$lowerEmail])) {
                    $skipCount++;
                    continue;
                }
                $existingEmails[$lowerEmail] = true;

                $roleId = $roleMap[$roleName] ?? null;
                if (!$roleId) {
                    $skipCount++;
                    continue;
                }

                $validSatker = ($kodeSatker && isset($satkerMap[$kodeSatker])) ? $kodeSatker : null;
                $pwdHashed   = Hash::make($password ?: 'bps123456');

                $user = User::create([
                    'nama_user'   => $nama ?: explode('@', $email)[0],
                    'email'       => $email,
                    'password'    => $pwdHashed,
                    'kode_satker' => $validSatker,
                    'nip_panjang' => $nipPanjang,
                    'nip_pendek'  => $nipPendek,
                ]);

                $user->assignRole($roleId);
                $successCount++;
            }

            DB::commit();

            return redirect()->route($this->getRedirectRoute($request))->with(
                'success',
                "Import selesai: {$successCount} user berhasil ditambahkan" . ($skipCount > 0 ? ", {$skipCount} baris dilewati (duplikat/tidak valid)." : ".")
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route($this->getRedirectRoute($request))->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }
}
