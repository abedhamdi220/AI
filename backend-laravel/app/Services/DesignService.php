<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class DesignService
{
    /**
     * توليد صورة باستخدام خادم الذكاء الاصطناعي (FastAPI)
     */
    public function generateImageWithAI($prompt, $clothingType, $color, $options = [])
    {
        $url = env('IMAGE_GENERATOR_URL', 'http://localhost:8002');

        try {
            // إرسال الطلب إلى خادم Python
            $response = Http::timeout(180)->post("$url/generate", [
                'prompt'            => $prompt,
                'clothing_type'     => $clothingType,
                'color'             => $color ?? '',
                'logo_base64'       => $options['logo_base64'] ?? null,
                'logo_position'     => $options['logo_position'] ?? 'center',
                'user_photo_base64' => $options['user_photo_base64'] ?? null,
                'view_angle'        => $options['view_angle'] ?? 'front'
            ]);

            // التحقق من نجاح الاستجابة
            if ($response->successful() && $response->json('success')) {
                return [
                    'image_base64'           => $response->json('image_base64'),
                    'composite_image_base64' => $response->json('composite_image_base64', ''),
                    'revised_prompt'         => $response->json('revised_prompt', $prompt)
                ];
            }

            // استخراج رسالة الخطأ في حال فشل الطلب
            $errorMsg = $response->json('error') ?? $response->json('detail') ?? 'فشل في توليد الصورة من خادم الذكاء الاصطناعي';

            // معالجة خطأ اشتراك Pro إذا ظهر
            if (str_contains($errorMsg, 'Pro members')) {
                $errorMsg = 'خدمة توليد الصور تتطلب اشتراك Pro مفعل في DeepAI أو SeaArt. يرجى التحقق من الحساب.';
            }

            throw new Exception($errorMsg, $response->status() == 200 ? 500 : $response->status());

        } catch (Exception $e) {
            // إعادة رمي الاستثناء ليتم معالجته في الـ Controller
            throw new Exception($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
