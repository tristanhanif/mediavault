<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - MediaVault</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full items-center justify-center bg-slate-900 px-4">
<div class="w-full max-w-md">
    <div class="mb-8 text-center">
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500 text-2xl font-bold text-white">V</span>
        <h1 class="mt-4 text-2xl font-bold text-white">MediaVault</h1>
        <p class="mt-1 text-sm text-slate-400">Digital Asset Management System</p>
    </div>

    <div class="rounded-2xl bg-white p-8 shadow-2xl">
        <h2 class="text-lg font-semibold text-slate-900">@yield('form-title')</h2>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @yield('content')
    </div>

    <p class="mt-6 text-center text-xs text-slate-500">Seluruh akses tercatat &amp; dilindungi.</p>
</div>
</body>
</html>
