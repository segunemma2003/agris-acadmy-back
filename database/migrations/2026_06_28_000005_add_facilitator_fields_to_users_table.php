<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('age');

            // Which facilitator (user with role=facilitator) is assigned to this learner
            $table->foreignId('facilitator_id')
                ->nullable()
                ->after('date_of_birth')
                ->constrained('users')
                ->nullOnDelete();

            // True when no matching facilitator was found; admin must review
            $table->boolean('is_in_facilitator_queue')->default(false)->after('facilitator_id');

            // JSON arrays for facilitators to declare their coverage areas
            $table->json('covered_states')->nullable()->after('is_in_facilitator_queue');
            $table->json('covered_lgas')->nullable()->after('covered_states');

            $table->index('facilitator_id');
            $table->index('is_in_facilitator_queue');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['facilitator_id']);
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
