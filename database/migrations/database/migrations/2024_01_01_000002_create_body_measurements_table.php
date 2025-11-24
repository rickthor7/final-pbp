<?php
// database/migrations/2024_01_01_000002_create_body_measurements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('measurement_name')->default('Default Measurement');
            
            // Basic Measurements
            $table->decimal('height', 5, 2)->comment('in cm');
            $table->decimal('weight', 5, 2)->comment('in kg');
            
            // Upper Body
            $table->decimal('chest_bust', 5, 2)->comment('in cm');
            $table->decimal('under_bust', 5, 2)->nullable()->comment('in cm');
            $table->decimal('waist', 5, 2)->comment('in cm');
            $table->decimal('hips', 5, 2)->comment('in cm');
            $table->decimal('shoulder_width', 5, 2)->comment('in cm');
            $table->decimal('back_width', 5, 2)->nullable()->comment('in cm');
            
            // Arms
            $table->decimal('arm_length', 5, 2)->comment('in cm');
            $table->decimal('bicep', 5, 2)->comment('in cm');
            $table->decimal('wrist', 5, 2)->comment('in cm');
            
            // Lower Body
            $table->decimal('thigh', 5, 2)->comment('in cm');
            $table->decimal('knee', 5, 2)->comment('in cm');
            $table->decimal('calf', 5, 2)->comment('in cm');
            $table->decimal('ankle', 5, 2)->comment('in cm');
            $table->decimal('inseam', 5, 2)->comment('in cm');
            $table->decimal('outseam', 5, 2)->comment('in cm');
            
            // Neck & Head
            $table->decimal('neck_circumference', 5, 2)->comment('in cm');
            $table->decimal('head_circumference', 5, 2)->nullable()->comment('in cm');
            
            // Additional custom measurements
            $table->json('custom_measurements')->nullable();
            
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('body_measurements');
    }
};
