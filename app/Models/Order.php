<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'design_id',
        'tailor_id',
        'body_measurement_id',
        'customer_notes',
        'tailor_notes',
        'preferred_completion_date',
        'fabric_cost',
        'tailoring_cost',
        'service_fee',
        'shipping_cost',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'status',
        'payment_method',
        'payment_status',
        'payment_gateway',
        'gateway_order_id',
        'gateway_transaction_id',
        'payment_data',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_zip_code',
        'shipping_country',
        'shipping_phone',
        'tracking_number',
        'shipping_carrier',
        'paid_at',
        'production_started_at',
        'quality_check_at',
        'shipped_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'fabric_cost' => 'decimal:2',
        'tailoring_cost' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'payment_data' => 'array',
        'preferred_completion_date' => 'date',
        'paid_at' => 'datetime',
        'production_started_at' => 'datetime',
        'quality_check_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function design()
    {
        return $this->belongsTo(CustomDesign::class);
    }

    public function tailor()
    {
        return $this->belongsTo(User::class, 'tailor_id');
    }

    public function bodyMeasurement()
    {
        return $this->belongsTo(BodyMeasurement::class);
    }

    public function orderFabrics()
    {
        return $this->hasMany(OrderFabric::class);
    }

    public function tailorAssignment()
    {
        return $this->hasOne(TailorAssignment::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeInProduction($query)
    {
        return $query->where('status', 'in_production');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTailor($query, $tailorId)
    {
        return $query->where('tailor_id', $tailorId);
    }

    // Methods
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'payment_pending' => 'bg-orange-100 text-orange-800',
            'paid' => 'bg-blue-100 text-blue-800',
            'fabric_ordering' => 'bg-indigo-100 text-indigo-800',
            'fabric_ordered' => 'bg-purple-100 text-purple-800',
            'in_production' => 'bg-teal-100 text-teal-800',
            'quality_check' => 'bg-cyan-100 text-cyan-800',
            'ready_for_shipping' => 'bg-lime-100 text-lime-800',
            'shipped' => 'bg-green-100 text-green-800',
            'delivered' => 'bg-emerald-100 text-emerald-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-gray-100 text-gray-800',
        ];

        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getPaymentStatusBadgeClassAttribute()
    {
        $classes = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'paid' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-gray-100 text-gray-800',
            'partially_refunded' => 'bg-orange-100 text-orange-800',
        ];

        return $classes[$this->payment_status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->amount_paid;
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'payment_pending', 'paid', 'fabric_ordering']);
    }

    public function markAsPaid()
    {
        $this->update([
            'payment_status' => 'paid',
            'status' => 'fabric_ordering',
            'paid_at' => now(),
            'amount_paid' => $this->total_amount
        ]);

        // Create fabric orders
        $this->createFabricOrders();
    }

    public function createFabricOrders()
    {
        $fabricRequirements = $this->design->fabric_requirements;
        
        foreach ($fabricRequirements as $part => $requirement) {
            OrderFabric::create([
                'order_id' => $this->id,
                'fabric_id' => $requirement['fabric_id'],
                'fabric_seller_id' => $requirement['fabric']->seller_id,
                'garment_part' => $part,
                'fabric_amount' => $requirement['adjusted_requirement'],
                'price_per_meter' => $requirement['fabric']->current_price,
                'total_price' => $requirement['adjusted_requirement'] * $requirement['fabric']->current_price,
                'status' => 'pending'
            ]);
        }
    }

    public function updateStatus($newStatus)
    {
        $this->update(['status' => $newStatus]);
        
        // Update timestamps based on status
        $timestampFields = [
            'in_production' => 'production_started_at',
            'quality_check' => 'quality_check_at',
            'shipped' => 'shipped_at',
            'delivered' => 'delivered_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
        ];

        if (isset($timestampFields[$newStatus])) {
            $this->update([$timestampFields[$newStatus] => now()]);
        }
    }

    public function getTimelineEventsAttribute()
    {
        $events = [];

        if ($this->created_at) {
            $events[] = [
                'event' => 'Order Created',
                'date' => $this->created_at,
                'description' => 'Your custom design order was created'
            ];
        }

        if ($this->paid_at) {
            $events[] = [
                'event' => 'Payment Received',
                'date' => $this->paid_at,
                'description' => 'Payment confirmed, ordering fabrics'
            ];
        }

        if ($this->production_started_at) {
            $events[] = [
                'event' => 'Production Started',
                'date' => $this->production_started_at,
                'description' => 'Tailor has started working on your garment'
            ];
        }

        if ($this->quality_check_at) {
            $events[] = [
                'event' => 'Quality Check',
                'date' => $this->quality_check_at,
                'description' => 'Your garment is undergoing quality inspection'
            ];
        }

        if ($this->shipped_at) {
            $events[] = [
                'event' => 'Shipped',
                'date' => $this->shipped_at,
                'description' => 'Your order has been shipped'
            ];
        }

        if ($this->delivered_at) {
            $events[] = [
                'event' => 'Delivered',
                'date' => $this->delivered_at,
                'description' => 'Your order has been delivered'
            ];
        }

        if ($this->completed_at) {
            $events[] = [
                'event' => 'Completed',
                'date' => $this->completed_at,
                'description' => 'Order completed successfully'
            ];
        }

        return $events;
    }
}
