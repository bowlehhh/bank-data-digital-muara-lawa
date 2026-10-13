<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
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
            'storageSize' => $this->formatFileSize($bytes),
            'recentDocuments' => (clone $documents)->with('folder')->latest('id')->limit(5)->get(),
            'folderCount' => Folder::query()->where('is_active', true)->where('is_temporary', false)->count(),
        ]);
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $size = $bytes;
        $unitIndex = 0;

        while (abs($size) / 1024 > 0.9 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format(round($size, 0, PHP_ROUND_HALF_EVEN), 0, '.', ',').' '.$units[$unitIndex];
    }
}
