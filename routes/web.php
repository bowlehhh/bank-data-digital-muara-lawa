<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\DocumentFilenamePreviewController;
use App\Http\Controllers\DocumentMoveController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\TemporaryArchiveController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents/filename-preview', DocumentFilenamePreviewController::class)->middleware('throttle:60,1')->name('documents.filename-preview');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::post('/documents/{document}/move', DocumentMoveController::class)->name('documents.move');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/document-files/{file}/download', DocumentDownloadController::class)->name('documents.download');
    Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
    Route::get('/folders/create', [FolderController::class, 'create'])->name('folders.create');
    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::get('/folders/{folder}/edit', [FolderController::class, 'edit'])->name('folders.edit');
    Route::put('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');
    Route::get('/folders/{folder}', [FolderController::class, 'show'])->name('folders.show');
    Route::get('/arsip-sementara', TemporaryArchiveController::class)->name('documents.archive');
    Route::get('/dashboard', DashboardController::class)->middleware('can:view-dashboard')->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
