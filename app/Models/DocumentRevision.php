<?php

namespace App\Models;

use Database\Factories\DocumentRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['document_id', 'version_code', 'revision_number', 'notes', 'submitted_by', 'submitted_at', 'verified_by', 'verified_at'])]
class DocumentRevision extends Model
{
    /** @use HasFactory<DocumentRevisionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['revision_number' => 'integer', 'submitted_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }
}
