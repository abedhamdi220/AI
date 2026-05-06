<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Design;
use App\Models\Order;
use App\Models\CouponUsage;
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
            return response()->json(['detail' => 'خطأ في جلب المستخدمين'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json(['detail' => 'المستخدم غير موجود'], 404);
            }

            if ($request->has('is_unlimited')) {
                $user->is_unlimited = $request->input('is_unlimited');
            }

            if ($request->has('designs_limit') && $request->input('designs_limit') >= 0) {
                $user->designs_limit = $request->input('designs_limit');
            }

            $user->save();

            return response()->json([
                'message' => 'تم تحديث حصة التصاميم بنجاح',
                'designs_limit' => $user->designs_limit,
                'is_unlimited' => $user->is_unlimited,
            ]);
        } catch (\Exception $error) {
            \Log::error('Update Designs Limit Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في تحديث حصة التصاميم'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json(['detail' => 'المستخدم غير موجود'], 404);
            }
            if ($user->is_admin) {
                return response()->json(['detail' => 'لا يمكن حذف مستخدم مدير'], 403);
            }
            Design::where('user_id', $id)->delete();
            Order::where('user_id', $id)->delete();
            CouponUsage::where('user_id', $id)->delete();
            $user->delete();

            return response()->json(['message' => 'تم حذف المستخدم وجميع بياناته بنجاح']);
        } catch (\Exception $error) {
            \Log::error('Delete User Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في حذف المستخدم'], 500);
        }
    }
}
