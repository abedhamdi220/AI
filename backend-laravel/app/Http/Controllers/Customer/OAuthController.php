<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OAuthService;
use Exception;
use Illuminate\Support\Facades\Log;

class OAuthController extends Controller
{
    protected $oauthService;

    public function __construct(OAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
    }

    public function google(Request $request)
    {
        try {
            $request->validate([
                'credential' => 'required|string',
            ], [
                'credential.required' => 'بيانات الاعتماد من Google غير متوفرة، يرجى المحاولة مرة أخرى.'
            ]);

            $result = $this->oauthService->handleGoogleAuthentication($request->credential);
            $user = $result['user'];

            return response()->json([
                'access_token' => $result['token'],
                'token_type' => 'bearer',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'designs_limit' => $user->designs_limit ?? 0,
                    'designs_used' => $user->designs_used ?? 0,
                    'email_verified' => $user->email_verified_at ? true : false,
                ]
            ], $result['is_new'] ? 201 : 200);

        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if ($statusCode < 100 || $statusCode > 599) $statusCode = 500;

            Log::error('Google OAuth Error: ' . $e->getMessage());

            return response()->json([
                'detail' => $e->getMessage() ?: 'عذراً، حدث خطأ أثناء محاولة تسجيل الدخول باستخدام حساب Google. يرجى المحاولة مرة أخرى.'
            ], $statusCode);
        }
    }
}
