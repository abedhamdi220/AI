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
            $data = $request->only([
                'design_image_base64', 'prompt', 'phone_number', 'size',
                'design_id', 'color', 'coupon_code'
            ]);

            if (empty($data['design_image_base64']) || empty($data['prompt']) || empty($data['phone_number'])) {
                return response()->json(['detail' => 'يرجى إدخال جميع البيانات المطلوبة'], 400);
            }

            $orderId = (string) Str::uuid();
            $order = Order::create([
                'id' => $orderId,
                'user_id' => $request->user()->id,
                'design_id' => $data['design_id'] ?? null,
                'design_image_base64' => $data['design_image_base64'],
                'prompt' => $data['prompt'],
                'phone_number' => $data['phone_number'],
                'size' => $data['size'] ?? 'M',
                'color' => $data['color'] ?? '',
                'price' => 0,
                'discount' => 0,
                'final_price' => 0,
                'status' => 'pending',
                'coupon_code' => $data['coupon_code'] ?? null,
            ]);

            if (!empty($data['coupon_code'])) {
                $coupon = Coupon::where('code', strtoupper($data['coupon_code']))->first();
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

            // إرسال الإشعار
            if(class_exists(Notification::class)) {
                Notification::create([
                    'user_id' => $request->user()->id,
                    'title' => 'تم إرسال طلبك بنجاح! 🎉',
                    'message' => 'سيتم التواصل معك قريباً لتأكيد الطلب والتفاصيل.',
                    'type' => 'success'
                ]);
            }

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
