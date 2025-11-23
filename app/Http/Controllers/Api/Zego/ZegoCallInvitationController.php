<?php

namespace App\Http\Controllers\Api\Zego;

use App\Http\Controllers\Controller;
use App\Models\PKBattle;
use App\Models\ZegoRoomInvite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Zego Call Invitation Callback Controller
 * Handles callbacks from Zego Cloud for call invitations
 * Documentation: https://docs.zegocloud.com/article/15461
 */
class ZegoCallInvitationController extends Controller
{
    public $withinTransaction = false;

    /**
     * Handle Send Call Invitation callback
     * Called when a call invitation is sent
     */
    public function handleCallInvitationSent(Request $request)
    {
        Log::info('🎮 ZEGO: Call invitation sent callback', $request->all());

        $validator = Validator::make($request->all(), [
            'call_id' => 'required|string',
            'caller_id' => 'required|string',
            'callee_id' => 'required|string',
            'type' => 'nullable|string', // pk_battle, video_call, etc.
            'extra_info' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        try {
            // Create or update invitation record
            ZegoRoomInvite::updateOrCreate(
                ['call_id' => $request->call_id],
                [
                    'caller_id' => $request->caller_id,
                    'callee_id' => $request->callee_id,
                    'type' => $request->type ?? 'call',
                    'status' => 'sent',
                    'extra_info' => $request->extra_info,
                    'sent_at' => now(),
                ]
            );

            return response()->json(['success' => true, 'message' => 'Call invitation sent recorded']);

        } catch (\Exception $e) {
            Log::error('🎮 ZEGO: Error handling call invitation sent', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle Cancel Call Invitation callback
     * Called when a call invitation is cancelled by the caller
     */
    public function handleCallInvitationCancelled(Request $request)
    {
        Log::info('🎮 ZEGO: Call invitation cancelled callback', $request->all());

        try {
            $invite = ZegoRoomInvite::where('call_id', $request->call_id)->first();
            if ($invite) {
                $invite->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Call invitation cancelled recorded']);

        } catch (\Exception $e) {
            Log::error('🎮 ZEGO: Error handling call invitation cancelled', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle Accept Call Invitation callback
     * Called when a call invitation is accepted by the callee
     */
    public function handleCallInvitationAccepted(Request $request)
    {
        Log::info('🎮 ZEGO: Call invitation accepted callback', $request->all());

        try {
            $invite = ZegoRoomInvite::where('call_id', $request->call_id)->first();
            if ($invite) {
                $invite->update([
                    'status' => 'accepted',
                    'accepted_at' => now(),
                ]);

                // If this is a PK Battle invitation, start the battle
                if ($invite->type === 'pk_battle') {
                    $this->startPKBattleFromInvitation($invite);
                }
            }

            return response()->json(['success' => true, 'message' => 'Call invitation accepted recorded']);

        } catch (\Exception $e) {
            Log::error('🎮 ZEGO: Error handling call invitation accepted', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle Reject Call Invitation callback
     * Called when a call invitation is rejected by the callee
     */
    public function handleCallInvitationRejected(Request $request)
    {
        Log::info('🎮 ZEGO: Call invitation rejected callback', $request->all());

        try {
            $invite = ZegoRoomInvite::where('call_id', $request->call_id)->first();
            if ($invite) {
                $invite->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Call invitation rejected recorded']);

        } catch (\Exception $e) {
            Log::error('🎮 ZEGO: Error handling call invitation rejected', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle Call Invitation Timeout callback
     * Called when a call invitation times out
     */
    public function handleCallInvitationTimeout(Request $request)
    {
        Log::info('🎮 ZEGO: Call invitation timeout callback', $request->all());

        try {
            $invite = ZegoRoomInvite::where('call_id', $request->call_id)->first();
            if ($invite) {
                $invite->update([
                    'status' => 'timeout',
                    'timeout_at' => now(),
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Call invitation timeout recorded']);

        } catch (\Exception $e) {
            Log::error('🎮 ZEGO: Error handling call invitation timeout', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle PK Battle specific invitation
     */
    public function handlePKBattleInvite(Request $request)
    {
        Log::info('🎮 ZEGO: PK Battle invite callback', $request->all());

        $validator = Validator::make($request->all(), [
            'pk_battle_id' => 'required|string',
            'challenger_id' => 'required|string',
            'opponent_id' => 'required|string',
            'periods' => 'required|integer|min:1|max:10',
            'period_duration' => 'required|numeric|min:0.5|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid PK Battle payload'], 400);
        }

        try {
            // Create PK Battle invitation record
            ZegoRoomInvite::create([
                'call_id' => $request->pk_battle_id,
                'caller_id' => $request->challenger_id,
                'callee_id' => $request->opponent_id,
                'type' => 'pk_battle',
                'status' => 'sent',
                'extra_info' => json_encode([
                    'periods' => $request->periods,
                    'period_duration' => $request->period_duration,
                ]),
                'sent_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'PK Battle invitation sent']);

        } catch (\Exception $e) {
            Log::error('�� ZEGO: Error handling PK Battle invite', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle PK Battle response (accept/reject)
     */
    public function handlePKBattleResponse(Request $request)
    {
        Log::info('🎮 ZEGO: PK Battle response callback', $request->all());

        try {
            $invite = ZegoRoomInvite::where('call_id', $request->pk_battle_id)->first();
            
            if (!$invite) {
                return response()->json(['error' => 'PK Battle invitation not found'], 404);
            }

            $invite->update([
                'status' => $request->response, // accepted or rejected
                $request->response . '_at' => now(),
            ]);

            if ($request->response === 'accepted') {
                $this->startPKBattleFromInvitation($invite);
            }

            return response()->json(['success' => true, 'message' => 'PK Battle response recorded']);

        } catch (\Exception $e) {
            Log::error('🎮 ZEGO: Error handling PK Battle response', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Start PK Battle from accepted invitation
     */
    private function startPKBattleFromInvitation(ZegoRoomInvite $invite)
    {
        try {
            $extraInfo = json_decode($invite->extra_info, true);
            
            PKBattle::create([
                'room_id' => $invite->call_id,
                'challenger_id' => $invite->caller_id,
                'opponent_id' => $invite->callee_id,
                'status' => 'active',
                'periods' => $extraInfo['periods'] ?? 3,
                'period_duration' => $extraInfo['period_duration'] ?? 5.0,
                'current_period' => 1,
                'challenger_score' => 0,
                'opponent_score' => 0,
                'started_at' => now(),
            ]);

            Log::info('🎮 ZEGO: PK Battle started from invitation', ['call_id' => $invite->call_id]);

        } catch (\Exception $e) {
            Log::error('🎮 ZEGO: Error starting PK Battle from invitation', [
                'error' => $e->getMessage(),
                'invite_id' => $invite->id
            ]);
        }
    }
}
