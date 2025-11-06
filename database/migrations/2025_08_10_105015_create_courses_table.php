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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('tutor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->text('description');
            $table->text('what_you_will_learn')->nullable(); // JSON array of learning outcomes
            $table->text('what_you_will_get')->nullable(); // JSON array of materials/resources
            $table->string('image')->nullable();
            $table->integer('materials_count')->default(0);
            $table->json('tags')->nullable();
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->integer('enrollment_count')->default(0);
            $table->decimal('price', 10, 2)->default(0.00);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('duration_minutes')->default(0);
            $table->string('level')->default('beginner'); // beginner, intermediate, advanced
            $table->string('language')->default('English');
            $table->json('course_information')->nullable(); // Additional course details
            $table->timestamps();

            // Indexes for performance with 100K users
            $table->index(['is_published', 'is_featured']);
            $table->index(['category_id', 'is_published']);
            $table->index(['tutor_id', 'is_published']);
            $table->index('slug');
            $table->index('rating');
            $table->index('enrollment_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
