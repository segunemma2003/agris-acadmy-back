<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_vr_content', function (Blueprint $table) {
            $table->foreignId('module_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
            $table->string('studio_slug', 64)->nullable()->unique()->after('vr_url');
            $table->text('instructions')->nullable()->after('description');
            $table->string('cta_label')->nullable()->after('instructions');
            $table->json('scene_json')->nullable()->after('cta_label');
            $table->string('studio_status', 20)->default('draft')->after('scene_json'); // draft|published
            $table->timestamp('published_at')->nullable()->after('studio_status');
        });
    }

    public function down(): void
    {
        Schema::table('course_vr_content', function (Blueprint $table) {
            $table->dropConstrainedForeignId('module_id');
            $table->dropColumn([
                'studio_slug',
                'instructions',
                'cta_label',
                'scene_json',
                'studio_status',
                'published_at',
            ]);
        });
    }
};
