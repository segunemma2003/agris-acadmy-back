<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Learner-entered CV details (past experience, education, languages…) and the chosen country template. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_cvs', function (Blueprint $table) {
            $table->json('details')->nullable()->after('data');
            $table->string('template', 30)->default('nigeria')->after('details');
            $table->string('target_country', 100)->nullable()->after('template');
        });
    }

    public function down(): void
    {
        Schema::table('learner_cvs', function (Blueprint $table) {
            $table->dropColumn(['details', 'template', 'target_country']);
        });
    }
};
