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
        Schema::create('staff_onboarding_video_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('video_id'); // YouTube video ID
            $table->timestamp('watched_at')->useCurrent();
            $table->integer('watch_duration')->nullable(); // Duration watched in seconds
            $table->boolean('is_completed')->default(false); // Marked as completed (watched fully or user marked complete)
            $table->timestamps();

            $table->unique(['user_id', 'video_id']);
            $table->index(['user_id', 'is_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_onboarding_video_views');
    }
};
