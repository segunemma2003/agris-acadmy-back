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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('enrollment_code')->unique()->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('progress_percentage', 5, 2)->default(0.00);
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate enrollments
            $table->unique(['user_id', 'course_id']);

            // Indexes for performance with 100K users
            $table->index(['user_id', 'status']);
            $table->index(['course_id', 'status']);
            $table->index('enrollment_code');
            $table->index('enrolled_at');
            $table->index('progress_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
