<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;

class OptionsController extends Controller
{

    public function clothingTypes()
    {
        return response()->json([
            [
                'value' => 'tshirt',
                'label' => 'تيشيرت',
                'emoji' => '👕',
                'active' => true,
                'color' => 'from-blue-400 to-blue-600',
                'description' => 'تصميم كاجوال وعملي للاستخدام اليومي.'
            ],
            [
                'value' => 'hoodie',
                'label' => 'هودي',
                'emoji' => '🧥',
                'active' => true,
                'color' => 'from-purple-400 to-purple-600',
                'description' => 'سترة مريحة وعملية مناسبة للأجواء الباردة.'
            ],
            [
                'value' => 'shirt',
                'label' => 'قميص',
                'emoji' => '👔',
                'active' => true,
                'color' => 'from-gray-500 to-gray-700',
                'description' => 'تصميم رسمي وعصري لمناسباتك.'
            ],
            [
                'value' => 'dress',
                'label' => 'فستان',
                'emoji' => '👗',
                'active' => false,
                'color' => 'from-pink-400 to-pink-600',
                'description' => 'فستان أنيق.'
            ],
            [
                'value' => 'pants',
                'label' => 'بنطلون',
                'emoji' => '👖',
                'active' => false,
                'color' => 'from-indigo-400 to-indigo-600',
                'description' => 'بنطلون بتصميم مميز.'
            ]
        ]);
    }

    public function viewAngles()
    {
        return response()->json([
            ['value' => 'front', 'label' => 'من الأمام', 'icon' => '👤'],
            ['value' => 'back', 'label' => 'من الخلف', 'icon' => '👥'],
            ['value' => 'side', 'label' => 'من الجانب', 'icon' => '🚶']
        ]);
    }

    public function logoPositions()
    {
        return response()->json([
            ['value' => 'center_chest', 'label' => 'منتصف الصدر'],
            ['value' => 'left_chest', 'label' => 'يسار الصدر (شعار جيب)'],
            ['value' => 'back', 'label' => 'الظهر كاملاً'],
        ]);
    }
}
