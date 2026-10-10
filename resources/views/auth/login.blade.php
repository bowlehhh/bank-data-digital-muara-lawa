<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Bank Data Digital Muara Lawa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-border bg-white px-6 py-5 sm:px-10"><x-brand /></header>
        <main class="mx-auto grid w-full max-w-6xl flex-1 items-center gap-12 px-5 py-10 md:grid-cols-2 md:gap-20 md:py-16">
            <section class="flex flex-col gap-6">
                <p class="text-sm font-semibold tracking-widest text-primary">PERENCANAAN &amp; KEUANGAN</p>
                <h1 class="text-3xl leading-tight font-bold sm:text-4xl">Dokumen tertata.<br>Pelayanan lebih baik.</h1>
                <p class="max-w-md text-base leading-relaxed text-muted">Bank Data Digital Perencanaan dan Keuangan Kecamatan Muara Lawa. Satu tempat untuk pengelolaan dokumen yang tertib dan terkontrol.</p>
                <div class="flex items-start gap-3 border-l-2 border-primary pl-4 text-sm leading-relaxed"><x-icon name="shield" class="mt-0.5 text-primary" /><p>Akses khusus pegawai yang telah terdaftar.<br><span class="text-muted">Gunakan akun yang diberikan administrator.</span></p></div>
            </section>
            <section class="panel p-6 sm:p-9" aria-labelledby="login-heading">
                <h2 id="login-heading" class="text-2xl font-bold">Masuk ke aplikasi</h2>
                <p class="mt-2 text-sm leading-relaxed text-muted">Silakan masukkan email dan kata sandi Anda.</p>
                @if(session('status'))<p role="status" class="mt-5 rounded-lg bg-green-50 p-3 text-sm text-success">{{ session('status') }}</p>@endif
                <form action="{{ route('login.store') }}" method="POST" class="mt-7 flex flex-col gap-5">
                    @csrf
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold">Alamat email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="field" autocomplete="username" required maxlength="255" autofocus aria-describedby="email-help{{ $errors->has('email') ? ' email-error' : '' }}" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                        <p id="email-help" class="mt-2 text-xs text-muted">Gunakan email yang terdaftar pada aplikasi.</p>
                        @error('email')<p id="email-error" role="alert" class="mt-2 text-sm text-primary">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold">Kata sandi</label>
                        <input id="password" name="password" type="password" class="field" autocomplete="current-password" required aria-describedby="password-help{{ $errors->has('password') ? ' password-error' : '' }}" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                        <div class="mt-1 flex items-center justify-between gap-2"><p id="password-help" class="text-xs text-muted">Perhatikan huruf besar dan kecil.</p><button type="button" class="button px-2 text-primary" data-toggle-password aria-controls="password" aria-pressed="false"><x-icon name="eye" /><span>Tampilkan</span></button></div>
                        @error('password')<p id="password-error" role="alert" class="mt-2 text-sm text-primary">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="button button-primary w-full"><x-icon name="login" />Masuk</button>
                </form>
                <p class="mt-6 border-t border-border pt-5 text-center text-sm leading-relaxed text-muted">Belum memiliki akun atau lupa kata sandi?<br>Silakan hubungi administrator kecamatan.</p>
            </section>
        </main>
        <footer class="px-5 pb-6 text-center text-xs text-muted">Kecamatan Muara Lawa · Kabupaten Kutai Barat</footer>
    </div>
</body>
</html>
