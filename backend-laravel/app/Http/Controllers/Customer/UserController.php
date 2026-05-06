<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            \Log::error('Get User Designs Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في جلب التصاميم'], 500);
        }
    }

    public function designsQuota(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            $user = User::where('id', $userId)->first();

            if (!$user) {
                return response()->json(['detail' => 'المستخدم غير موجود'], 404);
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
            \Log::error('Get Designs Quota Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في جلب حصة التصاميم'], 500);
        }
    }
}
