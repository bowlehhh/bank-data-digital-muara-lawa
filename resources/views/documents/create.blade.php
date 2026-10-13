<x-layout title="Upload Dokumen">
    <div><h1 class="text-2xl font-bold">Upload Dokumen</h1><p class="mt-2 text-sm text-muted">Isi informasi dokumen dan pilih folder penyimpanannya.</p></div>
    @if($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-primary-soft p-4 text-sm text-primary">Dokumen belum disimpan. Periksa pesan di bawah kolom dan pilih ulang file Anda.</div>@endif
    @if(session('duplicate_document_id'))<a class="button button-secondary self-start" href="{{ route('documents.show', session('duplicate_document_id')) }}"><x-icon name="document" />Lihat dokumen yang sudah tersimpan</a>@endif
    <form id="document-upload" action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="panel flex flex-col gap-6 p-5 sm:p-7" data-preview-url="{{ route('documents.filename-preview') }}">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="year" label="1. Tahun dokumen" help="Pilih tahun yang struktur foldernya sudah tersedia.">
                <select id="year" name="year" class="field" required aria-describedby="year-help year-error"><option value="">Pilih tahun</option>@foreach($years as $year)<option value="{{ $year }}" @selected(old('year', 2026) == $year)>{{ $year }}</option>@endforeach</select>
            </x-field>
            <x-field name="group_folder_id" label="2. Kelompok dokumen">
                <select id="group_folder_id" name="group_folder_id" class="field" required aria-describedby="group_folder_id-error"><option value="">Pilih kelompok dokumen</option>@foreach($groups as $folder)<option value="{{ $folder->id }}" data-year="{{ $folder->year }}" @selected(old('group_folder_id') == $folder->id)>{{ $folder->name }}</option>@endforeach</select>
            </x-field>
            <x-field name="target_folder_id" label="3. Jenis dokumen / folder tujuan" class="sm:col-span-2" help="Pilih subfolder seperti RENSTRA atau RENJA. Folder induk resmi bukan tujuan upload.">
                <div class="flex flex-col gap-2 sm:flex-row"><select id="target_folder_id" name="target_folder_id" class="field grow" required aria-describedby="target_folder_id-help target_folder_id-error"><option value="">Pilih jenis dokumen</option>@foreach($targets as $folder)<option value="{{ $folder->id }}" data-year="{{ $folder->year }}" data-group="{{ $targetGroups[$folder->id] }}" @selected(old('target_folder_id') == $folder->id)>{{ $folder->year.' · '.$folder->code.' · '.$folder->name }}</option>@endforeach</select><a href="{{ route('folders.create') }}" target="_blank" rel="noopener" class="button button-secondary whitespace-nowrap">Buat Folder Baru</a><button type="button" id="refresh-upload-folders" data-url="{{ route('documents.create') }}" class="button button-secondary">Perbarui Folder</button></div><p id="folder-refresh-status" class="mt-2 text-xs text-muted" aria-live="polite">Folder baru dibuka di tab terpisah. Setelah membuat folder, tekan Perbarui Folder di sini; isian dan file tetap tersimpan.</p>
            </x-field>
            <x-field name="title" label="4. Judul atau uraian" class="sm:col-span-2" help="Contoh: Kecamatan Muara Lawa, Belanja ATK, atau Rapat Evaluasi. Hindari nama umum seperti BARU atau FIX.">
                <input id="title" name="title" class="field" maxlength="120" value="{{ old('title') }}" required aria-describedby="title-help title-error">
            </x-field>
            <x-field name="timing" label="5. Periode atau tanggal" help="Pilih salah satu; tanpa periode untuk dokumen yang tidak periodik.">
                <select id="timing" name="timing" class="field" aria-describedby="timing-help timing-error">@foreach(['none' => 'Tanpa periode / tanggal', 'period' => 'Menggunakan periode', 'date' => 'Menggunakan tanggal'] as $value => $label)<option value="{{ $value }}" @selected(old('timing', 'none') === $value)>{{ $label }}</option>@endforeach</select>
            </x-field>
            <x-field name="period" label="Periode dokumen" data-timing-field="period">
                <select id="period" name="period" class="field" aria-describedby="period-error"><option value="">Pilih periode</option>@foreach(config('documents.periods') as $period)<option @selected(old('period') === $period) value="{{ $period }}">{{ str_replace('-', ' ', $period) }}</option>@endforeach</select>
            </x-field>
            <x-field name="document_date" label="Tanggal dokumen" data-timing-field="date">
                <input type="date" id="document_date" name="document_date" value="{{ old('document_date') }}" class="field" aria-describedby="document_date-error">
            </x-field>
            <x-field name="version_code" label="6. Versi dokumen" help="V01, V02, V10, FINAL, atau FINAL_TTD. Boleh kosong hanya untuk surat dan dokumen pemeriksaan bertanggal.">
                <select id="version_code" name="version_code" class="field" aria-describedby="version_code-help version_code-error"><option value="" @selected(old('version_code', 'V01') === '')>Tanpa versi (surat / pemeriksaan bertanggal)</option>@foreach(config('documents.versions') as $version)<option value="{{ $version }}" @selected(old('version_code', 'V01') === $version)>{{ $version }}</option>@endforeach</select>
            </x-field>
            <x-field name="file_role" label="7. Jenis file" help="Menjelaskan peran file, terpisah dari versi dokumen.">
                <select id="file_role" name="file_role" class="field" required aria-describedby="file_role-help file_role-error">@foreach(\App\FileRole::cases() as $role)<option value="{{ $role->value }}" @selected(old('file_role', 'editable') === $role->value)>{{ $role->label() }}</option>@endforeach</select>
            </x-field>
            <x-field name="file" label="8. Pilih file" class="sm:col-span-2" :help="'Format: '.implode(', ', array_keys(config('documents.mime_types'))).'. Maksimal '.(config('documents.max_size_kb') / 1024).' MB.'">
                <input type="file" id="file" name="file" class="field file:mr-3 file:rounded file:border-0 file:bg-primary-soft file:px-3 file:py-2 file:text-primary" required accept="{{ implode(',', array_map(fn ($ext) => '.'.$ext, array_keys(config('documents.mime_types')))) }}" aria-describedby="file-help file-error">
            </x-field>
            <x-field name="description" label="Keterangan tambahan (opsional)" class="sm:col-span-2">
                <textarea id="description" name="description" rows="3" maxlength="5000" class="field" aria-describedby="description-error">{{ old('description') }}</textarea>
            </x-field>
        </div>
        <div class="rounded-lg border border-border bg-background p-4"><h2 class="text-sm font-semibold">9. Preview nama file resmi</h2><p id="filename-preview" class="mt-2 break-all text-sm text-primary" aria-live="polite">Lengkapi informasi dan pilih file untuk melihat nama resmi.</p><p class="mt-2 text-xs text-muted">Nama akhir ditentukan server setelah isi file lolos validasi.</p></div>
        <div class="flex flex-col gap-3 border-t border-border pt-5 sm:flex-row"><button type="submit" class="button button-primary" data-submit-upload><x-icon name="upload" /><span>Simpan Dokumen</span></button><a href="{{ route('documents.index') }}" class="button button-secondary"><x-icon name="document" />Download File</a></div>
    </form>
</x-layout>
