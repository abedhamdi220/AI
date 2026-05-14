<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    public function index()
    {
        try {
            // 1. جلب الكوبونات الفعالة والتي لم تنتهِ صلاحيتها
            $coupons = Coupon::where('is_active', true)
                ->where('expiry_date', '>', now())
                ->get();

            // 2. الفلترة البرمجية المضمونة مع MongoDB
            $validCoupons = $coupons->filter(function ($coupon) {
                return is_null($coupon->max_uses) ||
                       $coupon->max_uses == 0 ||
                       $coupon->current_uses < $coupon->max_uses;
            })->map(function ($coupon) {
                // 3. تحديد الحقول الراجعة للواجهة فقط
                return [
                    'code' => $coupon->code,
                    'discount_percentage' => $coupon->discount_percentage,
                    'expiry_date' => $coupon->expiry_date,
                    'description' => $coupon->description,
                    'min_purchase' => $coupon->min_purchase,
                ];
            })->values();

            return response()->json($validCoupons);

        } catch (\Exception $error) {
            Log::error('Fetch Coupons Error: ' . $error->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر تحميل قائمة الكوبونات في الوقت الحالي. يرجى المحاولة لاحقاً.'], 500);
        }
    }

    public function validateCoupon(Request $request)
    {
        try {
            $code = $request->input('code');
            $totalAmount = $request->input('total_amount'); 

            if (!$code) {
                return response()->json(['detail' => 'يرجى إدخال كود الكوبون للتحقق منه.'], 400);
            }

            $coupon = Coupon::where('code', strtoupper($code))->first();

            if (!$coupon) {
                return response()->json(['detail' => 'كود الكوبون المدخل غير صحيح، يرجى التأكد منه.'], 404);
            }

            if (!$coupon->is_active) {
                return response()->json(['detail' => 'عذراً، هذا الكوبون غير فعال حالياً.'], 400);
            }

            if (now()->gt($coupon->expiry_date)) {
                return response()->json(['detail' => 'عذراً، لقد انتهت صلاحية هذا الكوبون.'], 400);
            }

            if ($coupon->max_uses && $coupon->current_uses >= $coupon->max_uses) {
                return response()->json(['detail' => 'عذراً، تم الوصول للحد الأقصى لاستخدام هذا الكوبون.'], 400);
            }


            if ($coupon->min_purchase && $totalAmount && $totalAmount < $coupon->min_purchase) {
                return response()->json(['detail' => "عذراً، الحد الأدنى لاستخدام هذا الكوبون هو {$coupon->min_purchase}"], 400);
            }

            return response()->json([
                'valid' => true,
                'discount_percentage' => $coupon->discount_percentage,
                'message' => "تم تفعيل خصم بقيمة {$coupon->discount_percentage}٪ بنجاح!",
            ]);
        } catch (\Exception $error) {
            Log::error('Validate Coupon Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ أثناء التحقق من الكوبون. يرجى المحاولة مرة أخرى.'], 500);
        }
    }
}
