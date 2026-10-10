<?php

namespace App\Http\Requests;

use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:2000']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $folder = $this->route('folder');
            $name = StoreFolderRequest::normalizeName((string) $this->input('name'));
            if ($name === '' || str_contains((string) $this->input('name'), '..') || preg_match('~[\\/:*?"<>|]~', (string) $this->input('name'))) {
                $validator->errors()->add('name', 'Nama folder tidak valid.');
            }
            if ($folder instanceof Folder && Folder::query()->where('parent_id', $folder->parent_id)->where('year', $folder->year)->where('name', $name)->where('id', '<>', $folder->id)->exists()) {
                $validator->errors()->add('name', 'Nama folder sudah digunakan pada tingkat yang sama.');
            }
        }];
    }
}
