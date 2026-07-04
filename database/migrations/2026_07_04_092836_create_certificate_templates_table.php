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
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path'); // PDF template stored on the 'public' disk
            $table->boolean('is_default')->default(false);
            $table->decimal('name_y_percent', 5, 2)->default(50); // vertical position of the name, % from top of page
            $table->unsignedInteger('font_size')->default(28);
            $table->string('font_color', 7)->default('#141414'); // hex color
            $table->string('font_family')->default('Helvetica');
            $table->string('font_style', 2)->default('B'); // '', B, I, BI
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
