<?php

namespace App\Http\Controllers\Admin;

use \Log;
use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminDesignController extends Controller
{
    public function index()
    {
        try {
            $paginator = Design::with('user')->orderBy('created_at', 'desc')->paginate(6);

            $transformedData = $paginator->getCollection()->map(function ($design) {
                $user = $design->user;
                return [
                    'id' => $design->id,
                    'user_id' => $design->user_id,
                    'user_name' => $user->username ?? 'غير معروف',
                    'user_email' => $user->email ?? 'غير معروف',
                    'prompt' => $design->prompt,
                    'image_url' => $design->image_url,
                    'clothing_type' => $design->clothing_type,
                    'color' => $design->color,
                    'phone_number' => $design->phone_number,
                    'is_favorite' => $design->is_favorite,
                    'created_at' => $design->created_at ? $design->created_at->toIso8601String() : null,
                ];
            });

            $paginator->setCollection($transformedData);

            return response()->json($paginator);
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

            // ✅ إضافة منطق حذف الملفات من السيرفر قبل حذف الـ Record
            if ($design->image_path) {
                Storage::disk('public')->delete($design->image_path);
            }
            if ($design->user_photo_path) {
                Storage::disk('public')->delete($design->user_photo_path);
            }
            if ($design->logo_path) {
                Storage::disk('public')->delete($design->logo_path);
            }

            User::where('id', $design->user_id)->decrement('designs_used');
            $design->delete();

            return response()->json(['message' => 'تم حذف التصميم بنجاح']);
        } catch (\Exception $error) {
            Log::error('Delete Design Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء محاولة حذف التصميم'], 500);
        }
    }
}
