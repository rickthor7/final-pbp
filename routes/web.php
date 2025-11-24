<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DesignStudioController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TailorDashboardController;
use App\Http\Controllers\FabricSellerController;
use App\Http\Controllers\FabricController;
use App\Http\Controllers\BodyMeasurementController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/how-it-works', [HomeController::class, 'howItWorks'])->name('how-it-works');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'contactSubmit'])->name('contact.submit');

// Public Fabric Gallery
Route::get('/fabrics', [FabricController::class, 'index'])->name('fabrics.index');
Route::get('/fabrics/{fabric}', [FabricController::class, 'show'])->name('fabrics.show');
Route::get('/fabrics/category/{category}', [FabricController::class, 'byCategory'])->name('fabrics.category');

// Public Tailors Directory
Route::get('/tailors', [HomeController::class, 'tailors'])->name('tailors.index');
Route::get('/tailors/{tailor}', [HomeController::class, 'tailorProfile'])->name('tailors.show');

// Authentication Routes
Route::middleware('guest')->group(function () {
    // Login Routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    
    // Registration Routes
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    
    // Password Reset Routes
    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Protected Routes - All Authenticated Users
Route::middleware(['auth'])->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Dashboard
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    
    // Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
    
    // Body Measurements
    Route::prefix('measurements')->name('measurements.')->group(function () {
        Route::get('/', [BodyMeasurementController::class, 'index'])->name('index');
        Route::get('/create', [BodyMeasurementController::class, 'create'])->name('create');
        Route::post('/', [BodyMeasurementController::class, 'store'])->name('store');
        Route::get('/{measurement}/edit', [BodyMeasurementController::class, 'edit'])->name('edit');
        Route::put('/{measurement}', [BodyMeasurementController::class, 'update'])->name('update');
        Route::delete('/{measurement}', [BodyMeasurementController::class, 'destroy'])->name('destroy');
        Route::post('/{measurement}/set-default', [BodyMeasurementController::class, 'setDefault'])->name('set-default');
    });
    
    // Design Studio Routes
    Route::prefix('design-studio')->name('design-studio.')->group(function () {
        Route::get('/', [DesignStudioController::class, 'index'])->name('index');
        Route::post('/preview', [DesignStudioController::class, 'generatePreview'])->name('preview');
        Route::post('/save-design', [DesignStudioController::class, 'saveDesign'])->name('save-design');
        Route::get('/template/{template}', [DesignStudioController::class, 'getTemplate'])->name('template');
        Route::get('/fabrics-data', [DesignStudioController::class, 'getFabricsData'])->name('fabrics-data');
    });
    
    // Design Management Routes
    Route::prefix('designs')->name('designs.')->group(function () {
        Route::get('/', [DesignController::class, 'index'])->name('index');
        Route::get('/create', [DesignController::class, 'create'])->name('create');
        Route::post('/', [DesignController::class, 'store'])->name('store');
        Route::get('/{design}', [DesignController::class, 'show'])->name('show');
        Route::get('/{design}/edit', [DesignController::class, 'edit'])->name('edit');
        Route::put('/{design}', [DesignController::class, 'update'])->name('update');
        Route::delete('/{design}', [DesignController::class, 'destroy'])->name('destroy');
        Route::post('/{design}/clone', [DesignController::class, 'clone'])->name('clone');
        Route::post('/{design}/publish', [DesignController::class, 'publish'])->name('publish');
        Route::post('/{design}/unpublish', [DesignController::class, 'unpublish'])->name('unpublish');
        Route::get('/{design}/order', [DesignController::class, 'orderForm'])->name('order');
    });
    
    // Order Management Routes
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/create/{design}', [OrderController::class, 'create'])->name('create');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::get('/{order}/track', [OrderController::class, 'track'])->name('track');
        Route::get('/{order}/timeline', [OrderController::class, 'timeline'])->name('timeline');
        Route::post('/{order}/review', [OrderController::class, 'addReview'])->name('review');
    });
    
    // Payment Routes
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/methods', [PaymentController::class, 'paymentMethods'])->name('methods');
        Route::post('/initialize', [PaymentController::class, 'initializePayment'])->name('initialize');
        Route::get('/{order}/checkout', [PaymentController::class, 'checkout'])->name('checkout');
        Route::get('/{order}/status', [PaymentController::class, 'paymentStatus'])->name('status');
        Route::get('/{order}/success', [PaymentController::class, 'paymentSuccess'])->name('success');
        Route::get('/{order}/failed', [PaymentController::class, 'paymentFailed'])->name('failed');
        Route::post('/{order}/retry', [PaymentController::class, 'retryPayment'])->name('retry');
        Route::post('/{order}/refund', [PaymentController::class, 'requestRefund'])->name('refund');
        Route::get('/history', [PaymentController::class, 'paymentHistory'])->name('history');
    });
    
    // Payment Callback (No Auth Required for Webhook)
    Route::post('/payment-callback', [PaymentController::class, 'paymentCallback'])->name('payment.callback');
});

