<?php

namespace App\GraphQL\Resolvers\Agora;

use Exception;
use App\Models\Agora\AgoraChannel;
use App\Models\Agora\AgoraChannelMessage;
use Illuminate\Support\Facades\Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\LiveStream\LiveStreamChatService;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AgoraMessageResolver
{
    protected $chatService;

    public function __construct(LiveStreamChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function sendMessageToStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args["input"];
        $user = $context->user();

        /** @var \App\Models\Agora\AgoraChannel $agoraChannel */
        $agoraChannel = AgoraChannel::find($input['agora_channel_id']);
        if (!$agoraChannel) {
            return [
                'success' => false,
                'message' => 'Canlı yayın bulunamadı.'
            ];
        }

        try {
            $this->chatService->sendMessage($agoraChannel, $user, $input['message']);

            return [
                'success' => true,
                'message' => 'Mesaj gönderildi'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getStreamMessages($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args["input"];

        $limit = $input['limit'] ?? 10;
        $page = $input['page'] ?? 1;
        $dir = $input['dir'] ?? 'desc';
        if (!in_array($dir, ['asc', 'desc'])) $dir = 'desc';


        /** @var \App\Models\Agora\AgoraChannel $agoraChannel */
        $agoraChannel = AgoraChannel::find($input['agora_channel_id']);
        if (!$agoraChannel) {
            return [
                'success' => false,
                'message' => 'Canlı yayın bulunamadı.'
            ];
        }

        if (!$agoraChannel->is_online) {
            return [
                'success' => false,
                'message' => 'Canlı yayın sonlandıgı için mesajlar gösterilemiyor.'
            ];
        }

        $query = AgoraChannelMessage::where('agora_channel_id', $agoraChannel->id)->orderBy('timestamp', $dir);

        return [
            'success' => true,
            'data' => $query->paginate($limit, ['*'], 'page', $page)
        ];
    }
}
