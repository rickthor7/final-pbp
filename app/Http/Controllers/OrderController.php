<?php
// app/Http/Controllers/OrderController.php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CustomDesign;
use App\Models\User;
use App\Models\OrderFabric;
use App\Models\TailorAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    // Create Order from Design
    public function createOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'design_id' => 'required|exists:custom_designs,id',
            'tailor_id' => 'required|exists:users,id',
            'body_measurement_id' => 'nullable|exists:body_measurements,id',
            'customer_notes' => 'nullable|string|max:1000',
            'preferred_completion_date' => 'nullable|date|after:today',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_state' => 'required|string',
            'shipping_zip_code' => 'required|string',
            'shipping_country' => 'required|string',
            'shipping_phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $design = CustomDesign::where('user_id', $user->id)
                ->where('status', 'completed')
                ->findOrFail($request->design_id);

            $tailor = User::tailors()->verified()->active()->findOrFail($request->tailor_id);

            // Generate unique order number
            $orderNumber = 'TC' . date('Ymd') . Str::upper(Str::random(6));

            // Calculate costs
            $costs = $this->calculateOrderCosts($design, $tailor);

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'design_id' => $design->id,
                'tailor_id' => $tailor->id,
                'body_measurement_id' => $request->body_measurement_id,
                'customer_notes' => $request->customer_notes,
                'preferred_completion_date' => $request->preferred_completion_date,
                'fabric_cost' => $costs['fabric_cost'],
                'tailoring_cost' => $costs['tailoring_cost'],
                'service_fee' => $costs['service_fee'],
                'shipping_cost' => $costs['shipping_cost'],
                'total_amount' => $costs['total_amount'],
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_zip_code' => $request->shipping_zip_code,
                'shipping_country' => $request->shipping_country,
                'shipping_phone' => $request->shipping_phone,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            // Update design status
            $design->update(['status' => 'ordered']);

            // Create fabric orders
            $this->createFabricOrders($order, $design);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $order->load(['design.garmentTemplate', 'tailor']),
                'payment_required' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get User Orders
    public function getUserOrders(Request $request)
    {
        try {
            $user = $request->user();
            $status = $request->get('status', 'all');
            $perPage = $request->get('per_page', 10);

            $query = Order::with([
                'design.garmentTemplate',
                'tailor',
                'orderFabrics.fabric',
                'tailorAssignment'
            ])->where('user_id', $user->id);

            if ($status !== 'all') {
                $query->where('status', $status);
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $orderStats = [
                'total' => Order::where('user_id', $user->id)->count(),
                'pending' => Order::where('user_id', $user->id)->whereIn('status', ['pending', 'payment_pending'])->count(),
                'in_production' => Order::where('user_id', $user->id)->whereIn('status', ['fabric_ordering', 'fabric_ordered', 'in_production'])->count(),
                'completed' => Order::where('user_id', $user->id)->where('status', 'completed')->count(),
            ];

            return response()->json([
                'success' => true,
                'orders' => $orders,
                'stats' => $orderStats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get Single Order
    public function getOrder($id)
    {
        try {
            $order = Order::with([
                'design.garmentTemplate',
                'tailor',
                'bodyMeasurement',
                'orderFabrics.fabric',
                'orderFabrics.fabricSeller',
                'tailorAssignment.tailor',
                'review'
            ])->findOrFail($id);

            // Check if user owns the order
            if ($order->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Get detailed fabric information
            $fabricDetails = [];
            foreach ($order->orderFabrics as $orderFabric) {
                $fabricDetails[] = [
                    'part' => $orderFabric->garment_part,
                    'fabric' => $orderFabric->fabric,
                    'amount' => $orderFabric->fabric_amount,
                    'price' => $orderFabric->total_price,
                    'status' => $orderFabric->status,
                    'seller' => $orderFabric->fabricSeller
                ];
            }

            return response()->json([
                'success' => true,
                'order' => $order,
                'fabric_details' => $fabricDetails,
                'timeline' => $order->timeline_events
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update Order
    public function updateOrder(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'customer_notes' => 'nullable|string|max:1000',
            'shipping_address' => 'sometimes|required|string',
            'shipping_city' => 'sometimes|required|string',
            'shipping_state' => 'sometimes|required|string',
            'shipping_zip_code' => 'sometimes|required|string',
            'shipping_phone' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($id);

            // Check if order can be updated
            if (!$this->canOrderBeUpdated($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be updated at this stage'
                ], 422);
            }

            $updateData = [];
            if ($request->has('customer_notes')) {
                $updateData['customer_notes'] = $request->customer_notes;
            }

            // Update shipping address if provided
            if ($request->has('shipping_address')) {
                $updateData = array_merge($updateData, [
                    'shipping_address' => $request->shipping_address,
                    'shipping_city' => $request->shipping_city,
                    'shipping_state' => $request->shipping_state,
                    'shipping_zip_code' => $request->shipping_zip_code,
                    'shipping_phone' => $request->shipping_phone,
                ]);
            }

            $order->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'order' => $order->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Cancel Order
    public function cancelOrder(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($id);

            if (!$order->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled at this stage'
                ], 422);
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'tailor_notes' => $request->cancellation_reason . ' - ' . ($order->tailor_notes ?? '')
            ]);

            // Cancel fabric orders
            $order->orderFabrics()->whereIn('status', ['pending', 'ordered'])->update([
                'status' => 'cancelled'
            ]);

            // Cancel tailor assignment if exists
            if ($order->tailorAssignment) {
                $order->tailorAssignment->update(['status' => 'cancelled']);
            }

            // Refund payment if already paid
            if ($order->isPaid()) {
                // Implement refund logic here
                $this->processRefund($order);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order cancellation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Track Order
    public function trackOrder($id)
    {
        try {
            $order = Order::with([
                'design.garmentTemplate',
                'tailor',
                'orderFabrics.fabric',
                'orderFabrics.fabricSeller',
                'tailorAssignment'
            ])->where('user_id', Auth::id())->findOrFail($id);

            $trackingInfo = [
                'current_status' => $order->status,
                'status_label' => $this->getStatusLabel($order->status),
                'progress_percentage' => $this->calculateProgressPercentage($order),
                'estimated_completion' => $this->getEstimatedCompletion($order),
                'next_steps' => $this->getNextSteps($order),
                'fabric_status' => $this->getFabricStatus($order),
                'tailor_status' => $this->getTailorStatus($order),
            ];

            return response()->json([
                'success' => true,
                'order' => $order,
                'tracking_info' => $trackingInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get Order Timeline
    public function getOrderTimeline($id)
    {
        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($id);

            return response()->json([
                'success' => true,
                'timeline' => $order->timeline_events
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order timeline',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Private helper methods
    private function calculateOrderCosts($design, $tailor)
    {
        $fabricCost = $design->fabric_cost;
        $tailoringCost = $design->tailoring_cost;
        $serviceFee = $design->garmentTemplate->service_fee;
        
        // Calculate shipping cost based on location (simplified)
        $shippingCost = 25000; // Default shipping cost in IDR
        
        $totalAmount = $fabricCost + $tailoringCost + $serviceFee + $shippingCost;

        return [
            'fabric_cost' => $fabricCost,
            'tailoring_cost' => $tailoringCost,
            'service_fee' => $serviceFee,
            'shipping_cost' => $shippingCost,
            'total_amount' => $totalAmount
        ];
    }

    private function createFabricOrders($order, $design)
    {
        $fabricRequirements = $design->fabric_requirements;

        foreach ($fabricRequirements as $part => $requirement) {
            OrderFabric::create([
                'order_id' => $order->id,
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

    private function canOrderBeUpdated($order)
    {
        return in_array($order->status, ['pending', 'payment_pending', 'paid']);
    }

    private function processRefund($order)
    {
        // Implement refund logic based on payment gateway
        // This is a placeholder for actual refund implementation
        $order->update([
            'payment_status' => 'refunded',
            'amount_paid' => 0
        ]);

        // Log refund activity
        activity()
            ->performedOn($order)
            ->causedBy(Auth::user())
            ->log('Order refund processed');
    }

    private function calculateProgressPercentage($order)
    {
        $statusWeights = [
            'pending' => 0,
            'payment_pending' => 10,
            'paid' => 20,
            'fabric_ordering' => 30,
            'fabric_ordered' => 40,
            'in_production' => 60,
            'quality_check' => 80,
            'ready_for_shipping' => 90,
            'shipped' => 95,
            'delivered' => 100,
            'completed' => 100,
        ];

        return $statusWeights[$order->status] ?? 0;
    }

    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'Order Placed',
            'payment_pending' => 'Waiting for Payment',
            'paid' => 'Payment Received',
            'fabric_ordering' => 'Ordering Fabrics',
            'fabric_ordered' => 'Fabrics Ordered',
            'in_production' => 'In Production',
            'quality_check' => 'Quality Check',
            'ready_for_shipping' => 'Ready for Shipping',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
        ];

        return $labels[$status] ?? 'Unknown Status';
    }

    private function getEstimatedCompletion($order)
    {
        if ($order->preferred_completion_date) {
            return $order->preferred_completion_date;
        }

        $baseDays = $order->design->garmentTemplate->completion_time_days;
        $estimatedDate = now()->addDays($baseDays);

        return $estimatedDate->format('Y-m-d');
    }

    private function getNextSteps($order)
    {
        $nextSteps = [];

        switch ($order->status) {
            case 'pending':
            case 'payment_pending':
                $nextSteps[] = 'Complete payment to start the process';
                break;
            case 'paid':
                $nextSteps[] = 'We are ordering your selected fabrics';
                break;
            case 'fabric_ordering':
                $nextSteps[] = 'Waiting for fabric sellers to confirm';
                break;
            case 'fabric_ordered':
                $nextSteps[] = 'Fabrics are being prepared for shipping';
                break;
            case 'in_production':
                $nextSteps[] = 'Your garment is being tailored';
                if ($order->tailorAssignment) {
                    $nextSteps[] = 'Progress: ' . $order->tailorAssignment->completion_percentage . '%';
                }
                break;
            case 'quality_check':
                $nextSteps[] = 'Your garment is undergoing quality inspection';
                break;
            case 'ready_for_shipping':
                $nextSteps[] = 'Your order is ready to be shipped';
                break;
            case 'shipped':
                $nextSteps[] = 'Your order is on the way';
                if ($order->tracking_number) {
                    $nextSteps[] = 'Tracking: ' . $order->tracking_number;
                }
                break;
        }

        return $nextSteps;
    }

    private function getFabricStatus($order)
    {
        $fabricOrders = $order->orderFabrics;
        $statusCounts = [
            'pending' => 0,
            'ordered' => 0,
            'shipped' => 0,
            'delivered_to_tailor' => 0,
        ];

        foreach ($fabricOrders as $fabricOrder) {
            $statusCounts[$fabricOrder->status]++;
        }

        return $statusCounts;
    }

    private function getTailorStatus($order)
    {
        if (!$order->tailorAssignment) {
            return 'Not assigned yet';
        }

        $assignment = $order->tailorAssignment;

        return [
            'status' => $assignment->status,
            'progress' => $assignment->completion_percentage,
            'deadline' => $assignment->deadline,
            'days_remaining' => $assignment->days_remaining,
        ];
    }
}
