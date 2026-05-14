<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCouponController extends Controller
{
   public function usage($id)
{
    try {
        $coupon = Coupon::where('id', $id)->first();

        if (!$coupon) {
            return response()->json(['detail' => 'الكوبون المطلوب غير موجود في النظام'], 404);
        }

        $usages = CouponUsage::where('coupon_id', $id)->orderBy('used_at', 'desc')->get();

     
        $userIds = $usages->pluck('user_id')->unique()->filter()->values()->all();
        $orderIds = $usages->pluck('order_id')->unique()->filter()->values()->all();


        $users = User::whereIn('id', $userIds)->select('id', 'username', 'email')->get()->keyBy('id');
        $orders = Order::whereIn('id', $orderIds)->select('id', 'final_price')->get()->keyBy('id');

        // 3. بناء هيكل البيانات المرجعة
        $usageDetails = $usages->map(function ($usage) use ($users, $orders) {
            $user = $users->get($usage->user_id);
            $order = $usage->order_id ? $orders->get($usage->order_id) : null;

            return [
                'id' => $usage->id,
                'user_id' => $usage->user_id,
                'username' => $user->username ?? 'غير معروف',
                'email' => $user->email ?? 'غير معروف',
                'order_id' => $usage->order_id,
                'order_amount' => $order->final_price ?? 0,
                'used_at' => $usage->used_at ? $usage->used_at->toIso8601String() : null,
            ];
        });

        return response()->json(['data' => $usageDetails]);

    } catch (\Exception $error) {
        \Log::error('Fetch Coupon Usages Error: ' . $error->getMessage());
        return response()->json(['detail' => 'حدث خطأ داخلي أثناء جلب بيانات الاستخدام'], 500);
    }
}
    public function stats()
    {
        try {
            $coupons = Coupon::all();

            $couponsWithStats = $coupons->map(function ($coupon) {
                $usageCount = CouponUsage::where('coupon_id', $coupon->id)->count();

                return [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'discount_percentage' => $coupon->discount_percentage,
                    'expiry_date' => $coupon->expiry_date ? $coupon->expiry_date->toIso8601String() : null,
                    'is_active' => $coupon->is_active,
                    'max_uses' => $coupon->max_uses,
                    'current_uses' => $usageCount,
                    'description' => $coupon->description,
                    'min_purchase' => $coupon->min_purchase,
                    'created_at' => $coupon->created_at ? $coupon->created_at->toIso8601String() : null,
                ];
            });

            return response()->json($couponsWithStats);
        } catch (\Exception $error) {
            \Log::error('Get Coupons Stats Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء جلب إحصائيات الكوبونات'], 500);
        }
    }

    public function index()
    {
        try {
            $coupons = Coupon::orderBy('created_at', 'desc')->get();

            $response = $coupons->map(function ($c) {
                return [
                    'id' => $c->id,
                    'code' => $c->code,
                    'discount_percentage' => $c->discount_percentage,
                    'expiry_date' => $c->expiry_date ? $c->expiry_date->toIso8601String() : null,
                    'is_active' => $c->is_active,
                    'max_uses' => $c->max_uses,
                    'current_uses' => $c->current_uses,
                    'description' => $c->description,
                    'min_purchase' => $c->min_purchase,
                    'created_at' => $c->created_at ? $c->created_at->toIso8601String() : null,
                ];
            });

            return response()->json($response);
        } catch (\Exception $error) {
            \Log::error('Get Coupons Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء جلب قائمة الكوبونات'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $code = $request->input('code');
            $discountPercentage = $request->input('discount_percentage');
            $expiryDate = $request->input('expiry_date');
            $maxUses = $request->input('max_uses');
            $description = $request->input('description');
            $minPurchase = $request->input('min_purchase');

            if (!$code || !$discountPercentage) {
                return response()->json(['detail' => 'البيانات غير مكتملة: يرجى إدخال كود الكوبون ونسبة الخصم'], 400);
            }

            $existingCoupon = Coupon::where('code', strtoupper($code))->first();
            if ($existingCoupon) {
                return response()->json(['detail' => 'كود الكوبون المدخل مستخدم بالفعل، يرجى اختيار كود آخر'], 400);
            }

            $expiryDateValue = $expiryDate ? Carbon::parse($expiryDate) : now()->addYear();

            $coupon = Coupon::create([
                'id' => (string) Str::uuid(),
                'code' => strtoupper($code),
                'discount_percentage' => $discountPercentage,
                'expiry_date' => $expiryDateValue,
                'is_active' => true,
                'max_uses' => $maxUses ?: null,
                'current_uses' => 0,
                'description' => $description,
                'min_purchase' => $minPurchase ?: null,
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'تم إنشاء الكوبون بنجاح',
                'id' => $coupon->id,
            ], 201);
        } catch (\Exception $error) {
            \Log::error('Create Coupon Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء محاولة إنشاء الكوبون'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $coupon = Coupon::where('id', $id)->first();

            if (!$coupon) {
                return response()->json(['detail' => 'الكوبون المراد تحديثه غير موجود'], 404);
            }

            if ($request->has('discount_percentage')) {
                $coupon->discount_percentage = $request->input('discount_percentage');
            }
            if ($request->has('expiry_date')) {
                $coupon->expiry_date = Carbon::parse($request->input('expiry_date'));
            }
            if ($request->has('is_active')) {
                $coupon->is_active = $request->input('is_active');
            }
            if ($request->has('max_uses')) {
                $coupon->max_uses = $request->input('max_uses');
            }

            if ($request->has('description')) {
                $coupon->description = $request->input('description');
            }
            if ($request->has('min_purchase')) {
                $coupon->min_purchase = $request->input('min_purchase');
            }

            $coupon->save();

            return response()->json(['message' => 'تم تحديث بيانات الكوبون بنجاح']);
        } catch (\Exception $error) {
            \Log::error('Update Coupon Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء تحديث بيانات الكوبون'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $result = Coupon::where('id', $id)->delete();

            if ($result === 0) {
                return response()->json(['detail' => 'الكوبون المراد حذفه غير موجود'], 404);
            }

            return response()->json(['message' => 'تم حذف الكوبون بنجاح']);
        } catch (\Exception $error) {
            \Log::error('Delete Coupon Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء محاولة حذف الكوبون'], 500);
        }
    }
}
