<?php
// database/migrations/2024_01_01_000006_create_custom_designs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('custom_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('garment_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('body_measurement_id')->nullable()->constrained()->onDelete('set null');
            
            // Design Info
            $table->string('design_name');
            $table->string('design_slug')->unique();
            $table->text('description')->nullable();
            $table->text('special_instructions')->nullable();
            
            // Design Data
            $table->json('fabric_assignments')->comment('Mapping of part to fabric_id');
            $table->json('custom_measurements')->comment('Custom measurements for this design');
            $table->json('design_data')->comment('Complete design configuration');
            $table->json('fabric_requirements')->comment('Calculated fabric requirements');
            
            // Visuals
            $table->string('preview_image');
            $table->json('design_images')->nullable();
            $table->string('3d_preview_url')->nullable();
            
            // Pricing
            $table->decimal('fabric_cost', 10, 2)->default(0);
            $table->decimal('tailoring_cost', 10, 2)->default(0);
            $table->decimal('total_estimated_cost', 10, 2)->default(0);
            
            // Status
            $table->enum('status', ['draft', 'completed', 'ordered', 'archived'])->default('draft');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            
            // Metadata
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('clone_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index(['garment_template_id', 'status']);
            $table->index(['status', 'is_public']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('custom_designs');
    }
};
