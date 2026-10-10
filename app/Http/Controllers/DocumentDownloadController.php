<?php

namespace App\Http\Controllers;

use App\Models\DocumentFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function __invoke(DocumentFile $file): StreamedResponse
    {
        $document = $file->document ?? $file->revision?->document;
        abort_unless($document && Gate::allows('download', $document), 404);
        abort_unless($file->disk === 'documents' && Storage::disk('documents')->exists($file->path), 404);

        return Storage::disk('documents')->download($file->path, $file->stored_filename, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
