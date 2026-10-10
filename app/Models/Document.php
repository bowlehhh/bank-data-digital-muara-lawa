<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['folder_id', 'target_folder_id', 'title', 'description', 'document_type', 'year', 'period', 'document_date', 'version_code', 'current_revision_id', 'created_by'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['year' => 'integer', 'document_date' => 'date'];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function targetFolder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'target_folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(DocumentRevision::class, 'current_revision_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(DocumentRevision::class)->orderByDesc('id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(DocumentLink::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }

    public function getLocationLabelAttribute(): string
    {
        return $this->folder?->is_temporary ? 'Arsip Sementara' : 'Tersimpan';
    }
}
