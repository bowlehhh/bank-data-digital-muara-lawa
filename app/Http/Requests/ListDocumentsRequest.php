<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Document::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'], 'year' => ['nullable', 'integer', 'between:2026,9999'],
            'folder_id' => ['nullable', 'integer'], 'period' => ['nullable', 'string', 'max:30'],
            'version_code' => ['nullable', 'string', 'max:20'], 'extension' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', Rule::in(['stored', 'temporary'])], 'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
