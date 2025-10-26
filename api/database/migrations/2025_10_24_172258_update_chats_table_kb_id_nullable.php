<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['kb_id']);
            
            $table->uuid('kb_id')->nullable()->change();
            
            $table->foreign('kb_id')
                ->references('id')
                ->on('knowledge_bases')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['kb_id']);
            
            $table->uuid('kb_id')->nullable(false)->change();
            
            $table->foreign('kb_id')
                ->references('id')
                ->on('knowledge_bases');
        });
    }
};
