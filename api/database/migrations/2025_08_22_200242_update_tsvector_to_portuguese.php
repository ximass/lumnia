<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all existing tsvector columns to use Portuguese language
        DB::statement("UPDATE chunks SET tsv = to_tsvector('portuguese', text) WHERE text IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to English if needed
        DB::statement("UPDATE chunks SET tsv = to_tsvector('english', text) WHERE text IS NOT NULL");
    }
};
