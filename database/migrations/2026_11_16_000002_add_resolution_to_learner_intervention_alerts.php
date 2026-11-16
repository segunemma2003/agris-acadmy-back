<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Alerts can be resolved by staff (with a note) or auto-resolved when the learner re-engages. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_intervention_alerts', function (Blueprint $table) {
            $table->timestamp('resolved_at')->nullable()->after('emailed_at')->index();
            $table->foreignId('resolved_by')->nullable()->after('resolved_at')->constrained('users')->nullOnDelete();
            $table->boolean('auto_resolved')->default(false)->after('resolved_by');
            $table->text('resolution_note')->nullable()->after('auto_resolved');
        });
    }

    public function down(): void
    {
        Schema::table('learner_intervention_alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropIndex(['resolved_at']);
            $table->dropColumn(['resolved_at', 'auto_resolved', 'resolution_note']);
        });
    }
};
