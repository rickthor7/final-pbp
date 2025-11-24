<?php
// database/migrations/2024_01_01_000009_create_tailor_assignments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tailor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('tailor_id')->constrained('users')->onDelete('cascade');
            
            // Assignment Details
            $table->enum('status', ['assigned', 'accepted', 'in_progress', 'completed', 'cancelled'])->default('assigned');
            $table->integer('priority')->default(1)->comment('1-5, 1 being highest');
            $table->date('assigned_date');
            $table->date('deadline');
            $table->date('completed_date')->nullable();
            
            // Work Details
            $table->text('special_instructions')->nullable();
            $table->json('work_steps')->nullable()->comment('Breakdown of tailoring steps');
            $table->decimal('completion_percentage', 5, 2)->default(0);
            
            // Quality Control
            $table->boolean('quality_check_passed')->default(false);
            $table->text('quality_notes')->nullable();
            $table->timestamp('quality_checked_at')->nullable();
            $table->foreignId('quality_checked_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            $table->index(['tailor_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index('deadline');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tailor_assignments');
    }
};
