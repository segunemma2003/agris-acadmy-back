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
        Schema::table('apprenticeships', function (Blueprint $table) {
            // Submitted by the host organisation when it requests sign-off on
            // a completed placement; an admin reviews these before confirming,
            // at which point they're snapshotted onto the issued certificate.
            $table->string('completion_role')->nullable();
            $table->json('completion_key_skills')->nullable();
            $table->date('completion_start_date')->nullable();
            $table->date('completion_end_date')->nullable();
            $table->timestamp('completion_requested_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apprenticeships', function (Blueprint $table) {
            $table->dropColumn([
                'completion_role',
                'completion_key_skills',
                'completion_start_date',
                'completion_end_date',
                'completion_requested_at',
            ]);
        });
    }
};
