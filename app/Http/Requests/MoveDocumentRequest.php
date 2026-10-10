<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('move', $this->route('document')) ?? false;
    }

    public function rules(): array
    {
        return ['folder_id' => ['required', 'integer', 'exists:folders,id']];
    }
}
