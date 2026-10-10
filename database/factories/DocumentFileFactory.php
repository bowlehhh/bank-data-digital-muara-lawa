<?php

namespace Database\Factories;

use App\FileRole;
use App\Models\DocumentFile;
use App\Models\DocumentRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentFile> */
class DocumentFileFactory extends Factory
{
    public function definition(): array
    {
        return ['document_revision_id' => DocumentRevision::factory(), 'original_filename' => 'contoh.pdf',
            'stored_filename' => '2026_RENJA_CONTOH_V01.pdf', 'disk' => 'documents',
            'path' => '2026/99_ARSIP_SEMENTARA/'.fake()->uuid().'/2026_RENJA_CONTOH_V01.pdf',
            'extension' => 'pdf', 'mime_type' => 'application/pdf', 'size' => 1024,
            'checksum_sha256' => hash('sha256', fake()->uuid()), 'file_role' => FileRole::FinalReference, 'uploaded_by' => User::factory(), ];
    }
}
