@props(['code', 'title', 'message'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Bank Data Digital Muara Lawa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center p-5">
    <main class="panel flex w-full max-w-lg flex-col gap-5 p-8">
        <x-brand />
        <p class="mt-4 text-sm font-semibold text-primary">{{ $code }}</p>
        <h1 class="text-2xl font-bold">{{ $title }}</h1>
        <p class="leading-relaxed text-muted">{{ $message }}</p>
        <a href="{{ route('login') }}" class="button button-primary"><x-icon name="login" />Kembali ke aplikasi</a>
    </main>
</body>
</html>
