<?php

namespace App\GraphQL\Resolvers;

use Exception;
use App\Models\Gift;
use App\Models\GiftBasket;

use Illuminate\Support\Facades\Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Jobs\GiftBasket\GiftBasketGiftSalesUpdate;
use App\Notifications\AddedGiftToBasketNotification;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use App\Jobs\GiftBasket\StoreCoinTransactionForGiftPurchase;

class GiftBasketResolver
{
    public function getGiftBasket($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];

        $authUser = Auth::user();
        $limit = $input['limit'] ?? 10;
        $page = $input['page'] ?? 1;

        return $authUser->gift_baskets()->paginate($limit, ['*'], 'page', $page);
    }

    public function addGiftToBasket($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $authUser = Auth::user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'gift_id' => 'required',
            'quantity' => 'required|numeric|min:1|max:1000',
            'custom_unit_price' => 'nullable|numeric|min:1',
        ], [
            'gift_id.required' => __('validation.required', ['attribute' => 'Hediye']),
            'quantity.required' => __('validation.required', ['attribute' => 'Miktar']),
            'quantity.numeric' => __('validation.numeric', ['attribute' => 'Miktar']),
            'quantity.min' => __('validation.min.numeric', ['attribute' => 'Miktar', 'min' => 1]),
            'quantity.max' => __('validation.max.numeric', ['attribute' => 'Miktar', 'max' => 1000]),
            'custom_unit_price.numeric' => __('validation.numeric', ['attribute' => 'Fiyat']),
            'custom_unit_price.min' => __('validation.min.numeric', ['attribute' => 'Fiyat', 'min' => 1]),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        try {
            $gift = Gift::find($input['gift_id']);
            if (!$gift) {
                return [
                    'success' => false,
                    'message' => 'Hediye sistemde bulunamadı. Başka bir hediye seçiniz.'
                ];
            }
            //* Toplam Ödenecek Coin Tutarı Hesapla
            // Hediye is_custom_gift ise kullanıcının belirlediği coin miktarı seçiliyor.
            $totalCost = $gift->get_final_price * $input['quantity'];
            if ($gift->is_custom_gift) {
                $totalCost = $input['custom_unit_price'] * $input['quantity'];
            }

            // 1. Bakiye kontrolü ve yereli bakiye var ise cüzdandan düşürme işlemi.
            if ($authUser->coin_balance < $totalCost) throw new Exception("Kullanıcının bakiyesi yetersiz.");

            $authUser->decrement("coin_balance", $totalCost);

            // 2. Gift Baskete kayıt oluştur.
            $giftBasket = GiftBasket::create([
                'user_id' => $authUser->id,
                'gift_id' => $gift->id,
                'custom_unit_price' => $input['custom_unit_price'],
                'quantity' => $input['quantity'],
            ]);

            // 3. Kullanıcıya bildirim gönder.
            $authUser->notify(new AddedGiftToBasketNotification($gift, $giftBasket));

            // 4. Hediye işlemleri için gerekli loglamalar ve veritabanı güncellemeleri arka planda paralel çalışacak.
            dispatch(new StoreCoinTransactionForGiftPurchase($authUser, $gift, $giftBasket, $totalCost));
            dispatch(new GiftBasketGiftSalesUpdate($gift, $totalCost, $input['quantity']));

            return [
                'success' => true,
                'message' => 'Hediye başarıyla çantaya eklendi.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
