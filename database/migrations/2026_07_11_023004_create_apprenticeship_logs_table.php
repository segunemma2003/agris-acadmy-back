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
        Schema::create('apprenticeship_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apprenticeship_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->boolean('attended')->default(true);
            $table->text('activity_description')->nullable();
            $table->enum('source', ['web', 'ussd'])->default('web');
            $table->timestamps();

            $table->unique(['apprenticeship_id', 'log_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apprenticeship_logs');
    }
};
