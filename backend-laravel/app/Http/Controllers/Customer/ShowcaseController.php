<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ShowcaseDesign;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShowcaseController extends Controller
{
    public function index()
    {
        try {

            $showcases = Cache::remember('public_showcase_designs', 60, function () {
                return ShowcaseDesign::with('design')
                    ->where('is_active', true)
                    ->orderBy('display_order', 'asc')
                    ->take(10)
                    ->get();
            });

         $response = $showcases->map(function ($s) {
    return [
        'id' => $s->id ?? $s->_id,
        'title' => $s->title,
        'description' => $s->description,
        'prompt' => $s->prompt,

        'image_url' => $s->image_url,
        'clothing_type' => $s->clothing_type,
        'color' => $s->color,
        'tags' => $s->tags ?? [],
        'is_featured' => $s->is_featured,
        'likes_count' => $s->likes_count ?? 0,
    ];
});

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Fetch Showcase Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر تحميل تصاميم المعرض في الوقت الحالي. يرجى المحاولة لاحقاً.'], 500);
        }
    }
}
