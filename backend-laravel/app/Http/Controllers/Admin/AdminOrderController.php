<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index()
    {
        try {
            $orders = Order::orderBy('created_at', 'desc')->get();

            $ordersWithUsers = $orders->map(function ($order) {
                $user = User::where('id', $order->user_id)->first();

                return [
                    'id' => $order->id,
                    'user_id' => $order->user_id,
                    'user_name' => $user->username ?? 'Unknown',
                    'user_email' => $user->email ?? 'Unknown',
                    'design_id' => $order->design_id,
                    'design_image_base64' => $order->design_image_base64,
                    'prompt' => $order->prompt,
                    'phone_number' => $order->phone_number,
                    'size' => $order->size,
                    'color' => $order->color,
                    'price' => $order->price,
                    'discount' => $order->discount,
                    'final_price' => $order->final_price,
                    'status' => $order->status,
                    'created_at' => $order->created_at ? $order->created_at->toIso8601String() : null,
                ];
            });

            return response()->json($ordersWithUsers);
        } catch (\Exception $error) {
            \Log::error('Get Orders Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في جلب الطلبات'], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $status = $request->input('status');

            if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
                return response()->json(['detail' => 'حالة غير صالحة'], 400);
            }

            $order = Order::where('id', $id)->first();

            if (!$order) {
                return response()->json(['detail' => 'الطلب غير موجود'], 404);
            }

            $order->status = $status;
            $order->save();

            return response()->json([
                'message' => 'تم تحديث حالة الطلب بنجاح',
                'status' => $status
            ]);
        } catch (\Exception $error) {
            \Log::error('Update Order Status Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في تحديث حالة الطلب'], 500);
        }
    }
}
