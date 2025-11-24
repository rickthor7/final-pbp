<?php
// database/migrations/2024_01_01_000008_create_order_fabrics_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_fabrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('fabric_id')->constrained()->onDelete('cascade');
            $table->foreignId('fabric_seller_id')->constrained('users')->onDelete('cascade');
            
            // Fabric Details
            $table->string('garment_part');
            $table->decimal('fabric_amount', 8, 3)->comment('Amount in meters');
            $table->decimal('price_per_meter', 8, 2);
            $table->decimal('total_price', 10, 2);
            
            // Status
            $table->enum('status', [
                'pending',
                'ordered',
                'cutting',
                'shipped',
                'delivered_to_tailor',
                'quality_check',
                'approved',
                'rejected'
            ])->default('pending');
            
            // Seller Information
            $table->text('seller_notes')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('estimated_delivery_date')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Tracking
            $table->string('tracking_number')->nullable();
            $table->string('shipping_carrier')->nullable();
            
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
            $table->index(['fabric_seller_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_fabrics');
    }
};
