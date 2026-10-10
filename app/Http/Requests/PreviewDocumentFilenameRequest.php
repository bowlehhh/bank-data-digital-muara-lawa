<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PreviewDocumentFilenameRequest extends StoreDocumentRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['file'], $rules['file_role']);
        $rules['extension'] = ['required', Rule::in(array_keys(config('documents.mime_types')))];

        return $rules;
    }

    protected function previewExtension(): string
    {
        return $this->string('extension')->toString();
    }
}