// Tailor Specific Routes
Route::middleware(['auth', 'role:tailor'])->prefix('tailor')->name('tailor.')->group(function () {
    // Tailor Dashboard
    Route::get('/dashboard', [TailorDashboardController::class, 'index'])->name('dashboard');
    
    // Order Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [TailorDashboardController::class, 'orders'])->name('index');
        Route::get('/{order}', [TailorDashboardController::class, 'orderDetail'])->name('show');
        Route::put('/{order}/status', [TailorDashboardController::class, 'updateOrderStatus'])->name('update-status');
        Route::put('/{order}/assignment', [TailorDashboardController::class, 'updateAssignment'])->name('update-assignment');
        Route::post('/{order}/notes', [TailorDashboardController::class, 'addNotes'])->name('add-notes');
        Route::get('/{order}/measurements', [TailorDashboardController::class, 'getMeasurements'])->name('measurements');
    });
    
    // Assignment Management
    Route::prefix('assignments')->name('assignments.')->group(function () {
        Route::get('/', [TailorDashboardController::class, 'assignments'])->name('index');
        Route::get('/{assignment}', [TailorDashboardController::class, 'assignmentDetail'])->name('show');
        Route::put('/{assignment}/status', [TailorDashboardController::class, 'updateAssignmentStatus'])->name('update-status');
        Route::put('/{assignment}/progress', [TailorDashboardController::class, 'updateProgress'])->name('update-progress');
        Route::post('/{assignment}/work-step', [TailorDashboardController::class, 'addWorkStep'])->name('add-work-step');
        Route::put('/{assignment}/work-step/{stepIndex}', [TailorDashboardController::class, 'completeWorkStep'])->name('complete-work-step');
    });
    
    // Work Management
    Route::prefix('work')->name('work.')->group(function () {
        Route::get('/queue', [TailorDashboardController::class, 'workQueue'])->name('queue');
        Route::get('/in-progress', [TailorDashboardController::class, 'workInProgress'])->name('in-progress');
        Route::get('/completed', [TailorDashboardController::class, 'workCompleted'])->name('completed');
        Route::get('/overdue', [TailorDashboardController::class, 'workOverdue'])->name('overdue');
    });
    
    // Quality Control
    Route::prefix('quality')->name('quality.')->group(function () {
        Route::get('/check', [TailorDashboardController::class, 'qualityCheck'])->name('check');
        Route::put('/{assignment}/approve', [TailorDashboardController::class, 'approveQuality'])->name('approve');
        Route::put('/{assignment}/reject', [TailorDashboardController::class, 'rejectQuality'])->name('reject');
        Route::post('/{assignment}/notes', [TailorDashboardController::class, 'addQualityNotes'])->name('notes');
    });
    
    // Tailor Profile & Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', [TailorDashboardController::class, 'profileSettings'])->name('profile');
        Route::put('/profile', [TailorDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::get('/availability', [TailorDashboardController::class, 'availabilitySettings'])->name('availability');
        Route::put('/availability', [TailorDashboardController::class, 'updateAvailability'])->name('availability.update');
        Route::get('/pricing', [TailorDashboardController::class, 'pricingSettings'])->name('pricing');
        Route::put('/pricing', [TailorDashboardController::class, 'updatePricing'])->name('pricing.update');
    });
    
    // Reports & Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [TailorDashboardController::class, 'reports'])->name('index');
        Route::get('/earnings', [TailorDashboardController::class, 'earningsReport'])->name('earnings');
        Route::get('/performance', [TailorDashboardController::class, 'performanceReport'])->name('performance');
        Route::get('/orders', [TailorDashboardController::class, 'ordersReport'])->name('orders');
        Route::get('/export/{type}', [TailorDashboardController::class, 'exportReport'])->name('export');
    });
});

