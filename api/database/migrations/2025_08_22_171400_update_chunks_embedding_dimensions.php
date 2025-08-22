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
        // Drop existing embedding column and index
        DB::statement('DROP INDEX IF EXISTS chunks_embedding_ivfflat_idx');
        DB::statement('ALTER TABLE chunks DROP COLUMN IF EXISTS embedding');
        
        // Recreate embedding column with 768 dimensions
        DB::statement('ALTER TABLE chunks ADD COLUMN embedding vector(768)');
        DB::statement('CREATE INDEX chunks_embedding_ivfflat_idx ON chunks USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop existing embedding column and index
        DB::statement('DROP INDEX IF EXISTS chunks_embedding_ivfflat_idx');
        DB::statement('ALTER TABLE chunks DROP COLUMN IF EXISTS embedding');
        
        // Recreate embedding column with 1536 dimensions (original)
        DB::statement('ALTER TABLE chunks ADD COLUMN embedding vector(1536)');
        DB::statement('CREATE INDEX chunks_embedding_ivfflat_idx ON chunks USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
    }
};
