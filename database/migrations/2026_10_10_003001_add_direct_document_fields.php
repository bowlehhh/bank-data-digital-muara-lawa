<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('version_code', 20)->nullable()->after('document_date');
            $table->index(['folder_id', 'year', 'version_code']);
        });
        Schema::table('document_files', function (Blueprint $table): void {
            $table->foreignId('document_id')->nullable()->after('id')->constrained('documents')->restrictOnDelete();
            $table->index(['document_id', 'checksum_sha256']);
        });
    }

    public function down(): void
    {
        Schema::table('document_files', fn (Blueprint $table) => $table->dropConstrainedForeignId('document_id'));
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex(['folder_id', 'year', 'version_code']);
            $table->dropColumn('version_code');
        });
    }
};
