<?php

namespace App\Services;

use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

class DocumentFileInspector
{
    public function inspect(UploadedFile $file): string
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages(['file' => 'File gagal diunggah. Pilih ulang file Anda.']);
        }
        $path = $file->getRealPath();
        if ($path === false || ! is_readable($path)) {
            throw ValidationException::withMessages(['file' => 'Isi file tidak dapat dibaca. Pilih ulang file Anda.']);
        }
        if ($file->getSize() < 1024 || $file->getSize() > config('documents.max_size_kb') * 1024) {
            throw ValidationException::withMessages(['file' => 'Ukuran file harus minimal 1 KB dan maksimal '.(config('documents.max_size_kb') / 1024).' MB.']);
        }
        if (! $this->supportsFileinfo()) {
            throw ValidationException::withMessages(['file' => 'Upload belum dapat diproses karena pemeriksaan keamanan file di server tidak tersedia. Hubungi administrator untuk mengaktifkan ekstensi PHP fileinfo. File belum disimpan.']);
        }
        try {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => 'Pemeriksaan keamanan file gagal di server. Hubungi administrator atau coba kembali. File belum disimpan.']);
        }
        if (! is_string($mime) || $mime === '') {
            throw ValidationException::withMessages(['file' => 'Jenis isi file tidak dapat diperiksa. Pilih ulang file Anda. File belum disimpan.']);
        }
        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = config('documents.mime_types');
        if (! isset($allowed[$extension]) || ! in_array($mime, $allowed[$extension], true)) {
            throw ValidationException::withMessages(['file' => 'Isi file dan extension tidak sesuai atau jenis file tidak diizinkan.']);
        }
        if (in_array($extension, ['docx', 'xlsx'], true)) {
            $this->inspectOffice($path, $extension);
        }
        if (in_array($extension, ['jpg', 'jpeg', 'png'], true) && @getimagesize($path) === false) {
            throw ValidationException::withMessages(['file' => 'Gambar tidak valid.']);
        }
        if ($extension === 'csv') {
            $content = @file_get_contents($path);
            if ($content === false) {
                throw ValidationException::withMessages(['file' => 'Isi CSV tidak dapat dibaca. Pilih ulang file Anda.']);
            }
            if (str_contains($content, "\0") || preg_match('/<\?(?:php|=)|<\s*(?:script|html|iframe)\b/i', $content)) {
                throw ValidationException::withMessages(['file' => 'CSV harus berupa data teks tanpa kode program atau HTML.']);
            }
        }
        if (mb_strlen($file->getClientOriginalName()) > 255) {
            throw ValidationException::withMessages(['file' => 'Nama file asli maksimal 255 karakter.']);
        }

        return $mime;
    }

    public function supportsFileinfo(): bool
    {
        return class_exists(finfo::class) && defined('FILEINFO_MIME_TYPE');
    }

    public function supportsZip(): bool
    {
        return class_exists(ZipArchive::class);
    }

    private function inspectOffice(string $path, string $extension): void
    {
        if (! $this->supportsZip()) {
            throw ValidationException::withMessages(['file' => 'Dokumen DOCX/XLSX belum dapat diperiksa karena ekstensi PHP zip di server tidak tersedia. Hubungi administrator. File belum disimpan.']);
        }
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw ValidationException::withMessages(['file' => 'Dokumen Office tidak valid.']);
        }
        try {
            $entry = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
            $valid = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName($entry) !== false;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                if ($name === false || str_contains(strtolower($name), 'vbaproject')
                    || preg_match('~(?:^|/)\.\.(?:/|$)|^/|\\\\|\.(exe|php|js|html|sh|phar)$~i', $name)) {
                    $valid = false;
                }
            }
            if (! $valid) {
                throw ValidationException::withMessages(['file' => 'Dokumen Office tidak valid atau berisi konten aktif yang tidak diizinkan.']);
            }
        } finally {
            $zip->close();
        }
    }
}
