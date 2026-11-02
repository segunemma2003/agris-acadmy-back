<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Timed captions per lesson video and language (English from YouTube, the rest translated).
        Schema::create('topic_captions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('language', 8);
            $table->json('cues')->nullable();          // [{start, end, text}] in seconds
            $table->unsignedInteger('translated_count')->default(0); // resume point for long translations
            $table->string('source', 20)->default('youtube'); // youtube | translated | edited
            $table->string('status', 20)->default('pending'); // pending | ready | failed | unavailable
            $table->text('error')->nullable();
            $table->string('youtube_video_id', 20)->nullable();
            $table->timestamps();
            $table->unique(['topic_id', 'language']);
        });

        // The Agrisiti YouTube channel connected by an admin (one row).
        Schema::create('youtube_connections', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id')->nullable();
            $table->string('channel_title')->nullable();
            $table->text('refresh_token');
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_captions');
        Schema::dropIfExists('youtube_connections');
    }
};
