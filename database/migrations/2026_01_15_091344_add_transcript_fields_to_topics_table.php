<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->text('transcript_english')->nullable()->after('transcript');
            $table->text('transcript_hausa')->nullable()->after('transcript_english');
            $table->boolean('transcription_completed')->default(false)->after('transcript_hausa');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn(['transcript_english', 'transcript_hausa', 'transcription_completed']);
        });
    }
};
