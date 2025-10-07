<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Team\StoreRequest;
use App\Http\Requests\Admin\Team\UpdateRequest;
use App\Models\Relations\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.settings.teams.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = Team::query();

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
        $columns = ['id', 'name'];

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $color1 = $item->colors['color1'] ?? '#000000';
            $color2 = $item->colors['color2'] ?? '#000000';
            $color = "<div class='d-flex'><span class='rounded-start-2' style='background-color: {$color1}; width: 25px; height: 25px; border: 1px solid #dad3d3;'></span><span class='rounded-end-2' style='background-color: {$color2}; width: 25px; height: 25px; border: 1px solid #dad3d3;'></span></div>";
            $logo = $item->logo ? '<div class="symbol symbol-50px">
                <span class="symbol-label" style="background-image:url(' . $item->logo . ');"></span>
            </div>' : '-';

            return [
                $item->id,
                $item->name,
                $color,
                $logo,
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => true,
                    'editBtnClass' => 'editTeamBtn',
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
        $data = Team::query()->find($id);
        if (!$data) {
            return response()->json([
                'message' => 'Takım bulunamadı.'
            ], 404);
        }

        return response()->json([
            'data' => $data
        ]);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $team = new Team();

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;
            $logoPath = $file->storeAs('teams', $fileName, 'public');

            $team->logo = $logoPath;
        }

        $team->name = $request->input('name');
        $team->colors = [
            'color1' => $request->input('color1'),
            'color2' => $request->input('color2'),
        ];
        $team->save();

        return response()->json([
            'message' => 'Takım başarıyla kaydedildi.'
        ]);
    }

    public function update(UpdateRequest $request, $id): JsonResponse
    {
        $team = Team::query()->find($id);
        if (!$team) {
            return response()->json([
                'message' => 'Takım bulunamadı.'
            ], 404);
        }

        if ($request->input('logo_changed') == 1 && $request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;
            $logoPath = $file->storeAs('teams', $fileName, 'public');

            $team->logo = $logoPath;
        }

        $team->name = $request->input('name');
        $team->colors = [
            'color1' => $request->input('color1'),
            'color2' => $request->input('color2'),
        ];
        $team->save();

        return response()->json([
            'message' => 'Takım başarıyla düzenlendi.'
        ]);
    }
}
