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
        Schema::create('apprenticeships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apprenticeship_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certificate_id')->nullable()->constrained()->nullOnDelete();
            // interested -> accepted (an active placement, loggable) -> completed, or rejected at any point before accepted.
            $table->enum('status', ['interested', 'accepted', 'rejected', 'completed'])->default('interested');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['apprenticeship_slot_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apprenticeships');
    }
};
