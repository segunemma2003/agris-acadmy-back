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
        Schema::table('users', function (Blueprint $table) {
            $table->string('state')->nullable()->after('location');
            $table->string('lga')->nullable()->after('state');
            $table->string('occupation')->nullable()->after('lga');
            $table->unsignedTinyInteger('age')->nullable()->after('occupation');
            $table->string('referral')->nullable()->after('age');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['state', 'lga', 'occupation', 'age', 'referral']);
        });
    }
};
