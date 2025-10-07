<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CommonHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Morph\ReportProblem;
use App\Http\Controllers\Controller;
use App\Models\Video;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReportProblemController extends Controller
{
    public function index()
    {
        return view('admin.pages.report-problems.index');
    }

    public function dataTable(Request $request): JsonResponse
    {

        $query = ReportProblem::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('message', 'ILIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->filled('report_problem_category_id')) {
            $query->where('report_problem_category_id', $request->input('report_problem_category_id'));
        }

        if ($request->filled('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['id', 'entity_type', 'user_id', 'status_id', 'report_problem_category_id', 'message', 'admin_id', 'admin_response'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'id';
            $orderDir = 'desc';
        }

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $statusColor = ReportProblem::$statusColors[$item->status_id];
            $status = "<span class='badge text-white' style='background-color:{$statusColor}'>{$item->get_status}</span>";
            return [
                $item->id,
                "<span class='badge badge-secondary'>{$item->get_entity_type}</span>",
                $item->user?->full_name ?? '-',
                $status,
                $item->report_problem_category->name,
                (new CommonHelper())->limitText($item->message),
                $item?->admin?->full_name ?? '-',
                (new CommonHelper())->limitText($item->admin_response ?? '-'),
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showView' => true,
                    'viewUrl' => route('admin.report-problems.show', ['id' => $item->id]),
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

    public function show($id)
    {
        $reportProblem = ReportProblem::find($id);
        if (!$reportProblem) {
            throw new NotFoundHttpException();
        }

        return view('admin.pages.report-problems.show', compact('reportProblem'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'admin_id'=> 'nullable|exists:admins,id',
            'status_id'=> 'required|in:' . implode(',', array_keys(ReportProblem::$statuses)),
            'admin_response'=> 'nullable|string|max:500',
        ], [
            'admin_response.max' => 'Admin notu en fazla 500 karakter olabilir.',
        ]);

        $reportProblem = ReportProblem::find($id);
        if (!$reportProblem) {
            return response()->json([
                'message' => 'Şikayet bulunamadı.'
            ], 404);
        }

        $reportProblem->admin_id = $request->input('admin_id');
        $reportProblem->status_id = $request->input('status_id');
        $reportProblem->admin_response = $request->input('admin_response');

        $reportProblem->save();

        return response()->json([
            'message' => 'Şikayet başarıyla güncellendi.'
        ]);
    }
}
