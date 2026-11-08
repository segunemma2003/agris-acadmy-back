<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** WEO learner baseline (MEL tool): accommodation yes/no, value chain track, post-training objective. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('needs_accommodation', 5)->nullable()->after('accommodation_needs');
            $table->string('value_chain_track', 30)->nullable()->after('value_chain');
            $table->string('value_chain_track_other', 255)->nullable()->after('value_chain_track');
            $table->string('training_objective', 30)->nullable()->after('value_chain_track_other');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['needs_accommodation', 'value_chain_track', 'value_chain_track_other', 'training_objective']);
        });
    }
};
