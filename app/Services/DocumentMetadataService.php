<?php

namespace App\Services;

use App\FolderType;
use App\Models\Folder;
use Illuminate\Validation\ValidationException;

class DocumentMetadataService
{
    /** @param array<string, mixed> $data */
    public function target(array $data): Folder
    {
        $folder = Folder::query()->with('parent')->find($data['target_folder_id']);
        $validTemporary = $folder?->is_temporary && $folder->parent_id === null && $folder->folder_type === FolderType::Temporary;
        $validCustomRoot = $folder?->is_custom && $folder->parent_id === null && $folder->year === (int) $data['year'];
        $validOfficial = $folder?->parent && $folder->parent->is_active
            && in_array($folder->parent->folder_type, [FolderType::Group, FolderType::DocumentType], true) && $folder->parent->year === $folder->year
            && $folder->folder_type === FolderType::DocumentType && $folder->parent_id === (int) $data['group_folder_id'];
        if (! $folder || ! $folder->is_active
            || ($folder->year !== null && $folder->year !== (int) $data['year'])
            || (! $validTemporary && ! $validOfficial && ! $validCustomRoot)) {
            throw ValidationException::withMessages(['target_folder_id' => 'Pilih jenis dokumen aktif dalam kelompok dan tahun yang sesuai.']);
        }
        if (! Folder::query()->where('year', $data['year'])->where('code', '99_ARSIP_SEMENTARA')->where('is_active', true)->where('is_official', true)->where('is_temporary', true)->exists()) {
            throw ValidationException::withMessages(['year' => 'Struktur arsip sementara untuk tahun ini belum tersedia.']);
        }

        return $folder;
    }

    public function documentType(Folder $folder): string
    {
        return substr($folder->code, 3);
    }
}
