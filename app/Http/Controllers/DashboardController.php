<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use Illuminate\Support\Number;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $documents = Document::query();
        $bytes = (int) Document::query()->withSum('files', 'size')->get()->sum('files_sum_size');

        return view('dashboard', [
            'documentTotal' => (clone $documents)->count(),
            'currentYearTotal' => (clone $documents)->where('year', now()->year)->count(),
            'temporaryTotal' => (clone $documents)->whereHas('folder', fn ($query) => $query->where('is_temporary', true))->count(),
            'storageSize' => Number::fileSize($bytes),
            'recentDocuments' => (clone $documents)->with('folder')->latest('id')->limit(5)->get(),
            'folderCount' => Folder::query()->where('is_active', true)->count(),
        ]);
    }
}
