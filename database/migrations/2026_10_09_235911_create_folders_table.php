<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('folders')->restrictOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('code', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('folder_type', ['group', 'document_type', 'temporary']);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_official')->default(true);
            $table->boolean('is_temporary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            // NULL pada folder global/root harus ikut dilindungi oleh unique constraint.
            $table->unsignedSmallInteger('year_scope')->storedAs('COALESCE(year, 0)');
            $table->unsignedBigInteger('parent_scope')->storedAs('COALESCE(parent_id, 0)');
            $table->unique(['year_scope', 'parent_scope', 'code']);
            $table->index(['year', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
