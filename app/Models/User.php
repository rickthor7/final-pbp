<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'avatar',
        'shop_name',
        'shop_description',
        'shop_logo',
        'shop_address',
        'rating',
        'total_reviews',
        'is_verified',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'rating' => 'decimal:2',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function bodyMeasurements()
    {
        return $this->hasMany(BodyMeasurement::class);
    }

    public function defaultBodyMeasurement()
    {
        return $this->hasOne(BodyMeasurement::class)->where('is_default', true);
    }

    public function customDesigns()
    {
        return $this->hasMany(CustomDesign::class);
    }

    public function customerOrders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function tailorOrders()
    {
        return $this->hasMany(Order::class, 'tailor_id');
    }

    public function tailorAssignments()
    {
        return $this->hasMany(TailorAssignment::class, 'tailor_id');
    }

    public function fabrics()
    {
        return $this->hasMany(Fabric::class, 'seller_id');
    }

    public function fabricOrders()
    {
        return $this->hasMany(OrderFabric::class, 'fabric_seller_id');
    }

    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function tailorReviews()
    {
        return $this->hasMany(Review::class, 'tailor_id');
    }

    public function fabricSellerReviews()
    {
        return $this->hasMany(Review::class, 'fabric_seller_id');
    }

    // Scopes
    public function scopeTailors($query)
    {
        return $query->where('role', 'tailor')->where('is_active', true);
    }

    public function scopeFabricSellers($query)
    {
        return $query->where('role', 'fabric_seller')->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function isTailor()
    {
        return $this->role === 'tailor';
    }

    public function isFabricSeller()
    {
        return $this->role === 'fabric_seller';
    }

    public function isCustomer()
    {
        return $this->role === 'customer';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function getShopLogoUrlAttribute()
    {
        return $this->shop_logo ? asset('storage/'.$this->shop_logo) : asset('images/default-shop.png');
    }

    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? asset('storage/'.$this->avatar) : asset('images/default-avatar.png');
    }

    public function updateRating()
    {
        if ($this->isTailor()) {
            $reviews = $this->tailorReviews();
            $avgRating = $reviews->avg('tailor_rating');
            $this->update([
                'rating' => $avgRating ?? 0,
                'total_reviews' => $reviews->count()
            ]);
        } elseif ($this->isFabricSeller()) {
            $reviews = $this->fabricSellerReviews();
            $avgRating = $reviews->avg('overall_rating');
            $this->update([
                'rating' => $avgRating ?? 0,
                'total_reviews' => $reviews->count()
            ]);
        }
    }
}
