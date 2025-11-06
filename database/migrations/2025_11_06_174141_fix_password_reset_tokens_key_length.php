<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fixes "Specified key was too long" error for password_reset_tokens table
     * by limiting email column to 191 characters (MySQL/MariaDB utf8mb4 limit)
     */
    public function up(): void
    {
        // Check if table exists
        if (Schema::hasTable('password_reset_tokens')) {
            try {
                // Try to modify the column directly
                // Drop primary key first
                DB::statement('ALTER TABLE password_reset_tokens DROP PRIMARY KEY');

                // Modify email column to 191 characters
                DB::statement('ALTER TABLE password_reset_tokens MODIFY email VARCHAR(191) NOT NULL');

                // Re-add primary key
                DB::statement('ALTER TABLE password_reset_tokens ADD PRIMARY KEY (email)');
            } catch (\Exception $e) {
                // If direct modification fails, drop and recreate
                Schema::dropIfExists('password_reset_tokens');
                Schema::create('password_reset_tokens', function (Blueprint $table) {
                    $table->string('email', 191)->primary();
                    $table->string('token');
                    $table->timestamp('created_at')->nullable();
                });
            }
        } else {
            // Create table if it doesn't exist
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email', 191)->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a fix migration, so we don't need to reverse it
        // The original migration will handle the table structure
    }
};
