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
        Schema::table('course_resources', function (Blueprint $table) {
            // Nullable: existing resources stay course-wide; new ones can be scoped to a lesson.
            $table->foreignId('topic_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_resources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('topic_id');
        });
    }
};
