@extends('layouts.guest')

@section('title', 'Verifikasi')

@section('content')
    <p class="mb-4 text-sm text-slate-600">Kami mengirim kode 6 digit ke email Anda. Masukkan kode tersebut untuk melanjutkan.</p>
    <form method="POST" action="{{ route('login.otp.verify') }}" class="space-y-4">
        @csrf
        <div>
            <label for="kode" class="label">Kode verifikasi</label>
            <input id="kode" name="kode" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code"
                   class="input text-center text-xl tracking-[0.5em]">
        </div>
        <button type="submit" class="btn-primary w-full">Verifikasi</button>
    </form>
    <form method="POST" action="{{ route('login.otp.resend') }}" class="mt-3 text-center">
        @csrf
        <button type="submit" class="text-sm text-biru2 underline">Kirim ulang kode</button>
    </form>
    <p class="mt-3 text-center text-sm"><a href="{{ route('login') }}" class="text-abu underline">Kembali ke halaman masuk</a></p>
@endsection
