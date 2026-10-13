<x-layout title="Buat Folder Baru">
    <div><h1 class="text-2xl font-bold">Buat Folder Baru</h1><p class="mt-2 text-sm text-muted">Tambahkan folder custom untuk mengatur dokumen tanpa mengubah folder resmi.</p></div>
    @if ($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-primary-soft p-4 text-sm text-primary">Periksa kembali data folder.</div>@endif
    <form action="{{ route('folders.store') }}" method="POST" class="panel flex flex-col gap-6 p-5 sm:p-7">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="year" label="Tahun"><input id="year" name="year" type="number" min="2026" max="9999" value="{{ old('year', 2026) }}" class="field" required></x-field>
            <x-field name="parent_id" label="Folder induk"><select id="parent_id" name="parent_id" class="field"><option value="">Folder utama (tanpa induk)</option>@foreach($folders as $folder)<option value="{{ $folder->id }}" @selected(old('parent_id') == $folder->id)>{{ $folder->year ? $folder->year.' · ' : 'Global · ' }}{{ $folder->code }} · {{ $folder->name }}</option>@endforeach</select><p class="mt-2 text-xs text-muted">Pilih “Folder utama” agar folder baru sejajar dengan folder induk lainnya.</p></x-field>
            <x-field name="name" label="Nama folder"><input id="name" name="name" value="{{ old('name') }}" class="field" required maxlength="120" placeholder="Dokumen Kegiatan Kecamatan"></x-field>
            <x-field name="code" label="Nomor folder" help="Nomor dibuat otomatis berdasarkan tahun dan folder induk yang dipilih."><input id="code" value="Otomatis" class="field bg-background" readonly aria-readonly="true"></x-field>
            <x-field name="description" label="Deskripsi opsional" class="sm:col-span-2"><textarea id="description" name="description" rows="3" class="field">{{ old('description') }}</textarea></x-field>
        </div>
        <div class="rounded-lg border border-border bg-background p-4"><p class="text-sm font-semibold">Preview folder</p><p class="mt-2 text-sm text-primary">Nomor akan ditentukan otomatis setelah tahun dan induk dipilih. Hasilnya akan mengikuti format <strong>08_DOKUMEN_KEGIATAN_KECAMATAN</strong>.</p></div>
        <div class="flex flex-col gap-3 border-t border-border pt-5 sm:flex-row"><button class="button button-primary"><x-icon name="folder" />Buat Folder</button><a href="{{ route('documents.index') }}" class="button button-secondary">Download File</a></div>
    </form>
</x-layout>
