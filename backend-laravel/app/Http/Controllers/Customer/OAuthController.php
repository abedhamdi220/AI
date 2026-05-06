<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OAuthService;
use Exception;

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
                'credential.required' => 'يرجى تقديم بيانات Google'
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
                    'designs_limit' => $user->designs_limit,
                    'designs_used' => $user->designs_used,
                    'email_verified' => $user->email_verified,
                ]
            ], $result['is_new'] ? 201 : 200);

        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if ($statusCode < 100 || $statusCode > 599) $statusCode = 500;

            return response()->json([
                'detail' => 'خطأ في تسجيل الدخول عبر Google: ' . $e->getMessage()
            ], $statusCode);
        }
    }
}
