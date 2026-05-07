<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        try {
            // 👉 استخدام ميزة Validation للتحقق من البيانات وتنظيمها
            $validated = $request->validate([
                'design_image_base64' => 'required|string',
                'prompt' => 'required|string',
                'phone_number' => 'required|string',
                'size' => 'nullable|string',
                'design_id' => 'nullable|string',
                'color' => 'nullable|string',
                'coupon_code' => 'nullable|string' // 👉 إضافة حقل الكوبون كما طُلب
            ]);

            $orderId = (string) Str::uuid();

            $order = Order::create([
                'id' => $orderId,
                'user_id' => $request->user()->id,
                'design_id' => $validated['design_id'] ?? null,
                'design_image_base64' => $validated['design_image_base64'],
                'prompt' => $validated['prompt'],
                'phone_number' => $validated['phone_number'],
                'size' => $validated['size'] ?? 'M',
                'color' => $validated['color'] ?? '',
                'price' => 0,
                'discount' => 0,
                'final_price' => 0,
                'status' => 'pending',
                'coupon_code' => $validated['coupon_code'] ?? null,
            ]);

            // 👉 معالجة الكوبون وتسجيل استخدامه إن وجد
            if (!empty($validated['coupon_code'])) {
                $coupon = Coupon::where('code', strtoupper($validated['coupon_code']))->first();
                if ($coupon) {
                    CouponUsage::create([
                        'id' => (string) Str::uuid(),
                        'coupon_id' => $coupon->id,
                        'coupon_code' => $coupon->code,
                        'user_id' => $request->user()->id,
                        'order_id' => $orderId,
                    ]);
                    $coupon->increment('current_uses');
                }
            }

            // 👉 استدعاء Notification مباشرة بدون class_exists
            Notification::create([
                'user_id' => $request->user()->id,
                'title' => 'تم إرسال طلبك بنجاح! 🎉',
                'message' => 'سيتم التواصل معك قريباً لتأكيد الطلب والتفاصيل.',
                'type' => 'success'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الطلب بنجاح',
                'order_id' => $orderId,
                'order' => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'phone_number' => $order->phone_number,
                    'size' => $order->size,
                    'created_at' => $order->created_at->toISOString(),
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json(['detail' => 'خطأ في إرسال الطلب: ' . $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $orders = Order::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $response = $orders->map(function($order) {
                return [
                    'id' => $order->id,
                    'design_id' => $order->design_id,
                    'design_image_base64' => $order->design_image_base64,
                    'prompt' => $order->prompt,
                    'phone_number' => $order->phone_number,
                    'size' => $order->size,
                    'color' => $order->color,
                    'price' => $order->price,
                    'final_price' => $order->final_price,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toISOString()
                ];
            });

            return response()->json($response);

        } catch (Exception $e) {
            return response()->json(['detail' => 'خطأ في جلب الطلبات'], 500);
        }
    }
}
