<?php

namespace App\Http\Controllers\Api\v1\Traits;

use App\Models\Agora\AgoraChannel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;

trait AgoraTokenTrait
{
    public function createChannel(Request $request): JsonResponse
    {
        $channelUniqueName = Str::uuid()->toString() . time();

        $validate = Validator::make($request->all(), [
            'uid' => 'required', // user id
            'language' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'response' => $validate->errors(),
                'message' => 'Validation Error.',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::find($request->uid);

        if (!$user) {
            return response()->json([
                'success' => false,
                'response' => [],
                'message' => 'User not found.',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = $this->generateToken(
            $request->get('uid'),
            $channelUniqueName
        );

        $user->agora_channel()->create([
            'channel_name' => $channelUniqueName,
            'is_online' => true,
            'language' => $request->language,
        ]);

        return response()->json([
            'success' => true,
            'is_broadcaster' => true,
            'channel_name' => $channelUniqueName,
            'channel_token' => $token,
        ], JsonResponse::HTTP_CREATED);
    }

    public function joinChannel(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'uid' => 'required', // user id
            'language' => 'required',
            'channel_name' => 'required|string',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'response' => $validate->errors(),
                'message' => 'Validation Error.',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::find($request->uid);

        if (!$user) {
            return response()->json([
                'success' => false,
                'response' => [],
                'message' => 'User not found.',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $channel = AgoraChannel::query()
            ->where('is_online', true)
            ->where('channel_name', $request->channel_name)
            ->first();

        if (!$channel) {
            return response()->json([
                'success' => false,
                'response' => [],
                'message' => 'Channel not found or currently not streaming',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);

        }

        $token = $this->generateToken(
            $request->get('uid'),
            $request->get('channel_name')
        );

        return response()->json([
            'success' => true,
            'is_broadcaster' => false,
            'channel_token' => $token,
        ], JsonResponse::HTTP_CREATED);
    }

    private function generateToken($uuid = null, $channel = null): string
    {
        $appId = config('services.agora.app_id');
        $appCertificate = config('services.agora.app_certificate');
        $channelName = $channel;
        $uid = $uuid;
        $expirationTimeInSeconds = 86400;
        $currentTimeStamp = time();
        $privilegeExpiredTs = $currentTimeStamp + $expirationTimeInSeconds;

        return RtcTokenBuilder::buildTokenWithUid($appId, $appCertificate, $channelName, $uid, RtcTokenBuilder::RolePublisher, $privilegeExpiredTs);

    }
}
