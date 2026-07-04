<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_comments', function (Blueprint $table) {
            // Applies to top-level threads only
            $table->boolean('is_pinned')->default(false)->after('comment');
            // Applies to a reply (parent_id set): marks it as the thread's accepted answer
            $table->boolean('is_accepted')->default(false)->after('is_pinned');
            // Optional module scoping for a thread, in addition to the required course scoping
            $table->foreignId('module_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_comments', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'is_accepted']);
            $table->dropConstrainedForeignId('module_id');
        });
    }
};
