<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Folder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DocumentMoveService
{
    public function move(Document $document, Folder $folder): void
    {
        if (! $folder->is_active || $folder->year !== $document->year
            || (! $folder->is_temporary && $folder->parent_id === null && ! $folder->is_custom)) {
            throw new RuntimeException('Folder tujuan tidak tersedia untuk tahun dokumen.');
        }
        foreach ($document->files as $file) {
            $newDirectory = $document->year.'/'.$folder->code.'/'.Str::uuid();
            $newPath = $newDirectory.'/'.$file->stored_filename;
            if (! Storage::disk($file->disk)->copy($file->path, $newPath)) {
                throw new RuntimeException('File gagal dipindahkan.');
            }
            Storage::disk($file->disk)->delete($file->path);
            $file->update(['path' => $newPath]);
        }
        $document->update(['folder_id' => $folder->id, 'target_folder_id' => $folder->id]);
    }
}
