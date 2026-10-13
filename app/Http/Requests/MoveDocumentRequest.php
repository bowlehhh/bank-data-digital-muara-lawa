<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('move', $this->route('document')) ?? false;
    }

    public function rules(): array
    {
        return ['folder_id' => ['required', 'integer', Rule::exists('folders', 'id')->where('is_active', true)->where('is_temporary', false)]];
    }

    public function messages(): array
    {
        return ['folder_id.exists' => 'Pilih folder tujuan aktif yang tersedia.'];
    }
}
