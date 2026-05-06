<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class DesignService
{

    public function generateImageWithAI($prompt, $clothingType, $color, $options = [])
    {
        $url = env('IMAGE_GENERATOR_URL', 'http://localhost:8002');

        $response = Http::timeout(180)->post("$url/generate", [
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
                'revised_prompt' => $response->json('revised_prompt', $prompt)
            ];
        }

        $errorMsg = $response->json('error') ?? 'فشل في توليد الصورة';

    
        throw new Exception($errorMsg, $response->status() == 200 ? 500 : $response->status());
    }
}
