<?php

namespace App\Http\Controllers\Admin;

use App\Events\NewShowcaseDesignCreated;
use App\Http\Controllers\Controller;
use App\Models\ShowcaseDesign;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminShowcaseController extends Controller
{
    public function index()
    {
        try {
            $designs = ShowcaseDesign::orderBy('created_at', 'desc')->get();

            $response = $designs->map(function ($d) {
                return [
                    'id' => $d->id,
                    'title' => $d->title,
                    'description' => $d->description,
                    'prompt' => $d->prompt,
                    'image_base64' => $d->image_base64,
                    'clothing_type' => $d->clothing_type,
                    'color' => $d->color,
                    'template_id' => $d->template_id,
                    'tags' => $d->tags ?? [],
                    'likes_count' => $d->likes_count,
                    'is_featured' => $d->is_featured,
                    'is_active' => $d->is_active,
                    'created_at' => $d->created_at ? $d->created_at->toIso8601String() : null,
                    'updated_at' => $d->updated_at ? $d->updated_at->toIso8601String() : null,
                ];
            });

            return response()->json($response);
        } catch (\Exception $error) {
            \Log::error('Get Showcase Designs Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء جلب قائمة التصاميم الملهمة'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (empty($data['title']) || empty($data['description']) || empty($data['prompt']) || empty($data['image_base64']) || empty($data['clothing_type'])) {
                return response()->json(['detail' => 'البيانات غير مكتملة: يرجى إدخال جميع الحقول المطلوبة للتصميم'], 400);
            }

            $design = ShowcaseDesign::create([
                'id' => (string) Str::uuid(),
                'title' => $data['title'],
                'description' => $data['description'],
                'prompt' => $data['prompt'],
                'image_base64' => $data['image_base64'],
                'clothing_type' => $data['clothing_type'],
                'color' => $data['color'] ?? null,
                'template_id' => $data['template_id'] ?? null,
                'tags' => $data['tags'] ?? [],
                'is_featured' => $data['is_featured'] ?? false,
                'is_active' => true,
                'likes_count' => 0,
            ]);
event(new NewShowcaseDesignCreated($design));
            return response()->json([
                'message' => 'تم إضافة التصميم الملهم بنجاح',
                'id' => $design->id,
            ], 201);
        } catch (\Exception $error) {
            \Log::error('Create Showcase Design Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء إضافة التصميم الملهم'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $design = ShowcaseDesign::where('id', $id)->first();

            if (!$design) {
                return response()->json(['detail' => 'التصميم الملهم المراد تحديثه غير موجود'], 404);
            }

            $fillable = ['title', 'description', 'prompt', 'image_base64', 'clothing_type', 'color', 'template_id', 'tags', 'is_featured', 'is_active'];

            foreach ($fillable as $field) {
                if ($request->has($field)) {
                    $design->$field = $request->input($field);
                }
            }

            $design->updated_at = now();
            $design->save();

            return response()->json(['message' => 'تم تحديث التصميم الملهم بنجاح']);
        } catch (\Exception $error) {
            \Log::error('Update Showcase Design Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء تحديث بيانات التصميم الملهم'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $result = ShowcaseDesign::where('id', $id)->delete();

            if ($result === 0) {
                return response()->json(['detail' => 'التصميم الملهم المراد حذفه غير موجود'], 404);
            }

            return response()->json(['message' => 'تم حذف التصميم الملهم بنجاح']);
        } catch (\Exception $error) {
            \Log::error('Delete Showcase Design Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء محاولة حذف التصميم الملهم'], 500);
        }
    }

    public function toggleFeatured($id)
    {
        try {
            $design = ShowcaseDesign::where('id', $id)->first();

            if (!$design) {
                return response()->json(['detail' => 'التصميم الملهم غير موجود'], 404);
            }

            $design->is_featured = !$design->is_featured;
            $design->updated_at = now();
            $design->save();

            $statusText = $design->is_featured ? 'مميز' : 'عادي';

            return response()->json([
                'message' => "تم تغيير حالة التصميم لتصبح: {$statusText}",
                'is_featured' => $design->is_featured,
            ]);
        } catch (\Exception $error) {
            \Log::error('Toggle Featured Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء تغيير حالة تمييز التصميم'], 500);
        }
    }
}
