<?php

namespace App\Http\Requests;

use App\FileRole;
use App\Models\Document;
use App\Rules\DocumentUploadFile;
use App\Services\DocumentFilenameService;
use App\Services\DocumentMetadataService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Document::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'between:2026,9999'],
            'group_folder_id' => ['required', 'integer'],
            'target_folder_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'timing' => ['required', Rule::in(['none', 'period', 'date'])],
            'period' => ['nullable', 'required_if:timing,period', 'prohibited_unless:timing,period', Rule::in(config('documents.periods'))],
            'document_date' => ['nullable', 'required_if:timing,date', 'prohibited_unless:timing,date', 'date_format:Y-m-d'],
            'version_code' => ['nullable', 'string', 'max:20'],
            'file_role' => ['required', Rule::enum(FileRole::class)],
            'file' => ['bail', 'required', 'file', 'min:1', 'max:'.config('documents.max_size_kb'), app(DocumentUploadFile::class)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            try {
                $metadata = app(DocumentMetadataService::class);
                $folder = $metadata->target($this->validated());
                app(DocumentFilenameService::class)->generate([...$this->validated(), 'document_type' => $metadata->documentType($folder)], $this->previewExtension());
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        }];
    }

    protected function previewExtension(): string
    {
        return strtolower($this->file('file')->getClientOriginalExtension());
    }

    public function attributes(): array
    {
        return ['year' => 'Tahun', 'group_folder_id' => 'Kelompok dokumen', 'target_folder_id' => 'Jenis dokumen', 'title' => 'Uraian', 'description' => 'Keterangan', 'timing' => 'Pilihan periode atau tanggal', 'period' => 'Periode', 'document_date' => 'Tanggal dokumen', 'version_code' => 'Versi', 'file_role' => 'Jenis file', 'file' => 'File'];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'integer' => ':attribute harus berupa angka.', 'string' => ':attribute harus berupa teks.', 'between' => ':attribute harus antara :min dan :max.', 'max' => ':attribute melebihi batas :max.', 'in' => 'Pilih :attribute yang tersedia.', 'enum' => 'Pilih jenis file yang tersedia.', 'required_if' => ':attribute wajib diisi sesuai pilihan Anda.', 'prohibited_unless' => ':attribute tidak boleh diisi untuk pilihan ini.', 'date_format' => 'Tanggal harus valid dengan format tahun-bulan-tanggal.', 'file.required' => 'Pilih file yang akan diunggah.', 'file.file' => 'Unggahan harus berupa file.', 'file.uploaded' => 'Unggahan gagal atau melebihi batas server. Pilih file lebih kecil.', 'file.min' => 'File tidak boleh kosong (minimal 1 KB).', 'file.max' => 'Ukuran file maksimal '.(config('documents.max_size_kb') / 1024).' MB.'];
    }
}
