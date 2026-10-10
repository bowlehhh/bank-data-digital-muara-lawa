<x-layout title="Edit Folder">
    <div><h1 class="text-2xl font-bold">Edit Folder</h1><p class="mt-2 text-sm text-muted">Perbaiki nama atau deskripsi folder. Nomor folder tetap agar file yang tersimpan tetap aman.</p></div>
    @if ($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-primary-soft p-4 text-sm text-primary">Periksa kembali nama folder.</div>@endif
    <form action="{{ route('folders.update', $folder) }}" method="POST" class="panel flex flex-col gap-6 p-5 sm:p-7">
        @csrf @method('PUT')
        <div class="grid gap-5 sm:grid-cols-2"><x-field name="name" label="Nama folder"><input id="name" name="name" value="{{ old('name', $folder->name) }}" class="field" required maxlength="120"></x-field><x-field name="code" label="Nomor folder"><input id="code" value="{{ substr($folder->code, 0, 2) }}" class="field bg-background" readonly></x-field><x-field name="description" label="Deskripsi opsional" class="sm:col-span-2"><textarea id="description" name="description" rows="4" class="field">{{ old('description', $folder->description) }}</textarea></x-field></div>
        <div class="flex flex-col gap-3 border-t border-border pt-5 sm:flex-row"><button class="button button-primary"><x-icon name="folder" />Simpan Perubahan</button><a href="{{ route('documents.index', ['folder_id' => $folder->id]) }}" class="button button-secondary">Batal</a></div>
    </form>
</x-layout>
