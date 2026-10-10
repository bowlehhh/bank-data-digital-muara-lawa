<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class DocumentChecksumService
{
    public function calculate(UploadedFile $file): string
    {
        $checksum = hash_file('sha256', $file->getRealPath());
        if ($checksum === false) {
            throw new RuntimeException('Checksum file tidak dapat dihitung.');
        }

        return $checksum;
    }
}
