<?php

namespace App\Models;

use Database\Factories\DocumentLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['document_id', 'folder_id', 'notes'])]
class DocumentLink extends Model
{
    /** @use HasFactory<DocumentLinkFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }
}
