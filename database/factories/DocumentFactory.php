<?php

namespace Database\Factories;

use App\FolderType;
use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return ['folder_id' => Folder::factory()->state(['folder_type' => FolderType::Temporary, 'is_temporary' => true]),
            'target_folder_id' => Folder::factory()->state(['parent_id' => Folder::factory(), 'folder_type' => FolderType::DocumentType]),
            'title' => fake()->sentence(3), 'document_type' => 'RENJA', 'year' => 2026,
            'version_code' => 'V01', 'created_by' => User::factory(), ];
    }
}
