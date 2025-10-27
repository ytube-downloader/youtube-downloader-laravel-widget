<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->uuid('download_id')->unique();
            $table->text('video_url');
            $table->string('video_title')->nullable();
            $table->string('platform')->nullable();
            $table->string('quality')->nullable();
            $table->string('format')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->enum('status', ['pending', 'queued', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('storage_path')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('download_id');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};