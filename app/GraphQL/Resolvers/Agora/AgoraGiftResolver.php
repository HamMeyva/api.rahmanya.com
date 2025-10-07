<?php

namespace App\GraphQL\Resolvers\Agora;

use Exception;
use App\Models\Agora\AgoraChannel;
use App\Models\Challenge\Challenge;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use App\Jobs\Challenges\ProcessChallengeRound;
use Illuminate\Validation\ValidationException;
use App\Services\LiveStream\LiveStreamGiftService;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AgoraGiftResolver
{
    protected $liveStreamGiftService;

    public function __construct(LiveStreamGiftService $liveStreamGiftService)
    {
        $this->liveStreamGiftService = $liveStreamGiftService;
    }

    public function sendGiftToStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $context->user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'agora_channel_id' => 'required',
            'recipient_user_id' => 'required',
            'gift_basket_id' => 'required',
            'quantity' => 'required|integer|min:1|max:1000'
        ], [
            'agora_channel_id.required' => __('validation.required', ['attribute' => 'Canlı yayın']),
            'recipient_user_id.required' => __('validation.required', ['attribute' => 'Alıcı']),
            'gift_basket_id.required' => __('validation.required', ['attribute' => 'Hediye']),
            'quantity.required' => __('validation.required', ['attribute' => 'Miktar']),
            'quantity.numeric' => __('validation.numeric', ['attribute' => 'Miktar']),
            'quantity.min' => __('validation.min.numeric', ['attribute' => 'Miktar', 'min' => 1]),
            'quantity.max' => __('validation.max.numeric', ['attribute' => 'Miktar', 'max' => 1000]),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        try {
            $this->liveStreamGiftService->sendGift($input, $authUser);

            return [
                'success' => true,
                'message' => 'Hediye başarıyla gönderildi'
            ];
        } catch (Exception $e) {
            Log::error('Failed to send gift to stream.', [
                'user_id' => $authUser->id ?? null,
                'agora_channel_id' => $input['agora_channel_id'] ?? null,
                'gift_basket_id' => $input['gift_basket_id'] ?? null,
                'quantity' => $input['quantity'] ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getGiftsByChannelId($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];

        $limit = $input['limit'] ?? 10;
        $page = $input['page'] ?? 1;
        $dir = $input['dir'] ?? 'desc';
        if (!in_array($dir, ['asc', 'desc'])) $dir = 'desc';
        
        $agoraChannel = AgoraChannel::find($input['agora_channel_id']);
        if (!$agoraChannel) {
            return [
                'success' => false,
                'message' => 'Canlı yayın kanalı bulunamadı.',
            ];
        }

        return $agoraChannel->gifts()->paginate($limit, ['*'], 'page', $page);
    }
}
