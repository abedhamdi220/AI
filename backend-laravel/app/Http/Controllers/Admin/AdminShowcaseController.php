<?php

namespace App\Http\Controllers\Admin;

use App\Events\NewShowcaseDesignCreated;
use App\Http\Controllers\Controller;
use App\Models\ShowcaseDesign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class AdminShowcaseController extends Controller
{
     private function saveBase64Image($base64String, $folder)
    {
        if (!$base64String) return null;
        try {
            $imageParts = explode(';base64,', $base64String);
            $image_base64 = base64_decode($imageParts[1] ?? $imageParts[0]);
            $fileName = Str::uuid() . '.png';
            $filePath = $folder . '/' . $fileName;
            Storage::disk('public')->put($filePath, $image_base64);
            return $filePath;
        } catch (\Exception $e) {
            Log::error('Image Save Error: ' . $e->getMessage());
            return null;
        }
    }
     public function index()
    {
        try {
            $paginator = ShowcaseDesign::orderBy('created_at', 'desc')->paginate(10);

            $response = $paginator->getCollection()->map(function ($d) {
                return [
                    'id' => $d->id,
                    'title' => $d->title,
                    'description' => $d->description,
                    'prompt' => $d->prompt,
                    'image_url' => $d->image_url, // استخدام الرابط الجديد
                    'clothing_type' => $d->clothing_type,
                    'color' => $d->color,
                    'template_id' => $d->template_id,
                    'tags' => $d->tags ?? [],
                    'likes_count' => $d->likes_count,
                    'is_featured' => $d->is_featured,
                    'is_active' => $d->is_active,
                    'created_at' => $d->created_at ? $d->created_at->toIso8601String() : null,
                ];
            });

            $paginator->setCollection($response);
            return response()->json($paginator);

        } catch (\Exception $error) {
            \Log::error('Get Showcase Designs Error: ' . $error->getMessage());
            return response()->json(['detail' => 'حدث خطأ داخلي أثناء جلب قائمة التصاميم الملهمة'], 500);
        }
    }

      public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string',
                'description' => 'nullable|string',
                'prompt' => 'required|string',
                'image_base64' => 'nullable|string', // قد تأتي من الواجهة
                'clothing_type' => 'required|string',
                'color' => 'nullable|string',
                'tags' => 'nullable|array',
            ]);

            $imagePath = null;
            if (!empty($validated['image_base64'])) {
                $imagePath = $this->saveBase64Image($validated['image_base64'], 'showcase');
            }

            $design = ShowcaseDesign::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'prompt' => $validated['prompt'],
                'image_path' => $imagePath,
                'clothing_type' => $validated['clothing_type'],
                'color' => $validated['color'] ?? '',
                'tags' => $validated['tags'] ?? [],
                'is_featured' => false,
                'is_active' => true,
            ]);

event(new NewShowcaseDesignCreated($design));
            return response()->json([
                'message' => 'تم إضافة التصميم الملهم بنجاح',
                'id' => $design->id,
            ], 201);
        } catch (\Exception $error) {
            Log::error('Create Showcase Design Error: ' . $error->getMessage());
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

        // معالجة الصورة إذا تم إرسال صورة جديدة
        if ($request->has('image_base64') && !empty($request->image_base64)) {
            // حذف الصورة القديمة من السيرفر إذا كانت موجودة
            if ($design->image_path) {
                Storage::disk('public')->delete($design->image_path);
            }
            // حفظ الصورة الجديدة
            $design->image_path = $this->saveBase64Image($request->image_base64, 'showcase');
        }

        // تحديث باقي الحقول (مع استبعاد image_base64 من اللوب)
        $fillable = ['title', 'description', 'prompt', 'clothing_type', 'color', 'template_id', 'tags', 'is_featured', 'is_active'];

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
            $design = ShowcaseDesign::where('id', $id)->first();

            if (!$design) {
                return response()->json(['detail' => 'التصميم الملهم المراد حذفه غير موجود'], 404);
            }

            // حذف الصورة من السيرفر
            if ($design->image_path) {
                Storage::disk('public')->delete($design->image_path);
            }

            $design->delete();

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
