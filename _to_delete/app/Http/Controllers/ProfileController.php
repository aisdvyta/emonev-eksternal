<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil pengguna yang sedang login.
     */
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $roleName = $user?->role?->nama_role;
        $homeRoute = match ($roleName) {
            'admin' => route('admin.index'),
            'evaluator' => route('evaluator.index'),
            'super_evaluator' => route('super-evaluator.index'),
            'operator' => route('operator.index'),
            'verifikator' => route('verifikator.index'),
            'penilai_eksternal' => route('penilai-eksternal.index'),
            default => route('dashboard'),
        };

        return view('profile.index', compact('user', 'homeRoute'));
    }

    /**
     * Perbarui data profil dan/atau kata sandi (Unified Form).
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isEksternal = $user?->role?->nama_role === 'penilai_eksternal';

        $rules = [
            'nama_user' => ['required', 'string', 'max:255', Rule::unique('users', 'nama_user')->ignore($user->id_user, 'id_user')],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id_user, 'id_user')],
        ];

        if (!$isEksternal) {
            $rules['nip_panjang'] = ['nullable', 'string', 'max:18', 'regex:/^[0-9]*$/'];
            $rules['nip_pendek']  = ['nullable', 'string', 'max:9', 'regex:/^[0-9]*$/'];
        }

        $messages = [
            'nama_user.required'                => 'Nama lengkap wajib diisi.',
            'nama_user.unique'                  => 'Nama pengguna sudah digunakan.',
            'email.required'                    => 'Alamat email wajib diisi.',
            'email.email'                       => 'Format email tidak valid.',
            'email.unique'                      => 'Email sudah terdaftar oleh pengguna lain.',
            'nip_panjang.regex'                 => 'NIP Panjang hanya boleh berisi angka.',
            'nip_panjang.max'                   => 'NIP Panjang maksimal 18 digit.',
            'nip_pendek.regex'                  => 'NIP Pendek hanya boleh berisi angka.',
            'nip_pendek.max'                    => 'NIP Pendek maksimal 9 digit.',
            'current_password.required'         => 'Password saat ini wajib diisi untuk mengubah password.',
            'current_password.current_password' => 'Password saat ini yang Anda masukkan salah.',
            'password.required'                 => 'Password baru wajib diisi.',
            'password.min'                      => 'Password baru minimal 6 karakter.',
            'password.confirmed'                => 'Konfirmasi password baru tidak cocok.',
        ];

        $isPasswordFilled = $request->filled('password') || $request->filled('current_password') || $request->filled('password_confirmation');

        if ($isPasswordFilled) {
            if (!empty($user->password)) {
                $rules['current_password'] = ['required', 'current_password'];
            }
            $rules['password'] = ['required', 'string', 'min:6', 'confirmed'];
        }

        $request->validate($rules, $messages);

        $user->nama_user = trim($request->nama_user);
        $user->email     = trim($request->email);

        if (!$isEksternal) {
            $user->nip_panjang = $request->filled('nip_panjang') ? trim($request->nip_panjang) : null;
            $user->nip_pendek  = $request->filled('nip_pendek') ? trim($request->nip_pendek) : null;
        }

        if ($isPasswordFilled && $request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $message = ($isPasswordFilled && $request->filled('password'))
            ? 'Profil dan kata sandi berhasil diperbarui.'
            : 'Profil berhasil diperbarui.';

        return redirect()->route('profile.show')->with('toast', [
            'type'    => 'success',
            'message' => $message,
        ])->with('success', $message);
    }

    /**
     * Perbarui kata sandi pengguna (forward ke update).
     */
    public function updatePassword(Request $request)
    {
        return $this->update($request);
    }
}
