<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chunks', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('source_id');
            $table->uuid('kb_id');
            $table->integer('chunk_index');
            $table->text('text');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('source_id')->references('id')->on('sources')->onDelete('cascade');
            $table->foreign('kb_id')->references('id')->on('knowledge_bases')->onDelete('cascade');
            $table->index('kb_id');
        });

        // Add tsvector column using raw SQL
        DB::statement('ALTER TABLE chunks ADD COLUMN tsv tsvector');

        // Create GIN index for full-text search
        DB::statement('CREATE INDEX chunks_tsv_gin_idx ON chunks USING gin(tsv)');

        // Add embedding column only if vector extension is available
        try {
            DB::statement('ALTER TABLE chunks ADD COLUMN embedding vector(1536)');
            DB::statement('CREATE INDEX chunks_embedding_ivfflat_idx ON chunks USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
        } catch (\Exception $e) {
            // Vector extension not available, skip embedding column
            // You can install it later with: CREATE EXTENSION vector;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chunks');
    }
};
