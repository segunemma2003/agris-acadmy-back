#!/bin/bash

# Fix password_reset_tokens table key length issue
# Run this script on your server if you're getting "Specified key was too long" error

set -e

echo "🔧 Fixing password_reset_tokens table key length issue..."

# Get database credentials from .env
if [ ! -f .env ]; then
    echo "❌ .env file not found!"
    exit 1
fi

# Source .env file to get database credentials
export $(grep -v '^#' .env | grep -E '^DB_' | xargs)

# Check if MySQL/MariaDB
if [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ]; then
    echo "📊 Database: $DB_CONNECTION"
    echo "🗄️  Database: $DB_DATABASE"

    # Fix the table
    mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -h "$DB_HOST" "$DB_DATABASE" <<EOF
-- Drop primary key
ALTER TABLE password_reset_tokens DROP PRIMARY KEY;

-- Modify email column to 191 characters
ALTER TABLE password_reset_tokens MODIFY email VARCHAR(191) NOT NULL;

-- Re-add primary key
ALTER TABLE password_reset_tokens ADD PRIMARY KEY (email);

-- Verify
SHOW CREATE TABLE password_reset_tokens;
EOF

    echo "✅ password_reset_tokens table fixed!"
else
    echo "⚠️  This script is for MySQL/MariaDB only."
    echo "   For other databases, run the migration:"
    echo "   php artisan migrate"
fi

