<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemporaryArchiveController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = Document::query()->with(['folder', 'creator', 'files'])->whereHas('folder', fn ($folder) => $folder->where('code', '99_ARSIP_SEMENTARA')->where('is_temporary', true));
        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(fn ($documents) => $documents->where('title', 'like', $term)->orWhere('description', 'like', $term)->orWhereHas('files', fn ($files) => $files->where('original_filename', 'like', $term)->orWhere('stored_filename', 'like', $term)));
        }
        $query->when($request->integer('year'), fn ($documents, $year) => $documents->where('year', $year));

        return view('documents.archive', ['documents' => $query->latest('id')->paginate(15)->withQueryString(), 'years' => Document::query()->whereHas('folder', fn ($folder) => $folder->where('code', '99_ARSIP_SEMENTARA'))->select('year')->distinct()->orderByDesc('year')->pluck('year'), 'archiveFolder' => Folder::query()->where('code', '99_ARSIP_SEMENTARA')->where('is_temporary', true)->firstOrFail()]);
    }
}
