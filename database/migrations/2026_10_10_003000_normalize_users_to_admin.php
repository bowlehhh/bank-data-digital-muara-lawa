<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        // Former role values are intentionally not reconstructed.
    }
};
