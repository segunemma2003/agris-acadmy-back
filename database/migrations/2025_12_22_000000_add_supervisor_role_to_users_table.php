<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table with the new enum value
        // For MySQL/PostgreSQL, we can modify the enum directly
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support modifying enum, so we'll use a workaround
            // We'll just allow the value in the application layer
            // The constraint will be enforced by Laravel validation instead
            DB::statement("PRAGMA foreign_keys=off;");
            DB::statement("CREATE TABLE users_new AS SELECT * FROM users;");
            DB::statement("DROP TABLE users;");
            DB::statement("CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                email_verified_at DATETIME NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(255) DEFAULT 'student',
                phone VARCHAR(255) NULL,
                bio TEXT NULL,
                avatar VARCHAR(255) NULL,
                is_active BOOLEAN DEFAULT 1,
                last_login_at DATETIME NULL,
                remember_token VARCHAR(100) NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            );");
            DB::statement("INSERT INTO users SELECT * FROM users_new;");
            DB::statement("DROP TABLE users_new;");
            DB::statement("CREATE INDEX users_role_index ON users(role);");
            DB::statement("CREATE INDEX users_is_active_index ON users(is_active);");
            DB::statement("PRAGMA foreign_keys=on;");
        } else {
            // For MySQL/PostgreSQL, modify the enum
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'tutor', 'student', 'tagdev', 'supervisor') DEFAULT 'student'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite workaround - recreate without supervisor
            DB::statement("PRAGMA foreign_keys=off;");
            DB::statement("CREATE TABLE users_new AS SELECT * FROM users;");
            DB::statement("DROP TABLE users;");
            DB::statement("CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                email_verified_at DATETIME NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(255) DEFAULT 'student',
                phone VARCHAR(255) NULL,
                bio TEXT NULL,
                avatar VARCHAR(255) NULL,
                is_active BOOLEAN DEFAULT 1,
                last_login_at DATETIME NULL,
                remember_token VARCHAR(100) NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            );");
            DB::statement("INSERT INTO users SELECT * FROM users_new;");
            DB::statement("DROP TABLE users_new;");
            DB::statement("CREATE INDEX users_role_index ON users(role);");
            DB::statement("CREATE INDEX users_is_active_index ON users(is_active);");
            DB::statement("PRAGMA foreign_keys=on;");
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'tutor', 'student', 'tagdev') DEFAULT 'student'");
        }
    }
};








