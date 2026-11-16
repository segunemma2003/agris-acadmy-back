<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Self-registration for employers / recruitment partners in Nigeria and abroad. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->string('organisation_type', 40)->nullable()->after('sector');
            $table->string('country', 100)->default('Nigeria')->after('organisation_type');
            $table->string('city', 120)->nullable()->after('lga');
            $table->string('phone', 30)->nullable()->after('website');
            $table->text('talent_needs')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->dropColumn(['organisation_type', 'country', 'city', 'phone', 'talent_needs']);
        });
    }
};
