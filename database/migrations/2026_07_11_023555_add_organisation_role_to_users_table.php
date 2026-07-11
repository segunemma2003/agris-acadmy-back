<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite stores `role` as a plain VARCHAR (no real ENUM), so only
        // MySQL/PostgreSQL need the ENUM widened. See the facilitator-role
        // migration for the same sqlite-vs-mysql split.
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'tutor', 'student', 'tagdev', 'facilitator', 'organisation') DEFAULT 'student'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::table('users')->where('role', 'organisation')->update(['role' => 'student']);
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'tutor', 'student', 'tagdev', 'facilitator') DEFAULT 'student'");
        }
    }
};
