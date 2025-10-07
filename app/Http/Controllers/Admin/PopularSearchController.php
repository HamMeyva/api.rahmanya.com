<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PopularSearch;
use App\Services\BunnyCdnService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Http\Requests\Admin\PopularSearch\StoreRequest;
use App\Http\Requests\Admin\PopularSearch\UpdateRequest;

class PopularSearchController extends Controller
{
    public function index()
    {
        return view('admin.pages.settings.popular-searches.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = PopularSearch::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('title', 'ILIKE', "%{$search}%");
        }

        // Filters
        //...


        // Order by
        $columns = ['id', 'title', 'is_active', 'queue'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'queue';
        $orderDir = $request->input('order.0.dir', 'asc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'queue';
            $orderDir = 'asc';
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

        $data = $list->map(function ($item) {
            $image = "<div class='symbol symbol-75px'>
                <span class='symbol-label' style='background-image:url({$item?->image_url});'></span>
            </div>";

            $status = $item->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Pasif</span>';

            return [
                $image,
                $item->title,
                $status,
                $item->queue,
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => true,
                    'editBtnClass' => 'editPopularSearchBtn',
                    'showDelete' => true
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

    public function store(StoreRequest $request, BunnyCdnService $bunnyCdnService)
    {
        $validatedData = $request->validated();
        $validatedData['is_active'] = $request->boolean('is_active');
        if (!$request->input('queue')) {
            $maxQueue = PopularSearch::max('queue');
            $validatedData['queue'] = $maxQueue ? ($maxQueue + 1) : 1;
        }
        $popularSearch = PopularSearch::create($validatedData);

        $imageName = Str::uuid() . '.' . $request->file("image")->extension();
        $imagePath = "popular-searches/{$popularSearch->id}/images/{$imageName}";

        $popularSearch->image_path = $imagePath;
        $popularSearch->save();

        $bunnyCdnService->uploadToStorage($imagePath, $request->file("image")->get());

        return response()->json([
            'message' => 'Popüler arama başarıyla eklendi.'
        ]);
    }

    public function show(string $id)
    {
        $popularSearch = PopularSearch::find($id);
        if (!$popularSearch) {
            return response()->json([
                'message' => 'Popüler arama bulunamadı.'
            ], 404);
        }

        return response()->json([
            'data' => $popularSearch
        ]);
    }

    public function update(UpdateRequest $request, string $id)
    {
        $popularSearch = PopularSearch::find($id);
        if (!$popularSearch) {
            return response()->json([
                'message' => 'Popüler arama bulunamadı.'
            ], 404);
        }

        $validatedData = $request->validated();
        $validatedData['is_active'] = $request->boolean('is_active');
        if (!$request->input('queue')) {
            $maxQueue = PopularSearch::max('queue');
            $validatedData['queue'] = $maxQueue ? ($maxQueue + 1) : 1;
        }

        if ($request->input('image_changed') == 1 && $request->hasFile('image')) {
            $imageName = Str::uuid() . '.' . $request->file("image")->extension();
            $imagePath = "popular-searches/{$popularSearch->id}/images/{$imageName}";

            $validatedData['image_path'] = $imagePath;
        }

        $popularSearch->update($validatedData);

        return response()->json([
            'message' => 'Popüler arama başarıyla güncellendi.'
        ]);
    }

    public function destroy(string $id)
    {
        $popularSearch = PopularSearch::find($id);
        if (!$popularSearch) {
            return response()->json([
                'message' => 'Popüler arama bulunamadı.'
            ], 404);
        }

        $popularSearch->delete();

        return response()->json([
            'message' => 'Popüler arama başarıyla silindi.'
        ]);
    }
}
