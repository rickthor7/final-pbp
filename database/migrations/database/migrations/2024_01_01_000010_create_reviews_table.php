<?php
// database/migrations/2024_01_01_000010_create_reviews_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            
            // Review Targets
            $table->foreignId('tailor_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('fabric_seller_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('fabric_id')->nullable()->constrained()->onDelete('cascade');
            
            // Ratings (1-5)
            $table->tinyInteger('overall_rating');
            $table->tinyInteger('tailor_rating')->nullable();
            $table->tinyInteger('fabric_quality_rating')->nullable();
            $table->tinyInteger('communication_rating')->nullable();
            $table->tinyInteger('timeliness_rating')->nullable();
            
            // Review Content
            $table->string('title');
            $table->text('comment');
            $table->text('tailor_feedback')->nullable();
            $table->text('fabric_feedback')->nullable();
            
            // Media
            $table->json('review_images')->nullable();
            
            // Status
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_public')->default(true);
            
            // Helpfulness
            $table->integer('helpful_count')->default(0);
            $table->integer('report_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['tailor_id', 'overall_rating']);
            $table->index(['fabric_seller_id', 'overall_rating']);
            $table->index(['fabric_id', 'fabric_quality_rating']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};
