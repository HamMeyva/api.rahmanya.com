<?php

namespace App\GraphQL\Resolvers;

use App\Models\Ad\AdClick;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AdClickResolver
{

    public function store($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $authUser = Auth::user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'ad_id' => 'required|exists:ads,id',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        /* 1. Reklam izlenmesini kaydet.*/
        AdClick::create([
            'ad_id' => (int) $input['ad_id'],
            'user_id' => $authUser?->id,
            'click_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        /* 2. Reklam metriklerini performans için redise at belirli süre sonra toplu kaydet. --FlushAdMetricsToAdsTable.php*/
        $adClick = AdClick::where('ad_id', (int)$input['ad_id'])->where('user_id', $authUser?->id)->exists();
        // Kullanıcı reklamı daha önce tıkladı ise tıklama artmamalı.
        if (!$adClick) {
            $adId = $input['ad_id'];
            $redisKey = "ad:{$adId}:metrics";
            Redis::hincrby($redisKey, 'clicks', 1);
        }

        return [
            'success' => true,
            'message' => 'Reklam tıklaması başarıyla kaydedildi.',
        ];
    }
}
