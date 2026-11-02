<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_intake_answers', function (Blueprint $table) {
            $table->string('interest')->nullable()->after('occupation');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_intake_answers', function (Blueprint $table) {
            $table->dropColumn('interest');
        });
    }
};
