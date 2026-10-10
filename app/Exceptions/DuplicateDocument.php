<?php

namespace App\Exceptions;

use App\Models\Document;
use RuntimeException;

class DuplicateDocument extends RuntimeException
{
    public function __construct(public readonly Document $document)
    {
        parent::__construct('File yang sama sudah tersimpan. Tidak ada salinan baru yang dibuat.');
    }
}
