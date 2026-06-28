<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_intake_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source', ['chatbot', 'ussd'])->default('chatbot');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('occupation')->nullable();
            $table->string('state_lga')->nullable();
            $table->string('learning_goal')->nullable();
            $table->string('experience_level')->nullable();
            $table->enum('preferred_language', ['en', 'ha'])->nullable();
            $table->foreignId('interested_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->json('skipped_questions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_intake_answers');
    }
};
