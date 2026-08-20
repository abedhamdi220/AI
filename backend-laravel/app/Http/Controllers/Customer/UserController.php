<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{

    public function designs(Request $request)
    {
        try {
            $userId = Auth::user()->id;

            $designs = Design::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            $response = $designs->map(function ($d) {
                return [
                    'id' => $d->id,
                    'prompt' => $d->prompt,
                    'image_base64' => $d->image_base64,
                    'clothing_type' => $d->clothing_type,
                    'color' => $d->color,
                    'is_favorite' => $d->is_favorite,
                    'created_at' => $d->created_at ? $d->created_at->toIso8601String() : null,
                ];
            });

            return response()->json($response);
        } catch (\Exception $error) {
            Log::error('Get User Designs Error: ' . $error->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر تحميل قائمة تصاميمك. يرجى المحاولة مرة أخرى.'], 500);
        }
    }

    public function designsQuota(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            $user = User::where('id', $userId)->first();

            if (!$user) {
                return response()->json(['detail' => 'عذراً، لم يتم العثور على بيانات المستخدم.'], 404);
            }

            $remaining = $user->is_unlimited
                ? 999
                : max(0, $user->designs_limit - $user->designs_used);

            return response()->json([
                'designs_limit' => $user->designs_limit,
                'designs_used' => $user->designs_used,
                'designs_remaining' => $remaining,
                'is_unlimited' => $user->is_unlimited,
            ]);
        } catch (\Exception $error) {
            Log::error('Get Designs Quota Error: ' . $error->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر جلب بيانات الرصيد المتبقي لتصاميمك.'], 500);
        }
    }

    public function getMeasurements(Request $request)
    {
        try {
            $user = $request->user();
            return response()->json([
                'chest' => $user->chest ?? null,
                'waist' => $user->waist ?? null,
                'hips' => $user->hips ?? null,
                'height' => $user->height ?? null,
                'weight' => $user->weight ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Measurements Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر تحميل بيانات المقاسات الخاصة بك. يرجى المحاولة مرة أخرى.'], 500);
        }
    }

    public function saveMeasurements(Request $request)
    {
        try {
            $validated = $request->validate([
                'chest' => 'nullable|numeric',
                'waist' => 'nullable|numeric',
                'hips' => 'nullable|numeric',
                'height' => 'nullable|numeric',
                'weight' => 'nullable|numeric',
            ]);

            $user = $request->user();
            $user->update($validated);

            $suggestedSize = 'M';
            $chest = $validated['chest'] ?? 0;
            if ($chest > 0) {
                if ($chest < 90) $suggestedSize = 'S';
                elseif ($chest >= 90 && $chest <= 100) $suggestedSize = 'M';
                elseif ($chest > 100 && $chest <= 110) $suggestedSize = 'L';
                elseif ($chest > 110 && $chest <= 120) $suggestedSize = 'XL';
                else $suggestedSize = 'XXL';
            }

            return response()->json([
                'message' => 'تم حفظ مقاساتك بنجاح.',
                'suggested_size' => $suggestedSize
            ]);

        } catch (\Exception $e) {
            Log::error('Save Measurements Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، حدث خطأ أثناء حفظ المقاسات. يرجى التأكد من الأرقام المدخلة والمحاولة مجدداً.'], 500);
        }
    }
}
