<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserDeletedByAdmin;
use App\Http\Controllers\Controller;
use App\Models\CouponUsage;
use App\Models\Design;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{

    public function index()
    {
        try {
            $users = User::orderBy('created_at', 'desc')->get();

            $usersResponse = $users->map(function ($user) {
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

            return response()->json($usersResponse);
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
            Design::where('user_id', $id)->delete();
            Order::where('user_id', $id)->delete();
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
