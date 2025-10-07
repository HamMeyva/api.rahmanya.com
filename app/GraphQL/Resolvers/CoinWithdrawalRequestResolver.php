<?php

namespace App\GraphQL\Resolvers;

use App\Helpers\Variable;
use App\Models\Coin\CoinWithdrawalPrice;
use Illuminate\Support\Facades\Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use App\Models\Coin\CoinWithdrawalRequest;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CoinWithdrawalRequestResolver
{
    public function index($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];

        $authUser = Auth::user();
        $limit = $input['limit'] ?? 10;
        $page = $input['page'] ?? 1;

        return $authUser->coin_withdrawal_requests()->paginate($limit, ['*'], 'page', $page);
    }

    public function store($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $authUser = Auth::user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'coin_amount' => 'required|integer',
            'currency_id' => 'required|exists:currencies,id'
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        /* 1. Gelen talep min çekim tutarının üzerinde mi? */
        $minWithdrawalAmount = 100;
        if ($minWithdrawalAmount < $input['coin_amount']) {
            return [
                'success' => false,
                'message' => "Çekim talebiniz minimum tutarın altında. En az {$minWithdrawalAmount} adet çekim yapabilirsiniz."
            ];
        }
        /* 2. Yeterli bakiye var mı? */
        if ($authUser->earned_coin_balance < $input['coin_amount']) {
            return [
                'success' => false,
                'message' => "Yeterli bakiyeniz yok."
            ];
        }

        /* 3. Aktif bir çekim talebli var mı? */
        $activeWithdrawalRequest = $authUser->coin_withdrawal_requests()->where('status_id', CoinWithdrawalRequest::STATUS_PENDING)->first();
        if ($activeWithdrawalRequest) {
            return [
                'success' => false,
                'message' => "Henüz tamamlanmamış bir çekim talebiniz bulunuyor. Tamamlandıktan sonra yeni talep girebilirsiniz."
            ];
        }

        /* 4. Çekim talebi oluştur.*/
        $coinWithdrawalPrice = CoinWithdrawalPrice::where("currency_id", $input["currency_id"])->first();
        if (!$coinWithdrawalPrice) {
            return [
                'success' => false,
                'message' => "Belirtilen para birimi için çekim tutarı bulunamadı."
            ];
        }

        $withdrawalRequest = $authUser->coin_withdrawal_requests()->create([
            "coin_amount" => $input['coin_amount'],
            "coin_unit_price" => $coinWithdrawalPrice->coin_unit_price,
            "coin_total_price" => $input['coin_amount'] * $coinWithdrawalPrice->coin_unit_price,
            "currency_id" => $input['currency_id'],
            "wallet_type_id" => Variable::WALLET_TYPE_EARNED,
            "status_id" => CoinWithdrawalRequest::STATUS_PENDING,
        ]);

        if (!$withdrawalRequest) {
            return [
                'success' => false,
                'message' => "Sistemsel bir sorun oluştu talebiniz kaydedilemedi. Tekrar deneyin."
            ];
        }

        /* 4. Adminlere bildirim gönderilebilir.*/
        //... $adminIds->notify(....);

        return [
            'success' => true,
            'message' => 'Çekim talebinizi aldık. En kısa sürede dönüş sağlayacağız.',
            'withdrawal_request' => $withdrawalRequest,
        ];
    }

    public function getStatuses($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return collect(CoinWithdrawalRequest::$statuses)->map(function ($name, $id) {
            return [
                'id' => $id,
                'name' => $name
            ];
        })->toArray();
    }
}
