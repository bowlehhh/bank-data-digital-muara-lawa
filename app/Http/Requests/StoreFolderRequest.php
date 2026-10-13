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
            $parent = Folder::query()->find($this->integer('parent_id'));
            $name = self::normalizeName((string) $this->input('name'));
            $code = $this->nextCode($parent);

            if ($parent && (! $parent->is_active || $parent->is_temporary || $parent->year !== null && $parent->year !== $this->integer('year'))) {
                $validator->errors()->add('parent_id', 'Folder induk tidak aktif atau tidak sesuai tahun.');
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

    private function nextCode(?Folder $parent): string
    {
        $used = Folder::query()->where('parent_id', $parent?->id)->where('year', $this->integer('year'))->pluck('code')->map(fn ($code) => (int) substr($code, 0, 2))->filter(fn ($code) => $code > 0 && $code < 99);
        for ($code = 1; $code <= 98; $code++) {
            if (! $used->contains($code)) {
                return str_pad((string) $code, 2, '0', STR_PAD_LEFT);
            }
        }

        return '00';
    }
}
