<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Partners (funders) are separate from organisations (internship hosts).
     * Partner dashboard access lives on users.dashboard_permissions.
     */
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'tutor', 'student', 'tagdev', 'facilitator', 'organisation', 'partner') DEFAULT 'student'");
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'dashboard_permissions')) {
                $table->json('dashboard_permissions')->nullable()->after('is_active');
            }
        });

        // Accounts that only had funder dashboard access (no host slots) become partners.
        if (Schema::hasTable('organisations') && Schema::hasColumn('organisations', 'dashboard_permissions')) {
            $orgs = DB::table('organisations')
                ->whereNotNull('dashboard_permissions')
                ->where('dashboard_permissions', '!=', '[]')
                ->where('dashboard_permissions', '!=', 'null')
                ->get(['id', 'user_id', 'dashboard_permissions', 'name']);

            foreach ($orgs as $org) {
                $hasSlots = Schema::hasTable('apprenticeship_slots')
                    && DB::table('apprenticeship_slots')->where('organisation_id', $org->id)->exists();

                if ($hasSlots) {
                    continue;
                }

                DB::table('users')->where('id', $org->user_id)->update([
                    'role' => 'partner',
                    'dashboard_permissions' => $org->dashboard_permissions,
                    'name' => $org->name ?: DB::table('users')->where('id', $org->user_id)->value('name'),
                ]);

                DB::table('organisations')->where('id', $org->id)->update([
                    'dashboard_permissions' => null,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dashboard_permissions')) {
                $table->dropColumn('dashboard_permissions');
            }
        });

        DB::table('users')->where('role', 'partner')->update(['role' => 'organisation']);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'tutor', 'student', 'tagdev', 'facilitator', 'organisation') DEFAULT 'student'");
        }
    }
};
