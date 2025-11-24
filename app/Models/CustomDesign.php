<?php
// app/Models/CustomDesign.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomDesign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'garment_template_id',
        'body_measurement_id',
        'design_name',
        'design_slug',
        'description',
        'special_instructions',
        'fabric_assignments',
        'custom_measurements',
        'design_data',
        'fabric_requirements',
        'preview_image',
        'design_images',
        '3d_preview_url',
        'fabric_cost',
        'tailoring_cost',
        'total_estimated_cost',
        'status',
        'is_public',
        'is_featured',
        'view_count',
        'like_count',
        'clone_count',
    ];

    protected $casts = [
        'fabric_assignments' => 'array',
        'custom_measurements' => 'array',
        'design_data' => 'array',
        'fabric_requirements' => 'array',
        'design_images' => 'array',
        'fabric_cost' => 'decimal:2',
        'tailoring_cost' => 'decimal:2',
        'total_estimated_cost' => 'decimal:2',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function garmentTemplate()
    {
        return $this->belongsTo(GarmentTemplate::class);
    }

    public function bodyMeasurement()
    {
        return $this->belongsTo(BodyMeasurement::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function getPreviewImageUrlAttribute()
    {
        return $this->preview_image ? asset('storage/'.$this->preview_image) : asset('images/default-design.jpg');
    }

    public function getDesignImageUrlsAttribute()
    {
        if (!$this->design_images) return [];
        
        return array_map(function($image) {
            return asset('storage/'.$image);
        }, $this->design_images);
    }

    public function getAssignedFabrics()
    {
        $fabricIds = array_values($this->fabric_assignments);
        return Fabric::whereIn('id', $fabricIds)->get();
    }

    public function getFabricForPart($part)
    {
        if (isset($this->fabric_assignments[$part])) {
            return Fabric::find($this->fabric_assignments[$part]);
        }
        return null;
    }

    public function calculateFabricRequirements()
    {
        $requirements = [];
        $templateRequirements = $this->garmentTemplate->fabric_requirements;
        
        foreach ($this->fabric_assignments as $part => $fabricId) {
            $fabric = Fabric::find($fabricId);
            if ($fabric && isset($templateRequirements[$part])) {
                $baseRequirement = $templateRequirements[$part];
                
                // Adjust based on custom measurements
                $adjustmentFactor = $this->calculateMeasurementAdjustment($part);
                $adjustedRequirement = $baseRequirement * $adjustmentFactor;
                
                $requirements[$part] = [
                    'fabric_id' => $fabricId,
                    'base_requirement' => $baseRequirement,
                    'adjusted_requirement' => round($adjustedRequirement, 3),
                    'fabric' => $fabric
                ];
            }
        }
        
        $this->update(['fabric_requirements' => $requirements]);
        return $requirements;
    }

    private function calculateMeasurementAdjustment($part)
    {
        $defaultMeasurements = $this->garmentTemplate->default_measurements;
        $customMeasurements = $this->custom_measurements;
        
        if (!isset($defaultMeasurements[$part]) || !isset($customMeasurements[$part])) {
            return 1.0;
        }
        
        $default = $defaultMeasurements[$part];
        $custom = $customMeasurements[$part];
        
        // Simple adjustment: custom / default
        return max(0.8, min(1.5, $custom / $default));
    }

    public function calculateCosts()
    {
        $fabricCost = 0;
        $requirements = $this->fabric_requirements;
        
        foreach ($requirements as $part => $requirement) {
            $fabric = Fabric::find($requirement['fabric_id']);
            if ($fabric) {
                $fabricCost += $requirement['adjusted_requirement'] * $fabric->current_price;
            }
        }
        
        $tailoringCost = $this->garmentTemplate->tailor_fee;
        $totalCost = $fabricCost + $tailoringCost + $this->garmentTemplate->service_fee;
        
        $this->update([
            'fabric_cost' => $fabricCost,
            'tailoring_cost' => $tailoringCost,
            'total_estimated_cost' => $totalCost
        ]);
        
        return $totalCost;
    }

    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
        $this->garmentTemplate->incrementUsageCount();
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function incrementLikeCount()
    {
        $this->increment('like_count');
    }

    public function incrementCloneCount()
    {
        $this->increment('clone_count');
    }
}
