<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consent proof (MEL tool, NDPA 2023): signed paper form filed / photo
 * uploaded, or digital opt-in only — same as the Finance platform.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('consent_method', 20)->nullable()->after('consent_version'); // physical | digital
            $table->string('consent_form_path')->nullable()->after('consent_method');
            $table->timestamp('consent_form_uploaded_at')->nullable()->after('consent_form_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['consent_method', 'consent_form_path', 'consent_form_uploaded_at']);
        });
    }
};
