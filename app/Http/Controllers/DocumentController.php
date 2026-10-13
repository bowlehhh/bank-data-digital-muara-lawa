<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateDocument;
use App\Http\Requests\ListDocumentsRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Folder;
use App\Services\DocumentMetadataService;
use App\Services\DocumentUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class DocumentController extends Controller
{
    public function index(ListDocumentsRequest $request): View
    {
        $data = $request->validated();
        $activeFolder = isset($data['folder_id']) ? Folder::query()->with('parent')->where('is_temporary', false)->find($data['folder_id']) : null;
        $tree = Folder::query()->with(['children.children.children.children'])->withCount(['children', 'documents'])->whereNull('parent_id')->where('is_active', true)->where('is_temporary', false)->orderBy('year')->orderBy('sort_order')->get();
        $query = Document::query()->with(['folder', 'currentRevision', 'files']);
        if ($term = $data['q'] ?? null) {
            $like = '%'.$term.'%';
            $query->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('document_type', 'like', $like)->orWhere('period', 'like', $like)->orWhere('year', 'like', $like)->orWhere('version_code', 'like', $like)->orWhereHas('folder', fn ($folder) => $folder->where('name', 'like', $like)->orWhere('code', 'like', $like))->orWhereHas('files', fn ($files) => $files->where('stored_filename', 'like', $like)->orWhere('original_filename', 'like', $like)));
        }

        return view('documents.index', [
            'documents' => $query->when($data['year'] ?? null, fn ($q, $value) => $q->where('year', $value))->when($data['folder_id'] ?? null, fn ($q, $value) => $q->where('folder_id', $value))->when($data['period'] ?? null, fn ($q, $value) => $q->where('period', $value))->when($data['version_code'] ?? null, fn ($q, $value) => $q->where('version_code', $value))->when($data['extension'] ?? null, fn ($q, $value) => $q->whereHas('files', fn ($files) => $files->where('extension', $value)))->orderBy('created_at', ($data['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc')->orderByDesc('id')->paginate(15)->withQueryString(),
            'years' => Document::query()->select('year')->distinct()->orderByDesc('year')->pluck('year'),
            'folders' => Folder::query()->where('is_active', true)->where('is_temporary', false)->orderBy('year')->orderBy('sort_order')->get(),
            'tree' => $tree,
            'activeFolder' => $activeFolder,
            'subfolders' => $activeFolder?->children()->where('is_active', true)->where('is_temporary', false)->withCount(['children', 'documents'])->orderBy('sort_order')->get() ?? collect(),
        ]);
    }

    public function create(DocumentMetadataService $metadata): View
    {
        Gate::authorize('create', Document::class);

        $folders = Folder::query()
            ->with('parent.parent.parent.parent')
            ->where('is_active', true)
            ->where('is_temporary', false)
            ->whereNotNull('year')
            ->orderBy('year')
            ->orderBy('sort_order')
            ->get();
        $targets = $folders->filter(fn (Folder $folder): bool => $metadata->uploadGroup($folder) !== null);
        $targetGroups = $targets->mapWithKeys(fn (Folder $folder): array => [$folder->id => $metadata->uploadGroup($folder)->id]);
        $groups = $folders->whereIn('id', $targetGroups->values());
        $years = $groups->pluck('year')->unique()->sortDesc()->values();

        return view('documents.create', compact('years', 'groups', 'targets', 'targetGroups'));
    }

    public function store(StoreDocumentRequest $request, DocumentUploadService $uploads): RedirectResponse
    {
        try {
            $document = $uploads->upload($request->user(), $request->safe()->except('file'), $request->file('file'));
        } catch (DuplicateDocument $e) {
            $response = back()->withInput($request->except('file'))->withErrors(['file' => $e->getMessage()]);
            if ($request->user()->can('view', $e->document)) {
                $response->with('duplicate_document_id', $e->document->id);
            }

            return $response;
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return back()->withInput($request->except('file'))->withErrors(['file' => 'Dokumen belum tersimpan karena terjadi gangguan. Silakan coba kembali.']);
        }

        return redirect()->route('documents.show', $document)->with('status', 'Dokumen berhasil disimpan.');
    }

    public function show(Document $document): View
    {
        abort_unless(Gate::allows('view', $document), 404);
        $document->load(['folder.parent', 'targetFolder.parent', 'creator', 'files', 'revisions.files', 'revisions.submitter']);
        $moveFolders = Folder::query()->with('parent.parent.parent.parent')->where('year', $document->year)->where('is_active', true)->where('is_temporary', false)->orderBy('sort_order')->get();

        return view('documents.show', compact('document', 'moveFolders'));
    }

    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);
        $document->delete();

        return redirect()->route('documents.index')->with('status', 'Dokumen dipindahkan ke sampah. File fisik belum dihapus permanen.');
    }
}
