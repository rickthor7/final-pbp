<?php
// app/Models/GarmentTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GarmentTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'gender',
        'difficulty_level',
        'parts',
        'default_measurements',
        'fabric_requirements',
        'preview_image',
        'template_image',
        'part_images',
        '3d_model_url',
        'base_price',
        'tailor_fee',
        'service_fee',
        'description',
        'features',
        'care_instructions',
        'completion_time_days',
        'usage_count',
        'rating',
        'review_count',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'parts' => 'array',
        'default_measurements' => 'array',
        'fabric_requirements' => 'array',
        'part_images' => 'array',
        'base_price' => 'decimal:2',
        'tailor_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function customDesigns()
    {
        return $this->hasMany(CustomDesign::class);
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, CustomDesign::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    // Methods
    public function getPreviewImageUrlAttribute()
    {
        return $this->preview_image ? asset('storage/'.$this->preview_image) : asset('images/default-template.jpg');
    }

    public function getTemplateImageUrlAttribute()
    {
        return $this->template_image ? asset('storage/'.$this->template_image) : $this->preview_image_url;
    }

    public function getPartImageUrlsAttribute()
    {
        if (!$this->part_images) return [];
        
        return array_map(function($image) {
            return asset('storage/'.$image);
        }, $this->part_images);
    }

    public function incrementUsageCount()
    {
        $this->increment('usage_count');
    }

    public function getEstimatedFabricRequirement($part = null)
    {
        $requirements = $this->fabric_requirements;
        
        if ($part && isset($requirements[$part])) {
            return $requirements[$part];
        }
        
        return $requirements;
    }

    public function getTotalPriceAttribute()
    {
        return $this->base_price + $this->tailor_fee + $this->service_fee;
    }
}
