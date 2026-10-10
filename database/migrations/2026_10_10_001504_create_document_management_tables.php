<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('folders')->restrictOnDelete();
            $table->foreignId('target_folder_id')->constrained('folders')->restrictOnDelete();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('document_type', 100);
            $table->unsignedSmallInteger('year');
            $table->string('period', 30)->nullable();
            $table->date('document_date')->nullable();
            $table->enum('workflow_status', ['draft', 'pending_verification', 'revision_required', 'approved', 'rejected'])->default('pending_verification');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['created_by', 'year', 'workflow_status']);
            $table->index(['workflow_status', 'created_at']);
        });
        Schema::create('document_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->string('version_code', 20)->nullable();
            $table->unsignedInteger('revision_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'revision_number']);
        });
        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_revision_id')->constrained()->restrictOnDelete();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('disk', 40)->default('documents');
            $table->string('path', 500)->unique();
            $table->string('extension', 10);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->char('checksum_sha256', 64)->index();
            $table->enum('file_role', ['editable', 'final_reference', 'signed']);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->foreignId('folder_id')->constrained()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'folder_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_links');
        Schema::dropIfExists('document_files');
        Schema::dropIfExists('document_revisions');
        Schema::dropIfExists('documents');
    }
};
