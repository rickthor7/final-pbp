<?php
// app/Models/Fabric.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fabric extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'sku',
        'description',
        'material_type',
        'weave_type',
        'weight',
        'stretch_type',
        'pattern',
        'color',
        'color_family',
        'season',
        'price_per_meter',
        'discount_price',
        'stock_meter',
        'min_order_meter',
        'main_image',
        'gallery_images',
        'texture_image',
        'swatch_image',
        'width',
        'care_instructions',
        'origin_country',
        'view_count',
        'sales_count',
        'rating',
        'review_count',
        'is_featured',
        'is_active',
        'featured_until',
    ];

    protected $casts = [
        'price_per_meter' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'width' => 'decimal:2',
        'gallery_images' => 'array',
        'rating' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'featured_until' => 'datetime',
    ];

    // Relationships
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category()
    {
        return $this->belongsTo(FabricCategory::class);
    }

    public function customDesigns()
    {
        return $this->hasMany(CustomDesign::class, 'fabric_assignments');
    }

    public function orderFabrics()
    {
        return $this->hasMany(OrderFabric::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                    ->where(function($q) {
                        $q->whereNull('featured_until')
                          ->orWhere('featured_until', '>', now());
                    });
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_meter', '>', 0);
    }

    public function scopeByMaterial($query, $material)
    {
        return $query->where('material_type', $material);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('price_per_meter', [$min, $max]);
    }

    // Methods
    public function getMainImageUrlAttribute()
    {
        return $this->main_image ? asset('storage/'.$this->main_image) : asset('images/default-fabric.jpg');
    }

    public function getTextureImageUrlAttribute()
    {
        return $this->texture_image ? asset('storage/'.$this->texture_image) : asset('images/default-texture.jpg');
    }

    public function getGalleryImagesUrlsAttribute()
    {
        if (!$this->gallery_images) return [];
        
        return array_map(function($image) {
            return asset('storage/'.$image);
        }, $this->gallery_images);
    }

    public function getCurrentPriceAttribute()
    {
        return $this->discount_price ?? $this->price_per_meter;
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->discount_price && $this->price_per_meter > 0) {
            return round((($this->price_per_meter - $this->discount_price) / $this->price_per_meter) * 100);
        }
        return 0;
    }

    public function isInStock()
    {
        return $this->stock_meter > 0;
    }

    public function hasEnoughStock($meters)
    {
        return $this->stock_meter >= $meters;
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function updateRating()
    {
        $reviews = $this->reviews();
        $avgRating = $reviews->avg('fabric_quality_rating');
        $this->update([
            'rating' => $avgRating ?? 0,
            'review_count' => $reviews->count()
        ]);
    }
}
