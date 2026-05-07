<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Order;
use App\Models\User;
use App\Models\Notification;
use App\Services\DesignService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class DesignController extends Controller
{
    protected $designService;

    public function __construct(DesignService $designService)
    {
        $this->designService = $designService;
    }
 public function sizeChart()
    {
        return response()->json([
            'S' => 'Small',
            'M' => 'Medium',
            'L' => 'Large',
            'XL' => 'Extra Large',
            'XXL' => 'Double Extra Large'
        ]);
    }
    public function enhancePrompt(Request $request)
    {
        try {
            $prompt = $request->input('prompt');
            $clothing_type = $request->input('clothing_type');

            if (!$prompt) {
                return response()->json(['detail' => 'يرجى إدخال الوصف'], 400);
            }

            $clothingTypeMap = [
                'tshirt' => 'تيشيرت',
                'shirt' => 'قميص',
                'hoodie' => 'هودي',
                'dress' => 'فستان',
                'jacket' => 'جاكيت',
                'pants' => 'بنطلون'
            ];
            $arabicClothingType = $clothingTypeMap[$clothing_type] ?? $clothing_type;

            $enhancedPrompt = "{$prompt}، {$arabicClothingType} بتصميم احترافي، خامة قطنية عالية الجودة، ألوان متناسقة، مناسب للموسم الحالي، تفاصيل دقيقة ومميزة، إطلالة عصرية وأنيقة";

            return response()->json([
                'success' => true,
                'enhanced_prompt' => $enhancedPrompt,
                'original_prompt' => $prompt
            ]);
        } catch (Exception $e) {
            return response()->json(['detail' => 'خطأ في تحسين الوصف'], 500);
        }
    }

    public function preview(Request $request)
    {
        try {
            $prompt = $request->input('prompt');
            $clothing_type = $request->input('clothing_type');
            $color = $request->input('color');
            $logo_base64 = $request->input('logo_base64');
            $logo_position = $request->input('logo_position', 'center');
            $user_photo_base64 = $request->input('user_photo_base64');
            $view_angle = $request->input('view_angle', 'front');

            if (!$prompt || !$clothing_type) {
                return response()->json(['detail' => 'يرجى إدخال الوصف ونوع الملبس'], 400);
            }

            $user = User::where('id', $request->user()->id)->first();

            if (!$user->is_unlimited && $user->designs_used >= $user->designs_limit) {
                return response()->json(['detail' => 'لقد وصلت إلى الحد الأقصى من التصاميم المجانية'], 403);
            }

            $clothingTypeMap = [
                'tshirt' => 't-shirt',
                'shirt' => 'formal shirt',
                'hoodie' => 'hoodie sweatshirt',
                'dress' => 'dress',
                'jacket' => 'jacket',
                'pants' => 'pants'
            ];
            $englishClothingType = $clothingTypeMap[$clothing_type] ?? $clothing_type;

            $result = $this->designService->generateImageWithAI(
                $prompt, $englishClothingType, $color,
                compact('logo_base64', 'logo_position', 'user_photo_base64', 'view_angle')
            );

            $user->increment('designs_used');
            $user->refresh();

            $designsRemaining = $user->is_unlimited ? 999 : ($user->designs_limit - $user->designs_used);

            return response()->json([
                'success' => true,
                'image_base64' => $result['image_base64'],
                'composite_image_base64' => $result['composite_image_base64'] ?? '',
                'prompt' => $result['revised_prompt'] ?? $prompt,
                'message' => 'تم إنشاء التصميم بنجاح',
                'designs_remaining' => $designsRemaining,
                'designs_used' => $user->designs_used,
                'designs_limit' => $user->designs_limit
            ]);

        }
        catch (Exception $e)
        {

//  $status = $e->getCode() ?: 500;
//             if ($status == 400) {
//                 return response()->json(['detail' => 'الوصف غير مناسب. يرجى تعديله والمحاولة مرة أخرى.'], 400);
//             }
//             if ($status == 429) {
//                 return response()->json(['detail' => 'تم تجاوز الحد المسموح. يرجى الانتظار والمحاولة لاحقاً.'], 429);
//             }

//             // إرجاع رسالة الخطأ الفعلية بدلاً من الرسالة العامة لتسهيل تتبع المشكلة
//             return response()->json(['detail' => $e->getMessage()], 500);
//         }
            $status = $e->getCode() ?: 500;
            if ($status == 400) {
                return response()->json(['detail' => 'الوصف غير مناسب. يرجى تعديله والمحاولة مرة أخرى.'], 400);
            }
            if ($status == 429) {
                return response()->json(['detail' => 'تم تجاوز الحد المسموح. يرجى الانتظار والمحاولة لاحقاً.'], 429);
            }
            return response()->json(['detail' => 'خطأ في إنشاء التصميم. يرجى المحاولة مرة أخرى.'], 500);
        }
    }

    public function save(Request $request)
    {
        try {
            // 👉 استخدام ميزة Validation بدلاً من التحقق اليدوي
            $validated = $request->validate([
                'prompt' => 'required|string',
                'image_base64' => 'required|string',
                'clothing_type' => 'required|string',
                'template_id' => 'nullable|string',
                'color' => 'nullable|string',
                'phone_number' => 'nullable|string',
                'user_photo_base64' => 'nullable|string',
                'logo_base64' => 'nullable|string'
            ]);

            $designId = (string) Str::uuid();

            $design = Design::create(array_merge($validated, [
                'id' => $designId,
                'user_id' => $request->user()->id,
                'is_favorite' => false,
            ]));

            User::where('id', $request->user()->id)->increment('designs_used');

            $orderId = (string) Str::uuid();
            Order::create([
                'id' => $orderId,
                'user_id' => $request->user()->id,
                'design_id' => $designId,
                'design_image_base64' => $validated['image_base64'],
                'prompt' => $validated['prompt'],
                'phone_number' => $validated['phone_number'] ?? 'غير محدد',
                'size' => 'M',
                'color' => $validated['color'] ?? '',
                'price' => 0,
                'discount' => 0,
                'final_price' => 0,
                'status' => 'pending',
            ]);

            // 👉 استدعاء Notification مباشرة بدون class_exists
            Notification::create([
                'user_id' => $request->user()->id,
                'title' => 'تم حفظ التصميم بنجاح',
                'message' => 'تم حفظ تصميمك الجديد وإنشاء طلب. سنتواصل معك قريباً!',
                'type' => 'success'
            ]);

            return response()->json([
                'id' => $design->id,
                'user_id' => $design->user_id,
                'prompt' => $design->prompt,
                'image_base64' => $design->image_base64,
                'created_at' => $design->created_at->toISOString(),
                'is_favorite' => $design->is_favorite,
                'clothing_type' => $design->clothing_type,
                'color' => $design->color,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['detail' => 'خطأ في حفظ التصميم: ' . $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $designs = Design::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $response = $designs->map(function($d) {
                return [
                    'id' => $d->id,
                    'user_id' => $d->user_id,
                    'prompt' => $d->prompt,
                    'image_base64' => $d->image_base64,
                    'clothing_type' => $d->clothing_type,
                    'template_id' => $d->template_id,
                    'color' => $d->color,
                    'phone_number' => $d->phone_number,
                    'is_favorite' => $d->is_favorite,
                    'created_at' => $d->created_at->toISOString(),
                ];
            });

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json(['detail' => 'خطأ في جلب التصاميم'], 500);
        }
    }

    public function toggleFavorite(Request $request, $id)
    {
        try {
            $design = Design::where('id', $id)->where('user_id', $request->user()->id)->first();

            if (!$design) {
                return response()->json(['detail' => 'التصميم غير موجود'], 404);
            }

            $design->is_favorite = !$design->is_favorite;
            $design->save();

            return response()->json([
                'message' => $design->is_favorite ? 'تمت الإضافة للمفضلة' : 'تمت الإزالة من المفضلة',
                'is_favorite' => $design->is_favorite,
            ]);
        } catch (Exception $e) {
            return response()->json(['detail' => 'خطأ في تحديث المفضلة'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $design = Design::where('id', $id)->where('user_id', $request->user()->id)->first();

            if (!$design) {
                return response()->json(['detail' => 'التصميم غير موجود'], 404);
            }

            $design->delete();

            User::where('id', $request->user()->id)->decrement('designs_used');

            return response()->json(['message' => 'تم حذف التصميم بنجاح']);
        } catch (Exception $e) {
            return response()->json(['detail' => 'خطأ في حذف التصميم'], 500);
        }
    }
}
