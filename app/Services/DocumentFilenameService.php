<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentFilenameService
{
    public function normalize(string $value, string $field = 'title'): string
    {
        if (preg_match('/[^\p{L}\p{N}\s_-]/u', $value) || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw ValidationException::withMessages([$field => 'Gunakan huruf, angka, spasi, atau tanda hubung; karakter khusus dan path tidak diizinkan.']);
        }
        $result = trim(preg_replace('/[\s_-]+/', '-', Str::upper(Str::ascii(trim($value)))), '-');
        if ($result === '' || ! preg_match('/\A[A-Z0-9]+(?:-[A-Z0-9]+)*\z/', $result)) {
            throw ValidationException::withMessages([$field => 'Isi teks yang dapat digunakan sebagai nama file.']);
        }

        return $result;
    }

    /** @param array<string, mixed> $data */
    public function generate(array $data, string $extension): string
    {
        $extension = strtolower($extension);
        if (! array_key_exists($extension, config('documents.mime_types'))) {
            throw ValidationException::withMessages(['file' => 'Jenis file tidak diizinkan.']);
        }
        $year = (int) $data['year'];
        if ($year < 2026 || $year > 9999) {
            throw ValidationException::withMessages(['year' => 'Tahun dokumen tidak valid.']);
        }
        $type = $this->normalize($data['document_type'], 'target_folder_id');
        $title = $this->normalize($data['title']);
        if (in_array($title, ['BARU', 'SCAN001', 'FILE-LAMA', 'DOKUMEN', 'FIX', 'FIX-2', 'PALING-FINAL', 'FINAL-BARU'], true)) {
            throw ValidationException::withMessages(['title' => 'Uraian terlalu umum. Gunakan uraian yang menjelaskan isi dokumen.']);
        }
        $version = $data['version_code'] ?? null;
        if ($version !== null && $version !== '' && ! preg_match('/\A(?:V(?:0[1-9]|[1-9][0-9]{1,8})|FINAL|FINAL_TTD)\z/', $version)) {
            throw ValidationException::withMessages(['version_code' => 'Gunakan V01, V02, V10 dan seterusnya, FINAL, atau FINAL_TTD.']);
        }
        $period = $data['period'] ?? null;
        $date = $data['document_date'] ?? null;
        if ($period && $date) {
            throw ValidationException::withMessages(['period' => 'Pilih periode atau tanggal, bukan keduanya.']);
        }
        $parts = [$year, $type, $title];
        if ($period) {
            if (! in_array($period, config('documents.periods'), true)) {
                throw ValidationException::withMessages(['period' => 'Pilih periode yang tersedia.']);
            }
            $parts[] = $period;
        }
        if ($date) {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (! $parsed || $parsed->format('Y-m-d') !== $date || (int) $parsed->format('Y') !== $year) {
                throw ValidationException::withMessages(['document_date' => 'Tanggal harus valid dan sesuai tahun dokumen.']);
            }
            $parts[] = $parsed->format('Ymd');
        }
        if ($version) {
            $parts[] = $version;
        } elseif (! $date || ! in_array($type, config('documents.version_optional_types'), true)) {
            throw ValidationException::withMessages(['version_code' => 'Versi wajib diisi. Tanpa versi hanya untuk surat atau dokumen pemeriksaan bertanggal.']);
        }
        $filename = implode('_', $parts).'.'.$extension;
        if (strlen($filename) > 240) {
            throw ValidationException::withMessages(['title' => 'Nama file terlalu panjang. Persingkat uraian.']);
        }

        return $filename;
    }
}
