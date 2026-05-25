<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserDeletedByAdmin;
use App\Http\Controllers\Controller;
use App\Models\CouponUsage;
use App\Models\Design;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminUserController extends Controller
{

    public function index()
    {
        try {
            // استخدام paginate بدلاً من get لمنع استنزاف الذاكرة (Memory Exhaustion)
            $paginator = User::orderBy('created_at', 'desc')->paginate(10);

            $usersResponse = $paginator->getCollection()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'designs_limit' => $user->designs_limit,
                    'designs_used' => $user->designs_used,
                    'is_unlimited' => $user->is_unlimited,
                    'email_verified' => $user->email_verified,
                    'created_at' => $user->created_at ? $user->created_at->toIso8601String() : null,
                ];
            });

            // إعادة تركيب البيانات المهيأة داخل الـ Paginator
            $paginator->setCollection($usersResponse);

            return response()->json($paginator);
        } catch (\Exception $error) {
            \Log::error('Get Users Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء جلب قائمة المستخدمين'], 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json(['detail' => 'المستخدم المراد تعديل بياناته غير موجود'], 404);
            }

            if ($request->has('is_unlimited')) {
                $user->is_unlimited = $request->input('is_unlimited');
            }

            if ($request->has('designs_limit') && $request->input('designs_limit') >= 0) {
                $user->designs_limit = $request->input('designs_limit');
            }

            $user->save();

            return response()->json([
                'message' => 'تم تحديث بيانات وصلاحيات التصاميم للمستخدم بنجاح',
                'designs_limit' => $user->designs_limit,
                'is_unlimited' => $user->is_unlimited,
            ]);
        } catch (\Exception $error) {
            \Log::error('Update Designs Limit Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء تحديث بيانات المستخدم'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json(['detail' => 'المستخدم المراد حذفه غير موجود'], 404);
            }
            if ($user->is_admin) {
                return response()->json(['detail' => 'إجراء غير مصرح به: لا يمكنك حذف حساب يمتلك صلاحيات مدير'], 403);
            }

            // Cascading Deletes - تصرف ممتاز للحفاظ على نظافة قاعدة البيانات
            // ✅ جلب مسارات الصور وحذفها من السيرفر قبل حذف البيانات
            $userDesigns = Design::where('user_id', $id)->get();
            foreach ($userDesigns as $design) {
                if ($design->image_path) Storage::disk('public')->delete($design->image_path);
                if ($design->user_photo_path) Storage::disk('public')->delete($design->user_photo_path);
                if ($design->logo_path) Storage::disk('public')->delete($design->logo_path);
            }

            // الآن يمكنك حذف البيانات بأمان
            Design::where('user_id', $id)->delete();
            Order::where('user_id', $id)->delete(); // (تأكد أيضاً إذا كان الطلب يحتوي على صور مستقلة أن تحذفها بنفس الطريقة)
            CouponUsage::where('user_id', $id)->delete();
            $user->delete();

            event(new UserDeletedByAdmin($user->id, $user->username, $user->email));

            return response()->json(['message' => 'تم حذف المستخدم وجميع بياناته المرتبطة بنجاح']);
        } catch (\Exception $error) {
            \Log::error('Delete User Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء محاولة حذف المستخدم'], 500);
        }
    }
}
