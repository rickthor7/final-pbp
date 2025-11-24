<?php
// app/Models/Review.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_id',
        'tailor_id',
        'fabric_seller_id',
        'fabric_id',
        'overall_rating',
        'tailor_rating',
        'fabric_quality_rating',
        'communication_rating',
        'timeliness_rating',
        'title',
        'comment',
        'tailor_feedback',
        'fabric_feedback',
        'review_images',
        'is_verified',
        'is_featured',
        'is_public',
        'helpful_count',
        'report_count',
    ];

    protected $casts = [
        'overall_rating' => 'integer',
        'tailor_rating' => 'integer',
        'fabric_quality_rating' => 'integer',
        'communication_rating' => 'integer',
        'timeliness_rating' => 'integer',
        'review_images' => 'array',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'is_public' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function tailor()
    {
        return $this->belongsTo(User::class, 'tailor_id');
    }

    public function fabricSeller()
    {
        return $this->belongsTo(User::class, 'fabric_seller_id');
    }

    public function fabric()
    {
        return $this->belongsTo(Fabric::class);
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeWithHighRating($query, $minRating = 4)
    {
        return $query->where('overall_rating', '>=', $minRating);
    }

    public function scopeForTailor($query, $tailorId)
    {
        return $query->where('tailor_id', $tailorId);
    }

    public function scopeForFabricSeller($query, $sellerId)
    {
        return $query->where('fabric_seller_id', $sellerId);
    }

    public function scopeForFabric($query, $fabricId)
    {
        return $query->where('fabric_id', $fabricId);
    }

    // Methods
    public function getReviewImageUrlsAttribute()
    {
        if (!$this->review_images) return [];
        
        return array_map(function($image) {
            return asset('storage/'.$image);
        }, $this->review_images);
    }

    public function getAverageRatingAttribute()
    {
        $ratings = array_filter([
            $this->overall_rating,
            $this->tailor_rating,
            $this->fabric_quality_rating,
            $this->communication_rating,
            $this->timeliness_rating
        ]);

        return count($ratings) > 0 ? array_sum($ratings) / count($ratings) : 0;
    }

    public function getStarRatingAttribute()
    {
        return round($this->average_rating * 2) / 2; // Returns 0, 0.5, 1, 1.5, ..., 5
    }

    public function incrementHelpfulCount()
    {
        $this->increment('helpful_count');
    }

    public function incrementReportCount()
    {
        $this->increment('report_count');
    }

    public function markAsVerified()
    {
        $this->update(['is_verified' => true]);
    }

    public function markAsFeatured()
    {
        $this->update(['is_featured' => true]);
    }

    public function updateRelatedRatings()
    {
        // Update tailor rating
        if ($this->tailor) {
            $this->tailor->updateRating();
        }

        // Update fabric seller rating
        if ($this->fabricSeller) {
            $this->fabricSeller->updateRating();
        }

        // Update fabric rating
        if ($this->fabric) {
            $this->fabric->updateRating();
        }
    }
}
