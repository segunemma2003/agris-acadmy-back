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
        Schema::table('topics', function (Blueprint $table) {
            $table->string('title_ha')->nullable()->after('title');
            $table->text('write_up_ha')->nullable()->after('write_up');
            $table->boolean('is_translated_ha')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn(['title_ha', 'write_up_ha', 'is_translated_ha']);
        });
    }
};
