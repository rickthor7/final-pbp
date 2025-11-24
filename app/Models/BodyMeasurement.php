<?php
// app/Models/BodyMeasurement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BodyMeasurement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'measurement_name',
        'height',
        'weight',
        'chest_bust',
        'under_bust',
        'waist',
        'hips',
        'shoulder_width',
        'back_width',
        'arm_length',
        'bicep',
        'wrist',
        'thigh',
        'knee',
        'calf',
        'ankle',
        'inseam',
        'outseam',
        'neck_circumference',
        'head_circumference',
        'custom_measurements',
        'notes',
        'is_default',
    ];

    protected $casts = [
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'chest_bust' => 'decimal:2',
        'under_bust' => 'decimal:2',
        'waist' => 'decimal:2',
        'hips' => 'decimal:2',
        'shoulder_width' => 'decimal:2',
        'back_width' => 'decimal:2',
        'arm_length' => 'decimal:2',
        'bicep' => 'decimal:2',
        'wrist' => 'decimal:2',
        'thigh' => 'decimal:2',
        'knee' => 'decimal:2',
        'calf' => 'decimal:2',
        'ankle' => 'decimal:2',
        'inseam' => 'decimal:2',
        'outseam' => 'decimal:2',
        'neck_circumference' => 'decimal:2',
        'head_circumference' => 'decimal:2',
        'custom_measurements' => 'array',
        'is_default' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customDesigns()
    {
        return $this->hasMany(CustomDesign::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Methods
    public function getBmiAttribute()
    {
        if ($this->height && $this->weight) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 1);
        }
        return null;
    }

    public function getBmiCategoryAttribute()
    {
        $bmi = $this->bmi;
        if (!$bmi) return null;

        if ($bmi < 18.5) return 'Underweight';
        if ($bmi < 25) return 'Normal';
        if ($bmi < 30) return 'Overweight';
        return 'Obese';
    }

    public function setAsDefault()
    {
        // Remove default status from other measurements of this user
        $this->user->bodyMeasurements()->update(['is_default' => false]);
        
        // Set this as default
        $this->update(['is_default' => true]);
    }

    public function getMeasurementSummaryAttribute()
    {
        return [
            'height' => $this->height,
            'weight' => $this->weight,
            'chest' => $this->chest_bust,
            'waist' => $this->waist,
            'hips' => $this->hips,
            'shoulder_width' => $this->shoulder_width,
            'arm_length' => $this->arm_length,
        ];
    }
}
