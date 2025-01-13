<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInformationSourcesTable extends Migration
{
    public function up(): void
    {
        Schema::create('information_sources', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('information_sources');
    }
}