<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DIY tasks become step-by-step practicals: numbered steps, an observation form
 * the learner fills in (once, or several times e.g. daily), and tutor review.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_diy_content', function (Blueprint $table) {
            $table->foreignId('tutor_id')->nullable()->after('course_id')->constrained('users')->nullOnDelete();
            $table->json('steps')->nullable()->after('instructions');
            $table->json('observation_fields')->nullable()->after('steps');
            $table->unsignedSmallInteger('entries_required')->default(1)->after('observation_fields');
            $table->string('entry_label', 40)->default('Day')->after('entries_required');
            $table->boolean('requires_review')->default(true)->after('entry_label');
        });

        Schema::create('course_diy_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_diy_content_id')->constrained('course_diy_content')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('steps_done')->nullable();
            $table->json('entries')->nullable();
            $table->string('status', 20)->default('in_progress'); // in_progress | submitted | approved | needs_work
            $table->text('tutor_feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['course_diy_content_id', 'user_id']);
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_diy_submissions');
        Schema::table('course_diy_content', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tutor_id');
            $table->dropColumn(['steps', 'observation_fields', 'entries_required', 'entry_label', 'requires_review']);
        });
    }
};
