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
use Illuminate\Support\Str;

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
            Log::error('Enhance Prompt Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر تحسين الوصف في الوقت الحالي. يرجى المحاولة مرة أخرى.'], 500);
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
                return response()->json(['detail' => 'يرجى إدخال وصف التصميم واختيار نوع الملابس.'], 400);
            }

            $user = User::where('id', $request->user()->id)->first();

            if (!$user->is_unlimited && $user->designs_used >= $user->designs_limit) {
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
                'message' => 'تم إنشاء تصميمك بنجاح!',
                'designs_remaining' => $designsRemaining,
                'designs_used' => $user->designs_used,
                'designs_limit' => $user->designs_limit
            ]);

        }
        catch (Exception $e)
        {
            $status = $e->getCode() ?: 500;
            if ($status == 400) {
                return response()->json(['detail' => 'وصف التصميم غير مناسب أو يحتوي على كلمات غير مسموحة. يرجى تعديله والمحاولة مرة أخرى.'], 400);
            }
            if ($status == 429) {
                return response()->json(['detail' => 'يوجد ضغط كبير على النظام حالياً. يرجى الانتظار قليلاً ثم المحاولة مرة أخرى.'], 429);
            }
            Log::error('Preview Design Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، حدث خطأ أثناء معالجة التصميم. يرجى المحاولة مرة أخرى.'], 500);
        }
    }

    public function save(Request $request)
    {
        try {
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


             event(new DesignCreated($design));

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
            Log::error('Save Design Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، حدث خطأ أثناء حفظ التصميم. يرجى المحاولة مرة أخرى.'], 500);
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
            Log::error('Fetch Designs Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر تحميل قائمة تصاميمك. يرجى إعادة تحميل الصفحة.'], 500);
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
            Log::error('Toggle Favorite Error: ' . $e->getMessage());
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

            $design->delete();

            User::where('id', $request->user()->id)->decrement('designs_used');

            return response()->json(['message' => 'تم حذف التصميم بنجاح.']);
        } catch (Exception $e) {
            Log::error('Delete Design Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر حذف التصميم. يرجى المحاولة مجدداً.'], 500);
        }
    }
}
