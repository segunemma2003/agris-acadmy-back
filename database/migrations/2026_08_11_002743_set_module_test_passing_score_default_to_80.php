<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Align module quiz threshold with the learner unlock rule (80%).
        DB::table('module_tests')
            ->where('passing_score', '<', 80)
            ->update(['passing_score' => 80]);
    }

    public function down(): void
    {
        // Intentionally left blank — previous per-test scores are not restored.
    }
};
