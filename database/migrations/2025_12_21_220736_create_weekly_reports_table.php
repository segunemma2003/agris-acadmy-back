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
        Schema::create('weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
            $table->date('report_week_start'); // Start date of the week being reported
            $table->date('report_week_end'); // End date of the week being reported
            $table->text('weekly_plan')->nullable(); // Plans for the week
            $table->text('achievements')->nullable(); // What was achieved
            $table->text('activities_completed')->nullable(); // Activities completed
            $table->integer('total_students')->default(0); // Total number of students
            $table->integer('active_students')->default(0); // Active students count
            $table->integer('completed_assignments')->default(0); // Completed assignments
            $table->text('challenges')->nullable(); // Challenges faced
            $table->text('next_week_plans')->nullable(); // Plans for next week
            $table->json('images')->nullable(); // Array of image paths
            $table->enum('status', ['draft', 'submitted', 'reviewed'])->default('draft');
            $table->text('admin_feedback')->nullable(); // Admin feedback
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['tutor_id', 'report_week_start']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_reports');
    }
};
