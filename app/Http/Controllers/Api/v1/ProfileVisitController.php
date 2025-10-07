<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileVisitController extends Controller
{
    public function __invoke($userId, Request $request)
    {
        $me = $request->user();
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'response' => [],
                'message' => 'User not found',
            ]);
        }

        if ($user->is_private) {
            return response()->json([
                'success' => false,
                'response' => [],
                'message' => 'User is private',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        if ($user->blocked_users()->whereAll(['user_id', 'blocked_id'], $me->id)->exists()) {
             return response()->json([
                 'success' => false,
                 'response' => null,
                 'message' => 'User has blocked you',
             ], JsonResponse::HTTP_FORBIDDEN);
        }

        $user->load(['videos', 'followers', 'following', 'primary_team', 'user_teams', 'agora_channel']);

        return response()->json([
            'success' => true,
            'response' => [
                'user' => UserResource::make($user),
            ],
        ]);
    }
}
