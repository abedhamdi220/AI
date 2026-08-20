<?php

namespace App\Http\Controllers\Customer;

use App\Events\UserLoggedIn;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function register(RegisterRequest $request)
    {
        try {
            $user = User::create([
                'id' => Str::uuid()->toString(),
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified' => false,
                'designs_limit' => 5,
                'designs_used' => 0,
                'is_admin' => false,
            ]);

            $token = auth('api')->login($user);

            return $this->respondWithToken($token, $user, 201);

        } catch (Exception $e) {
            Log::error('Register Error: ' . $e->getMessage());
            return response()->json([
                'detail' => 'عذراً، حدث خطأ أثناء إنشاء الحساب. يرجى التأكد من صحة البيانات والمحاولة مجدداً.'
            ], 500);
        }
    }


    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only('username', 'password');

            if (!$token = auth('api')->attempt($credentials)) {
                return response()->json(['detail' => 'بيانات الدخول غير صحيحة، يرجى التأكد من اسم المستخدم وكلمة المرور.'], 401);
            }
  event(new UserLoggedIn(auth('api')->user()));
            return $this->respondWithToken($token, auth('api')->user(), 200);

        } catch (Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            return response()->json([
                'detail' => 'عذراً، حدث خطأ غير متوقع أثناء تسجيل الدخول. يرجى المحاولة مرة أخرى لاحقاً.'
            ], 500);
        }
    }


    public function me()
    {
        try {
            $user = auth('api')->user();

            return response()->json([
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'designs_limit' => $user->designs_limit,
                'designs_used' => $user->designs_used,
                'email_verified' => $user->email_verified,
            ]);

        } catch (Exception $e) {
            Log::error('Get User Profile Error: ' . $e->getMessage());
            return response()->json([
                'detail' => 'عذراً، تعذر جلب بيانات الحساب في الوقت الحالي. يرجى إعادة تحميل الصفحة.'
            ], 500);
        }
    }


    public function logout()
    {
        auth('api')->logout();
        return response()->json(['detail' => 'تم تسجيل الخروج بنجاح.']);
    }


    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh(), auth('api')->user());
    }


    protected function respondWithToken($token, $user, $status = 200)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'designs_limit' => $user->designs_limit,
                'designs_used' => $user->designs_used,
                'email_verified' => $user->email_verified,
            ]
        ], $status);
    }
}
