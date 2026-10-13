<?php

namespace App\Services;

use App\FolderType;
use App\Models\Folder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FolderStructureService
{
    public function createForYear(int $year): void
    {
        if ($year < 2026 || $year > 9999) {
            throw new InvalidArgumentException('Tahun harus berada antara 2026 dan 9999.');
        }

        DB::transaction(function () use ($year): void {
            $this->createGroups(config('folder_structure.global'), null);
            $this->createGroups(config('folder_structure.annual'), $year);
        });
    }

    /** @param array<string, list<string>> $groups */
    private function createGroups(array $groups, ?int $year): void
    {
        foreach ($groups as $code => $children) {
            $parent = $this->createFolder($code, $year, null, FolderType::Group);
            foreach ($children as $childCode) {
                $this->createFolder($childCode, $year, $parent->id, FolderType::DocumentType);
            }
        }
    }

    private function createFolder(string $code, ?int $year, ?int $parentId, FolderType $type): Folder
    {
        return Folder::query()->firstOrCreate([
            'year' => $year,
            'parent_id' => $parentId,
            'code' => $code,
        ], [
            'name' => str_replace('_', ' ', substr($code, 3)),
            'folder_type' => $type,
            'sort_order' => (int) substr($code, 0, 2),
            'is_official' => true,
            'is_temporary' => false,
            'is_active' => true,
        ]);
    }
}
