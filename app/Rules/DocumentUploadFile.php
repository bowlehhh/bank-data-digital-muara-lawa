<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class DocumentUploadFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('File gagal diunggah. Pilih ulang file Anda.');

            return;
        }
        $extension = strtolower($value->getClientOriginalExtension());
        $allowed = config('documents.mime_types');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($value->getRealPath());
        if (! isset($allowed[$extension]) || ! in_array($mime, $allowed[$extension], true)) {
            $fail('Isi file dan extension tidak sesuai atau jenis file tidak diizinkan.');

            return;
        }
        if (in_array($extension, ['docx', 'xlsx'], true)) {
            $zip = new ZipArchive;
            if ($zip->open($value->getRealPath()) !== true) {
                $fail('Dokumen Office tidak valid.');

                return;
            }
            $entry = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
            $valid = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName($entry) !== false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_contains(strtolower($name), 'vbaproject') || preg_match('/\.(exe|php|js|html|sh|phar)$/i', $name)) {
                    $valid = false;
                }
            }
            $zip->close();
            if (! $valid) {
                $fail('Dokumen Office tidak valid atau berisi konten aktif yang tidak diizinkan.');
            }
        }
        if (in_array($extension, ['jpg', 'jpeg', 'png'], true) && @getimagesize($value->getRealPath()) === false) {
            $fail('Gambar tidak valid.');
        }
        if ($extension === 'csv') {
            $content = file_get_contents($value->getRealPath());
            if (str_contains($content, "\0") || preg_match('/<\?(?:php|=)|<\s*(?:script|html|iframe)\b/i', $content)) {
                $fail('CSV harus berupa data teks tanpa kode program atau HTML.');
            }
        }
        if (mb_strlen($value->getClientOriginalName()) > 255) {
            $fail('Nama file asli maksimal 255 karakter.');
        }
    }
}
