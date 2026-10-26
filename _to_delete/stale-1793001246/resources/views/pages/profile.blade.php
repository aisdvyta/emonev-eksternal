@extends('layouts.app')

@section('title', 'Profil')

@section('content')
    <h1 class="mb-4 text-xl font-bold text-biru1">Profil</h1>

    <section class="card mb-6">
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-xs uppercase text-abu">Nama</dt><dd>{{ $user->name }}</dd></div>
            <div><dt class="text-xs uppercase text-abu">Email</dt><dd>{{ $user->email }}</dd></div>
        </dl>
        <p class="mt-3 text-xs text-abu">Nama dan email dikelola admin HaloKIP. Untuk mengubahnya, hubungi admin.</p>
    </section>

    <form method="POST" action="{{ route('profile.password') }}" class="card max-w-xl space-y-4">
        @csrf
        @method('PUT')
        <h2 class="font-semibold text-biru1">Ganti kata sandi</h2>
        <div>
            <label for="current_password" class="label">Kata sandi saat ini</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="input">
        </div>
        <div>
            <label for="password" class="label">Kata sandi baru</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" minlength="10" class="input">
            <p class="mt-1 text-xs text-abu">Minimal 10 karakter, mengandung huruf dan angka.</p>
        </div>
        <div>
            <label for="password_confirmation" class="label">Ulangi kata sandi baru</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
        </div>
        <button type="submit" class="btn-primary">Simpan</button>
    </form>
@endsection
