<?php

namespace App\Http\Controllers\Customer;

use App\Models\User;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Exception;

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
                'designs_limit' => 3,
                'designs_used' => 0,
                'is_admin' => false,
            ]);

            $token = auth('api')->login($user);

            return $this->respondWithToken($token, $user, 201);

        } catch (Exception $e) {
            return response()->json([
                'detail' => 'خطأ في التسجيل: ' . $e->getMessage()
            ], 500);
        }
    }


    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only('username', 'password');

            if (!$token = auth('api')->attempt($credentials)) {
                return response()->json(['detail' => 'بيانات الدخول غير صحيحة'], 401);
            }

            return $this->respondWithToken($token, auth('api')->user(), 200);

        } catch (Exception $e) {
            return response()->json([
                'detail' => 'خطأ في تسجيل الدخول: ' . $e->getMessage()
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
            return response()->json([
                'detail' => 'خطأ في جلب بيانات المستخدم'
            ], 500);
        }
    }


    public function logout()
    {
        auth('api')->logout();
        return response()->json(['detail' => 'Successfully logged out.']);
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
