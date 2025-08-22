<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');
        
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');
        } catch (\Exception $e) {
        }
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS vector;');
        DB::statement('DROP EXTENSION IF EXISTS pg_trgm;');
    }
};
