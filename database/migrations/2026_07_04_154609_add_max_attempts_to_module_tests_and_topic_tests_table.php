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
        Schema::table('module_tests', function (Blueprint $table) {
            $table->unsignedInteger('max_attempts')->nullable()->after('passing_score');
        });

        Schema::table('topic_tests', function (Blueprint $table) {
            $table->unsignedInteger('max_attempts')->nullable()->after('passing_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_tests', function (Blueprint $table) {
            $table->dropColumn('max_attempts');
        });

        Schema::table('topic_tests', function (Blueprint $table) {
            $table->dropColumn('max_attempts');
        });
    }
};
