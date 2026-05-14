<?php

use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminDesignController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminShowcaseController;
use App\Http\Controllers\Admin\AdminStatsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Customer\AuthController;
use App\Http\Controllers\Customer\CouponController;
use App\Http\Controllers\Customer\DesignController;
use App\Http\Controllers\Customer\OAuthController;
use App\Http\Controllers\Customer\OptionsController; // تم إضافة الكنترولر الجديد
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ShowcaseController;
use App\Http\Controllers\Customer\UserController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| المسارات العامة (Public Routes)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth_strict');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth_strict');
});

Route::post('/oauth/google', [OAuthController::class, 'google']);
Route::post('/auth/v1/env/oauth', [OAuthController::class, 'google']);

// مسار للتحقق من هوية المستخدم النشط وجلب بياناته (مهم جداً للـ App.js)
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/designs/showcase', [ShowcaseController::class, 'index']);

// 👉 مسارات الخيارات الديناميكية المضافة حديثاً لتجنب 404
Route::prefix('options')->group(function () {
    Route::get('/clothing-types', [OptionsController::class, 'clothingTypes']);
    Route::get('/view-angles', [OptionsController::class, 'viewAngles']);
    Route::get('/logo-positions', [OptionsController::class, 'logoPositions']);
});

/*
|--------------------------------------------------------------------------
| المسارات المحمية للمستخدمين (User Routes)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/v1/env/oauth', [OAuthController::class, 'google']);
    });

    Route::prefix('user')->group(function () {
        Route::get('/designs', [UserController::class, 'designs']);
        Route::get('/designs-quota', [UserController::class, 'designsQuota']);

        // 👉 مسارات المقاسات (تم إضافة GET و PUT لتعمل بالشكل الصحيح)
        Route::get('/measurements', [UserController::class, 'getMeasurements']);
        Route::put('/measurements', [UserController::class, 'saveMeasurements']);
    });

    Route::prefix('designs')->group(function () {
        Route::post('/enhance-prompt', [DesignController::class, 'enhancePrompt']);
        Route::post('/preview', [DesignController::class, 'preview']);
        Route::post('/save', [DesignController::class, 'save']);
        Route::get('/', [DesignController::class, 'index']);
        Route::put('/{id}/favorite', [DesignController::class, 'toggleFavorite']);
        Route::delete('/{id}', [DesignController::class, 'destroy']);
        Route::get('/size-chart', [DesignController::class, 'sizeChart']);
    });

    // 👉 تم التأكد من أن المسار هنا مطابق لاستدعاء الواجهة
    Route::prefix('orders')->group(function () {
        Route::post('/create', [OrderController::class, 'store']);
        Route::get('/my-orders', [OrderController::class, 'index']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{id}/read', [NotificationController::class, 'read']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    Route::prefix('coupons')->group(function () {
        Route::get('/', [CouponController::class, 'index']);
        Route::post('/validate', [CouponController::class, 'validateCoupon']);
    });
});

/*
|--------------------------------------------------------------------------
| مسارات الكوبونات للإدارة (Admin Coupons CRUD)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'is_admin'])->prefix('admin/coupons')->group(function () {
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
    Route::get('/stats', [AdminStatsController::class, 'stats']);
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::put('/users/{id}/designs-limit', [AdminUserController::class, 'update']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);
    Route::get('/designs', [AdminDesignController::class, 'index']);
    Route::delete('/designs/{id}', [AdminDesignController::class, 'destroy']);
    Route::get('/showcase-designs', [AdminShowcaseController::class, 'index']);
    Route::post('/showcase-designs', [AdminShowcaseController::class, 'store']);
    Route::put('/showcase-designs/{id}', [AdminShowcaseController::class, 'update']);
    Route::delete('/showcase-designs/{id}', [AdminShowcaseController::class, 'destroy']);
    Route::put('/showcase-designs/{id}/toggle-featured', [AdminShowcaseController::class, 'toggleFeatured']);
    Route::get('/coupons/{id}/usage', [AdminCouponController::class, 'usage']);
    Route::get('/coupons-stats', [AdminCouponController::class, 'stats']);
});
