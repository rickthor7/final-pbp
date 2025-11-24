<?php
// database/migrations/2024_01_01_000005_create_garment_templates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('garment_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['shirt', 'pants', 'dress', 'jacket', 'skirt', 'blouse', 'traditional', 'suit', 'other']);
            $table->enum('gender', ['male', 'female', 'unisex']);
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->default('intermediate');
            
            // Template Structure
            $table->json('parts')->comment('Array of garment parts: body, sleeves, collar, etc');
            $table->json('default_measurements')->comment('Default measurements for this template');
            $table->json('fabric_requirements')->comment('Estimated fabric needs for each part');
            
            // Visual Assets
            $table->string('preview_image');
            $table->string('template_image')->nullable();
            $table->json('part_images')->nullable();
            $table->string('3d_model_url')->nullable();
            
            // Pricing
            $table->decimal('base_price', 10, 2);
            $table->decimal('tailor_fee', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            
            // Description
            $table->text('description');
            $table->text('features')->nullable();
            $table->text('care_instructions')->nullable();
            
            // Metadata
            $table->integer('completion_time_days')->default(7);
            $table->integer('usage_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            
            // Status
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['type', 'is_active']);
            $table->index(['gender', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('garment_templates');
    }
};
