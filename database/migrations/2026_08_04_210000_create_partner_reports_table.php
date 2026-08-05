<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->enum('period_type', ['weekly', 'monthly', 'quarterly', 'annual', 'custom'])->default('monthly');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->text('summary')->nullable();
            $table->unsignedInteger('participants_registered_count')->default(0);
            $table->unsignedInteger('participants_selected_count')->default(0);
            $table->unsignedInteger('participants_enrolled_count')->default(0);
            $table->unsignedInteger('jobs_enabled')->default(0);
            $table->unsignedInteger('jobs_created')->default(0);
            $table->unsignedInteger('demo_hubs')->default(0);
            $table->unsignedInteger('enterprises_created')->default(0);
            $table->json('participants_registered')->nullable();
            $table->json('participants_selected')->nullable();
            $table->json('participants_enrolled')->nullable();
            $table->json('google_doc_links')->nullable();
            $table->json('image_links')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'status']);
            $table->index(['period_type', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_reports');
    }
};
