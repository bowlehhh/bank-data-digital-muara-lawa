<x-layout title="Download File">
    @if (session('status'))<p role="status" class="rounded-lg bg-green-50 p-4 text-sm text-success">{{ session('status') }}</p>@endif
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"><div><h1 class="text-2xl font-bold">Download File</h1><p class="mt-2 text-sm text-muted">Jelajahi folder dan dokumen seperti file explorer.</p></div><div class="flex flex-wrap gap-2"><a class="button button-secondary" href="{{ route('folders.create') }}"><x-icon name="folder" />Buat Folder Baru</a><a class="button button-primary" href="{{ route('documents.create') }}"><x-icon name="upload" />Upload Dokumen</a></div></div>
    <div class="grid gap-6 xl:grid-cols-[18rem_1fr]">
        <aside class="panel hidden h-fit p-4 xl:block"><h2 class="mb-3 text-sm font-semibold">Folder</h2><div class="flex flex-col gap-1 text-sm">@foreach($tree as $root)<a href="{{ route('documents.index', ['folder_id' => $root->id]) }}" data-folder-id="{{ $root->id }}" data-folder-custom="{{ $root->is_custom ? '1' : '0' }}" data-folder-edit="{{ route('folders.edit', $root) }}" data-folder-delete="{{ route('folders.destroy', $root) }}" @class(['folder-target rounded-lg px-3 py-3 hover:bg-primary-soft', 'bg-primary-soft font-semibold text-primary' => $activeFolder?->id === $root->id])><span class="flex items-center gap-2"><x-icon name="folder" />{{ $root->name }}{{ $root->year ? ' · '.$root->year : '' }}</span><span class="mt-1 block text-xs text-muted">{{ $root->children_count }} folder · {{ $root->documents_count }} dokumen</span></a>@endforeach</div></aside>
        <div class="flex min-w-0 flex-col gap-5">
            <details class="panel p-4 xl:hidden"><summary class="cursor-pointer font-semibold">Folder</summary><div class="mt-3 flex flex-col gap-1 text-sm">@foreach($tree as $root)<a class="folder-target rounded-lg px-3 py-3 hover:bg-primary-soft" data-folder-custom="{{ $root->is_custom ? '1' : '0' }}" data-folder-edit="{{ route('folders.edit', $root) }}" data-folder-delete="{{ route('folders.destroy', $root) }}" href="{{ route('documents.index', ['folder_id' => $root->id]) }}"><x-icon name="folder" />{{ $root->name }}{{ $root->year ? ' · '.$root->year : '' }}</a>@endforeach</div></details>
            <form action="{{ route('documents.index') }}" method="GET" class="panel grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4"><x-field name="q" label="Pencarian"><input id="q" name="q" class="field" value="{{ request('q') }}" placeholder="Judul, uraian, nama file, folder"></x-field><x-field name="year" label="Tahun"><select id="year" name="year" class="field"><option value="">Semua tahun</option>@foreach($years as $year)<option value="{{ $year }}" @selected(request('year') == $year)>{{ $year }}</option>@endforeach</select></x-field><x-field name="extension" label="Jenis file"><input id="extension" name="extension" value="{{ request('extension') }}" class="field" placeholder="pdf, docx, xlsx"></x-field><x-field name="version_code" label="Versi"><select id="version_code" name="version_code" class="field"><option value="">Semua versi</option>@foreach(config('documents.versions') as $version)<option value="{{ $version }}" @selected(request('version_code') === $version)>{{ $version }}</option>@endforeach</select></x-field><input type="hidden" name="folder_id" value="{{ request('folder_id') }}"><div class="flex flex-wrap gap-2 sm:col-span-2 xl:col-span-4"><button class="button button-primary"><x-icon name="search" />Terapkan filter</button><a href="{{ route('documents.index', request('folder_id') ? ['folder_id' => request('folder_id')] : []) }}" class="button button-secondary">Reset</a></div></form>
            <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap text-sm text-muted"><a href="{{ route('documents.index') }}" class="text-primary">Download File</a>@if($activeFolder)<span>›</span><span class="font-semibold text-text">{{ $activeFolder->name }}</span>@endif</div>
            @if($subfolders->isNotEmpty())<section><h2 class="mb-3 text-lg font-semibold">Folder</h2><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach($subfolders as $folder)<a href="{{ route('documents.index', ['folder_id' => $folder->id]) }}" data-folder-id="{{ $folder->id }}" data-folder-custom="{{ $folder->is_custom ? '1' : '0' }}" data-folder-edit="{{ route('folders.edit', $folder) }}" data-folder-delete="{{ route('folders.destroy', $folder) }}" class="folder-target panel flex min-h-24 items-start gap-3 p-4 hover:border-primary"><x-icon name="folder" class="text-primary" /><span><strong class="block break-words">{{ $folder->code }}</strong><span class="text-sm text-muted">{{ $folder->name }} · {{ $folder->children_count }} subfolder · {{ $folder->documents_count }} dokumen</span></span></a>@endforeach</div></section>@endif
            <section class="panel overflow-hidden"><div class="flex items-center justify-between gap-3 border-b border-border px-5 py-4"><h2 class="font-semibold">{{ $activeFolder ? 'Isi folder' : 'Dokumen' }}</h2><span class="text-sm text-muted">{{ $documents->total() }} item</span></div>@if($documents->isEmpty())<div class="flex flex-col items-center gap-3 px-5 py-14 text-center"><x-icon name="document" class="size-9 text-muted" /><h3 class="font-semibold">Belum ada dokumen yang sesuai</h3><p class="text-sm text-muted">Gunakan pencarian atau pilih folder lain.</p></div>@else<div class="overflow-x-auto"><table class="w-full min-w-220 text-left text-sm"><thead class="border-b border-border bg-background text-muted"><tr>@foreach(['Nama','Jenis','Tahun','Ukuran','Terakhir diubah','Tindakan'] as $heading)<th class="px-5 py-4 font-semibold">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-border">@foreach($documents as $document)@php($file=$document->files->first())<tr><td class="px-5 py-4"><span class="block break-all font-medium">{{ $file?->stored_filename ?? $document->title }}</span><span class="mt-1 block text-xs text-muted">{{ $document->title }}</span></td><td class="px-5 py-4 uppercase">{{ $file?->extension ?? $document->document_type }}</td><td class="px-5 py-4">{{ $document->year }}</td><td class="px-5 py-4">{{ $file ? number_format($file->size / 1024, 1, ',', '.').' KB' : '-' }}</td><td class="px-5 py-4">{{ $document->updated_at->format('d/m/Y H:i') }}</td><td class="px-5 py-4"><div class="flex flex-wrap gap-2"><a class="button button-secondary" href="{{ route('documents.show', $document) }}"><x-icon name="eye" />Detail</a>@if($file)<a class="button button-secondary" href="{{ route('documents.download', $file) }}"><x-icon name="download" />Download</a>@endif</div></td></tr>@endforeach</tbody></table></div>@endif</section>
            {{ $documents->links() }}
        </div>
    </div>
    <div id="folder-context-menu" class="fixed z-50 hidden min-w-48 rounded-lg border border-border bg-white p-2 shadow-lg" role="menu">
        <p id="folder-context-title" class="px-3 py-2 text-xs font-semibold text-muted"></p>
        <a id="folder-context-edit" class="block rounded px-3 py-2 text-sm hover:bg-primary-soft" role="menuitem">Edit nama folder</a>
        <form id="folder-context-delete" method="POST" onsubmit="return confirm('Hapus folder custom ini? Folder harus kosong dan tidak memiliki subfolder.')">
            @csrf @method('DELETE')
            <button class="block w-full rounded px-3 py-2 text-left text-sm text-primary hover:bg-primary-soft" role="menuitem">Hapus folder</button>
        </form>
    </div>
    <script>
        (() => {
            const menu = document.getElementById('folder-context-menu');
            const title = document.getElementById('folder-context-title');
            const edit = document.getElementById('folder-context-edit');
            const remove = document.getElementById('folder-context-delete');
            document.querySelectorAll('.folder-target').forEach((folder) => folder.addEventListener('contextmenu', (event) => {
                event.preventDefault();
                const custom = folder.dataset.folderCustom === '1';
                title.textContent = custom ? 'Aksi folder custom' : 'Aksi folder resmi';
                edit.href = folder.dataset.folderEdit;
                edit.classList.remove('pointer-events-none', 'opacity-50');
                edit.setAttribute('aria-disabled', 'false');
                remove.action = folder.dataset.folderDelete;
                remove.classList.remove('hidden');
                menu.style.left = `${Math.min(event.clientX, window.innerWidth - 210)}px`;
                menu.style.top = `${Math.min(event.clientY, window.innerHeight - 110)}px`;
                menu.classList.remove('hidden');
            }));
            document.addEventListener('click', () => menu.classList.add('hidden'));
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape') menu.classList.add('hidden'); });
        })();
    </script>
</x-layout>
