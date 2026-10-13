<?php

namespace App\Services;

use App\Exceptions\DuplicateDocument;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DocumentUploadService
{
    public function __construct(private DocumentFilenameService $filenames, private DocumentChecksumService $checksums, private DocumentMetadataService $metadata, private DocumentFileInspector $inspector) {}

    /** @param array<string, mixed> $data */
    public function upload(User $user, array $data, UploadedFile $file): Document
    {
        Gate::forUser($user)->authorize('create', Document::class);
        $mime = $this->inspector->inspect($file);
        $checksum = $this->checksums->calculate($file);
        $directory = null;
        try {
            return DB::transaction(function () use ($user, $data, $file, $checksum, $mime, &$directory): Document {
                // Satu row tetap menyerialkan pengecekan checksum lintas pengguna/tahun sampai commit.
                Folder::query()->where('code', '00_PEDOMAN_DAN_SOP')->whereNull('year')->whereNull('parent_id')->lockForUpdate()->firstOrFail();
                $target = $this->metadata->target($data);
                $duplicate = DocumentFile::query()->where('checksum_sha256', $checksum)->with(['document', 'revision.document'])->first();
                if ($duplicate) {
                    throw new DuplicateDocument($duplicate->document ?? $duplicate->revision->document);
                }
                $type = $this->metadata->documentType($target);
                $extension = strtolower($file->getClientOriginalExtension());
                $name = $this->filenames->generate([...$data, 'document_type' => $type], $extension);
                $document = Document::create([
                    'folder_id' => $target->id, 'target_folder_id' => $target->id,
                    'title' => trim(preg_replace('/\s+/u', ' ', $data['title'])), 'description' => $data['description'] ?? null,
                    'document_type' => $type, 'year' => $data['year'], 'period' => $data['period'] ?? null,
                    'document_date' => $data['document_date'] ?? null, 'version_code' => ($data['version_code'] ?? null) ?: null, 'created_by' => $user->id,
                ]);
                $revision = $document->revisions()->create(['version_code' => ($data['version_code'] ?? null) ?: null, 'revision_number' => 1, 'submitted_by' => $user->id, 'submitted_at' => now()]);
                $directory = $data['year'].'/'.$target->code.'/'.Str::uuid();
                $path = Storage::disk('documents')->putFileAs($directory, $file, $name, ['visibility' => 'private']);
                if ($path === false) {
                    throw new RuntimeException('Penyimpanan file gagal.');
                }
                $revision->files()->create([
                    'document_id' => $document->id,
                    'original_filename' => $file->getClientOriginalName(), 'stored_filename' => $name, 'disk' => 'documents', 'path' => $path,
                    'extension' => $extension, 'mime_type' => $mime, 'size' => $file->getSize(),
                    'checksum_sha256' => $checksum, 'file_role' => $data['file_role'], 'uploaded_by' => $user->id,
                ]);
                $document->update(['current_revision_id' => $revision->id]);

                return $document;
            });
        } catch (Throwable $e) {
            if ($directory !== null) {
                try {
                    if (! Storage::disk('documents')->deleteDirectory($directory)) {
                        throw new RuntimeException('Pembersihan unggahan gagal.');
                    }
                } catch (Throwable $cleanupError) {
                    report($cleanupError);
                }
            }
            throw $e;
        }
    }
}
