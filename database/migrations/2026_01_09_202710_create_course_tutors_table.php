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
        Schema::create('course_tutors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // Primary tutor flag
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Unique constraint to prevent duplicate tutor assignments
            $table->unique(['course_id', 'tutor_id']);
            
            // Indexes for performance
            $table->index(['course_id', 'is_primary']);
            $table->index('tutor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_tutors');
    }
};
