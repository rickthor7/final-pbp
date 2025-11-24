<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            // Custom API v1 routes with versioning
            Route::prefix('api/v1')
                ->middleware('api')
                ->namespace($this->namespace . '\\Api')
                ->group(base_path('routes/api.php'));
        });

        // Custom route model bindings
        Route::model('design', \App\Models\CustomDesign::class);
        Route::model('order', \App\Models\Order::class);
        Route::model('fabric', \App\Models\Fabric::class);
        Route::model('measurement', \App\Models\BodyMeasurement::class);
        Route::model('tailor', \App\Models\User::class);
        Route::model('assignment', \App\Models\TailorAssignment::class);
        
        // Pattern constraints for IDs
        Route::pattern('id', '[0-9]+');
        Route::pattern('design', '[0-9]+');
        Route::pattern('order', '[0-9]+');
        Route::pattern('fabric', '[0-9]+');
        Route::pattern('measurement', '[0-9]+');
        Route::pattern('tailor', '[0-9]+');
        Route::pattern('assignment', '[0-9]+');
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('designs', function (Request $request) {
            return Limit::perMinute(30)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(20)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
