# Quick Fix: "Specified key was too long" Error

## Immediate Fix (Run on Server)

### Step 1: Update AppServiceProvider

The `AppServiceProvider.php` has been updated to set default string length to 191. Make sure this file is updated on your server:

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    // Fix for MySQL/MariaDB "Specified key was too long" error
    Schema::defaultStringLength(191);
}
```

### Step 2: Clear Config Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### Step 3: Fix Existing Table (if it exists)

```bash
# Option A: Run the fix script
php scripts/fix-password-reset-before-migrate.php

# Option B: Manual MySQL fix
mysql -u root -p your_database_name <<EOF
ALTER TABLE password_reset_tokens DROP PRIMARY KEY;
ALTER TABLE password_reset_tokens MODIFY email VARCHAR(191) NOT NULL;
ALTER TABLE password_reset_tokens ADD PRIMARY KEY (email);
EOF
```

### Step 4: Run Migrations

```bash
php artisan migrate --force
```

## If Table Doesn't Exist Yet

If you're getting the error during initial migration:

1. **Make sure AppServiceProvider is updated** (see Step 1)
2. **Clear caches** (see Step 2)
3. **Run migrations**:
    ```bash
    php artisan migrate --force
    ```

## Verification

After fixing, verify the table structure:

```bash
php artisan tinker
```

```php
DB::select("SHOW CREATE TABLE password_reset_tokens");
```

You should see `email VARCHAR(191)` in the output.

## What Was Fixed

1. ✅ **AppServiceProvider** - Sets global default string length to 191
2. ✅ **Migration file** - Explicitly uses VARCHAR(191) for email
3. ✅ **Fix scripts** - Automatically fix existing tables
4. ✅ **Deployment script** - Runs fixes before/after migrations

---

**After applying these fixes, the error should be resolved!**
