<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveDocumentRequest;
use App\Models\Document;
use App\Models\Folder;
use App\Services\DocumentMoveService;
use Illuminate\Http\RedirectResponse;

class DocumentMoveController extends Controller
{
    public function __invoke(MoveDocumentRequest $request, Document $document, DocumentMoveService $moves): RedirectResponse
    {
        $folder = Folder::query()->findOrFail($request->integer('folder_id'));
        $moves->move($document, $folder);

        return back()->with('status', 'Dokumen berhasil dipindahkan ke folder baru.');
    }
}
