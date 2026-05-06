<?php

use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminDesignController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminShowcaseController;
use App\Http\Controllers\Admin\AdminStatsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Customer\AuthController;
use App\Http\Controllers\Customer\CouponController;
use App\Http\Controllers\Customer\DesignController;
use App\Http\Controllers\Customer\OAuthController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ShowcaseController;
use App\Http\Controllers\Customer\UserController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;



/*
|--------------------------------------------------------------------------
| المسارات العامة (Public Routes)
|--------------------------------------------------------------------------
*/

// Auth (from auth.js)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth_strict');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth_strict');
});

// OAuth (from oauth.js)
Route::post('/oauth/google', [OAuthController::class, 'google']);
Route::post('/auth/v1/env/oauth', [OAuthController::class, 'google'])->withoutMiddleware([VerifyCsrfToken::class]);
// Public Designs (from designs.js)
Route::get('/designs/showcase', [ShowcaseController::class, 'index']);

// Health Check
// Route::get('/ping', function () {
//     return response()->json(['status' => 'ok']);
// });


/*
|--------------------------------------------------------------------------
| المسارات المحمية للمستخدمين (User Routes)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    // Auth Protected (from auth.js)
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']); // خاص بـ Laravel
        Route::post('/refresh', [AuthController::class, 'refresh']); // خاص بـ Laravel
        Route::post('/v1/env/oauth', [OAuthController::class, 'google']);
    });

    // User Data (from user.js)
    Route::prefix('user')->group(function () {
        Route::get('/designs', [UserController::class, 'designs']);
        Route::get('/designs-quota', [UserController::class, 'designsQuota']);
    });

    // Designs (from designs.js)
    Route::prefix('designs')->group(function () {
        Route::post('/enhance-prompt', [DesignController::class, 'enhancePrompt']);
        Route::post('/preview', [DesignController::class, 'preview']);
        Route::post('/save', [DesignController::class, 'save']);
        Route::get('/', [DesignController::class, 'index']);
        Route::put('/{id}/favorite', [DesignController::class, 'toggleFavorite']);
        Route::delete('/{id}', [DesignController::class, 'destroy']);
    });

    // Orders (from orders.js)
    Route::prefix('orders')->group(function () {
        Route::post('/create', [OrderController::class, 'store']);
        Route::get('/my-orders', [OrderController::class, 'index']);
    });

    // Notifications (from notifications.js)
    Route::prefix('notifications')->group(function () {
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']); // يجب أن يكون قبل {id}
        Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead']); // يجب أن يكون قبل {id}
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{id}/read', [NotificationController::class, 'read']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Coupons Validation (from coupons.js)
    Route::post('/coupons/validate', [CouponController::class, 'validateCoupon']);
});


/*
|--------------------------------------------------------------------------
| مسارات الكوبونات للإدارة (Admin Coupons CRUD - from coupons.js)
| ملاحظة: في Node.js تم استخدام مسار /api/coupons بدلاً من /api/admin/coupons
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'is_admin'])->prefix('coupons')->group(function () {
    Route::get('/', [AdminCouponController::class, 'index']);
    Route::post('/', [AdminCouponController::class, 'store']);
    Route::put('/{id}', [AdminCouponController::class, 'update']);
    Route::delete('/{id}', [AdminCouponController::class, 'destroy']);
});


/*
|--------------------------------------------------------------------------
| مسارات لوحة التحكم للمديرين (Admin API Routes - from admin.js)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'is_admin'])->prefix('admin')->group(function () {

    // 1. الإحصائيات
    Route::get('/stats', [AdminStatsController::class, 'stats']);

    // 2. إدارة الطلبات
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

    // 3. إدارة المستخدمين
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::put('/users/{id}/designs-limit', [AdminUserController::class, 'update']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

    // 4. إدارة التصاميم
    Route::get('/designs', [AdminDesignController::class, 'index']);
    Route::delete('/designs/{id}', [AdminDesignController::class, 'destroy']);

    // 5. تصاميم العرض (Showcase)
    Route::get('/showcase-designs', [AdminShowcaseController::class, 'index']);
    Route::post('/showcase-designs', [AdminShowcaseController::class, 'store']);
    Route::put('/showcase-designs/{id}', [AdminShowcaseController::class, 'update']);
    Route::delete('/showcase-designs/{id}', [AdminShowcaseController::class, 'destroy']);
    Route::put('/showcase-designs/{id}/toggle-featured', [AdminShowcaseController::class, 'toggleFeatured']);

    // 6. إحصائيات واستخدام الكوبونات (من admin.js)
    Route::get('/coupons/{id}/usage', [AdminCouponController::class, 'usage']);
    Route::get('/coupons-stats', [AdminCouponController::class, 'stats']);
});
