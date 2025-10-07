<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\View\View;
use App\Models\Music\Artist;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ArtistController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.artists.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = Artist::query();

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
                    'showEdit' => $authUser->can('artist update'),
                    'editBtnClass' => 'editBtn',
                    'showDelete' => $authUser->can('artist delete'),
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
            'name' => 'required|string|max:255|unique:artists,name',
        ], [
            'name.required' => __('validation.required', ['attribute' => 'Sanatçı Adı']),
            'name.max' => __('validation.max.string', ['attribute' => 'Sanatçı Adı', 'max' => 255]),
            'name.unique' => __('validation.unique', ['attribute' => 'Sanatçı Adı']),
        ]);

        try {
            Artist::create([
                'name' => $request->input('name'),
            ]);

            return response()->json([
                'message' => 'Sanatçı oluşturuldu.',
            ]);
        } catch (Exception $th) {
            return response()->json([
                'error' => $th->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $artist = Artist::find($id);
        if (!$artist) {
            return response()->json([
                'error' => 'Sanatçı bulunamadı.',
            ], 404);
        }

        $request->validate([
            'name' => "required|string|max:255|unique:artists,name,{$id}",
        ], [
            'name.required' => __('validation.required', ['attribute' => 'Sanatçı Adı']),
            'name.max' => __('validation.max.string', ['attribute' => 'Sanatçı Adı', 'max' => 255]),
            'name.unique' => __('validation.unique', ['attribute' => 'Sanatçı Adı']),
        ]);

        try {
            $artist->update([
                'name' => $request->input('name'),
            ]);

            return response()->json([
                'message' => 'Sanatçı güncellendi.',
            ]);
        } catch (Exception $th) {
            return response()->json([
                'error' => $th->getMessage(),
            ], 404);
        }
    }

    public function show($id): JsonResponse
    {
        $artist = Artist::find($id);
        if (!$artist) {
            return response()->json([
                'error' => 'Sanatçı bulunamadı.',
            ], 404);
        }


        return response()->json([
            'data' => $artist,
        ]);
    }

    public function delete($id): JsonResponse
    {
        $artist = Artist::find($id);
        if (!$artist) {
            return response()->json([
                'error' => 'Sanatçı bulunamadı.',
            ], 404);
        }

        $artist->delete();

        return response()->json([
            'message' => 'Sanatçı silindi.',
        ]);
    }
}
