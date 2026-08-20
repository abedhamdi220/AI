<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class DesignService
{

    public function generateImageWithAI($prompt, $clothingType, $color, $options = [])
    {
        $url = env('AI_SERVICE_URL', 'https://styloraify-ai-image.onrender.com');
        $internalKey = env('INTERNAL_SERVICE_KEY'); // يجب أن يطابق INTERNAL_SERVICE_KEY في خدمة بايثون

        $request = Http::timeout(200);
        if ($internalKey) {
            $request = $request->withHeaders(['X-Internal-Key' => $internalKey]);
        }

        $response = $request->post("$url/generate", [
            'prompt' => $prompt,
            'clothing_type' => $clothingType,
            'color' => $color ?? '',
            'logo_base64' => $options['logo_base64'] ?? null,
            'logo_position' => $options['logo_position'] ?? 'center',
            'user_photo_base64' => $options['user_photo_base64'] ?? null,
            'view_angle' => $options['view_angle'] ?? 'front'
        ]);

        if ($response->successful() && $response->json('success') && $response->json('image_base64')) {
            return [
                'image_base64' => $response->json('image_base64'),
                'composite_image_base64' => $response->json('composite_image_base64', ''),
                'revised_prompt' => $response->json('revised_prompt', $prompt),
                // جديد: نمرر هذه القيم بدل ما نفترض إن اللوغو/الصورة اتدمجوا بنجاح دائماً
                'logo_applied' => (bool) $response->json('logo_applied', false),
                'logo_warning' => $response->json('logo_warning', ''),
                'user_photo_applied' => (bool) $response->json('user_photo_applied', false),
                'user_photo_warning' => $response->json('user_photo_warning', ''),
            ];
        }

        $errorMsg = $response->json('error') ?? 'فشل في توليد الصورة';


        throw new Exception($errorMsg, $response->status() == 200 ? 500 : $response->status());
    }
}
