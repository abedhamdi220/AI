<?php

namespace App\Http\Controllers\Customer;

use App\Events\CouponUsedOnOrder;
use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\CreateOrderRequest;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Design;
use App\Models\Notification;
use App\Models\Order;
use App\Models\ShowcaseDesign;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    private function saveBase64Image($base64String, $folder)
    {
        if (!$base64String) return null;
        try {
            $imageParts = explode(';base64,', $base64String);
            $image_base64 = base64_decode($imageParts[1] ?? $imageParts[0]);
            $fileName = Str::uuid() . '.png';
            $filePath = $folder . '/' . $fileName;
            Storage::disk('public')->put($filePath, $image_base64);
            return $filePath;
        } catch (Exception $e) {
            Log::error('Image Save Error: ' . $e->getMessage());
            return null;
        }
    }

    public function store(CreateOrderRequest $request)
    {
        try {
            // التحقق (بما فيه الحد الأقصى الفعلي لحجم design_image_base64) بيتم
            // الآن عبر CreateOrderRequest بدل التحقق اليدوي المكرر بدون حدود حجم.
            $validated = $request->validated();

            $user = $request->user();
            $imagePath = null;
            // تُستخدم فقط عندما لا يوجد ملف فعلي محفوظ على السيرفر (تصاميم/معرض قديمة كانت مخزنة Base64 فقط)
            $imageBase64Fallback = null;
            $prompt = $validated['prompt'] ?? null;
            $clothingTypeInput = $validated['clothing_type'] ?? null;
            $colorInput = $validated['color'] ?? null;
            $showcaseDesignId = $validated['showcase_design_id'] ?? null;

            // 0. طلب نفس تصميم جاهز من المعرض (Showcase) مباشرة بدون توليد AI جديد
            if (!empty($showcaseDesignId)) {
                $showcaseDesign = ShowcaseDesign::find($showcaseDesignId);

                if (!$showcaseDesign) {
                    return response()->json(['detail' => 'عذراً، هذا التصميم لم يعد متاحاً.'], 400);
                }

                if ($showcaseDesign->image_path) {
                    $imagePath = $showcaseDesign->image_path;
                } elseif ($showcaseDesign->design && $showcaseDesign->design->image_path) {
                    $imagePath = $showcaseDesign->design->image_path;
                } elseif ($showcaseDesign->image_url) {
                    // توافق رجعي: صورة قديمة مخزّنة كـ Base64 فقط (سيتم حفظها في design_image_base64 بالأسفل)
                    $imageBase64Fallback = $showcaseDesign->image_url;
                } else {
                    return response()->json(['detail' => 'تعذر العثور على صورة هذا التصميم.'], 400);
                }

                $prompt = $prompt ?: $showcaseDesign->prompt;
                $clothingTypeInput = $clothingTypeInput ?: $showcaseDesign->clothing_type;
                $colorInput = $colorInput ?: $showcaseDesign->color;
            }
            // 1. معالجة الصورة بذكاء (منع تكرار التخزين)
            elseif (!empty($validated['design_id'])) {
                $design = Design::find($validated['design_id']);
                if ($design) {
                    $imagePath = $design->image_path;
                    if (!$imagePath && $design->image_url) {
                        // توافق رجعي: تصميم قديم مخزّن Base64 فقط بدون ملف فعلي
                        $imageBase64Fallback = $design->image_url;
                    }
                } else {
                    return response()->json(['detail' => 'رقم التصميم غير صالح.'], 400);
                }
            } else {
                $imagePath = $this->saveBase64Image($validated['design_image_base64'], 'orders');
            }

            if (!$prompt) {
                return response()->json(['detail' => 'تعذر تحديد وصف التصميم.'], 400);
            }

            // 2. منطق التسعير (أسعار افتراضية كمثال، يمكنك تعديلها)
            $basePrices = [
                't-shirt' => 0,
                'hoodie' => 0,
                'sweatshirt' => 0,
            ];

            $clothingType = strtolower($clothingTypeInput ?? 't-shirt');
            $originalPrice = $basePrices[$clothingType] ?? 150;
            $finalPrice = $originalPrice;
            $appliedCoupon = null;

            // 3. التحقق الأمني الكامل من الكوبون في الـ Backend
            if (!empty($validated['coupon_code'])) {
                $coupon = Coupon::where('code', strtoupper($validated['coupon_code']))->first();

                if ($coupon) {
                    $isValid = true;
                    $errorMessage = '';

                    if (!$coupon->is_active) {
                        $isValid = false;
                        $errorMessage = 'الكوبون غير فعال.';
                    } elseif (now()->gt($coupon->expiry_date)) {
                        $isValid = false;
                        $errorMessage = 'لقد انتهت صلاحية هذا الكوبون.';
                    } elseif ($coupon->max_uses && $coupon->current_uses >= $coupon->max_uses) {
                        $isValid = false;
                        $errorMessage = 'تم الوصول للحد الأقصى لاستخدام الكوبون.';
                    } elseif ($coupon->min_purchase && $originalPrice < $coupon->min_purchase) {
                        $isValid = false;
                        $errorMessage = "الحد الأدنى لاستخدام الكوبون هو {$coupon->min_purchase}.";
                    }

                    if (!$isValid) {
                        return response()->json(['detail' => $errorMessage], 400);
                    }

                    // تطبيق الخصم
                    $discountAmount = ($originalPrice * $coupon->discount_percentage) / 100;
                    $finalPrice = $originalPrice - $discountAmount;
                    $appliedCoupon = $coupon;
                } else {
                    return response()->json(['detail' => 'كود الخصم غير صحيح.'], 400);
                }
            }

            // 4. حفظ الطلب
            $order = Order::create([
                'user_id' => $user->id,
                'design_id' => $validated['design_id'] ?? null,
                'showcase_design_id' => $showcaseDesignId,
                'design_image_path' => $imagePath,
                'design_image_base64' => $imageBase64Fallback,
                'prompt' => $prompt,
                'phone_number' => $validated['phone_number'],
                'size' => $validated['size'],
                'color' => $colorInput ?? 'white',
                'clothing_type' => $clothingType,
                'price' => $originalPrice,
                'final_price' => $finalPrice,
                'status' => 'pending'
            ]);

            // 5. تسجيل استخدام الكوبون بشكل صحيح وآمن
            if ($appliedCoupon) {
                $appliedCoupon->increment('current_uses');

                CouponUsage::create([
                    'coupon_id' => $appliedCoupon->id ?? $appliedCoupon->_id,
                    'user_id' => $user->id,
                    'order_id' => $order->id ?? $order->_id,
                ]);

                event(new CouponUsedOnOrder($appliedCoupon, $order));
            }

            // إطلاق حدث نجاح الطلب
            event(new OrderPlaced($order));

            return response()->json([
                'message' => 'تم إنشاء الطلب بنجاح',
                'order' => [
                    'id' => $order->id ?? $order->_id,
                    'price' => $originalPrice,
                    'final_price' => $finalPrice,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toISOString(),
                ]
            ], 201);

        } catch (Exception $e) {
            Log::error('Create Order Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());
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
                    'id' => $order->id ?? $order->_id,
                    'design_id' => $order->design_id,
                    'image_url' => $order->design_image_url,
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
            return response()->json(['detail' => 'عذراً، تعذر جلب قائمة طلباتك في الوقت الحالي.'], 500);
        }
    }
}
