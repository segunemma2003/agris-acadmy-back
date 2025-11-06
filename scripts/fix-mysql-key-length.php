<?php

/**
 * Fix MySQL "Specified key was too long" error
 * Run this script on your server: php scripts/fix-mysql-key-length.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔧 Fixing MySQL key length issues...\n\n";

try {
    // Fix password_reset_tokens table
    if (Schema::hasTable('password_reset_tokens')) {
        echo "📊 Fixing password_reset_tokens table...\n";

        try {
            // Drop primary key
            DB::statement('ALTER TABLE password_reset_tokens DROP PRIMARY KEY');
            echo "  ✓ Dropped primary key\n";

            // Modify email column to 191 characters
            DB::statement('ALTER TABLE password_reset_tokens MODIFY email VARCHAR(191) NOT NULL');
            echo "  ✓ Modified email column to VARCHAR(191)\n";

            // Re-add primary key
            DB::statement('ALTER TABLE password_reset_tokens ADD PRIMARY KEY (email)');
            echo "  ✓ Re-added primary key\n";

            echo "✅ password_reset_tokens table fixed!\n\n";
        } catch (\Exception $e) {
            echo "  ⚠️  Error: " . $e->getMessage() . "\n";
            echo "  🔄 Trying to recreate table...\n";

            // Drop and recreate
            Schema::dropIfExists('password_reset_tokens');
            Schema::create('password_reset_tokens', function ($table) {
                $table->string('email', 191)->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });

            echo "  ✅ Table recreated successfully!\n\n";
        }
    } else {
        echo "⚠️  password_reset_tokens table does not exist. Creating it...\n";
        Schema::create('password_reset_tokens', function ($table) {
            $table->string('email', 191)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        echo "✅ Table created!\n\n";
    }

    echo "✅ All fixes applied successfully!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n💡 Try running the migration instead:\n";
    echo "   php artisan migrate\n";
    exit(1);
}

