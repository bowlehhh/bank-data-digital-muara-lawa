<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class DocumentFileStorageFailed extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('File tidak dapat disimpan.', previous: $previous);
    }
}
