<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use App\Models\Coin\CoinWithdrawalPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoinWithdrawalPriceController extends Controller
{
    public function index()
    {
        $prices = CoinWithdrawalPrice::all();
        return view('admin.pages.coin-withdrawal-prices.index', compact('prices'));
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        foreach ($request->input('prices') as $id => $data) {
            $model = CoinWithdrawalPrice::find($id);
            if(!$model) continue;
      
            $model->coin_unit_price = (new CommonHelper())->formatDecimalInput($data['coin_unit_price']);
            $model->save();
        }

        return response()->json([
            'message' => 'Çekim fiyatları başarıyla güncellendi.'
        ]);
    }
}
