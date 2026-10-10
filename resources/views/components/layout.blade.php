@props(['title' => 'Dashboard'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Bank Data Digital Muara Lawa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#konten-utama" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:bg-white focus:p-4">Lewati ke konten utama</a>
    <aside class="fixed inset-y-0 left-0 hidden w-68 flex-col border-r border-border bg-surface lg:flex">
        <div class="border-b border-border p-6"><x-brand /></div>
        <div class="flex-1 overflow-y-auto px-4 py-6"><x-navigation /></div>
        <div class="flex flex-col gap-3 border-t border-border p-4">
            <div class="px-2"><p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p><p class="text-xs text-muted">{{ auth()->user()->role->label() }}</p></div>
            <x-logout />
        </div>
    </aside>
    <dialog id="menu-drawer" class="sidebar-dialog border-r border-border bg-surface p-5" aria-labelledby="menu-title">
        <div class="mb-6 flex items-center justify-between gap-3"><h2 id="menu-title" class="font-bold">Menu aplikasi</h2><button type="button" class="button button-secondary" data-close-menu><x-icon name="close" />Tutup</button></div>
        <x-brand />
        <div class="my-6"><x-navigation /></div>
        <div class="mb-4 border-t border-border pt-4"><p class="font-semibold">{{ auth()->user()->name }}</p><p class="text-sm text-muted">{{ auth()->user()->role->label() }}</p></div>
        <x-logout />
    </dialog>
    <div class="min-h-screen lg:pl-68">
        <header class="flex min-h-20 items-center justify-between gap-3 border-b border-border bg-white px-4 sm:px-8">
            <button type="button" class="button button-secondary lg:hidden" data-open-menu aria-haspopup="dialog" aria-controls="menu-drawer" aria-expanded="false"><x-icon name="menu" />Menu</button>
            <div class="hidden sm:block"><p class="text-sm font-semibold">Perencanaan dan Keuangan</p><p class="mt-1 text-xs text-muted">Kecamatan Muara Lawa</p></div>
            <div class="min-w-0 text-right"><p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p><p class="text-xs text-muted">{{ auth()->user()->role->label() }}</p></div>
        </header>
        <main id="konten-utama" class="mx-auto flex max-w-7xl flex-col gap-7 px-4 py-7 sm:px-8 sm:py-8" tabindex="-1">
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-muted"><x-icon name="home" class="size-4" /><span>Beranda</span><span aria-hidden="true">/</span><span class="text-text" aria-current="page">{{ $title }}</span></nav>
            {{ $slot }}
            <footer class="border-t border-border pt-5 text-xs leading-relaxed text-muted">Bank Data Digital Perencanaan dan Keuangan Kecamatan Muara Lawa</footer>
        </main>
    </div>
</body>
</html>
