<?php
// database/migrations/2024_01_01_000004_create_fabrics_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fabrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('fabric_categories')->onDelete('cascade');
            
            // Basic Info
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description');
            
            // Fabric Properties
            $table->enum('material_type', ['cotton', 'linen', 'silk', 'wool', 'polyester', 'rayon', 'denim', 'chiffon', 'satin', 'velvet', 'jersey', 'other']);
            $table->string('weave_type')->nullable();
            $table->string('weight')->nullable()->comment('gsm - grams per square meter');
            $table->string('stretch_type')->nullable()->comment('none, two-way, four-way');
            
            // Design Properties
            $table->string('pattern')->nullable()->comment('solid, striped, floral, geometric, etc');
            $table->string('color');
            $table->string('color_family')->nullable();
            $table->string('season')->nullable()->comment('all-season, summer, winter, etc');
            
            // Pricing & Stock
            $table->decimal('price_per_meter', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->integer('stock_meter');
            $table->integer('min_order_meter')->default(1);
            
            // Images
            $table->string('main_image');
            $table->json('gallery_images')->nullable();
            $table->string('texture_image');
            $table->string('swatch_image')->nullable();
            
            // Specifications
            $table->decimal('width', 5, 2)->comment('Fabric width in cm');
            $table->string('care_instructions')->nullable();
            $table->string('origin_country')->nullable();
            
            // Metadata
            $table->integer('view_count')->default(0);
            $table->integer('sales_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            
            // Status
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('featured_until')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['seller_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->index(['material_type', 'is_active']);
            $table->index(['price_per_meter', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fabrics');
    }
};
