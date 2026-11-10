@extends('layouts.auth')

@section('title', 'Register')
@section('form-title', 'Buat akun baru')

@section('content')
<form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
    @csrf

    <div>
        <label for="name" class="block text-sm font-medium text-slate-700">Nama Lengkap</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required
               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-slate-700">Password (min. 8 karakter)</label>
        <input id="password" type="password" name="password" required
               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required
               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
        Daftar
    </button>
</form>

<p class="mt-6 text-center text-sm text-slate-600">
    Sudah punya akun?
    <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:underline">Masuk di sini</a>
</p>
@endsection
