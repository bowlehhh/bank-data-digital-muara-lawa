<x-layout title="Detail Dokumen">
    @if (session('status'))
        <p class="rounded-lg bg-green-50 p-4 text-sm text-success" role="status">{{ session('status') }}</p>
    @endif

    <div class="flex flex-col gap-3">
        <h1 class="break-words text-2xl font-bold">{{ $document->title }}</h1>
        <span class="self-start rounded bg-background px-3 py-1.5 text-sm font-medium">{{ $document->location_label }}</span>
    </div>

    <section class="panel p-5 sm:p-7">
        <dl class="grid gap-6 sm:grid-cols-2">
            @foreach ([
                'Jenis dokumen' => str_replace('_', ' ', $document->document_type),
                'Tahun' => $document->year,
                'Folder' => $document->folder->parent ? $document->folder->parent->name.' / '.$document->folder->name : $document->folder->name,
                'Periode / tanggal' => $document->period ?? $document->document_date?->format('d/m/Y') ?? 'Tidak digunakan',
                'Versi' => $document->version_code ?? 'Tanpa versi',
                'Pengunggah' => $document->creator->name,
                'Waktu upload' => $document->created_at->format('d/m/Y H:i').' WITA',
            ] as $label => $value)
                <div>
                    <dt class="text-sm text-muted">{{ $label }}</dt>
                    <dd class="mt-1 break-words text-sm font-semibold">{{ $value }}</dd>
                </div>
            @endforeach

            @if ($document->description)
                <div class="sm:col-span-2">
                    <dt class="text-sm text-muted">Keterangan</dt>
                    <dd class="mt-1 whitespace-pre-line">{{ $document->description }}</dd>
                </div>
            @endif
        </dl>
    </section>

    @foreach ($document->files as $file)
        <section class="panel p-5 sm:p-7">
            <h2 class="text-lg font-semibold">File dokumen</h2>
            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                <div class="sm:col-span-2"><dt class="text-muted">Nama file resmi</dt><dd class="mt-1 break-all font-semibold">{{ $file->stored_filename }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-muted">Nama file asli</dt><dd class="mt-1 break-all">{{ $file->original_filename }}</dd></div>
                <div><dt class="text-muted">Extension / MIME</dt><dd class="mt-1">{{ $file->extension }} · {{ $file->mime_type }}</dd></div>
                <div><dt class="text-muted">Ukuran</dt><dd class="mt-1">{{ number_format($file->size / 1024, 1, ',', '.') }} KB</dd></div>
                <div><dt class="text-muted">Checksum SHA-256</dt><dd class="mt-1 font-mono">{{ substr($file->checksum_sha256, 0, 16) }}…</dd></div>
            </dl>
            <a class="button button-primary mt-5" href="{{ route('documents.download', $file) }}"><x-icon name="download" />Download file</a>
        </section>
    @endforeach

    <section class="panel p-5 sm:p-7">
        <h2 class="text-lg font-semibold">Pindahkan folder</h2>
        <p class="mt-1 text-sm text-muted">Pindahkan dokumen ke folder tujuan yang sesuai.</p>
        <form action="{{ route('documents.move', $document) }}" method="POST" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <x-field name="folder_id" label="Folder tujuan" class="grow">
                <select id="folder_id" name="folder_id" class="field" required>
                    @foreach ($moveFolders as $folder)
                        <option value="{{ $folder->id }}" @selected($folder->id === $document->folder_id)>{{ $folder->path_label }}</option>
                    @endforeach
                </select>
            </x-field>
            <button class="button button-primary"><x-icon name="folder" />Pindahkan</button>
        </form>
    </section>

    <section class="flex flex-wrap gap-3">
        <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('Pindahkan dokumen ini ke sampah? File fisik tidak langsung dihapus.')">
            @csrf
            @method('DELETE')
            <button class="button button-secondary text-primary"><x-icon name="trash" />Pindahkan ke sampah</button>
        </form>
        <a href="{{ route('documents.index') }}" class="button button-secondary"><x-icon name="document" />Download File</a>
    </section>
</x-layout>
