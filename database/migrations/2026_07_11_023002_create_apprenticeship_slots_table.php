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
        Schema::create('apprenticeship_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('sector')->nullable();
            $table->string('state');
            $table->string('lga')->nullable();
            $table->string('duration');
            $table->foreignId('required_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->unsignedInteger('openings')->default(1);
            $table->date('application_deadline')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['state', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apprenticeship_slots');
    }
};
