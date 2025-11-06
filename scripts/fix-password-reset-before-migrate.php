<?php

/**
 * Fix password_reset_tokens table BEFORE running migrations
 * This script should be run if you're getting "key too long" errors during migration
 * 
 * Usage: php scripts/fix-password-reset-before-migrate.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔧 Fixing password_reset_tokens table before migration...\n\n";

try {
    $connection = DB::connection();
    $driver = $connection->getDriverName();
    
    echo "📊 Database driver: $driver\n";
    
    if ($driver === 'mysql' || $driver === 'mariadb') {
        // Check if table exists
        $tableExists = Schema::hasTable('password_reset_tokens');
        
        if ($tableExists) {
            echo "📋 Table exists. Checking structure...\n";
            
            // Get table structure
            $columns = DB::select("SHOW COLUMNS FROM password_reset_tokens WHERE Field = 'email'");
            
            if (!empty($columns)) {
                $column = $columns[0];
                $type = $column->Type;
                
                echo "  Current email column type: $type\n";
                
                // Check if it's VARCHAR(255) or longer
                if (preg_match('/varchar\((\d+)\)/i', $type, $matches)) {
                    $length = (int)$matches[1];
                    
                    if ($length > 191) {
                        echo "  ⚠️  Email column is too long ($length). Fixing...\n";
                        
                        try {
                            // Drop primary key
                            DB::statement('ALTER TABLE password_reset_tokens DROP PRIMARY KEY');
                            echo "    ✓ Dropped primary key\n";
                            
                            // Modify column
                            DB::statement('ALTER TABLE password_reset_tokens MODIFY email VARCHAR(191) NOT NULL');
                            echo "    ✓ Modified email column to VARCHAR(191)\n";
                            
                            // Re-add primary key
                            DB::statement('ALTER TABLE password_reset_tokens ADD PRIMARY KEY (email)');
                            echo "    ✓ Re-added primary key\n";
                            
                            echo "  ✅ Table fixed!\n\n";
                        } catch (\Exception $e) {
                            echo "  ❌ Error fixing table: " . $e->getMessage() . "\n";
                            echo "  🔄 Dropping and recreating table...\n";
                            
                            Schema::dropIfExists('password_reset_tokens');
                            Schema::create('password_reset_tokens', function ($table) {
                                $table->string('email', 191)->primary();
                                $table->string('token', 64);
                                $table->timestamp('created_at')->nullable();
                            });
                            
                            echo "  ✅ Table recreated!\n\n";
                        }
                    } else {
                        echo "  ✅ Email column is already correct (length: $length)\n\n";
                    }
                }
            }
        } else {
            echo "📋 Table does not exist. It will be created with correct structure during migration.\n\n";
        }
    } else {
        echo "ℹ️  This fix is for MySQL/MariaDB only. Your database driver is: $driver\n";
        echo "   The migration should work fine for other databases.\n\n";
    }
    
    echo "✅ Pre-migration check complete!\n";
    echo "   You can now run: php artisan migrate\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

