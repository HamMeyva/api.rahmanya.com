<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Http\JsonResponse;
use App\Models\LiveStreamCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LiveStreamCatagory\StoreRequest;
use App\Http\Requests\Admin\LiveStreamCatagory\UpdateRequest;

class LiveStreamCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.live-stream-categories.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = LiveStreamCategory::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        // Filters
        //----

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['id', 'name', 'display_order', 'is_active'];

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderColumn = $columns[$orderColumnIndex] ?? 'display_order';
        $orderDir = $request->input('order.0.dir', 'asc');

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $image = "<div class='symbol symbol-50px position-relative d-flex align-items-center'>
                <span class='symbol-label' style='background-image:url(" . asset('storage/' . $item->icon) . ");'></span>
            </div>";

            $limitedDesc = (new CommonHelper())->limitText($item->description);

            $column = "<div class='d-flex'>
                    {$image}
                    <div class='ms-5 d-flex flex-column justify-content-center'>
                        {$item->name}                            
                        <div class='text-muted fs-7 fw-bold'>{$limitedDesc}</div>
                    </div>
                </div>";

            return [
                $item->id,
                $column,
                $item->display_order,
                $item->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Pasif</span>',
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => true,
                    'editBtnClass' => 'editCategoryBtn',
                    'showDelete' => false,
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

    public function show($id): JsonResponse
    {
        $data = LiveStreamCategory::query()->find($id);
        if (!$data) {
            return response()->json([
                'message' => 'Kategori bulunamadı.'
            ], 404);
        }

        return response()->json([
            'data' => $data
        ]);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $category = new LiveStreamCategory();

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;
            $iconPath = $file->storeAs('live-stream-categories', $fileName, 'public');

            $category->icon = $iconPath;
        }

        $category->name = $request->input('name');
        $category->description = $request->input('description');
        $category->is_active = $request->has('is_active') ? true : false;
        $category->display_order = $request->input('display_order', 1);
        $category->save();

        return response()->json([
            'message' => 'Kategori başarıyla kaydedildi.'
        ]);
    }

    public function update(UpdateRequest $request, $id): JsonResponse
    {
        $category = LiveStreamCategory::query()->find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Kategori bulunamadı.'
            ], 404);
        }

        if ($request->input('logo_changed') == 1 && $request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;
            $iconPath = $file->storeAs('live-stream-categories', $fileName, 'public');

            $category->icon = $iconPath;
        }


        $category->name = $request->input('name');
        $category->description = $request->input('description');
        $category->is_active = $request->has('is_active') ? true : false;
        $category->display_order = $request->input('display_order', 1);
        $category->save();

        return response()->json([
            'message' => 'Kategori başarıyla düzenlendi.'
        ]);
    }
}
