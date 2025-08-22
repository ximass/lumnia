<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kb_id');
            $table->string('source_type');
            $table->string('source_identifier');
            $table->string('content_hash');
            $table->string('status');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('kb_id')->references('id')->on('knowledge_bases')->onDelete('cascade');
            $table->index('kb_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
