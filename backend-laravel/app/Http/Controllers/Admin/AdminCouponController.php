<?php

namespace App\Http\Controllers\Admin;

use App\Events\NewCouponCreated;
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
    public function index()
    {
        try {
            // إضافة Paging هنا أيضاً لحماية الذاكرة
            $paginator = Coupon::orderBy('created_at', 'desc')->paginate(10);

            $response = $paginator->getCollection()->map(function ($coupon) {
                return [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => $coupon->discount_value,
                    'expiry_date' => $coupon->expiry_date ? Carbon::parse($coupon->expiry_date)->toIso8601String() : null,
                    'is_active' => $coupon->is_active,
                    'max_uses' => $coupon->max_uses,
                    'description' => $coupon->description,
                    'min_purchase' => $coupon->min_purchase,
                    'created_at' => $coupon->created_at ? $coupon->created_at->toIso8601String() : null,
                ];
            });

            $paginator->setCollection($response);
            return response()->json($paginator);
        } catch (\Exception $error) {
            \Log::error('Get Coupons Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء جلب قائمة الكوبونات'], 500);
        }
    }

    public function stats()
    {
        try {
            // 🚀 الحل السحري لمشكلة N+1 Query
            // استخدام withCount يجلب عدد الاستخدامات لكل كوبون باستعلام واحد فقط في قاعدة البيانات
            $coupons = Coupon::withCount('usages')->get();

            $response = $coupons->map(function ($coupon) {
                // الكود القديم الذي تم إزالته: $usageCount = CouponUsage::where('coupon_id', $coupon->id)->count();
                // الكود الجديد يستخدم الخاصية الجاهزة من withCount
                $usageCount = $coupon->usages_count ?? 0;

                return [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'discount_value' => $coupon->discount_value,
                    'discount_type' => $coupon->discount_type,
                    'is_active' => $coupon->is_active,
                    'max_uses' => $coupon->max_uses,
                    'usage_count' => $usageCount, // استخدام المتغير المجهز مسبقاً
                    'usage_percentage' => $coupon->max_uses > 0 ? round(($usageCount / $coupon->max_uses) * 100, 2) : 0,
                ];
            });

            return response()->json($response);
        } catch (\Exception $error) {
            \Log::error('Get Coupon Stats Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء جلب إحصائيات الكوبونات'], 500);
        }
    }

    public function usage($id)
    {
        try {
            $coupon = Coupon::where('id', $id)->first();

            if (!$coupon) {
                return response()->json(['detail' => 'الكوبون المطلوب غير موجود في النظام'], 404);
            }

            // Paging لمعلومات الاستخدام لأنها قد تكون بالآلاف
            $usagesPaginator = CouponUsage::where('coupon_id', $id)
                ->orderBy('used_at', 'desc')
                ->paginate(20);

            $usages = $usagesPaginator->getCollection();

            $userIds = $usages->pluck('user_id')->unique()->filter()->values()->all();
            $orderIds = $usages->pluck('order_id')->unique()->filter()->values()->all();

            $users = User::whereIn('id', $userIds)->select('id', 'username', 'email')->get()->keyBy('id');
            $orders = Order::whereIn('id', $orderIds)->select('id', 'final_price')->get()->keyBy('id');

            $usageDetails = $usages->map(function ($usage) use ($users, $orders) {
                $user = $users->get($usage->user_id);
                $order = $usage->order_id ? $orders->get($usage->order_id) : null;

                return [
                    'usage_id' => $usage->id,
                    'used_at' => $usage->used_at ? Carbon::parse($usage->used_at)->toIso8601String() : null,
                    'user' => $user ? [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                    ] : null,
                    'order' => $order ? [
                        'id' => $order->id,
                        'final_price' => $order->final_price,
                    ] : null,
                    'discount_applied' => $usage->discount_applied,
                ];
            });

            $usagesPaginator->setCollection($usageDetails);

            return response()->json([
                'coupon' => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => $coupon->discount_value,
                    'max_uses' => $coupon->max_uses,
                    'is_active' => $coupon->is_active,
                ],
                'usages' => $usagesPaginator
            ]);
        } catch (\Exception $error) {
            \Log::error('Get Coupon Usage Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء جلب تفاصيل استخدام الكوبون'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|unique:coupons',
                'discount_type' => 'required|string|in:percentage,fixed',
                'discount_value' => 'required|numeric',
                'expiry_date' => 'nullable|date',
                'max_uses' => 'nullable|integer',
                'description' => 'nullable|string',
                'min_purchase' => 'nullable|numeric'
            ]);

            $couponId = (string) Str::uuid();

            $coupon = Coupon::create([
                'id' => $couponId,
                'code' => strtoupper($validated['code']),
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'],
                'expiry_date' => isset($validated['expiry_date']) ? Carbon::parse($validated['expiry_date']) : null,
                'is_active' => true,
                'max_uses' => $validated['max_uses'] ?? null,
                'description' => $validated['description'] ?? '',
                'min_purchase' => $validated['min_purchase'] ?? 0,
            ]);

            return response()->json([
                'message' => 'تم إنشاء الكوبون بنجاح',
                'coupon' => $coupon
            ], 201);
        } catch (\Exception $error) {
            \Log::error('Create Coupon Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء إنشاء الكوبون'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $coupon = Coupon::where('id', $id)->first();

            if (!$coupon) {
                return response()->json(['detail' => 'الكوبون المراد تحديثه غير موجود'], 404);
            }

            if ($request->has('discount_type')) {
                $coupon->discount_type = $request->input('discount_type');
            }
            if ($request->has('discount_value')) {
                $coupon->discount_value = $request->input('discount_value');
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