// Fabric Seller Specific Routes
Route::middleware(['auth', 'role:fabric_seller'])->prefix('seller')->name('seller.')->group(function () {
    // Seller Dashboard
    Route::get('/dashboard', [FabricSellerController::class, 'index'])->name('dashboard');
    
    // Fabric Management
    Route::prefix('fabrics')->name('fabrics.')->group(function () {
        Route::get('/', [FabricSellerController::class, 'fabrics'])->name('index');
        Route::get('/create', [FabricSellerController::class, 'createFabric'])->name('create');
        Route::post('/', [FabricSellerController::class, 'storeFabric'])->name('store');
        Route::get('/{fabric}', [FabricSellerController::class, 'showFabric'])->name('show');
        Route::get('/{fabric}/edit', [FabricSellerController::class, 'editFabric'])->name('edit');
        Route::put('/{fabric}', [FabricSellerController::class, 'updateFabric'])->name('update');
        Route::delete('/{fabric}', [FabricSellerController::class, 'destroyFabric'])->name('destroy');
        Route::post('/{fabric}/toggle-status', [FabricSellerController::class, 'toggleFabricStatus'])->name('toggle-status');
        Route::post('/{fabric}/feature', [FabricSellerController::class, 'toggleFeatured'])->name('toggle-featured');
        Route::post('/{fabric}/stock', [FabricSellerController::class, 'updateStock'])->name('update-stock');
        Route::post('/{fabric}/images', [FabricSellerController::class, 'updateImages'])->name('update-images');
        Route::get('/{fabric}/analytics', [FabricSellerController::class, 'fabricAnalytics'])->name('analytics');
    });
    
    // Order Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [FabricSellerController::class, 'orders'])->name('index');
        Route::get('/{orderFabric}', [FabricSellerController::class, 'orderDetail'])->name('show');
        Route::put('/{orderFabric}/status', [FabricSellerController::class, 'updateOrderStatus'])->name('update-status');
        Route::post('/{orderFabric}/ship', [FabricSellerController::class, 'shipOrder'])->name('ship');
        Route::post('/{orderFabric}/tracking', [FabricSellerController::class, 'updateTracking'])->name('update-tracking');
        Route::post('/{orderFabric}/notes', [FabricSellerController::class, 'addOrderNotes'])->name('add-notes');
    });
    
    // Inventory Management
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [FabricSellerController::class, 'inventory'])->name('index');
        Route::get('/low-stock', [FabricSellerController::class, 'lowStock'])->name('low-stock');
        Route::get('/out-of-stock', [FabricSellerController::class, 'outOfStock'])->name('out-of-stock');
        Route::post('/bulk-update', [FabricSellerController::class, 'bulkUpdateStock'])->name('bulk-update');
        Route::get('/categories', [FabricSellerController::class, 'inventoryCategories'])->name('categories');
    });
    
    // Customer Management
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [FabricSellerController::class, 'customers'])->name('index');
        Route::get('/{customer}', [FabricSellerController::class, 'customerDetail'])->name('show');
        Route::get('/{customer}/orders', [FabricSellerController::class, 'customerOrders'])->name('orders');
    });
    
    // Seller Profile & Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', [FabricSellerController::class, 'profileSettings'])->name('profile');
        Route::put('/profile', [FabricSellerController::class, 'updateProfile'])->name('profile.update');
        Route::get('/business', [FabricSellerController::class, 'businessSettings'])->name('business');
        Route::put('/business', [FabricSellerController::class, 'updateBusiness'])->name('business.update');
        Route::get('/shipping', [FabricSellerController::class, 'shippingSettings'])->name('shipping');
        Route::put('/shipping', [FabricSellerController::class, 'updateShipping'])->name('shipping.update');
    });
    
    // Reports & Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [FabricSellerController::class, 'reports'])->name('index');
        Route::get('/sales', [FabricSellerController::class, 'salesReport'])->name('sales');
        Route::get('/inventory', [FabricSellerController::class, 'inventoryReport'])->name('inventory');
        Route::get('/customers', [FabricSellerController::class, 'customersReport'])->name('customers');
        Route::get('/performance', [FabricSellerController::class, 'performanceReport'])->name('performance');
        Route::get('/export/{type}', [FabricSellerController::class, 'exportReport'])->name('export');
    });
});

// Admin Routes (if needed in future)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    
    // User Management
    Route::get('/users', function () {
        return view('admin.users.index');
    })->name('users.index');
    
    // System Settings
    Route::get('/settings', function () {
        return view('admin.settings');
    })->name('settings');
});

// Fallback Route for SPA (if using Vue/React)
Route::get('/{any}', function () {
    return view('layouts.app');
})->where('any', '.*');
