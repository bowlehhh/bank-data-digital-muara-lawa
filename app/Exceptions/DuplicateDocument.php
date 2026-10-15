<?php

namespace App\Exceptions;

use App\Models\Document;
use RuntimeException;

class DuplicateDocument extends RuntimeException
{
    public function __construct(public readonly Document $document)
    {
        parent::__construct('File yang sama masih tersimpan pada dokumen aktif. Hapus dokumen tersebut atau gunakan file lain.');
    }
}
