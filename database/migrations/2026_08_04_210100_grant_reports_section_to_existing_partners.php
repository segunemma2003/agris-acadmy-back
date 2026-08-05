<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Grant the new Programme Reports section to existing partner accounts
     * that already have at least one dashboard section enabled.
     */
    public function up(): void
    {
        User::query()
            ->where('role', 'partner')
            ->whereNotNull('dashboard_permissions')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $perms = $user->dashboard_permissions ?? [];
                    if (! is_array($perms) || $perms === []) {
                        continue;
                    }
                    if (in_array('reports', $perms, true)) {
                        continue;
                    }
                    $perms[] = 'reports';
                    $user->dashboard_permissions = array_values($perms);
                    $user->save();
                }
            });
    }

    public function down(): void
    {
        User::query()
            ->where('role', 'partner')
            ->whereNotNull('dashboard_permissions')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $perms = $user->dashboard_permissions ?? [];
                    if (! is_array($perms)) {
                        continue;
                    }
                    $filtered = array_values(array_filter($perms, fn ($key) => $key !== 'reports'));
                    $user->dashboard_permissions = $filtered;
                    $user->save();
                }
            });
    }
};
