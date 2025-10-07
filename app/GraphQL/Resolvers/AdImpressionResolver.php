<?php

namespace App\GraphQL\Resolvers;

use App\Services\AdService;
use App\Models\Ad\AdImpression;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AdImpressionResolver
{

    public function store($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $authUser = Auth::user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'ad_id' => 'required|exists:ads,id',
            'duration' => 'required|integer',
            'is_completed' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        /* 1. Reklam izlenmesini kaydet.*/
        AdImpression::create([
            'ad_id' => (int) $input['ad_id'],
            'user_id' => $authUser?->id,
            'impression_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'duration' => $input['duration'],
            'is_completed' => $input['is_completed'],
        ]);

        /* 2. Reklam metriklerini performans için redise at belirli süre sonra toplu kaydet. --FlushAdMetricsToAdsTable.php*/
        $adImpression = AdImpression::where('ad_id', (int)$input['ad_id'])->where('user_id', $authUser?->id)->exists();
        // Kullanıcı reklamı daha önce izledi ise izlenme artmamalı.
        if (!$adImpression) {
            $adId = $input['ad_id'];
            $redisKey = "ad:{$adId}:metrics";
            Redis::hincrby($redisKey, 'impressions', 1);
        }

        /* 3. Cacheye kaydet. */
        app(AdService::class)->markAdAsSeen($authUser->id, $input['ad_id']);


        return [
            'success' => true,
            'message' => 'Reklam izlenmesi başarıyla kaydedildi.',
        ];
    }
}
