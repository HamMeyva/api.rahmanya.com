<?php

namespace App\GraphQL\Resolvers;

use App\Models\TwoFactorAuth;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use PragmaRX\Recovery\Recovery;

class TwoFactorAuthResolver
{
    /**
     * İki faktörlü kimlik doğrulama için gizli anahtar oluşturur
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @param ResolveInfo $resolveInfo
     * @return array
     */
    public function generateSecret($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                Log::error('2FA gizli anahtar oluşturulurken hata: Kullanıcı bulunamadı');
                return [                
                    'secret' => '',
                    'qrCodeUrl' => '',
                    'backupCodes' => [],
                ];
            }
            
            // Google2FA-Laravel kütüphanesini kullanarak gizli anahtar oluştur
            $secret = Google2FA::generateSecretKey();
            
            // QR kod URI'sini oluştur
            $qrCodeUrl = Google2FA::getQRCodeUrl(
                'Shoot90',
                $user->email,
                $secret
            );
            
            // Recovery paketi ile yedek kodları oluştur
            $recovery = new Recovery();
            $backupCodes = $recovery
                ->setCount(8)
                ->setBlocks(1)
                ->setChars(10)
                ->toArray();
            
            // TwoFactorAuth kaydı oluştur veya güncelle
            $twoFactorAuth = TwoFactorAuth::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'secret' => $secret,
                    'backup_codes' => $backupCodes,
                    'verified' => false,
                ]
            );
            
            return [
                'secret' => $secret,
                'qrCodeUrl' => $qrCodeUrl,
                'backupCodes' => $backupCodes,
            ];
        } catch (\Exception $e) {
            Log::error('2FA gizli anahtar oluşturulurken hata: ' . $e->getMessage());
            return [
                'secret' => null,
                'qrCodeUrl' => null,
                'backupCodes' => [],
            ];
        }
    }
    
    /**
     * İki faktörlü kimlik doğrulama kodunu doğrular
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @param ResolveInfo $resolveInfo
     * @return array
     */
    public function verifyCode($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = Auth::user();
            $code = $args['code'];
            
            // Kullanıcının 2FA kaydını al
            $twoFactorAuth = TwoFactorAuth::where('user_id', $user->id)->first();
            
            // 2FA kaydı yoksa veya gizli anahtar yoksa hata döndür
            if (!$twoFactorAuth || empty($twoFactorAuth->secret)) {
                return [
                    'success' => false,
                    'message' => 'İki faktörlü kimlik doğrulama henüz kurulmamış.',
                ];
            }
            
            // Google2FA-Laravel kütüphanesini kullanarak kodu doğrula
            $valid = Google2FA::verifyKey($twoFactorAuth->secret, $code);
            
            if ($valid) {
                // Doğrulama başarılı, iki faktörlü kimlik doğrulamayı etkinleştir
                $user->two_factor_enabled = true;
                $user->save();
                
                // 2FA kaydını güncelle
                $twoFactorAuth->verified = true;
                $twoFactorAuth->last_used_at = now();
                $twoFactorAuth->save();
                
                return [
                    'success' => true,
                    'message' => 'İki faktörlü kimlik doğrulama başarıyla etkinleştirildi.',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Doğrulama kodu geçersiz. Lütfen tekrar deneyin.',
            ];
        } catch (\Exception $e) {
            Log::error('2FA kodu doğrulanırken hata: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Doğrulama işlemi sırasında bir hata oluştu.',
            ];
        }
    }
    
    /**
     * İki faktörlü kimlik doğrulamayı devre dışı bırakır
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @param ResolveInfo $resolveInfo
     * @return array
     */
    public function disableTwoFactorAuth($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = Auth::user();
            
            // İki faktörlü kimlik doğrulama etkin değilse hata döndür
            if (!$user->two_factor_enabled) {
                return [
                    'success' => false,
                    'message' => 'İki faktörlü kimlik doğrulama zaten devre dışı.',
                ];
            }
            
            // 2FA kaydını bul ve sil
            $twoFactorAuth = TwoFactorAuth::where('user_id', $user->id)->first();
            if ($twoFactorAuth) {
                $twoFactorAuth->delete();
            }
            
            // İki faktörlü kimlik doğrulamayı devre dışı bırak
            $user->two_factor_enabled = false;
            $user->save();
            
            return [
                'success' => true,
                'message' => 'İki faktörlü kimlik doğrulama başarıyla devre dışı bırakıldı.',
            ];
        } catch (\Exception $e) {
            Log::error('2FA devre dışı bırakılırken hata: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'İki faktörlü kimlik doğrulama devre dışı bırakılırken bir hata oluştu.',
            ];
        }
    }
    
    /**
     * Yedek kod ile giriş yapar
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @param ResolveInfo $resolveInfo
     * @return array
     */
    public function verifyBackupCode($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = Auth::user();
            $code = $args['code'];
            
            // Kullanıcının 2FA kaydını al
            $twoFactorAuth = TwoFactorAuth::where('user_id', $user->id)->first();
            
            // 2FA kaydı yoksa veya yedek kodlar yoksa hata döndür
            if (!$twoFactorAuth || empty($twoFactorAuth->backup_codes)) {
                return [
                    'success' => false,
                    'message' => 'Yedek kodlar bulunamadı.',
                ];
            }
            
            $backupCodes = $twoFactorAuth->backup_codes; // Zaten array olarak cast edildi
            
            // Yedek kodu kontrol et
            if (in_array($code, $backupCodes)) {
                // Kullanılan yedek kodu listeden çıkar
                $backupCodes = array_diff($backupCodes, [$code]);
                $twoFactorAuth->backup_codes = array_values($backupCodes);
                $twoFactorAuth->last_used_at = now();
                $twoFactorAuth->save();
                
                return [
                    'success' => true,
                    'message' => 'Yedek kod doğrulandı.',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Yedek kod geçersiz.',
            ];
        } catch (\Exception $e) {
            Log::error('2FA yedek kodu doğrulanırken hata: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Yedek kod doğrulanırken bir hata oluştu.',
            ];
        }
    }
    
    /**
     * Kullanıcının iki faktörlü kimlik doğrulama durumunu kontrol eder
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @param ResolveInfo $resolveInfo
     * @return bool
     */
    public function getTwoFactorStatus($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return false;
            }
            
            $twoFactorAuth = TwoFactorAuth::where('user_id', $user->id)->first();
            return $twoFactorAuth && $twoFactorAuth->verified ? true : false;
        } catch (\Exception $e) {
            Log::error('2FA durumu kontrol edilirken hata: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Bu metodu çağırmadan önce aşağıdaki komutları çalıştırarak gerekli paketleri yüklemelisiniz:
     * composer require pragmarx/google2fa-laravel
     * composer require pragmarx/recovery
     */
    
    /**
     * Rastgele yedek kod oluşturur
     *
     * @return string
     */
    private function generateBackupCode()
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        
        for ($i = 0; $i < 10; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $code;
    }
}
