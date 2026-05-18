<?php

namespace App\Http\Controllers\Customer;

use App\Events\CouponUsedOnOrder;
use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Notification;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'design_image_base64' => 'required|string',
                'prompt' => 'required|string',
                'phone_number' => 'required|string',
                'size' => 'nullable|string',
                'design_id' => 'nullable|string',
                'color' => 'nullable|string',
                'coupon_code' => 'nullable|string'
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

              event(new OrderPlaced($order));

            // التحقق مما إذا كان هناك كوبون مستخدم في الطلب لإطلاق حدث الكوبون
            if (!empty($validated['coupon_code'])) {
                event(new CouponUsedOnOrder($order, $validated['coupon_code']));
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال طلبك بنجاح، شكراً لثقتك بنا.',
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
            Log::error('Create Order Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، حدث خطأ أثناء إرسال الطلب. يرجى التأكد من البيانات والمحاولة مرة أخرى.'], 500);
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
            Log::error('Fetch Orders Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر جلب قائمة طلباتك في الوقت الحالي. يرجى إعادة تحميل الصفحة.'], 500);
        }
    }
}
