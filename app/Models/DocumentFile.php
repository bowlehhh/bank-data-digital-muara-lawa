<?php

namespace App\Models;

use App\FileRole;
use Database\Factories\DocumentFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['document_id', 'document_revision_id', 'original_filename', 'stored_filename', 'disk', 'path', 'extension', 'mime_type', 'size', 'checksum_sha256', 'file_role', 'uploaded_by'])]
#[Hidden(['path', 'disk'])]
class DocumentFile extends Model
{
    /** @use HasFactory<DocumentFileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['size' => 'integer', 'file_role' => FileRole::class];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(DocumentRevision::class, 'document_revision_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
