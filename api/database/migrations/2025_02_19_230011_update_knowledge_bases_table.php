<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->timestampTz('modified_at')->nullable();
            $table->bigInteger('size')->nullable();
            $table->string('digest')->nullable();
            $table->text('details')->nullable();
            $table->text('content')->nullable()->change();
        });
    }
};