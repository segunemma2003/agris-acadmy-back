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
        Schema::table('topic_test_questions', function (Blueprint $table) {
            $table->text('question_ha')->nullable()->after('question');
            $table->json('options_ha')->nullable()->after('options');
            $table->text('explanation_ha')->nullable()->after('explanation');
            $table->boolean('is_translated_ha')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topic_test_questions', function (Blueprint $table) {
            $table->dropColumn(['question_ha', 'options_ha', 'explanation_ha', 'is_translated_ha']);
        });
    }
};
