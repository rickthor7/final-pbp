<?php
// app/Http/Controllers/PaymentController.php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Configure Midtrans
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    // Initialize Payment
    public function initializePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:gopay,shopeepay,bank_transfer,qris,credit_card',
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
            $order = Order::where('user_id', $user->id)
                ->where('status', 'pending')
                ->findOrFail($request->order_id);

            // Create Midtrans transaction
            $transaction = $this->createMidtransTransaction($order, $user, $request->payment_method);

            if ($transaction) {
                $order->update([
                    'payment_method' => $request->payment_method,
                    'payment_gateway' => 'midtrans',
                    'gateway_order_id' => $transaction->order_id,
                    'midtrans_token' => $transaction->token,
                    'midtrans_redirect_url' => $transaction->redirect_url,
                    'status' => 'payment_pending'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment initialized successfully',
                    'payment_data' => [
                        'token' => $transaction->token,
                        'redirect_url' => $transaction->redirect_url,
                        'order_id' => $transaction->order_id,
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Payment initialization failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Handle Payment Callback from Midtrans
    public function paymentCallback(Request $request)
    {
        try {
            $notification = new Notification();
            
            $transaction = $notification->transaction_status;
            $type = $notification->payment_type;
            $orderId = $notification->order_id;
            $fraud = $notification->fraud_status;

            Log::info('Payment callback received:', [
                'order_id' => $orderId,
                'transaction_status' => $transaction,
                'payment_type' => $type,
                'fraud_status' => $fraud
            ]);

            // Find order by gateway order ID
            $order = Order::where('gateway_order_id', $orderId)->first();

            if (!$order) {
                Log::error('Order not found for gateway order ID: ' . $orderId);
                return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
            }

            // Handle transaction status
            if ($transaction == 'capture') {
                // For credit card transaction, check fraud status
                if ($type == 'credit_card') {
                    if ($fraud == 'challenge') {
                        $this->handleChallengePayment($order, $notification);
                    } else {
                        $this->handleSuccessfulPayment($order, $notification);
                    }
                }
            } else if ($transaction == 'settlement') {
                $this->handleSuccessfulPayment($order, $notification);
            } else if ($transaction == 'pending') {
                $this->handlePendingPayment($order, $notification);
            } else if ($transaction == 'deny') {
                $this->handleFailedPayment($order, 'Payment denied by payment gateway');
            } else if ($transaction == 'expire') {
                $this->handleFailedPayment($order, 'Payment expired');
            } else if ($transaction == 'cancel') {
                $this->handleFailedPayment($order, 'Payment cancelled');
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Payment callback error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Check Payment Status
    public function checkPaymentStatus($orderId)
    {
        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($orderId);

            if (!$order->gateway_order_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payment information found'
                ], 404);
            }

            // Check status with Midtrans
            $status = \Midtrans\Transaction::status($order->gateway_order_id);

            // Update order status based on payment status
            $this->updateOrderFromPaymentStatus($order, $status);

            return response()->json([
                'success' => true,
                'payment_status' => $status->transaction_status,
                'order_status' => $order->status,
                'payment_data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get Payment Methods
    public function getPaymentMethods()
    {
        try {
            $paymentMethods = [
                [
                    'code' => 'qris',
                    'name' => 'QRIS',
                    'description' => 'Scan QR code with your mobile banking app',
                    'icon' => 'qrcode',
                    'fee' => 0
                ],
                [
                    'code' => 'gopay',
                    'name' => 'GoPay',
                    'description' => 'Pay with GoPay wallet',
                    'icon' => 'mobile',
                    'fee' => 0
                ],
                [
                    'code' => 'shopeepay',
                    'name' => 'ShopeePay',
                    'description' => 'Pay with ShopeePay wallet',
                    'icon' => 'mobile',
                    'fee' => 0
                ],
                [
                    'code' => 'bank_transfer',
                    'name' => 'Bank Transfer',
                    'description' => 'Transfer to any supported bank',
                    'icon' => 'bank',
                    'fee' => 0
                ],
                [
                    'code' => 'credit_card',
                    'name' => 'Credit Card',
                    'description' => 'Visa, MasterCard, JCB',
                    'icon' => 'credit-card',
                    'fee' => 0.02 // 2% fee
                ]
            ];

            return response()->json([
                'success' => true,
                'payment_methods' => $paymentMethods
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get Payment History
    public function getPaymentHistory(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 10);

            $payments = Order::with(['design.garmentTemplate'])
                ->where('user_id', $user->id)
                ->where('payment_status', '!=', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'payments' => $payments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Request Refund
    public function requestRefund(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'refund_reason' => 'required|string|max:500',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($orderId);

            if (!$order->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is not paid, cannot process refund'
                ], 422);
            }

            if ($order->payment_status === 'refunded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund already processed for this order'
                ], 422);
            }

            $refundAmount = $request->refund_amount ?? $order->amount_paid;

            // Process refund through Midtrans
            $refund = $this->processMidtransRefund($order, $refundAmount, $request->refund_reason);

            if ($refund) {
                $order->update([
                    'payment_status' => 'refunded',
                    'amount_paid' => $order->amount_paid - $refundAmount
                ]);

                // Log refund activity
                activity()
                    ->performedOn($order)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'refund_amount' => $refundAmount,
                        'refund_reason' => $request->refund_reason
                    ])
                    ->log('Refund processed');

                return response()->json([
                    'success' => true,
                    'message' => 'Refund request submitted successfully',
                    'refund_amount' => $refundAmount
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Refund processing failed'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Refund request failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Refund request failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Private helper methods
    private function createMidtransTransaction($order, $user, $paymentMethod)
    {
        $params = [
            'transaction_details' => [
                'order_id' => $order->gateway_order_id ?? 'TC-' . $order->order_number . '-' . time(),
                'gross_amount' => (int) $order->total_amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'billing_address' => [
                    'first_name' => $user->name,
                    'phone' => $user->phone,
                    'address' => $order->shipping_address,
                    'city' => $order->shipping_city,
                    'postal_code' => $order->shipping_zip_code,
                    'country_code' => 'IDN'
                ],
                'shipping_address' => [
                    'first_name' => $user->name,
                    'phone' => $order->shipping_phone,
                    'address' => $order->shipping_address,
                    'city' => $order->shipping_city,
                    'postal_code' => $order->shipping_zip_code,
                    'country_code' => 'IDN'
                ]
            ],
            'enabled_payments' => [$paymentMethod],
            'callbacks' => [
                'finish' => config('app.url') . '/payment/success',
                'error' => config('app.url') . '/payment/error',
                'pending' => config('app.url') . '/payment/pending'
            ],
            'expiry' => [
                'start_time' => date('Y-m-d H:i:s O'),
                'unit' => 'hours',
                'duration' => 24
            ]
        ];

        try {
            return Snap::createTransaction($params);
        } catch (\Exception $e) {
            Log::error('Midtrans transaction creation failed: ' . $e->getMessage());
            return null;
        }
    }

    private function handleSuccessfulPayment($order, $notification)
    {
        $order->update([
            'payment_status' => 'paid',
            'status' => 'fabric_ordering',
            'paid_at' => now(),
            'amount_paid' => $order->total_amount,
            'payment_data' => json_encode($notification)
        ]);

        // Create fabric orders
        $this->createFabricOrders($order);

        // Notify stakeholders
        $this->notifyPaymentSuccess($order);

        Log::info('Payment successful for order: ' . $order->order_number);
    }

    private function handlePendingPayment($order, $notification)
    {
        $order->update([
            'payment_status' => 'pending',
            'payment_data' => json_encode($notification)
        ]);

        Log::info('Payment pending for order: ' . $order->order_number);
    }

    private function handleChallengePayment($order, $notification)
    {
        $order->update([
            'payment_status' => 'challenge',
            'payment_data' => json_encode($notification)
        ]);

        // Notify admin about challenged payment
        $this->notifyChallengedPayment($order);

        Log::info('Payment challenged for order: ' . $order->order_number);
    }

    private function handleFailedPayment($order, $reason)
    {
        $order->update([
            'payment_status' => 'failed',
            'status' => 'pending',
            'tailor_notes' => $reason . ' - ' . ($order->tailor_notes ?? '')
        ]);

        Log::info('Payment failed for order: ' . $order->order_number . ' - ' . $reason);
    }

    private function updateOrderFromPaymentStatus($order, $status)
    {
        switch ($status->transaction_status) {
            case 'capture':
            case 'settlement':
                if ($order->payment_status !== 'paid') {
                    $this->handleSuccessfulPayment($order, $status);
                }
                break;
            case 'pending':
                if ($order->payment_status !== 'pending') {
                    $this->handlePendingPayment($order, $status);
                }
                break;
            case 'deny':
            case 'expire':
            case 'cancel':
                if ($order->payment_status !== 'failed') {
                    $this->handleFailedPayment($order, $status->transaction_status);
                }
                break;
        }
    }

    private function processMidtransRefund($order, $amount, $reason)
    {
        try {
            $params = [
                'refund_key' => 'refund-' . $order->gateway_order_id . '-' . time(),
                'amount' => $amount,
                'reason' => $reason
            ];

            $refund = \Midtrans\Transaction::refund($order->gateway_order_id, $params);

            Log::info('Refund processed: ', $refund);

            return true;

        } catch (\Exception $e) {
            Log::error('Midtrans refund failed: ' . $e->getMessage());
            return false;
        }
    }

    private function createFabricOrders($order)
    {
        // This method should create fabric orders when payment is successful
        // Implementation depends on your fabric ordering logic
        $fabricRequirements = $order->design->fabric_requirements;

        foreach ($fabricRequirements as $part => $requirement) {
            // Create fabric order logic here
            // This is a placeholder for actual implementation
        }
    }

    private function notifyPaymentSuccess($order)
    {
        // Notify customer
        // Mail::to($order->user->email)->send(new PaymentSuccessMail($order));

        // Notify tailor
        // Mail::to($order->tailor->email)->send(new NewOrderMail($order));

        // You can also integrate with notification systems here
    }

    private function notifyChallengedPayment($order)
    {
        // Notify admin about challenged payment
        // Mail::to(config('mail.admin_email'))->send(new ChallengedPaymentMail($order));
    }
}
