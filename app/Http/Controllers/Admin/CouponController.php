<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\Common\Country;
use App\Models\Common\Currency;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Coupon\StoreRequest;
use App\Http\Requests\Admin\Coupon\UpdateRequest;

class CouponController extends Controller
{
    public function index()
    {
        return view('admin.pages.coupons.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = Coupon::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('code', 'ILIKE', "%{$search}%");
        }

        // Filters
        //...


        // Order by
        $columns = ['id', 'code', 'discount_type', 'discount_amount', 'start_date', 'end_date', 'is_active', 'max_usage', 'usage_count', 'country_id'];

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
            $statusText = $item->is_active ? 'Aktif' : 'Pasif';
            $statusBg = $item->is_active ? 'success' : 'danger';
            $status = "<span class='badge badge-{$statusBg}'>{$statusText}</span>";

            return [
                $item->id,
                $item->code,
                $item->get_discount_type,
                $item->draw_discount_amount,
                $item->get_start_date,
                $item->get_end_date,
                $status,
                $item->max_usage,
                $item->usage_count,
                $item->country?->native ?? '-',
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => true,
                    'editUrl' => route('admin.coupons.edit', ['coupon' => $item->id]),
                    'showDelete' => true,
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

    public function create()
    {
        return view('admin.pages.coupons.create-edit');
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['is_active'] = $request->input('is_active') ? 1 : 0;
        $validatedData['start_date'] = Carbon::parse($validatedData['start_date']);
        $validatedData['end_date'] = Carbon::parse($validatedData['end_date']);
        $country = Country::find($validatedData['country_id']);
        $currency = Currency::where('code', $country->currency)->first();
        $validatedData['currency_id'] = $currency ? $currency->id : 2; // default USD

        Coupon::create($validatedData);

        return response()->json([
            'message' => 'Kupon kodu başarıyla oluşturuldu.',
            'redirect' => route('admin.coupons.index')
        ]);
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.pages.coupons.create-edit', compact('coupon'));
    }

    public function update(UpdateRequest $request, Coupon $coupon): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['is_active'] = $request->input('is_active') ? 1 : 0;
        $validatedData['start_date'] = Carbon::parse($validatedData['start_date']);
        $validatedData['end_date'] = Carbon::parse($validatedData['end_date']);
        $country = Country::find($validatedData['country_id']);
        $currency = Currency::where('code', $country->currency)->first();
        $validatedData['currency_id'] = $currency ? $currency->id : 2; // default USD

        $coupon->update($validatedData);

        return response()->json([
            'message' => 'Kupon kodu başarıyla güncellendi.',
            'redirect' => route('admin.coupons.index')
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json([
                'message' => 'Kupon kodu bulunamadı.'
            ], 404);
        }
        $coupon->delete();

        return response()->json([
            'message' => 'Kupon kodu başarıyla silindi.'
        ]);
    }
}
