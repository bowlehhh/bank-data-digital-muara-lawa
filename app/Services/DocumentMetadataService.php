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
        $folder = Folder::query()->with('parent.parent.parent.parent')->find($data['target_folder_id']);
        if (! $folder || ! $folder->is_active || $folder->is_temporary) {
            throw ValidationException::withMessages(['target_folder_id' => 'Folder tujuan tidak tersedia atau tidak aktif.']);
        }
        if ($folder->year !== null && $folder->year !== (int) $data['year']) {
            throw ValidationException::withMessages(['target_folder_id' => 'Tahun folder tujuan tidak sesuai dengan tahun dokumen.']);
        }
        $group = $this->uploadGroup($folder);
        if (! $group) {
            throw ValidationException::withMessages(['target_folder_id' => 'Pilih subfolder jenis dokumen yang aktif, bukan folder induk resmi.']);
        }
        if ($group->id !== (int) $data['group_folder_id']) {
            throw ValidationException::withMessages(['target_folder_id' => 'Folder tujuan tidak berada dalam kelompok dokumen yang dipilih.']);
        }

        return $folder;
    }

    public function uploadGroup(Folder $folder): ?Folder
    {
        if (! $folder->is_active || $folder->is_temporary) {
            return null;
        }
        if ($folder->parent_id === null) {
            return $folder->folder_type === FolderType::Group && $folder->is_custom ? $folder : null;
        }
        if ($folder->folder_type !== FolderType::DocumentType) {
            return null;
        }

        $ancestor = $folder;
        $visited = [$folder->id];
        while ($ancestor->parent_id !== null) {
            $ancestor = $ancestor->parent;
            if (! $ancestor || in_array($ancestor->id, $visited, true) || ! $ancestor->is_active
                || $ancestor->is_temporary || $ancestor->year !== $folder->year
                || ! in_array($ancestor->folder_type, [FolderType::Group, FolderType::DocumentType], true)) {
                return null;
            }
            $visited[] = $ancestor->id;
        }

        return $ancestor->folder_type === FolderType::Group
            && ($ancestor->is_official || $ancestor->is_custom && $ancestor->year !== null)
            ? $ancestor : null;
    }

    public function documentType(Folder $folder): string
    {
        return substr($folder->code, 3);
    }
}
