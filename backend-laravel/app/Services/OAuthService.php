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
    $response = Http::timeout(20)
        ->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $credential
        ]);

    if (!$response->successful()) {

        Log::error('Google Token Info Error: ' . $response->body());
        throw new Exception('توكن Google غير صالح أو انتهت صلاحيته، أو تعذر الاتصال بجوجل.', 401);
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


    private function createNewGoogleUser(string $email, ?string $name): User
    {
        $baseUsername = explode('@', $email)[0];

        $username = $baseUsername . '_' . Str::random(4);

        return User::create([
            'username' => $name ?? $username,
            'email' => $email,
            'password' => Hash::make(Str::random(24)),
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);
    }
}
