<?php

namespace Database\Factories;

use App\FolderType;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Folder> */
class FolderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'year' => 2026,
            'code' => '01_'.strtoupper(fake()->unique()->lexify('????????')),
            'name' => fake()->words(3, true),
            'folder_type' => FolderType::Group,
            'sort_order' => 1,
            'is_official' => true,
            'is_temporary' => false,
            'is_active' => true,
        ];
    }
}
