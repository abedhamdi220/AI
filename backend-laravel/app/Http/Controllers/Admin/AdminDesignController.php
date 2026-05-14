<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDesignController extends Controller
{
    public function index()
    {
        try {
            $designs = Design::orderBy('created_at', 'desc')->get();

            $designsWithUsers = $designs->map(function ($design) {
                $user = User::where('id', $design->user_id)->select('username', 'email')->first();
                return [
                    'id' => $design->id,
                    'user_id' => $design->user_id,
                    'user_name' => $user->username ?? 'غير معروف',
                    'user_email' => $user->email ?? 'غير معروف',
                    'prompt' => $design->prompt,
                    'image_base64' => $design->image_base64,
                    'clothing_type' => $design->clothing_type,
                    'color' => $design->color,
                    'phone_number' => $design->phone_number,
                    'is_favorite' => $design->is_favorite,
                    'created_at' => $design->created_at ? $design->created_at->toIso8601String() : null,
                ];
            });

            return response()->json($designsWithUsers);
        } catch (\Exception $error) {
            \Log::error('Get Designs Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء جلب قائمة التصاميم'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $design = Design::where('id', $id)->first();

            if (!$design) {
                return response()->json(['detail' => 'التصميم المراد حذفه غير موجود'], 404);
            }
            User::where('id', $design->user_id)->decrement('designs_used');

            $design->delete();

            return response()->json(['message' => 'تم حذف التصميم بنجاح']);
        } catch (\Exception $error) {
            \Log::error('Delete Design Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء محاولة حذف التصميم'], 500);
        }
    }
}
