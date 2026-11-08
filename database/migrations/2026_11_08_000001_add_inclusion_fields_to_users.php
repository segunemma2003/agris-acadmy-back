<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Baseline update: disability self-description and accommodation requests. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('disability_description')->nullable()->after('disability_types');
            $table->json('accommodation_needs')->nullable()->after('disability_description');
            $table->text('accommodation_notes')->nullable()->after('accommodation_needs');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['disability_description', 'accommodation_needs', 'accommodation_notes']);
        });
    }
};
