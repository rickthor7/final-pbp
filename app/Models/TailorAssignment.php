<?php
// app/Models/TailorAssignment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TailorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tailor_id',
        'status',
        'priority',
        'assigned_date',
        'deadline',
        'completed_date',
        'special_instructions',
        'work_steps',
        'completion_percentage',
        'quality_check_passed',
        'quality_notes',
        'quality_checked_at',
        'quality_checked_by',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'deadline' => 'date',
        'completed_date' => 'date',
        'work_steps' => 'array',
        'completion_percentage' => 'decimal:2',
        'quality_check_passed' => 'boolean',
        'quality_checked_at' => 'datetime',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function tailor()
    {
        return $this->belongsTo(User::class, 'tailor_id');
    }

    public function qualityChecker()
    {
        return $this->belongsTo(User::class, 'quality_checked_by');
    }

    // Scopes
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())->where('status', '!=', 'completed');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 1);
    }

    // Methods
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'assigned' => 'bg-blue-100 text-blue-800',
            'accepted' => 'bg-green-100 text-green-800',
            'in_progress' => 'bg-yellow-100 text-yellow-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];

        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getPriorityBadgeClassAttribute()
    {
        $classes = [
            1 => 'bg-red-100 text-red-800',
            2 => 'bg-orange-100 text-orange-800',
            3 => 'bg-yellow-100 text-yellow-800',
            4 => 'bg-blue-100 text-blue-800',
            5 => 'bg-gray-100 text-gray-800',
        ];

        return $classes[$this->priority] ?? 'bg-gray-100 text-gray-800';
    }

    public function getPriorityTextAttribute()
    {
        $levels = [
            1 => 'Very High',
            2 => 'High',
            3 => 'Medium',
            4 => 'Low',
            5 => 'Very Low',
        ];

        return $levels[$this->priority] ?? 'Unknown';
    }

    public function isOverdue()
    {
        return $this->deadline < now() && !in_array($this->status, ['completed', 'cancelled']);
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->status === 'completed') {
            return 0;
        }

        return max(0, now()->diffInDays($this->deadline, false));
    }

    public function updateCompletion($percentage)
    {
        $this->update(['completion_percentage' => max(0, min(100, $percentage))]);
        
        if ($percentage >= 100 && $this->status !== 'completed') {
            $this->markAsCompleted();
        }
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_date' => now(),
            'completion_percentage' => 100
        ]);

        // Update order status to quality check
        $this->order->updateStatus('quality_check');
    }

    public function addWorkStep($step, $description, $completed = false)
    {
        $steps = $this->work_steps ?? [];
        $steps[] = [
            'step' => $step,
            'description' => $description,
            'completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ];

        $this->update(['work_steps' => $steps]);
    }

    public function completeWorkStep($stepIndex)
    {
        $steps = $this->work_steps;
        if (isset($steps[$stepIndex])) {
            $steps[$stepIndex]['completed'] = true;
            $steps[$stepIndex]['completed_at'] = now();
            $this->update(['work_steps' => $steps]);
        }
    }
}
