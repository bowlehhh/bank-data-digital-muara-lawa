<x-layout title="Jelajah Folder">
    <div><h1 class="text-2xl font-bold">Jelajah Folder</h1><p class="mt-2 text-sm text-muted">Pilih lemari arsip untuk melihat dokumen di dalamnya.</p></div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">@foreach($folders as $folder)<a href="{{ route('folders.show', $folder) }}" class="panel p-5 transition hover:border-primary"><div class="flex items-start justify-between gap-3"><x-icon name="folder" class="text-primary" /><span class="rounded bg-background px-2 py-1 text-xs text-muted">{{ $folder->documents_count }} dokumen</span></div><h2 class="mt-5 font-semibold">{{ $folder->name }}</h2><p class="mt-1 text-sm text-muted">{{ $folder->year ?? 'Folder global' }} · {{ $folder->children_count }} subfolder</p></a>@endforeach</div>
</x-layout>
