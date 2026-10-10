<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table): void {
            $table->boolean('is_custom')->default(false)->after('is_official');
            $table->foreignId('created_by')->nullable()->after('is_custom')->constrained('users')->nullOnDelete();
            $table->index(['year', 'parent_id', 'is_active', 'is_custom']);
        });
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['year', 'parent_id', 'is_active', 'is_custom']);
            $table->dropColumn(['is_custom', 'created_by']);
        });
    }
};
