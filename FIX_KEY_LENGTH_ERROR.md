# Fix "Specified key was too long" Error

## Problem

When using MySQL/MariaDB with utf8mb4 character set, you may encounter this error:

```
SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long;
max key length is 767 bytes
```

This happens because the `password_reset_tokens` table uses `email` as a primary key, and with utf8mb4, a VARCHAR(255) column exceeds MySQL's maximum key length.

## Solution

### Option 1: Run the Fix Migration (Recommended)

```bash
php artisan migrate
```

This will run the fix migration that updates the email column to VARCHAR(191).

### Option 2: Run the Fix Script

```bash
# Using PHP script
php scripts/fix-mysql-key-length.php

# Or using bash script (MySQL/MariaDB only)
./scripts/fix-password-reset-tokens.sh
```

### Option 3: Manual Fix via MySQL

```sql
-- Connect to MySQL
mysql -u root -p

-- Use your database
USE your_database_name;

-- Drop primary key
ALTER TABLE password_reset_tokens DROP PRIMARY KEY;

-- Modify email column
ALTER TABLE password_reset_tokens MODIFY email VARCHAR(191) NOT NULL;

-- Re-add primary key
ALTER TABLE password_reset_tokens ADD PRIMARY KEY (email);
```

### Option 4: Drop and Recreate Table

```bash
php artisan tinker
```

```php
Schema::dropIfExists('password_reset_tokens');
Schema::create('password_reset_tokens', function ($table) {
    $table->string('email', 191)->primary();
    $table->string('token');
    $table->timestamp('created_at')->nullable();
});
```

## For New Deployments

The migration has been fixed, so new deployments won't have this issue. The `password_reset_tokens` table will be created with `VARCHAR(191)` for the email column.

## Verification

After applying the fix, verify it worked:

```bash
php artisan tinker
```

```php
DB::select('SHOW CREATE TABLE password_reset_tokens');
```

You should see `email VARCHAR(191)` in the output.

## Why 191?

-   MySQL/MariaDB with utf8mb4 uses 4 bytes per character
-   Maximum key length is 767 bytes
-   767 ÷ 4 = 191.75, so maximum is 191 characters
-   Email addresses are typically much shorter than 191 characters

## Additional Notes

-   This fix only affects the `password_reset_tokens` table
-   All other tables should work fine
-   The fix is backward compatible
-   No data will be lost during the fix

---

**The fix has been applied to the migration files. Run `php artisan migrate` to apply it to your database.**
