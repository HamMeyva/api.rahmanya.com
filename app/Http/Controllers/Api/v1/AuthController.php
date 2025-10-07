<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Relations\Team;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Random\RandomException;
use App\Services\Sms\Netgsm;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'nickname' => 'required|string|max:255|unique:users,nickname',
            'gender_id' => 'required|exists:gender,id',
            'birthday' => 'required|date',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|regex:/^[0-9]{10,15}$/',
            'password' => 'required|string|min:8|confirmed',
            'fcm_token' => 'required|string',
            'primary_team_id' => 'required|integer',
            'user_teams' => 'nullable|array',
            'preferred_language_id' => 'required|exists:languages,id',

            'device_type' => 'required|string|max:255',
            'device_os' => 'required|string|max:255',
            'device_os_version' => 'required|string|max:255',
            'device_model' => 'required|string|max:255',
            'device_brand' => 'required|string|max:255',
            'device_unique_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation failed',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();

            $otp = random_int(100000, 999999);
            $user = User::create([
                'name' => $request->name,
                'surname' => $request->surname,
                'nickname' => $request->nickname,
                'gender_id' => $request->gender_id,
                'birthday' => $request->birthday,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'fcm_token' => $request->fcm_token,
                'primary_team_id' => $request->primary_team_id,
                'preferred_language_id' => $request->preferred_language_id,
                'agora_uid' => random_int(100000, 9999999),
            ]);

            if ($request->has('user_teams')) {
                $user->user_teams()->sync($request->user_teams);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // Eğer web oturumu varsa, polimorfik ilişki için session'ı güncelle
            if ($request->hasSession()) {
                $sessionId = $request->session()->getId();
                
                // Optimize session update by using a single query without first fetching
                // This reduces the number of database queries from 2 to 1
                \DB::table('sessions')
                    ->where('id', $sessionId)
                    ->update([
                        'sessionable_id' => $user->id,
                        'sessionable_type' => User::class,
                        'last_activity' => time() // Update last_activity at the same time
                    ]);
            }

            DB::commit(); // **İşlem başarılıysa commit et**

            return response()->json([
                'access_token' => 'Bearer ' . $token,
                'response' => UserResource::make($user),
                'message' => 'User registered successfully.',
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack(); // **Hata olursa rollback yap**

            \Log::error("User registration failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration.',
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Login user and create token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nickname' => 'required',
            'password' => 'required',
            'fcm_token' => 'required|string',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response' => $validator->errors(),
                'message' => 'Validation failed',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('nickname', $request->nickname)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'response' => null,
                'message' => 'Invalid Credentials.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user->update([
            'fcm_token' => $request->input('fcm_token'),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->deviceRegister($user, $token, $request);

        // Eğer web oturumu varsa, polimorfik ilişki için session'ı güncelle
        if ($request->hasSession()) {
            $sessionId = $request->session()->getId();
            
            // Optimize session update by using a single query without first fetching
            // This reduces the number of database queries from 2 to 1
            \DB::table('sessions')
                ->where('id', $sessionId)
                ->update([
                    'sessionable_id' => $user->id,
                    'sessionable_type' => User::class,
                    'last_activity' => time() // Update last_activity at the same time
                ]);
        }

        return response()->json([
            'access_token' => 'Bearer ' . $token,
            'response' => UserResource::make($user),
        ], JsonResponse::HTTP_OK);

    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();


        return response()->json([
            'success' => true,
            'response' => null,
            'message' => 'Logged out successfully.'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Send a reset password link.
     */
    public function sendResetPasswordLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response' => null,
                'message' => 'Invalid Email Provided.'
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'response' => null,
                'message' => 'Password reset link sent.'
            ], JsonResponse::HTTP_OK);
        }

        return response()->json([
            'success' => false,
            'response' => null,
            'message' => 'Failed to send password reset link.'
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);

    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response' => null,
                'message' => 'Invalid Token.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'response' => null,
                'message' => 'Password reset successfully.'
            ], JsonResponse::HTTP_OK);
        }

        return response()->json([
            'success' => false,
            'response' => null,
            'message' => 'Password reset failed.'
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);

    }

    /**
     * Verify email.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'response' => null,
                'message' => 'Email already verified.',
            ], JsonResponse::HTTP_OK);
        }

        $request->user()->markEmailAsVerified();

        return response()->json([
            'success' => true,
            'response' => null,
            'message' => 'Email verified successfully.'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Resend email verification link.
     */
    public function resendEmailVerification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'response' => null,
                'message' => 'Email already verified.'
            ], JsonResponse::HTTP_OK);

        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'response' => null,
            'message' => 'Email verification link sent.'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Phone verification with OTP.
     */
    public function verifyPhone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response' => $validator->errors(),
                'message' => 'Invalid OTP.'
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Example OTP verification logic
        $otpRecord = DB::table('otp_verifications')->where('phone', $request->phone)->first();

        if (!$otpRecord || $otpRecord->otp !== $request->otp) {
            return response()->json([
                'success' => false,
                'response' => null,
                'message' => 'Invalid OTP.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'response' => null,
                'message' => 'User not found.'
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Mark phone as verified
        $user->phone_verified_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'response' => null,
            'message' => 'Phone verified successfully.'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Send OTP to phone.
     * @throws RandomException
     */
    public function sendOTP(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|unique:users,phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response' => $validator->errors(),
                'message' => 'Invalid OTP.'
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $otp = random_int(100000, 999999);

        // Store OTP in database
        DB::table('otp_verifications')->updateOrInsert(
            ['phone' => $request->phone],
            ['otp' => $otp, 'created_at' => now()]
        );

        $message = 'SMS Validation Code: ' . $otp;
        $phoneNumber = $request->phone;

        $netgsm = new Netgsm();
        $netgsm->send($phoneNumber, $message);

        return response()->json([
            'success' => true,
            'response' => null,
            'message' => 'OTP sent successfully.'
        ], JsonResponse::HTTP_OK);
    }


    public function terminateOtherSessions($currentDeviceId, Request $request): void
    {
        $user = $request->user();

        $currentDevice = $user->devices()
            ->where('id', $currentDeviceId)
            ->first();

        $user->tokens()
            ->where('token', '!=', $currentDevice->token)
            ->delete();

        $user->devices()
            ->where('id', '!=', $currentDeviceId)
            ->delete();
    }

    private function deviceRegister($user, $token, Request $request): void
    {
        $user->devices()->create([
            'device_type' => $request->device_type,
            'device_os' => $request->device_os,
            'device_os_version' => $request->device_os_version,
            'device_model' => $request->device_model,
            'device_brand' => $request->device_brand,
            'device_ip' => $request->ip(),
            'device_unique_id' => $request->device_unique_id,
            'token' => $token,
        ]);
    }

    public function fetchAllTeams(): JsonResponse
    {
        $teams = Team::all();
        return response()->json([
            'success' => true,
            'response' => $teams,
        ]);
    }
}
