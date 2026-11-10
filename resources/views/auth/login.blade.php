@extends('layouts.auth')

@section('title', 'Login')
@section('form-title', 'Masuk ke akun Anda')

@section('content')
<form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
    @csrf

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
        <input id="password" type="password" name="password" required
               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600">
        Ingat saya
    </label>

    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
        Masuk
    </button>
</form>

<p class="mt-6 text-center text-sm text-slate-600">
    Belum punya akun?
    <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:underline">Daftar sekarang</a>
</p>
@endsection
