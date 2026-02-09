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
        Schema::create('video_call_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type')->default('video_call'); // video_call, video_request
            $table->string('status')->default('pending'); // pending, confirmed, in_progress, completed, cancelled
            $table->datetime('scheduled_at');
            $table->integer('duration_minutes'); // 5, 10, or 15
            $table->integer('extension_minutes')->default(0); // 0, 5, or 10 (can only extend once)
            $table->boolean('is_extended')->default(false); // Track if already extended
            $table->datetime('started_at')->nullable();
            $table->datetime('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('meeting_id')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['student_id', 'scheduled_at']);
            $table->index(['tutor_id', 'scheduled_at']);
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_call_bookings');
    }
};
