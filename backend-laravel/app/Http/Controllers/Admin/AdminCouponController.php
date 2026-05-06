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
                return response()->json(['detail' => 'الكوبون غير موجود'], 404);
            }

            $usages = CouponUsage::where('coupon_id', $id)->orderBy('used_at', 'desc')->get();

            $usageDetails = $usages->map(function ($usage) {
                $user = User::where('id', $usage->user_id)->select('username', 'email')->first();
                $order = $usage->order_id ? Order::where('id', $usage->order_id)->select('final_price')->first() : null;

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

            return response()->json([
                'coupon_id' => $id,
                'coupon_code' => $coupon->code,
                'discount_percentage' => $coupon->discount_percentage,
                'total_uses' => $usages->count(),
                'max_uses' => $coupon->max_uses,
                'usages' => $usageDetails,
            ]);
        } catch (\Exception $error) {
            \Log::error('Get Coupon Usage Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في جلب إحصائيات الكوبون'], 500);
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
                    'created_at' => $coupon->created_at ? $coupon->created_at->toIso8601String() : null,
                ];
            });

            return response()->json($couponsWithStats);
        } catch (\Exception $error) {
            \Log::error('Get Coupons Stats Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في جلب إحصائيات الكوبونات'], 500);
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
                    'created_at' => $c->created_at ? $c->created_at->toIso8601String() : null,
                ];
            });

            return response()->json($response);
        } catch (\Exception $error) {
            \Log::error('Get Coupons Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في جلب الكوبونات'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $code = $request->input('code');
            $discountPercentage = $request->input('discount_percentage');
            $expiryDate = $request->input('expiry_date');
            $maxUses = $request->input('max_uses');

            if (!$code || !$discountPercentage) {
                return response()->json(['detail' => 'يرجى إدخال كود الكوبون ونسبة الخصم'], 400);
            }

            $existingCoupon = Coupon::where('code', strtoupper($code))->first();
            if ($existingCoupon) {
                return response()->json(['detail' => 'كود الكوبون موجود بالفعل'], 400);
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
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'تم إنشاء الكوبون بنجاح',
                'id' => $coupon->id,
            ], 201);
        } catch (\Exception $error) {
            \Log::error('Create Coupon Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في إنشاء الكوبون: ' . $error->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $coupon = Coupon::where('id', $id)->first();

            if (!$coupon) {
                return response()->json(['detail' => 'الكوبون غير موجود'], 404);
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

            $coupon->save();

            return response()->json(['message' => 'تم تحديث الكوبون بنجاح']);
        } catch (\Exception $error) {
            \Log::error('Update Coupon Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في تحديث الكوبون: ' . $error->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $result = Coupon::where('id', $id)->delete();

            if ($result === 0) {
                return response()->json(['detail' => 'الكوبون غير موجود'], 404);
            }

            return response()->json(['message' => 'تم حذف الكوبون بنجاح']);
        } catch (\Exception $error) {
            \Log::error('Delete Coupon Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في حذف الكوبون'], 500);
        }
    }
}
