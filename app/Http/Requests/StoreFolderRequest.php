<?php

namespace App\Http\Requests;

use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'between:2026,9999'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $parent = Folder::query()->find($this->integer('parent_id'));
            $name = self::normalizeName((string) $this->input('name'));

            if ($parent && (! $parent->is_active || $parent->is_temporary || $parent->year !== $this->integer('year'))) {
                $validator->errors()->add('parent_id', 'Pilih folder induk aktif dengan tahun yang sama, selain Arsip Sementara.');
            }
            if ($parent && $this->depth($parent) >= 5) {
                $validator->errors()->add('parent_id', 'Kedalaman folder maksimal lima tingkat.');
            }
            if ($name === '' || str_contains((string) $this->input('name'), '..') || preg_match('~[\\/:*?"<>|]~', (string) $this->input('name'))) {
                $validator->errors()->add('name', 'Nama folder tidak valid.');
            }
            if (Folder::query()->where('parent_id', $parent?->id)->where('year', $this->integer('year'))->where('name', $name)->exists()) {
                $validator->errors()->add('name', 'Nama folder sudah digunakan pada folder induk.');
            }
        }];
    }

    public static function normalizeName(string $name): string
    {
        $name = preg_replace('~[\\/:*?"<>|]~', '', trim($name)) ?? '';
        $name = preg_replace('/[\s_]+/', '_', $name) ?? '';

        return strtoupper(trim($name, '_'));
    }

    private function depth(Folder $folder): int
    {
        $depth = 1;
        while ($folder->parent_id && $depth <= 5) {
            $folder = $folder->parent ?: Folder::query()->find($folder->parent_id);
            $depth++;
        }

        return $depth;
    }
}
