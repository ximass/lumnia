<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 50); // error, warning, critical, etc
            $table->string('message', 1000);
            $table->text('exception')->nullable(); // exception class
            $table->text('file')->nullable();
            $table->integer('line')->nullable();
            $table->text('trace')->nullable(); // stack trace
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, etc
            $table->json('request_data')->nullable();
            $table->string('ip', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('level');
            $table->index('created_at');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
