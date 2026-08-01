<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_intervention_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason'); // inactive_7d | quiz_failed_twice
            $table->string('context_key'); // e.g. inactive_7d, module_test:12, topic_test:5
            $table->string('learner_name');
            $table->timestamp('last_login_at')->nullable();
            $table->string('stuck_label')->nullable(); // module / quiz name
            $table->unsignedBigInteger('facilitator_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reason', 'context_key'], 'learner_intervention_unique');
            $table->index(['emailed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_intervention_alerts');
    }
};
