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
        Schema::table('assignments', function (Blueprint $table) {
            $table->foreignId('topic_id')->nullable()->after('module_id')->constrained('topics')->onDelete('cascade');
            $table->index(['topic_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['topic_id']);
            $table->dropIndex(['topic_id', 'is_active']);
            $table->dropColumn('topic_id');
        });
    }
};
