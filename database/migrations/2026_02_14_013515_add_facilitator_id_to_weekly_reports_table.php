<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_reports', function (Blueprint $table) {
            $table->foreignId('facilitator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('id'); // adjust position if you want
        });
    }

    public function down(): void
    {
        Schema::table('weekly_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facilitator_id');
        });
    }
};
