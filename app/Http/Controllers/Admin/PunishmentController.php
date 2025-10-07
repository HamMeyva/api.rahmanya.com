<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Punishment\StoreRequest;
use App\Http\Requests\Admin\Punishment\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Punishment;
use App\Models\PunishmentCategory;

class PunishmentController extends Controller
{
    public function index()
    {
        return view('admin.pages.settings.punishments.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = Punishment::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('description', 'ILIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('card_type_id')) {
            $query->where('card_type_id', $request->input('card_type_id'));
        }


        // Order by
        $columns = ['description'];

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

        $data = $list->map(function ($item) {
            $cardBg = $item->card_type_id == Punishment::YELLOW_CARD ? 'yellow' : 'red';
            $image = "<div class='symbol symbol-50px position-relative d-flex align-items-center'>
                <span class='symbol-label' style='background-image:url(" . assetAdmin("images/icon-filled.svg") . ");'></span>
                <div class='punishment-card' data-card='{$cardBg}'></div>
            </div>";

            $column = "<div class='d-flex'>
                        {$image}
                        <div class='ms-5'>
                            {$item->get_card_type}                            
                            <div class='text-muted fs-7 fw-bold'>{$item->description}</div>
                        </div>
                    </div>";

            $isPunishment = $item->is_direct_punishment ? 'check text-success' : 'xmark text-danger';


            return [
                $column,
                "<span class='d-flex justify-content-center'><i class='fa fa-{$isPunishment}'></i></span>",
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => true,
                    'editBtnClass' => 'editPunishmentBtn',
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

    public function store(StoreRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['is_direct_punishment'] = $request->has('is_direct_punishment');

        Punishment::create($validatedData);

        return response()->json([
            'message' => 'Ceza başarıyla eklendi'
        ]);
    }

    public function show($id): JsonResponse
    {
        $punishment = Punishment::find($id);
        if (!$punishment) {
            return response()->json([
                'message' => 'Ceza bulunamadı.'
            ], 404);
        }

        return response()->json([
            'data' => $punishment
        ]);
    }

    public function update(UpdateRequest $request, $id): JsonResponse
    {
        $punishment = Punishment::find($id);
        if (!$punishment) {
            return response()->json([
                'message' => 'Ceza bulunamadı.'
            ], 404);
        }

        $validatedData = $request->validated();
        $validatedData['is_direct_punishment'] = $request->has('is_direct_punishment');

        $punishment->update($validatedData);

        return response()->json([
            'message' => 'Ceza başarıyla güncellendi'
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $punishment = Punishment::find($id);
        if (!$punishment) {
            return response()->json([
                'message' => 'Ceza bulunamadı.'
            ], 404);
        }

        $punishment->delete();

        return response()->json([
            'message' => 'Ceza başarıyla silindi'
        ]);
    }
}
