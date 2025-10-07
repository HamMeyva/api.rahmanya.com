<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Music\MusicCategory;
use App\Http\Controllers\Controller;

class MusicCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.musics.categories.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = MusicCategory::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere("slug", "LIKE", "%{$search}%");
        }

        // Filters
        //...


        // Order by
        $columns = ['id', 'name', 'slug'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'id';
            $orderDir = 'desc';
        }

        $query->orderBy($orderColumn, $orderDir);

        //
        $recordsFiltered = (clone $query)->count();

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $authUser = $request->user();
        $data = $list->map(function ($item) use ($authUser) {
            return [
                $item->id,
                $item->name,
                $item->slug,
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => $authUser->can('music category update'),
                    'editBtnClass' => 'editBtn',
                    'showDelete' => $authUser->can('music category delete'),
                    'deleteBtnClass' => 'deleteBtn',
                ])->render()
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:music_categories,name',
        ], [
            'name.required' => __('validation.required', ['attribute' => 'Kategori Adı']),
            'name.max' => __('validation.max.string', ['attribute' => 'Kategori Adı', 'max' => 255]),
            'name.unique' => __('validation.unique', ['attribute' => 'Kategori Adı']),
        ]);

        try {
            MusicCategory::create([
                'name' => $request->input('name'),
            ]);

            return response()->json([
                'message' => 'Kategori oluşturuldu.',
            ]);
        } catch (Exception $th) {
            return response()->json([
                'error' => $th->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $category = MusicCategory::find($id);
        if (!$category) {
            return response()->json([
                'error' => 'Kategori bulunamadı.',
            ], 404);
        }

        $request->validate([
            'name' => "required|string|max:255|unique:music_categories,name,{$id}",
        ], [
            'name.required' => __('validation.required', ['attribute' => 'Kategori Adı']),
            'name.max' => __('validation.max.string', ['attribute' => 'Kategori Adı', 'max' => 255]),
            'name.unique' => __('validation.unique', ['attribute' => 'Kategori Adı']),
        ]);

        try {
            $category->update([
                'name' => $request->input('name'),
            ]);

            return response()->json([
                'message' => 'Kategori güncellendi.',
            ]);
        } catch (Exception $th) {
            return response()->json([
                'error' => $th->getMessage(),
            ], 404);
        }
    }

    public function show($id): JsonResponse
    {
        $category = MusicCategory::find($id);
        if (!$category) {
            return response()->json([
                'error' => 'Kategori bulunamadı.',
            ], 404);
        }


        return response()->json([
            'data' => $category,
        ]);
    }

    public function delete($id): JsonResponse
    {
        $category = MusicCategory::find($id);
        if (!$category) {
            return response()->json([
                'error' => 'Kategori bulunamadı.',
            ], 404);
        }

        $category->delete();

        return response()->json([
            'message' => 'Kategori silindi.',
        ]);
    }
}
