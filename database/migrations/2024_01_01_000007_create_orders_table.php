<?php
// database/migrations/2024_01_01_000007_create_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('design_id')->constrained('custom_designs')->onDelete('cascade');
            $table->foreignId('tailor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('body_measurement_id')->nullable()->constrained()->onDelete('set null');
            
            // Order Details
            $table->text('customer_notes')->nullable();
            $table->text('tailor_notes')->nullable();
            $table->date('preferred_completion_date')->nullable();
            
            // Pricing Breakdown
            $table->decimal('fabric_cost', 10, 2)->default(0);
            $table->decimal('tailoring_cost', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            
            // Order Status
            $table->enum('status', [
                'pending',
                'payment_pending',
                'paid',
                'fabric_ordering',
                'fabric_ordered',
                'in_production',
                'quality_check',
                'ready_for_shipping',
                'shipped',
                'delivered',
                'completed',
                'cancelled',
                'refunded'
            ])->default('pending');
            
            // Payment Information
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $table->string('payment_gateway')->nullable()->comment('midtrans, stripe, etc');
            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->text('payment_data')->nullable()->comment('Raw payment response data');
            
            // Shipping Information
            $table->string('shipping_address');
            $table->string('shipping_city');
            $table->string('shipping_state');
            $table->string('shipping_zip_code');
            $table->string('shipping_country')->default('Indonesia');
            $table->string('shipping_phone');
            $table->string('tracking_number')->nullable();
            $table->string('shipping_carrier')->nullable();
            
            // Timeline
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('production_started_at')->nullable();
            $table->timestamp('quality_check_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index(['tailor_id', 'status']);
            $table->index(['status', 'payment_status']);
            $table->index('order_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
