# Fix 403 Error: Admin Access Denied

## Problem

You created a user with `php artisan make:filament-user` but get a 403 error when trying to login to the admin panel.

## Cause

The `filament-user` command creates a user but doesn't set the `role` field. The default role is `'student'`, but the admin panel requires the `'admin'` role.

## Solution

### Option 1: Use the Artisan Command (Recommended)

```bash
php artisan user:make-admin your-email@example.com
```

### Option 2: Use the PHP Script

```bash
php scripts/make-user-admin.php your-email@example.com
```

### Option 3: Update via Tinker

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'your-email@example.com')->first();
$user->role = 'admin';
$user->is_active = true;
$user->save();
```

### Option 4: Update via Database

```sql
UPDATE users SET role = 'admin', is_active = 1 WHERE email = 'your-email@example.com';
```

## Verify the Fix

After updating the user, verify:

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'your-email@example.com')->first();
echo "Role: " . $user->role . "\n";
echo "Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . "\n";
```

You should see:

-   Role: admin
-   Is Admin: Yes

## Create Admin User Directly

To create an admin user directly (without using filament-user):

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('your-password'),
    'role' => 'admin',
    'is_active' => true,
]);
```

## Roles Available

-   `admin` - Full access to admin panel
-   `tutor` - Access to tutor panel
-   `student` - Default role, API access only

## After Fixing

1. Log out if you're currently logged in
2. Clear your browser cache/cookies
3. Try logging in again at `/admin`

---

**The user should now be able to access the admin panel!**
