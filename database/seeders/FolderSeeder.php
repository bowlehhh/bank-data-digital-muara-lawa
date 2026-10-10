<?php

namespace Database\Seeders;

use App\Services\FolderStructureService;
use Illuminate\Database\Seeder;

class FolderSeeder extends Seeder
{
    public function run(FolderStructureService $structure): void
    {
        $structure->createForYear(2026);
    }
}
