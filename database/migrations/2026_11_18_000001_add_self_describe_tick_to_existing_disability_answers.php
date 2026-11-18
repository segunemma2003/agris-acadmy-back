<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Self-describe" is now a tick in the disability list. Learners who already
 * wrote a self-description get the tick so their text keeps showing.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('has_disability', 'yes')
            ->whereNotNull('disability_description')->where('disability_description', '!=', '')
            ->select('id', 'disability_types')->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $types = json_decode($row->disability_types ?? '[]', true) ?: [];
                    if (in_array('self_describe', $types, true) || in_array('prefer_not_to_say', $types, true)) {
                        continue;
                    }
                    $types[] = 'self_describe';
                    DB::table('users')->where('id', $row->id)->update(['disability_types' => json_encode(array_values($types))]);
                }
            });
    }

    public function down(): void
    {
        // Leaving the tick in place is harmless
    }
};
