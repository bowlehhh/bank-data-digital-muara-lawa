<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentRevision> */
class DocumentRevisionFactory extends Factory
{
    public function definition(): array
    {
        return ['document_id' => Document::factory(), 'version_code' => 'V01', 'revision_number' => 1,
            'submitted_by' => User::factory(), 'submitted_at' => now(), ];
    }
}
