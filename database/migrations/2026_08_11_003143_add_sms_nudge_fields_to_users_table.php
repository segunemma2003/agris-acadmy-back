<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('sms_opted_out_at')->nullable()->after('notification_preferences');
            $table->timestamp('sms_nudge_sent_at')->nullable()->after('sms_opted_out_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sms_opted_out_at', 'sms_nudge_sent_at']);
        });
    }
};
