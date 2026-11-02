<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Participant registration form (Parts 1–4) and the end-of-course check
 * (Part 5). Option values live in App\Support\ParticipantProfile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Consent to the "Your Information, Your Choice" notice
            $table->timestamp('consent_given_at')->nullable();
            $table->string('consent_version', 50)->nullable();
            // Part 1 — about you
            $table->string('first_name', 100)->nullable();
            $table->string('surname', 100)->nullable();
            $table->string('nationality', 20)->nullable();
            $table->string('has_disability', 20)->nullable();
            $table->json('disability_types')->nullable();
            $table->string('displacement_status', 30)->nullable();
            $table->string('women_category', 30)->nullable();
            $table->string('education_level', 30)->nullable();
            // Part 2 — where you live (state, lga already exist)
            $table->string('locality_type', 20)->nullable();
            // Part 3 — your work today
            $table->string('learner_category', 30)->nullable();
            $table->string('main_work', 30)->nullable();
            $table->string('job_title')->nullable();
            $table->string('employment_type', 20)->nullable();
            $table->unsignedTinyInteger('work_started_month')->nullable();
            $table->unsignedSmallInteger('work_started_year')->nullable();
            $table->string('work_organisation')->nullable();
            $table->string('work_sector', 20)->nullable();
            $table->string('main_commodity', 30)->nullable();
            $table->string('income_band', 30)->nullable();
            $table->string('value_chain', 30)->nullable();
        });

        // Part 4 — answered per course, when enrolling
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('learning_type', 30)->nullable();
            $table->string('learning_mode', 20)->nullable();
            $table->string('heard_from', 30)->nullable();
            $table->string('partner_hub')->nullable();
        });

        // Part 5 — end-of-course check, at completion and six months later
        Schema::create('course_outcome_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 20); // completion | six_month
            $table->boolean('completed_course')->nullable();
            $table->string('work_change', 30)->nullable();
            $table->boolean('started_enterprise')->nullable();
            $table->string('feels_respected', 20)->nullable();
            $table->string('income_band', 30)->nullable();
            $table->string('rating', 20)->nullable();
            $table->string('used_learning', 20)->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->text('improvement')->nullable();
            $table->string('declaration_name')->nullable();
            $table->date('declaration_date')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'stage']);
            $table->index(['course_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_outcome_surveys');
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['learning_type', 'learning_mode', 'heard_from', 'partner_hub']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'consent_given_at', 'consent_version', 'first_name', 'surname', 'nationality', 'has_disability',
                'disability_types', 'displacement_status', 'women_category', 'education_level', 'locality_type',
                'learner_category', 'main_work', 'job_title', 'employment_type', 'work_started_month',
                'work_started_year', 'work_organisation', 'work_sector', 'main_commodity', 'income_band', 'value_chain',
            ]);
        });
    }
};
