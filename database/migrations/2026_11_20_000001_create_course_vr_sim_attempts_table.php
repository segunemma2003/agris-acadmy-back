<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per finished attempt at a VR Studio simulation lesson
 * (e.g. the fish pond). Stores the outcome plus the student's actions and
 * answers so tutors can review what each learner did.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_vr_sim_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_vr_content_id')->constrained('course_vr_content')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('lesson_id', 100)->nullable();
            $table->string('mode', 10)->default('simple'); // simple | 3d
            $table->boolean('passed')->default(false);
            $table->unsignedTinyInteger('score')->default(0);
            $table->unsignedSmallInteger('correct_answers')->default(0);
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->json('outcome')->nullable();
            $table->json('actions')->nullable();
            $table->json('answers')->nullable();
            $table->json('summary')->nullable(); // final pond numbers (fish alive, weight, ...)
            $table->timestamps();

            $table->index(['course_vr_content_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_vr_sim_attempts');
    }
};
