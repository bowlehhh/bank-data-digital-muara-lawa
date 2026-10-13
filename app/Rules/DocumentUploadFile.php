<?php

namespace App\Rules;

use App\Services\DocumentFileInspector;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class DocumentUploadFile implements ValidationRule
{
    public function __construct(private DocumentFileInspector $inspector) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('File gagal diunggah. Pilih ulang file Anda.');

            return;
        }
        try {
            $this->inspector->inspect($value);
        } catch (ValidationException $exception) {
            foreach ($exception->errors()['file'] as $message) {
                $fail($message);
            }
        }
    }
}
