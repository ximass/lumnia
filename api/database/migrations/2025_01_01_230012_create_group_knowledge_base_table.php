<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupKnowledgeBaseTable extends Migration
{
    public function up()
    {
        Schema::create('group_knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->uuid('kb_id');
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreign('kb_id')->references('id')->on('knowledge_bases')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('group_knowledge_base');
    }
}