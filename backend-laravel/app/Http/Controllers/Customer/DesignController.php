<?php

namespace App\Http\Controllers\Customer;

use App\Events\DesignCreated;
use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Services\DesignService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DesignController extends Controller
{
    protected $designService;

    public function __construct(DesignService $designService)
    {
        $this->designService = $designService;
    }
 private function saveBase64Image($base64String, $folder)
    {
        if (!$base64String) return null;

        try {
            // إزالة بادئة data:image إذا كانت موجودة
            $imageParts = explode(';base64,', $base64String);
            $image_base64 = base64_decode($imageParts[1] ?? $imageParts[0]);

            $fileName = Str::uuid() . '.png';
            $filePath = $folder . '/' . $fileName;

            // حفظ الملف في مجلد public
            Storage::disk('public')->put($filePath, $image_base64);

            return $filePath;
        } catch (Exception $e) {
            Log::error('Image Save Error: ' . $e->getMessage());
            return null;
        }
    }
    public function sizeChart()
    {
        return response()->json([
            'S' => ['chest' => '86-94', 'waist' => '71-79', 'hips' => '89-97'],
            'M' => ['chest' => '94-102', 'waist' => '79-86', 'hips' => '97-104'],
            'L' => ['chest' => '102-112', 'waist' => '86-97', 'hips' => '104-114'],
            'XL' => ['chest' => '112-122', 'waist' => '97-107', 'hips' => '114-124'],
            'XXL' => ['chest' => '122-132', 'waist' => '107-119', 'hips' => '124-134']
        ]);
    }

    public function enhancePrompt(Request $request)
    {
        try {
            $prompt = $request->input('prompt');
            $clothing_type = $request->input('clothing_type');

            if (!$prompt) {
                return response()->json(['detail' => 'يرجى كتابة وصف للتصميم أولاً.'], 400);
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
            Log::error('Enhance Prompt Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            return response()->json(['detail' => 'عذراً، تعذر تحسين الوصف في الوقت الحالي. يرجى المحاولة مرة أخرى.'], 500);
        }
    }

    public function preview(Request $request)
    {
        try {
            Log::info('--- بدأ طلب معاينة تصميم جديد (Preview Design) ---');

            $prompt = $request->input('prompt');
            $clothing_type = $request->input('clothing_type');
            $color = $request->input('color');
            $logo_base64 = $request->input('logo_base64');
            $logo_position = $request->input('logo_position', 'center');
            $user_photo_base64 = $request->input('user_photo_base64');
            $view_angle = $request->input('view_angle', 'front');

            Log::info('بيانات الطلب المستلمة: ', [
                'prompt' => $prompt,
                'clothing_type' => $clothing_type,
                'has_logo' => !empty($logo_base64),
                'has_user_photo' => !empty($user_photo_base64),
            ]);

            if (!$prompt || !$clothing_type) {
                Log::warning('خطأ في التحقق من صحة البيانات: الوصف أو نوع الملابس مفقود.');
                return response()->json(['detail' => 'يرجى إدخال وصف التصميم واختيار نوع الملابس.'], 400);
            }

            $user = User::where('id', $request->user()->id)->first();

            if (!$user->is_unlimited && $user->designs_used >= $user->designs_limit) {
                Log::warning('المستخدم استنفد رصيد التصاميم. User ID: ' . $user->id);
                return response()->json(['detail' => 'عذراً، لقد استنفدت رصيدك من التصاميم المجانية المتاحة.'], 403);
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

            Log::info('جاري الاتصال بـ DesignService لتوليد الصورة...');
            $result = $this->designService->generateImageWithAI(
                $prompt, $englishClothingType, $color,
                compact('logo_base64', 'logo_position', 'user_photo_base64', 'view_angle')
            );
            Log::info('تم توليد الصورة واستلامها من DesignService بنجاح.');

            $user->increment('designs_used');
            $user->refresh();

            $designsRemaining = $user->is_unlimited ? 999 : ($user->designs_limit - $user->designs_used);

            Log::info('تم الانتهاء من طلب المعاينة بنجاح وإرجاع الاستجابة للواجهة.');
            return response()->json([
                'success' => true,
                'image_base64' => $result['image_base64'],
                'composite_image_base64' => $result['composite_image_base64'] ?? '',
                'prompt' => $result['revised_prompt'] ?? $prompt,
                'message' => 'تم إنشاء تصميمك بنجاح!',
                'designs_remaining' => $designsRemaining,
                'designs_used' => $user->designs_used,
                'designs_limit' => $user->designs_limit
            ]);

        }
        catch (Exception $e)
        {
            $status = $e->getCode() ?: 500;

            // تسجيل تفاصيل الخطأ المعمقة في السجل
            Log::error('Preview Design Error: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            Log::error('Stack Trace: ' . $e->getTraceAsString());

            if ($status == 400) {
                return response()->json([
                    'detail' => 'وصف التصميم غير مناسب أو يحتوي على كلمات غير مسموحة. يرجى تعديله والمحاولة مرة أخرى.',
                    'debug_message' => $e->getMessage()
                ], 400);
            }
            if ($status == 429) {
                return response()->json([
                    'detail' => 'يوجد ضغط كبير على النظام حالياً. يرجى الانتظار قليلاً ثم المحاولة مرة أخرى.',
                    'debug_message' => $e->getMessage()
                ], 429);
            }

            // إرجاع رسالة الخطأ التفصيلية في الاستجابة (مفيد جداً لاكتشاف سبب خطأ 500 في أدوات المطور)
            return response()->json([
                'detail' => 'عذراً، حدث خطأ أثناء معالجة التصميم. يرجى المحاولة مرة أخرى.',
                'debug_message' => $e->getMessage(),
                'debug_file' => $e->getFile(),
                'debug_line' => $e->getLine()
            ], 500);
        }
    }

    public function save(Request $request)
    {
        try {
            $user = User::where('id', $request->user()->id)->first();

            // الدالة لم تعد تستدعي الـ AI ولن تستهلك من باقة المستخدم هنا لأنه تم سحبها في preview
            $validated = $request->validate([
                'image_base64' => 'required|string', // يجب إرسال الصورة الجاهزة من المعاينة
                'prompt' => 'required|string|max:1000',
                'clothing_type' => 'required|string',
                'color' => 'nullable|string',
                'logo_base64' => 'nullable|string',
                'user_photo_base64' => 'nullable|string'
            ]);

            // تحويل الصور المستلمة إلى ملفات فعلية
            $imagePath = $this->saveBase64Image($validated['image_base64'], 'designs/generated');
            $logoPath = $this->saveBase64Image($validated['logo_base64'] ?? null, 'designs/logos');
            $userPhotoPath = $this->saveBase64Image($validated['user_photo_base64'] ?? null, 'designs/users');

            $designId = (string) Str::uuid();

            $design = Design::create([
                'id' => $designId,
                'user_id' => $user->id,
                'prompt' => $validated['prompt'],
                'image_path' => $imagePath, // حفظ المسار
                'logo_path' => $logoPath,
                'user_photo_path' => $userPhotoPath,
                'clothing_type' => $validated['clothing_type'],
                'color' => $validated['color'] ?? '',
                'is_favorite' => false,
            ]);

            return response()->json([
                'message' => 'تم حفظ التصميم بنجاح!',
                'design' => [
                    'id' => $design->id,
                    'prompt' => $design->prompt,
                    'image_url' => $design->image_url, // سيعيد الرابط الجاهز
                    'clothing_type' => $design->clothing_type,
                    'color' => $design->color,
                    'is_favorite' => $design->is_favorite,
                    'created_at' => $design->created_at->toISOString()
                ]
            ], 201);

        } catch (Exception $e) {
            Log::error('Save Design Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            return response()->json(['detail' => 'عذراً، حدث خطأ أثناء حفظ التصميم. حاول مرة أخرى لاحقاً.'], 500);
        }
    }

   public function index(Request $request)
    {
        try {
            $designs = Design::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $response = $designs->map(function($design) {
                return [
                    'id' => $design->id,
                    'prompt' => $design->prompt,
                    'image_url' => $design->image_url, // الواجهة ستعتمد على هذا الحقل فقط
                    'clothing_type' => $design->clothing_type,
                    'color' => $design->color,
                    'is_favorite' => $design->is_favorite,
                    'created_at' => $design->created_at->toISOString()
                ];
            });

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('Fetch Designs Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر جلب قائمة تصاميمك.'], 500);
        }
    }

    public function toggleFavorite(Request $request, $id)
    {
        try {
            $design = Design::where('id', $id)->where('user_id', $request->user()->id)->first();

            if (!$design) {
                return response()->json(['detail' => 'عذراً، لم يتم العثور على هذا التصميم.'], 404);
            }

            $design->is_favorite = !$design->is_favorite;
            $design->save();

            return response()->json([
                'message' => $design->is_favorite ? 'تمت إضافة التصميم إلى المفضلة' : 'تمت إزالة التصميم من المفضلة',
                'is_favorite' => $design->is_favorite,
            ]);
        } catch (Exception $e) {
            Log::error('Toggle Favorite Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            return response()->json(['detail' => 'عذراً، تعذر تحديث حالة المفضلة. يرجى المحاولة مجدداً.'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $design = Design::where('id', $id)->where('user_id', $request->user()->id)->first();

            if (!$design) {
                return response()->json(['detail' => 'عذراً، لم يتم العثور على هذا التصميم.'], 404);
            }

            // حذف ملف الصورة من السيرفر إذا كان موجوداً
            if ($design->image_path) {
                Storage::disk('public')->delete($design->image_path);
            }
            if ($design->logo_path) {
                 Storage::disk('public')->delete($design->logo_path);
            }
             if ($design->user_photo_path) {
                 Storage::disk('public')->delete($design->user_photo_path);
            }

            $design->delete();

            User::where('id', $request->user()->id)->decrement('designs_used');

            return response()->json(['message' => 'تم حذف التصميم بنجاح.']);
        } catch (Exception $e) {
            Log::error('Delete Design Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر حذف التصميم. يرجى المحاولة لاحقاً.'], 500);
        }
    }
}
