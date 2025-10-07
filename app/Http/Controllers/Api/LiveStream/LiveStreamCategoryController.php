<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Http\Controllers\Controller;
use App\Http\Resources\LiveStreamCategoryResource;
use App\Models\LiveStreamCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LiveStreamCategoryController extends Controller
{
    /**
     * Tüm kategorileri listeler
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $mainOnly = $request->boolean('main_only', false);
        $query = LiveStreamCategory::active()->ordered();
        
        if ($mainOnly) {
            $query->mainCategories();
        }
        
        $categories = $query->get();

        return response()->json([
            'success' => true,
            'data' => LiveStreamCategoryResource::collection($categories)
        ]);
    }

    /**
     * Belirli bir kategorinin alt kategorilerini listeler
     *
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function subcategories($categoryId)
    {
        $category = LiveStreamCategory::findOrFail($categoryId);
        $subcategories = LiveStreamCategory::active()
            ->byParent($categoryId)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'parent' => new LiveStreamCategoryResource($category),
                'subcategories' => LiveStreamCategoryResource::collection($subcategories)
            ]
        ]);
    }

    /**
     * Kategori detaylarını getirir
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $category = LiveStreamCategory::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new LiveStreamCategoryResource($category)
        ]);
    }

    /**
     * Bir kategorinin yayınlarını listeler
     *
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function streams($categoryId)
    {
        $category = LiveStreamCategory::findOrFail($categoryId);
        
        // Alt kategorileri de dahil et
        $categoryIds = [$category->id];
        $subcategories = LiveStreamCategory::where('parent_id', $category->id)->get();
        
        foreach ($subcategories as $subcategory) {
            $categoryIds[] = $subcategory->id;
        }
        
        // Aktif yayınları bul
        $streams = \App\Models\AgoraChannel::active()
            ->whereIn('category_id', $categoryIds)
            ->orderBy('viewer_count', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => new LiveStreamCategoryResource($category),
                'streams' => \App\Http\Resources\AgoraChannelResource::collection($streams)
            ]
        ]);
    }

    /**
     * Kategorileri arar
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        if (!$query || strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Search query must be at least 2 characters'
            ], 422);
        }
        
        $categories = LiveStreamCategory::active()
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->ordered()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => LiveStreamCategoryResource::collection($categories)
        ]);
    }
}
