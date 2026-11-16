<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Location-based (learner ↔ facilitator) messages have no course. They used to
 * be written with course_id = 0, which the courses foreign key rejects; they
 * are now stored with course_id NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rows without a course can't satisfy NOT NULL; leave the column nullable.
    }
};
