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
        // Add tutor_id to modules table
        Schema::table('modules', function (Blueprint $table) {
            $table->unsignedBigInteger('tutor_id')->nullable()->after('course_id');
            $table->foreign('tutor_id')->references('id')->on('users')->onDelete('set null');
        });

        // Add tutor_id to topics table
        Schema::table('topics', function (Blueprint $table) {
            $table->unsignedBigInteger('tutor_id')->nullable()->after('module_id');
            $table->foreign('tutor_id')->references('id')->on('users')->onDelete('set null');
        });

        // Add tutor_id to module_tests table
        Schema::table('module_tests', function (Blueprint $table) {
            $table->unsignedBigInteger('tutor_id')->nullable()->after('course_id');
            $table->foreign('tutor_id')->references('id')->on('users')->onDelete('set null');
        });

        // Add tutor_id to topic_tests table
        Schema::table('topic_tests', function (Blueprint $table) {
            $table->unsignedBigInteger('tutor_id')->nullable()->after('course_id');
            $table->foreign('tutor_id')->references('id')->on('users')->onDelete('set null');
        });

        // Add tutor_id to course_vr_content table
        Schema::table('course_vr_content', function (Blueprint $table) {
            $table->unsignedBigInteger('tutor_id')->nullable()->after('course_id');
            $table->foreign('tutor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove tutor_id from course_vr_content table
        Schema::table('course_vr_content', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->dropColumn('tutor_id');
        });

        // Remove tutor_id from topic_tests table
        Schema::table('topic_tests', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->dropColumn('tutor_id');
        });

        // Remove tutor_id from module_tests table
        Schema::table('module_tests', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->dropColumn('tutor_id');
        });

        // Remove tutor_id from topics table
        Schema::table('topics', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->dropColumn('tutor_id');
        });

        // Remove tutor_id from modules table
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->dropColumn('tutor_id');
        });
    }
};
