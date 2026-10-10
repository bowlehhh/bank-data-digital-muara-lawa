<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FolderController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('documents.index');
    }

    public function create(): View
    {
        $folders = Folder::query()->where('is_active', true)->orderBy('year')->orderBy('sort_order')->get();

        return view('folders.create', compact('folders'));
    }

    public function store(StoreFolderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $name = StoreFolderRequest::normalizeName($data['name']);
        $code = $this->nextCode($data['parent_id'] ?? null, (int) $data['year']);
        $fullCode = $code.'_'.$name;

        $folder = DB::transaction(function () use ($data, $name, $code, $fullCode, $request): Folder {
            $folder = Folder::create([
                'parent_id' => $data['parent_id'] ?? null, 'year' => $data['year'], 'code' => $fullCode,
                'name' => $name, 'description' => $data['description'] ?? null, 'folder_type' => $data['parent_id'] ? 'document_type' : 'group',
                'sort_order' => (int) $code, 'is_official' => false, 'is_custom' => true,
                'created_by' => $request->user()->id, 'is_temporary' => false, 'is_active' => true,
            ]);
            if (! Storage::disk('documents')->makeDirectory($data['year'].'/'.$fullCode)) {
                throw new \RuntimeException('Direktori folder gagal dibuat.');
            }

            return $folder;
        });

        return redirect()->route('documents.index', ['folder_id' => $folder->id])->with('status', 'Folder baru berhasil dibuat.');
    }

    private function nextCode(?int $parentId, int $year): string
    {
        $used = Folder::query()->where('parent_id', $parentId)->where('year', $year)->pluck('code')->map(fn ($code) => (int) substr($code, 0, 2));
        for ($number = 1; $number <= 98; $number++) {
            if (! $used->contains($number)) {
                return str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            }
        }

        abort(422, 'Nomor folder untuk induk ini sudah penuh.');
    }

    public function show(Folder $folder): View
    {
        abort_unless($folder->is_active && $folder->is_official, 404);
        $folder->load(['parent', 'children'])->loadCount('documents');
        $documents = $folder->documents()->with('files')->latest('id')->paginate(15)->withQueryString();

        return view('folders.show', compact('folder', 'documents'));
    }

    public function edit(Folder $folder): View
    {
        abort_unless($folder->is_active, 404);

        return view('folders.edit', compact('folder'));
    }

    public function update(UpdateFolderRequest $request, Folder $folder): RedirectResponse
    {
        abort_unless($folder->is_active, 404);
        $folder->update(['name' => StoreFolderRequest::normalizeName($request->string('name')->toString()), 'description' => $request->input('description')]);

        return redirect()->route('documents.index', ['folder_id' => $folder->id])->with('status', 'Folder berhasil diperbarui.');
    }

    public function destroy(Folder $folder): RedirectResponse
    {
        abort_unless($folder->is_active, 404);
        if ($folder->children()->exists() || $folder->documents()->exists()) {
            return back()->withErrors(['folder' => 'Folder tidak dapat dihapus karena masih berisi subfolder atau dokumen.']);
        }
        Storage::disk('documents')->deleteDirectory($folder->year.'/'.$folder->code);
        $folder->update(['is_active' => false]);

        return redirect()->route('documents.index')->with('status', 'Folder berhasil dihapus.');
    }
}
