<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->change();
            $table->unsignedBigInteger('enrollment_id')->nullable()->change();

            $table->string('type')->default('course')->after('id');
            $table->foreignId('apprenticeship_id')->nullable()->unique()->after('enrollment_id')
                ->constrained()->nullOnDelete();

            // Snapshotted at issuance so the certificate stays accurate even if
            // the organisation profile or apprenticeship record changes later.
            $table->string('organisation_name')->nullable();
            $table->string('role')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('key_skills')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('apprenticeship_id');
            $table->dropColumn(['type', 'organisation_name', 'role', 'start_date', 'end_date', 'key_skills']);
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
            $table->unsignedBigInteger('enrollment_id')->nullable(false)->change();
        });
    }
};
