<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('age');

            $table->unsignedBigInteger('facilitator_id')->nullable()->after('date_of_birth');

            $table->boolean('is_in_facilitator_queue')->default(false)->after('facilitator_id');

            $table->json('covered_states')->nullable()->after('is_in_facilitator_queue');
            $table->json('covered_lgas')->nullable()->after('covered_states');

            $table->index('facilitator_id');
            $table->index('is_in_facilitator_queue');
        });

        // SQLite cannot ALTER TABLE to add FKs without a full table rebuild, which
        // collides with its reserved auto-index names. Skip the constraint for SQLite;
        // the Eloquent relationship still works, and the index above covers performance.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('facilitator_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['facilitator_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth',
                'facilitator_id',
                'is_in_facilitator_queue',
                'covered_states',
                'covered_lgas',
            ]);
        });
    }
};
