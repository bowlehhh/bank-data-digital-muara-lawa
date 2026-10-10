<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewDocumentFilenameRequest;
use App\Services\DocumentFilenameService;
use App\Services\DocumentMetadataService;
use Illuminate\Http\JsonResponse;

class DocumentFilenamePreviewController extends Controller
{
    public function __invoke(PreviewDocumentFilenameRequest $request, DocumentFilenameService $filenames, DocumentMetadataService $metadata): JsonResponse
    {
        $data = $request->validated();
        $target = $metadata->target($data);

        return response()->json(['filename' => $filenames->generate([...$data, 'document_type' => $metadata->documentType($target)], $data['extension'])]);
    }
}
