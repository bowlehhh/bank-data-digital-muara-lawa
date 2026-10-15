<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class DocumentMetadataStorageFailed extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Metadata dokumen tidak dapat disimpan.', previous: $previous);
    }
}
