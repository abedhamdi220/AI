<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Exception;
use Illuminate\Http\Request;

class CouponController extends Controller
{
      public function index()
    {
        try {
            $coupons = Coupon::where('is_active', true)
                ->where('expiry_date', '>', now())
                ->where(function ($query) {
                    $query->whereNull('max_uses')
                          ->orWhereRaw('current_uses < max_uses');
                })
                ->select('code', 'discount_percentage', 'expiry_date')
                ->get();

            return response()->json($coupons);
        } catch (\Exception $error) {
            \Log::error('Fetch Coupons Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في جلب الكوبونات'], 500);
        }
    }
 public function validateCoupon(Request $request)
    {
        try {
            $code = $request->input('code');

            if (!$code) {
                return response()->json(['detail' => 'يرجى إدخال كود الكوبون'], 400);
            }

            $coupon = Coupon::where('code', strtoupper($code))->first();

            if (!$coupon) {
                return response()->json(['detail' => 'كود الكوبون غير صحيح'], 404);
            }


            if (!$coupon->is_active) {
                return response()->json(['detail' => 'الكوبون غير فعال'], 400);
            }


            if (now()->gt($coupon->expiry_date)) {
                return response()->json(['detail' => 'الكوبون منتهي الصلاحية'], 400);
            }


            if ($coupon->max_uses && $coupon->current_uses >= $coupon->max_uses) {
                return response()->json(['detail' => 'تم استخدام الكوبون بالكامل'], 400);
            }

            return response()->json([
                'valid' => true,
                'discount_percentage' => $coupon->discount_percentage,
                'message' => "خصم {$coupon->discount_percentage}٪",
            ]);
        } catch (\Exception $error) {
            \Log::error('Validate Coupon Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في التحقق من الكوبون'], 500);
        }
    }
}
