<?php

namespace App\GraphQL\Resolvers;

use Exception;
use App\Events\Test;
use App\Events\UserOffline;
use App\Models\User;
use Illuminate\Support\Str;
use App\Services\Sms\Netgsm;
use Illuminate\Http\Request;
use App\Models\Relations\Team;
use App\Models\UserSessionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AuthResolver
{
    public function changePassword($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $this->getUser();
        $input = $args['input'];

        // Girdi doğrulama
        $validator = Validator::make($input, [
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            Log::warning('Şifre değiştirme doğrulama hatası', [
                'user_id' => $user->id,
                'errors' => $validator->errors()->toArray()
            ]);
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        // Mevcut şifreyi doğrula
        if (!Hash::check($input['current_password'], $user->password)) {
            Log::warning('Mevcut şifre yanlış', ['user_id' => $user->id]);
            throw new \Exception('Mevcut şifreniz yanlış.');
        }

        // Yeni şifreyi güncelle
        $user->password = Hash::make($input['new_password']);
        $user->save();

        Log::info('Şifre başarıyla güncellendi', ['user_id' => $user->id]);

        return [
            'status' => 'SUCCESS',
            'message' => 'Şifreniz başarıyla güncellendi.'
        ];
    }

    private function getUser()
    {
        $user = Auth::user();
        if (!$user) {
            throw new Exception("Yetkisiz erişim.");
        }
        return $user;
    }

    protected function generateToken(User $user)
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    protected function generateUniqueAgoraUid(): int
    {
        $attempts = 0;
        $maxAttempts = 10;

        do {
            $uid = random_int(100000, 9999999);
            $exists = \App\Models\User::where('agora_uid', $uid)->exists();
            $attempts++;
        } while ($exists && $attempts < $maxAttempts);

        if ($exists) {
            // If we still have a collision after max attempts, use a larger range
            $uid = random_int(10000000, 999999999);
        }

        return $uid;
    }

    public function terminateOtherSessions($root, array $args, GraphQLContext $context)
    {
        $user = $this->getUser();
        $currentDeviceId = $args['device_id'] ?? null;

        if (!$currentDeviceId) {
            throw new \Exception("Cihaz ID'si gereklidir.");
        }

        // Mevcut cihazı kontrol et
        $currentDevice = $user->devices()
            ->where('device_unique_id', $currentDeviceId)
            ->first();

        if (!$currentDevice) {
            throw new \Exception("Belirtilen cihaz bulunamadı.");
        }

        // Diğer cihazlara ait tokenları sil
        $user->tokens()
            ->whereNotIn('id', [$currentDevice->token_id])
            ->delete();

        // Diğer cihazları sil
        $user->devices()
            ->where('device_unique_id', '!=', $currentDeviceId)
            ->delete();

        // Bağlantılı hesaplara ait oturumları da sonlandır
        if ($user->account_type === 'primary') {
            // Ana hesap ise, bağlı tüm alt hesapların oturumlarını sonlandır
            $linkedAccounts = $user->secondaryAccounts;
            foreach ($linkedAccounts as $linkedAccount) {
                $linkedAccount->tokens()->delete();
                $linkedAccount->devices()->delete();
            }
        } elseif ($user->account_type === 'secondary' && $user->parent_user_id) {
            // Alt hesap ise, ana hesap ve diğer alt hesapların oturumlarını sonlandır
            $parentUser = $user->parentUser;
            if ($parentUser) {
                $parentUser->tokens()->delete();
                $parentUser->devices()->delete();

                // Diğer alt hesaplar
                $otherSecondaryAccounts = $parentUser->secondaryAccounts()
                    ->where('id', '!=', $user->id)
                    ->get();

                foreach ($otherSecondaryAccounts as $otherAccount) {
                    $otherAccount->tokens()->delete();
                    $otherAccount->devices()->delete();
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Diğer tüm oturumlar sonlandırıldı.'
        ];
    }

    public function requestAccountDeletion($root, array $args, GraphQLContext $context)
    {
        $user = $this->getUser();
        $input = $args['input'] ?? [];
        $reason = $input['reason'] ?? null;

        // Kullanıcının zaten aktif bir silme isteği var mı kontrol et
        if ($user->hasActiveDeletionRequest()) {
            return [
                'success' => false,
                'message' => 'Zaten aktif bir hesap silme isteğiniz bulunmaktadır.'
            ];
        }

        // Yeni hesap silme isteği oluştur
        $deletionRequest = new \App\Models\AccountDeletionRequest([
            'user_id' => $user->id,
            'reason' => $reason,
            'status' => 'pending'
        ]);

        $deletionRequest->save();

        // Yöneticilere bildirim gönder
        // TODO: Bildirim sistemi entegrasyonu

        return [
            'success' => true,
            'message' => 'Hesap silme isteğiniz alınmıştır. İsteğiniz yöneticiler tarafından incelenecektir.'
        ];
    }

    private function deviceRegister(User $user, string $token, array $args)
    {
        // Cihaz bilgileri varsa kaydet
        if (isset($args['device_type']) && isset($args['device_unique_id'])) {
            $user->devices()->updateOrCreate(
                [
                    'device_unique_id' => $args['device_unique_id'],
                ],
                [
                    'device_type' => $args['device_type'] ?? null,
                    'device_os' => $args['device_os'] ?? null,
                    'device_os_version' => $args['device_os_version'] ?? null,
                    'device_model' => $args['device_model'] ?? null,
                    'device_brand' => $args['device_brand'] ?? null,
                    'access_token' => $token,
                ]
            );
        }
    }

    public function register($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];

        $validator = Validator::make($input, [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'nickname' => 'required|string|max:255|unique:users,nickname',
            'gender_id' => 'required|exists:genders,id',
            'birthday' => 'required|date',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|regex:/^[0-9]{10,15}$/',
            'password' => 'required|string|min:8|confirmed',
            'fcm_token' => 'required|string',
            'country_id' => 'required|exists:countries,id',
            'primary_team_id' => 'nullable|exists:teams,id',
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
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        // Step 1: Create user without transaction
        try {
            $userData = [
                'name' => $input['name'],
                'surname' => $input['surname'],
                'nickname' => $input['nickname'],
                'gender_id' => $input['gender_id'],
                'birthday' => $input['birthday'],
                'phone' => $input['phone'],
                'email' => $input['email'],
                'password' => $input['password'],
                'fcm_token' => $input['fcm_token'],
                'country_id' => $input['country_id'],
                'preferred_language_id' => $input['preferred_language_id'],
                'agora_uid' => $this->generateUniqueAgoraUid(),
            ];

            // primary_team_id opsiyonel
            if (isset($input['primary_team_id'])) {
                $userData['primary_team_id'] = $input['primary_team_id'];
            }

            $user = User::create($userData);
        } catch (Exception $e) {
            throw new Exception('Kullanıcı oluşturma başarısız: ' . $e->getMessage());
        }

        // Step 2: Generate token without transaction
        try {
            $token = $this->generateToken($user);
        } catch (Exception $e) {
            Log::error('Token oluşturma başarısız: ' . $e->getMessage());
            throw new Exception('Token oluşturma başarısız');
        }

        // Step 3: Handle user teams separately
        if (isset($input['user_teams']) && !empty($input['user_teams'])) {
            try {
                // Verify all team IDs exist
                $validTeamIds = Team::whereIn('id', $input['user_teams'])->pluck('id')->toArray();
                if (!empty($validTeamIds)) {
                    $user->user_teams()->sync($validTeamIds);
                }
            } catch (Exception $e) {
                Log::error('Failed to sync user teams: ' . $e->getMessage());
                // Continue with registration without failing
            }
        }

        // Step 4: Device registration
        try {
            $this->deviceRegister($user, $token, $input);
        } catch (Exception $e) {
            Log::error('Cihaz kaydı başarısız: ' . $e->getMessage());
            // Continue without failing
        }

        // Step 5: Follow oldest users
        try {
            $oldestUsers = DB::table('users')
                ->where('id', '!=', $user->id) // Kendini takip etmesini önlemek için
                ->orderBy('created_at')
                ->take(2)
                ->pluck('id')
                ->toArray();

            if (!empty($oldestUsers)) {
                $user->follows()->sync($oldestUsers);
            }
        } catch (Exception $e) {
            Log::error('Kullanıcı takip işlemi başarısız: ' . $e->getMessage());
            // Continue without failing
        }

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('sanctum.expiration') * 60, // dakikayı saniyeye çevir
            'user' => $user
        ];
    }
    public function login($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $input = $args['input'];

        $validator = Validator::make($input, [
            'nickname' => 'required',
            'password' => 'required',
            'fcm_token' => 'required|string',
            'device_type' => 'required|string|max:255',
            'device_os' => 'required|string|max:255',
            'device_os_version' => 'required|string|max:255',
            'device_model' => 'required|string|max:255',
            'device_brand' => 'required|string|max:255',
            'device_unique_id' => 'required|string|max:255',
            'remember_me' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $user = User::where('nickname', $input['nickname'])->first();
        if (!$user || !Hash::check($input['password'], $user->password)) {
            throw new Exception('Geçersiz kimlik bilgileri.');
        }
        if ($user->is_banned) {
            $reason = $user->ban_reason ?? 'Belirtilmemiş';
            throw new Exception("Hesabınız askıya alınmıştır. Sebep: {$reason}");
        }
        $userDevice = $user->devices()->where('device_unique_id', $input['device_unique_id'])->first();
        if ($userDevice && $userDevice->is_banned) {
            throw new Exception('Bu cihazdan girişiniz askıya alınmıştır. Başka cihaz ile tekrar deneyiniz.');
        }

        $rememberMe = isset($input['remember_me']) && $input['remember_me'] === true;

        // Auth token oluştur - Beni Hatırla seçeneğine göre token süresi ayarlanır
        if ($rememberMe) {
            // 30 gün süreyle hatırla
            $token = $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken;
            $token = "Bearer " . $token;

            // Remember token güncelle
            $user->setRememberToken(Str::random(60));
            $user->save();
        } else {
            // Normal token oluştur (varsayılan süre)
            $token = "Bearer " . $this->generateToken($user);
        }

        // FCM token güncelle
        $user->update([
            'fcm_token' => $input['fcm_token'],
        ]);

        $user->devices()->updateOrCreate(
            [
                'device_unique_id' => $input['device_unique_id'],
            ],
            [
                'device_type' => $input['device_type'] ?? null,
                'device_os' => $input['device_os'] ?? null,
                'device_os_version' => $input['device_os_version'] ?? null,
                'device_model' => $input['device_model'] ?? null,
                'device_brand' => $input['device_brand'] ?? null,
                'device_ip' => request()->ip(),
                'token' => $token,
            ]
        );

        $user->deviceLogins()->create([
            'device_type' => $input['device_type'] ?? null,
            'device_unique_id' => $input['device_unique_id'],
            'device_os' => $input['device_os'] ?? null,
            'device_os_version' => $input['device_os_version'] ?? null,
            'device_model' => $input['device_model'] ?? null,
            'device_brand' => $input['device_brand'] ?? null,
            'device_ip' => request()->ip(),
            'access_token' => $token,
            'last_activity_at' => now(),
        ]);

        return [
            'access_token' => $token,
            'user' => $user
        ];
    }

    public function logout($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();
        $user->currentAccessToken()->delete();

        return [
            'success' => true,
            'message' => 'Çıkış yapıldı.'
        ];
    }

    public function refreshToken($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $this->getUser();

        if ($user->currentAccessToken()) {
            $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
        }

        $token = $this->generateToken($user);

        return [
            'access_token' => $token,
            'user' => $user
        ];
    }

    public function sendResetPasswordLink($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];

        $validator = Validator::make($input, [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $status = Password::sendResetLink(['email' => $input['email']]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new \Exception(__($status));
        }

        return [
            'success' => true,
            'message' => __($status)
        ];
    }

    /**
     * 📌 Şifre Sıfırlama
     */
    public function resetPassword($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];

        $validator = Validator::make($input, [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $status = Password::reset(
            [
                'email' => $input['email'],
                'password' => $input['password'],
                'password_confirmation' => $input['password_confirmation'],
                'token' => $input['token'],
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new \Exception(__($status));
        }

        return [
            'success' => true,
            'message' => __($status)
        ];
    }

    /**
     * 📌 E-posta Doğrulama
     */
    public function verifyEmail($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];

        $validator = Validator::make($input, [
            'id' => 'required',
            'hash' => 'required',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $user = User::find($input['id']);

        if (!$user) {
            throw new \Exception('Kullanıcı bulunamadı.');
        }

        if ($user->hasVerifiedEmail()) {
            return [
                'success' => true,
                'message' => 'E-posta zaten doğrulanmış.'
            ];
        }

        $user->markEmailAsVerified();

        return [
            'success' => true,
            'message' => 'E-posta başarıyla doğrulandı.'
        ];
    }

    /**
     * 📌 E-posta Doğrulama Bağlantısı Yeniden Gönderme
     */
    public function resendEmailVerification($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $this->getUser();

        if ($user->hasVerifiedEmail()) {
            throw new \Exception('E-posta zaten doğrulanmış.');
        }

        $user->sendEmailVerificationNotification();

        return [
            'success' => true,
            'message' => 'E-posta doğrulama bağlantısı gönderildi.'
        ];
    }

    /**
     * 📌 Telefon Doğrulama
     */
    public function verifyPhone($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];

        $validator = Validator::make($input, [
            'phone' => 'required|string',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        // OTP doğrulama mantığı
        $otpRecord = DB::table('otp_verifications')->where('phone', $input['phone'])->first();

        if (!$otpRecord || $otpRecord->otp !== $input['otp']) {
            throw new \Exception('Geçersiz OTP kodu.');
        }

        $user = User::where('phone', $input['phone'])->first();

        if (!$user) {
            throw new \Exception('Kullanıcı bulunamadı.');
        }

        // Telefonu doğrulanmış olarak işaretle
        $user->phone_verified_at = now();
        $user->save();

        return [
            'success' => true,
            'message' => 'Telefon başarıyla doğrulandı.'
        ];
    }

    /**
     * 📌 OTP Gönderme
     * @throws \Random\RandomException
     */
    public function sendOTP($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];

        $validator = Validator::make($input, [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $otp = random_int(100000, 999999);

        // OTP'yi veritabanında sakla
        DB::table('otp_verifications')->updateOrInsert(
            ['phone' => $input['phone']],
            ['otp' => $otp, 'created_at' => now()]
        );

        $message = 'SMS Validation Code: ' . $otp;
        $phoneNumber = $input['phone'];

        $netgsm = new Netgsm();
        $netgsm->send($phoneNumber, $message);

        return [
            'success' => true,
            'message' => 'OTP başarıyla gönderildi.'
        ];
    }

    /**
     * 📌 Tüm Takımları Getir
     */
    public function fetchAllTeams($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $teams = Team::all();

        return [
            'success' => true,
            'teams' => $teams
        ];
    }

    /**
     * İkinci hesap oluşturma
     */
    public function createSecondaryAccount($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Mevcut kullanıcıyı al
        $user = $this->getUser();

        // Input parametrelerini al
        $input = $args['input'];

        // Validasyon kuralları
        $validator = Validator::make($input, [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'nickname' => 'required|string|max:255|unique:users,nickname',
            'gender_id' => 'required|exists:gender,id',
            'birthday' => 'required|date',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'fcm_token' => 'required|string',
            'primary_team_id' => 'required|exists:teams,id',
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
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        DB::beginTransaction();
        try {
            // Ana kullanıcı mı kontrol et
            $parentUserId = null;
            $accountType = 'secondary';

            if ($user->account_type === 'primary') {
                $parentUserId = $user->id;
            } else if ($user->parent_user_id) {
                $parentUserId = $user->parent_user_id;
            }

            // Yeni kullanıcı oluştur
            $secondaryUser = User::create([
                'name' => $input['name'],
                'surname' => $input['surname'],
                'nickname' => $input['nickname'],
                'gender_id' => $input['gender_id'],
                'birthday' => $input['birthday'],
                'phone' => $user->phone, // Aynı telefon numarasını kullan
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'fcm_token' => $input['fcm_token'],
                'primary_team_id' => $input['primary_team_id'],
                'preferred_language_id' => $input['preferred_language_id'],
                'agora_uid' => $this->generateUniqueAgoraUid(),
                'parent_user_id' => $parentUserId,
                'account_type' => $accountType,
            ]);

            if (isset($input['user_teams'])) {
                $secondaryUser->user_teams()->sync($input['user_teams']);
            }

            $token = $this->generateToken($secondaryUser);

            // Cihaz kaydı
            $this->deviceRegister($secondaryUser, $token, $input);

            DB::commit();
            return [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('sanctum.expiration') * 60, // dakikayı saniyeye çevir
                'user' => $secondaryUser
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('İkinci hesap oluşturma başarısız: ' . $e->getMessage());
        }
    }

    /**
     * Hesaplar arası geçiş yapma
     */
    public function switchAccount($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Mevcut kullanıcıyı al
        $user = $this->getUser();

        // Input parametrelerini al
        $input = $args['input'];

        // Validasyon kuralları
        $validator = Validator::make($input, [
            'user_id' => 'required|exists:users,id',
            'device_unique_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        // Geçiş yapılacak kullanıcıyı bul
        $targetUser = User::find($input['user_id']);
        if (!$targetUser) {
            throw new \Exception('Kullanıcı bulunamadı.');
        }

        // Kullanıcının bu hesaba erişim yetkisi var mı kontrol et
        $linkedAccounts = $user->getLinkedAccounts();
        $otherEmailAccounts = $user->getOtherEmailAccounts();

        $hasAccess = $linkedAccounts->contains('id', $targetUser->id) ||
            $otherEmailAccounts->contains('id', $targetUser->id);

        if (!$hasAccess) {
            throw new \Exception('Bu hesaba erişim yetkiniz yok.');
        }

        // Yeni token oluştur
        $token = $this->generateToken($targetUser);

        // Cihaz bilgilerini güncelle
        $targetUser->devices()->updateOrCreate(
            [
                'device_unique_id' => $input['device_unique_id'],
            ],
            [
                'token' => $token,
            ]
        );

        // Giriş geçmişine yeni kayıt ekle
        $targetUser->deviceLogins()->create([
            'device_unique_id' => $input['device_unique_id'],
            'device_ip' => request()->ip(),
            'access_token' => $token,
            'last_activity_at' => now(),
        ]);

        return [
            'access_token' => $token,
            'user' => $targetUser
        ];
    }

    /**
     * Kullanıcının bağlantılı hesaplarını getir
     */
    public function getLinkedAccounts($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $this->getUser();

        $linkedAccounts = $user->getLinkedAccounts();
        $otherEmailAccounts = $user->getOtherEmailAccounts();

        return [
            'same_email_accounts' => $linkedAccounts,
            'other_email_accounts' => $otherEmailAccounts
        ];
    }

    /**
     * Kullanıcı aktifliği sonlanır oturum bilgileri kaydedilir.
     */
    public function disconnectSession($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();
        if (!$user) {
            throw new Exception('Kullanıcı bulunamadı.');
        }

        $input = $args['input'];
        $socketId = $input['socket_id'] ?? null;

        $key = "socket-user:{$socketId}";

        $dataJson = Redis::get($key);
        if (!$dataJson) {
            return [
                'success' => false,
                'message' => 'Socket ID bulunamadı.'
            ];
        }

        $data = json_decode($dataJson, true);

        $userId = $data['user_id'] ?? null;
        $startAt = $data['start_at'] ?? null;

        if ($userId != $user->id) {
            return [
                'success' => false,
                'message' => 'Kullanıcı ID socket ID ile eşleşmiyor.'
            ];
        }

        if (!$startAt) {
            Redis::del($key);
            $socketCountKey = "active-socket-count:user:{$userId}";
            Redis::decr($socketCountKey);

            return [
                'success' => false,
                'message' => 'Başlangıç zamanı bulunamadı.'
            ];
        }

        UserSessionLog::create([
            'user_id' => $userId,
            'start_at' => $startAt,
            'end_at' => now(),
        ]);

        Redis::del($key);
        Redis::decr("active-socket-count:user:{$userId}");

        $socketCountKey = "active-socket-count:user:{$userId}";
        $socketCount = Redis::get($socketCountKey);
        if ($socketCount == 0) {
            Redis::srem("active-users", $userId);
            Redis::del($socketCountKey);
        }
        foreach ($user->allConversationsUserIds() as $receiverId) {
            event(new UserOffline($receiverId));
        }


        return [
            'success' => true,
            'message' => 'Kullanıcı bağlantısı sonlandırıldı.'
        ];
    }
}
