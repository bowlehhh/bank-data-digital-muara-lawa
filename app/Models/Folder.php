<?php

namespace App\Models;

use App\FolderType;
use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['parent_id', 'year', 'code', 'name', 'description', 'folder_type', 'sort_order', 'is_official', 'is_custom', 'created_by', 'is_temporary', 'is_active'])]
#[Hidden(['year_scope', 'parent_scope'])]
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'folder_type' => FolderType::class,
            'sort_order' => 'integer',
            'is_official' => 'boolean',
            'is_custom' => 'boolean',
            'is_temporary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function getPathLabelAttribute(): string
    {
        return $this->parent ? $this->parent->path_label.' / '.$this->name : $this->name;
    }
}
