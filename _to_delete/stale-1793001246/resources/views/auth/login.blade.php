@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4" autocomplete="on">
        @csrf
        <div>
            <label for="login" class="label">Email atau username</label>
            <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username" class="input">
        </div>
        <div>
            <label for="password" class="label">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
        </div>
        <button type="submit" class="btn-primary w-full">Masuk</button>
    </form>
@endsection
