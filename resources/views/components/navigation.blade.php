<nav aria-label="Menu utama" class="flex flex-col gap-1">
    <p class="px-3 pb-2 text-xs font-semibold tracking-wider text-muted">MENU UTAMA</p>
    <a href="{{ route('dashboard') }}" @class(['nav-item', 'bg-primary-soft text-primary' => request()->routeIs('dashboard')])><x-icon name="home" />Dashboard</a>
    <a href="{{ route('documents.create') }}" @class(['nav-item', 'bg-primary-soft text-primary' => request()->routeIs('documents.create')])><x-icon name="upload" />Upload Dokumen</a>
    <a href="{{ route('documents.index') }}" @class(['nav-item', 'bg-primary-soft text-primary' => request()->routeIs('documents.index', 'documents.show')])><x-icon name="document" />Download File</a>
    <a href="{{ route('folders.create') }}" @class(['nav-item', 'bg-primary-soft text-primary' => request()->routeIs('folders.create')])><x-icon name="folder" />Buat Folder Baru</a>
    <p class="px-3 pt-5 text-xs leading-relaxed text-muted">Semua akun aktif menggunakan akses Admin.</p>
</nav>
