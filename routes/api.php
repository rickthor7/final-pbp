<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DesignStudioApiController;
use App\Http\Controllers\Api\DesignApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\BodyMeasurementApiController;
use App\Http\Controllers\Api\FabricApiController;
use App\Http\Controllers\Api\TailorApiController;
use App\Http\Controllers\Api\FabricSellerApiController;
use App\Http\Controllers\Api\NotificationApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public API Routes
Route::get('/health', function () {
    return response()->json(['status' => 'OK', 'timestamp' => now()]);
});

Route::prefix('v1')->group(function () {
    
    // Public Routes
    Route::prefix('public')->group(function () {
        // Fabrics
        Route::get('/fabrics', [FabricApiController::class, 'indexPublic']);
        Route::get('/fabrics/{fabric}', [FabricApiController::class, 'showPublic']);
        Route::get('/fabrics/category/{category}', [FabricApiController::class, 'byCategoryPublic']);
        
        // Tailors
        Route::get('/tailors', [TailorApiController::class, 'indexPublic']);
        Route::get('/tailors/{tailor}', [TailorApiController::class, 'showPublic']);
        
        // Templates
        Route::get('/templates', [DesignStudioApiController::class, 'getTemplatesPublic']);
    });
    
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        
        // Email Verification
        Route::post('/email/verify', [AuthController::class, 'verifyEmail'])->middleware('auth:sanctum');
        Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])->middleware('auth:sanctum');
    });
    
    // Protected Routes
    Route::middleware(['auth:sanctum'])->group(function () {
        
        // Authentication
        Route::prefix('auth')->group(function () {
            Route::get('/user', function (Request $request) {
                return $request->user();
            });
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::put('/password', [AuthController::class, 'changePassword']);
        });
        
        // User Profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileApiController::class, 'getProfile']);
            Route::put('/', [ProfileApiController::class, 'updateProfile']);
            Route::post('/avatar', [ProfileApiController::class, 'updateAvatar']);
            Route::put('/password', [ProfileApiController::class, 'updatePassword']);
            Route::get('/stats', [ProfileApiController::class, 'getUserStats']);
        });
        
        // Body Measurements
        Route::apiResource('measurements', BodyMeasurementApiController::class);
        Route::post('/measurements/{measurement}/set-default', [BodyMeasurementApiController::class, 'setDefault']);
        
        // Design Studio
        Route::prefix('design-studio')->group(function () {
            Route::get('/data', [DesignStudioApiController::class, 'getStudioData']);
            Route::post('/designs', [DesignStudioApiController::class, 'createDesign']);
            Route::post('/preview', [DesignStudioApiController::class, 'generatePreview']);
            Route::get('/templates', [DesignStudioApiController::class, 'getTemplates']);
            Route::get('/templates/{template}', [DesignStudioApiController::class, 'getTemplate']);
            Route::get('/fabrics', [DesignStudioApiController::class, 'getFabrics']);
            Route::get('/fabrics/filters', [DesignStudioApiController::class, 'getFabricFilters']);
            Route::get('/tailors/{design}', [DesignStudioApiController::class, 'getAvailableTailors']);
        });
        
        // Design Management
        Route::apiResource('designs', DesignApiController::class);
        Route::post('/designs/{design}/clone', [DesignApiController::class, 'clone']);
        Route::post('/designs/{design}/publish', [DesignApiController::class, 'publish']);
        Route::post('/designs/{design}/unpublish', [DesignApiController::class, 'unpublish']);
        Route::get('/designs/{design}/order-data', [DesignApiController::class, 'getOrderData']);
        
        // Order Management
        Route::apiResource('orders', OrderApiController::class);
        Route::post('/orders/{order}/cancel', [OrderApiController::class, 'cancel']);
        Route::get('/orders/{order}/track', [OrderApiController::class, 'track']);
        Route::get('/orders/{order}/timeline', [OrderApiController::class, 'timeline']);
        Route::post('/orders/{order}/review', [OrderApiController::class, 'addReview']);
        
        // Payment Management
        Route::prefix('payments')->group(function () {
            Route::get('/methods', [PaymentApiController::class, 'getPaymentMethods']);
            Route::post('/initialize', [PaymentApiController::class, 'initializePayment']);
            Route::get('/{order}/status', [PaymentApiController::class, 'checkPaymentStatus']);
            Route::get('/history', [PaymentApiController::class, 'getPaymentHistory']);
            Route::post('/{order}/refund', [PaymentApiController::class, 'requestRefund']);
        });
        
        // Fabric Management
        Route::apiResource('fabrics', FabricApiController::class)->only(['index', 'show']);
        Route::get('/fabrics/categories', [FabricApiController::class, 'getCategories']);
        Route::get('/fabrics/search', [FabricApiController::class, 'search']);
        
        // Notification Management
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationApiController::class, 'index']);
            Route::get('/unread', [NotificationApiController::class, 'unread']);
            Route::put('/{notification}/read', [NotificationApiController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationApiController::class, 'markAllAsRead']);
            Route::get('/count', [NotificationApiController::class, 'getCount']);
        });
        
        // Tailor Specific API Routes
        Route::middleware(['role:tailor'])->prefix('tailor')->group(function () {
            Route::get('/dashboard', [TailorApiController::class, 'getDashboard']);
            Route::get('/stats', [TailorApiController::class, 'getStats']);
            
            // Orders
            Route::get('/orders', [TailorApiController::class, 'getOrders']);
            Route::get('/orders/{order}', [TailorApiController::class, 'getOrderDetail']);
            Route::put('/orders/{order}/status', [TailorApiController::class, 'updateOrderStatus']);
            Route::post('/orders/{order}/notes', [TailorApiController::class, 'addOrderNotes']);
            
            // Assignments
            Route::get('/assignments', [TailorApiController::class, 'getAssignments']);
            Route::get('/assignments/{assignment}', [TailorApiController::class, 'getAssignmentDetail']);
            Route::put('/assignments/{assignment}/status', [TailorApiController::class, 'updateAssignmentStatus']);
            Route::put('/assignments/{assignment}/progress', [TailorApiController::class, 'updateProgress']);
            Route::post('/assignments/{assignment}/work-step', [TailorApiController::class, 'addWorkStep']);
            Route::put('/assignments/{assignment}/work-step/{stepIndex}', [TailorApiController::class, 'completeWorkStep']);
            
            // Work Management
            Route::get('/work/queue', [TailorApiController::class, 'getWorkQueue']);
            Route::get('/work/in-progress', [TailorApiController::class, 'getWorkInProgress']);
            Route::get('/work/completed', [TailorApiController::class, 'getWorkCompleted']);
            
            // Quality Control
            Route::get('/quality/pending', [TailorApiController::class, 'getPendingQualityChecks']);
            Route::put('/quality/{assignment}/approve', [TailorApiController::class, 'approveQuality']);
            Route::put('/quality/{assignment}/reject', [TailorApiController::class, 'rejectQuality']);
            
            // Reports
            Route::get('/reports/earnings', [TailorApiController::class, 'getEarningsReport']);
            Route::get('/reports/performance', [TailorApiController::class, 'getPerformanceReport']);
        });
        
        // Fabric Seller Specific API Routes
        Route::middleware(['role:fabric_seller'])->prefix('seller')->group(function () {
            Route::get('/dashboard', [FabricSellerApiController::class, 'getDashboard']);
            Route::get('/stats', [FabricSellerApiController::class, 'getStats']);
            
            // Fabric Management
            Route::apiResource('fabrics', FabricSellerApiController::class);
            Route::post('/fabrics/{fabric}/toggle-status', [FabricSellerApiController::class, 'toggleFabricStatus']);
            Route::post('/fabrics/{fabric}/feature', [FabricSellerApiController::class, 'toggleFeatured']);
            Route::post('/fabrics/{fabric}/stock', [FabricSellerApiController::class, 'updateStock']);
            Route::post('/fabrics/{fabric}/images', [FabricSellerApiController::class, 'updateImages']);
            Route::get('/fabrics/{fabric}/analytics', [FabricSellerApiController::class, 'getFabricAnalytics']);
            
            // Order Management
            Route::get('/orders', [FabricSellerApiController::class, 'getOrders']);
            Route::get('/orders/{orderFabric}', [FabricSellerApiController::class, 'getOrderDetail']);
            Route::put('/orders/{orderFabric}/status', [FabricSellerApiController::class, 'updateOrderStatus']);
            Route::post('/orders/{orderFabric}/ship', [FabricSellerApiController::class, 'shipOrder']);
            Route::post('/orders/{orderFabric}/tracking', [FabricSellerApiController::class, 'updateTracking']);
            
            // Inventory Management
            Route::get('/inventory', [FabricSellerApiController::class, 'getInventory']);
            Route::get('/inventory/low-stock', [FabricSellerApiController::class, 'getLowStock']);
            Route::post('/inventory/bulk-update', [FabricSellerApiController::class, 'bulkUpdateStock']);
            
            // Customer Management
            Route::get('/customers', [FabricSellerApiController::class, 'getCustomers']);
            Route::get('/customers/{customer}', [FabricSellerApiController::class, 'getCustomerDetail']);
            
            // Reports
            Route::get('/reports/sales', [FabricSellerApiController::class, 'getSalesReport']);
            Route::get('/reports/inventory', [FabricSellerApiController::class, 'getInventoryReport']);
        });
    });
    
    // Payment Webhook (No Auth Required)
    Route::post('/payment-webhook', [PaymentApiController::class, 'paymentWebhook']);
});

// API Fallback
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found. Please check the documentation.',
        'status' => 404
    ], 404);
});
