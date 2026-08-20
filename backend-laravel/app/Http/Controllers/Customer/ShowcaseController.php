<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ShowcaseDesign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShowcaseController extends Controller
{
    // عدد التصاميم التي تُحمّل في كل صفحة/كل مرة يتم فيها التمرير للأسفل
    private const PER_PAGE = 12;

    public function index(Request $request)
    {
        try {
            $page = max((int) $request->query('page', 1), 1);
            $perPage = self::PER_PAGE;

            $showcases = Cache::remember("public_showcase_designs_page_{$page}", 60, function () use ($page, $perPage) {
                return ShowcaseDesign::with('design')
                    ->where('is_active', true)
                    ->orderBy('display_order', 'asc')
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->get();
            });

            // إجمالي عدد التصاميم المفعّلة، لمعرفة هل يوجد المزيد لتحميله عند التمرير للأسفل
            $totalActive = Cache::remember('public_showcase_designs_count', 60, function () {
                return ShowcaseDesign::where('is_active', true)->count();
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

            return response()->json([
                'data' => $response,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalActive,
                'has_more' => ($page * $perPage) < $totalActive,
            ]);

        } catch (\Exception $e) {
            Log::error('Fetch Showcase Error: ' . $e->getMessage());
            return response()->json(['detail' => 'عذراً، تعذر تحميل تصاميم المعرض في الوقت الحالي. يرجى المحاولة لاحقاً.'], 500);
        }
    }
}
