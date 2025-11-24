<?php
// app/Models/OrderFabric.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderFabric extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'fabric_id',
        'fabric_seller_id',
        'garment_part',
        'fabric_amount',
        'price_per_meter',
        'total_price',
        'status',
        'seller_notes',
        'ordered_at',
        'estimated_delivery_date',
        'shipped_at',
        'delivered_at',
        'tracking_number',
        'shipping_carrier',
    ];

    protected $casts = [
        'fabric_amount' => 'decimal:3',
        'price_per_meter' => 'decimal:2',
        'total_price' => 'decimal:2',
        'ordered_at' => 'datetime',
        'estimated_delivery_date' => 'date',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function fabric()
    {
        return $this->belongsTo(Fabric::class);
    }

    public function fabricSeller()
    {
        return $this->belongsTo(User::class, 'fabric_seller_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOrdered($query)
    {
        return $query->where('status', 'ordered');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered_to_tailor');
    }

    // Methods
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'ordered' => 'bg-blue-100 text-blue-800',
            'cutting' => 'bg-indigo-100 text-indigo-800',
            'shipped' => 'bg-purple-100 text-purple-800',
            'delivered_to_tailor' => 'bg-green-100 text-green-800',
            'quality_check' => 'bg-cyan-100 text-cyan-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
        ];

        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function markAsOrdered()
    {
        $this->update([
            'status' => 'ordered',
            'ordered_at' => now()
        ]);

        // Update fabric stock
        $this->fabric->decrement('stock_meter', $this->fabric_amount);
    }

    public function markAsShipped($trackingNumber = null, $carrier = null)
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
            'tracking_number' => $trackingNumber,
            'shipping_carrier' => $carrier
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => 'delivered_to_tailor',
            'delivered_at' => now()
        ]);
    }

    public function isDelivered()
    {
        return in_array($this->status, ['delivered_to_tailor', 'quality_check', 'approved']);
    }
}
