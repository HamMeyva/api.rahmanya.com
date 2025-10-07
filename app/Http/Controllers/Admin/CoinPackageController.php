<?php

namespace App\Http\Controllers\Admin;

use App\Models\Coin\CoinPackage;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CoinPackage\StoreRequest;
use App\Http\Requests\Admin\CoinPackage\UpdateRequest;

class CoinPackageController extends Controller
{
    public function index()
    {
        return view('admin.pages.coin-packages.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = CoinPackage::query();

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            //$query->where('coin_amount', 'ILIKE', "%{$search}%");
        }

        // Filters
        //...


        // Order by
        $columns = ['coin_amount', 'price', 'currency_id', 'is_active', 'country_id'];

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
            $price = $item->is_discount ? "<del class='fs-7 text-danger'>{$item->draw_price}</del><div class='fw-bold'>{$item->draw_final_price}</div>" : "{$item->draw_price}";
            $status = $item->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Pasif</span>';

            return [
                $item->id,
                "{$item->coin_amount} Adet",
                $price,
                $item->currency->name,
                $status,
                $item->country->native,
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => true,
                    'editBtnClass' => 'editBtn',
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

    public function store(StoreRequest $request)
    {
        foreach ($request->input('package_repeater_condition_area') as $condition) {
            CoinPackage::query()->create([
                'coin_amount' => $condition['coin_amount'],
                'price' => (new CommonHelper)->formatDecimalInput($condition['price']),
                'is_discount' => $condition['discounted_price'] ? true : false,
                'discounted_price' => (new CommonHelper)->formatDecimalInput($condition['discounted_price']),
                'currency_id' => $condition['currency_id'],
                'is_active' => true,
                'country_id' => $condition['country_id'],
            ]);
        }

        return response()->json([
            'message' => 'Coin Paketleri başarıyla kaydedildi'
        ]);
    }

    public function show(string $id)
    {
        $coinPackage = CoinPackage::find($id);
        if (!$coinPackage) {
            return response()->json([
                'message' => "Coin Paketi bulunamadı.",
            ], 404);
        }
        return response()->json([
            'data' => $coinPackage
        ]);
    }

    public function update(UpdateRequest $request, string $id)
    {
        $coinPackage = CoinPackage::find($id);
        if (!$coinPackage) {
            return response()->json([
                'message' => "Coin Paketi bulunamadı.",
            ], 404);
        }

        $validatedData = $request->validated();
        $validatedData['price'] = (new CommonHelper)->formatDecimalInput($validatedData['price']);
        $validatedData['is_discount'] = $request->input('discounted_price') ? true : false;
        $validatedData['discounted_price'] = $request->input('discounted_price') ? (new CommonHelper)->formatDecimalInput($validatedData['discounted_price']) : null;
        $validatedData['is_active'] = $request->input('is_active') ? true : false;

        $coinPackage->update($validatedData);

        return response()->json([
            'message' => "Coin Paketi başarıyla güncellendi.",
        ]);
    }

    public function destroy(string $id)
    {
        $coinPackage = CoinPackage::find($id);
        if (!$coinPackage) {
            return response()->json([
                'message' => "Coin Paketi bulunamadı.",
            ], 404);
        }
        $coinPackage->delete();

        return response()->json([
            'message' => "Coin Paketi başarıyla silindi.",
        ]);
    }
}
