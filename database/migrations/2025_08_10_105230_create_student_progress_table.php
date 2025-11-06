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
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->boolean('is_completed')->default(false);
            $table->integer('watch_time_seconds')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0.00);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate progress records
            $table->unique(['user_id', 'topic_id']);

            // Indexes for performance with 100K users
            $table->index(['user_id', 'course_id']);
            $table->index(['course_id', 'is_completed']);
            $table->index('last_accessed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
