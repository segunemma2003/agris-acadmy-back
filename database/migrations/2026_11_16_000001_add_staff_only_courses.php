<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff-only courses: hidden from the public catalogue and from learners who
 * aren't staff. Staff = users ticked "Is staff" by an admin, plus every
 * admin, tutor and facilitator account (see User::isStaff()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('is_staff_only')->default(false)->after('is_featured')->index();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_staff')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['is_staff_only']);
            $table->dropColumn('is_staff_only');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_staff');
        });
    }
};
