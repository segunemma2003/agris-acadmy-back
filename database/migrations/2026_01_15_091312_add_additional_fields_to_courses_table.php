<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('preview_video_url')->nullable()->after('image');
            $table->text('about')->nullable()->after('description');
            $table->text('requirements')->nullable()->after('about');
            $table->text('what_to_expect')->nullable()->after('requirements');
            $table->boolean('certificate_included')->default(false)->after('what_to_expect');
            $table->integer('lessons_count')->default(0)->after('certificate_included');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'preview_video_url',
                'about',
                'requirements',
                'what_to_expect',
                'certificate_included',
                'lessons_count',
            ]);
        });
    }
};
