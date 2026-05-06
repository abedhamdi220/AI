<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class OAuthService
{
 public function handleGoogleAuthentication(string $credential): array
{

    $clientId = config('services.google.client_id');

    $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
        'id_token' => $credential
    ]);

    if (!$response->successful()) {
        throw new Exception('توكن Google غير صالح أو منتهي الصلاحية.', 401);
    }

    $googleData = $response->json();

    if ($googleData['aud'] !== $clientId) {
        throw new Exception('محاولة اختراق: التوكن غير مخصص لهذا التطبيق.', 403);
    }

    if (!isset($googleData['email_verified']) || $googleData['email_verified'] !== "true") {
        throw new Exception('البريد الإلكتروني غير مُحقق.', 400);
    }

    $email = $googleData['email'];
    $user = User::where('email', $email)->first();
    $isNewUser = false;

    if (!$user) {
        $user = $this->createNewGoogleUser($email, $googleData['name'] ?? null);
        $isNewUser = true;
    }

    $token = auth('api')->login($user);

    return [
        'user' => $user,
        'token' => $token,
        'is_new' => $isNewUser
    ];
}


    private function createNewGoogleUser(string $email): User
    {
        $baseUsername = explode('@', $email)[0];
        $username = $baseUsername . '_' . substr(time(), -4);

        return User::create([
            'username' => $username,
            'email' => $email,
            'password' => Hash::make(Str::random(16)),
            'designs_limit' => 3,
            'designs_used' => 0,
            'is_admin' => false,
            'email_verified' => true,
        ]);
    }
}
