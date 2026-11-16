<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auto-generated learner CVs (built on course completion) and employer job
 * matches (learner ↔ posted opening, scored, then selected by the employer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->string('pdf_path')->nullable(); // private disk
            $table->boolean('open_to_work')->default(true);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('job_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apprenticeship_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->json('reasons')->nullable();
            // suggested → selected (employer chose) → accepted / declined (learner answered); dismissed = "not a fit"
            $table->string('status', 20)->default('suggested')->index();
            $table->timestamp('selected_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('apprenticeship_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['apprenticeship_slot_id', 'user_id']);
            $table->index(['apprenticeship_slot_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_matches');
        Schema::dropIfExists('learner_cvs');
    }
};
