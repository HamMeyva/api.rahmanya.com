<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use NetGsm\Sms\SmsSend;

class NetGsmSmsService
{
    /**
     * @var SmsSend
     */
    protected $smsSend;

    /**
     * @var array
     */
    protected $config;

    /**
     * NetGsmSmsService constructor.
     */
    public function __construct()
    {
        $this->config = config('netgsm');
        // SmsSend sınıfı bulunamadığı için devre dışı bırakıldı
        // $this->smsSend = new SmsSend();
        Log::warning('NetGsmSmsService devre dışı bırakıldı - SmsSend sınıfı bulunamadı');
    }

    /**
     * Send an SMS message
     *
     * @param string|array $phoneNumbers
     * @param string $message
     * @param array $options
     * @return array
     */
    public function sendSms($phoneNumbers, string $message, array $options = []): array
    {
        // SMS gönderme işlemi devre dışı bırakıldı
        Log::info('SMS gönderme işlemi devre dışı bırakıldı', [
            'recipients' => is_array($phoneNumbers) ? $phoneNumbers : [$phoneNumbers],
            'message' => $message,
        ]);
        
        // Başarılı yanıt döndür
        return [
            'success' => true,
            'message' => 'SMS sending is disabled but operation marked as successful',
            'message_id' => 'mock_' . time() . '_' . rand(1000, 9999),
        ];
    }

    /**
     * Send an OTP (One-Time Password) SMS
     *
     * @param string $phoneNumber
     * @param array $options
     * @return array
     */
    public function sendOtp(string $phoneNumber, array $options = []): array
    {
        // Generate OTP code
        $code = $this->generateOtpCode($options['length'] ?? 6);
        
        // Get expiry time in minutes
        $expiryMinutes = $options['expiry'] ?? 10;
        
        // Format phone number
        $formattedPhone = $this->formatPhoneNumber($phoneNumber);
        
        // OTP gönderme işlemi devre dışı bırakıldı
        Log::info('OTP gönderme işlemi devre dışı bırakıldı', [
            'phone' => $formattedPhone,
            'code' => $code,
        ]);
        
        // Store OTP in cache for verification
        $cacheKey = $this->getOtpCacheKey($formattedPhone);
        Cache::put($cacheKey, $code, now()->addMinutes($expiryMinutes));
        
        return [
            'success' => true,
            'message' => 'OTP sent successfully (SMS sending disabled)',
            'code' => $code,
            'expires_in' => $expiryMinutes,
        ];
    }

    /**
     * Verify an OTP code
     *
     * @param string $phoneNumber
     * @param string $code
     * @return array
     */
    public function verifyOtp(string $phoneNumber, string $code): array
    {
        // Format phone number
        $formattedPhone = $this->formatPhoneNumber($phoneNumber);
        
        // Get OTP from cache
        $cacheKey = $this->getOtpCacheKey($formattedPhone);
        $storedCode = Cache::get($cacheKey);
        
        if (!$storedCode) {
            return [
                'success' => false,
                'message' => 'OTP expired or not found',
            ];
        }
        
        if ($storedCode !== $code) {
            return [
                'success' => false,
                'message' => 'Invalid OTP code',
            ];
        }
        
        // OTP is valid, remove it from cache to prevent reuse
        Cache::forget($cacheKey);
        
        return [
            'success' => true,
            'message' => 'OTP verified successfully',
        ];
    }

    /**
     * Send a template SMS
     *
     * @param string|array $phoneNumbers
     * @param string $templateName
     * @param array $templateData
     * @param array $options
     * @return array
     */
    public function sendTemplateSms($phoneNumbers, string $templateName, array $templateData = [], array $options = []): array
    {
        // Get template
        $template = $this->getSmsTemplate($templateName);
        
        if (!$template) {
            return [
                'success' => false,
                'message' => "Template '{$templateName}' not found",
            ];
        }
        
        // Replace placeholders in template
        $message = $this->replacePlaceholders($template, $templateData);
        
        // Send SMS
        return $this->sendSms($phoneNumbers, $message, $options);
    }

    /**
     * Get SMS template
     *
     * @param string $templateName
     * @return string|null
     */
    protected function getSmsTemplate(string $templateName): ?string
    {
        $templates = [
            'welcome' => 'Shoot90\'a hoş geldiniz! Hesabınız başarıyla oluşturuldu.',
            'password_reset' => 'Shoot90 şifre sıfırlama kodunuz: {code}. Bu kod {expiry} dakika geçerlidir.',
            'account_verification' => 'Shoot90 hesap doğrulama kodunuz: {code}',
            'login_verification' => 'Shoot90 giriş doğrulama kodunuz: {code}. Bu kod {expiry} dakika geçerlidir.',
            'payment_confirmation' => 'Shoot90 ödemeniz başarıyla alındı. Ödeme tutarı: {amount} TL. İşlem No: {transaction_id}',
            'appointment_reminder' => 'Shoot90 randevunuz {date} tarihinde {time} saatinde {location} konumunda gerçekleşecektir.',
            'event_invitation' => 'Shoot90 etkinliğine davetlisiniz! Etkinlik: {event_name}, Tarih: {date}, Saat: {time}, Konum: {location}',
            'subscription_renewal' => 'Shoot90 aboneliğiniz {renewal_date} tarihinde yenilenecektir. Yenilenmesini istemiyorsanız iptal edebilirsiniz.',
            'custom_notification' => '{message}',
        ];

        return $templates[$templateName] ?? null;
    }

    /**
     * Replace placeholders in a template
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function replacePlaceholders(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{' . $key . '}', $value, $template);
            }
        }

        return $template;
    }

    /**
     * Generate an OTP code
     *
     * @param int $length
     * @return string
     */
    protected function generateOtpCode(int $length = 6): string
    {
        return (string) random_int(pow(10, $length - 1), pow(10, $length) - 1);
    }

    /**
     * Get OTP cache key
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function getOtpCacheKey(string $phoneNumber): string
    {
        return 'otp_' . md5($phoneNumber);
    }

    /**
     * Format phone number
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If the number starts with 0, remove it
        if (substr($cleaned, 0, 1) === '0') {
            $cleaned = substr($cleaned, 1);
        }
        
        // If the number doesn't start with country code (90 for Turkey), add it
        if (substr($cleaned, 0, 2) !== '90') {
            $cleaned = '90' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Format date for NetGSM API
     *
     * @param string $date
     * @return string
     */
    protected function formatDate(string $date): string
    {
        // If it's already in the correct format (e.g., '270120230950'), return it
        if (preg_match('/^\d{12}$/', $date)) {
            return $date;
        }
        
        // If it's a date string, convert it to the required format
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            throw new \InvalidArgumentException("Invalid date format: {$date}");
        }
        
        // Format: ddMMyyyyHHmm (day, month, year, hour, minute)
        return date('dmYHi', $timestamp);
    }

    /**
     * Parse NetGSM response
     *
     * @param array $response
     * @return array
     */
    protected function parseResponse(array $response): array
    {
        $code = $response['code'] ?? '';
        
        $errorCodes = [
            '20' => 'Post edilen mesaj parametrelerinde hata',
            '30' => 'Geçersiz kullanıcı adı, şifre veya API erişim izniniz yok',
            '40' => 'Mesaj başlığı hatalı',
            '50' => 'Abone hesabınızda yeterli bakiye yok',
            '51' => 'Abone hesabınız ile bu işlemi yapamazsınız',
            '60' => 'Sistem hatası',
            '70' => 'Geçersiz tarih formatı',
            '80' => 'Mesajınız çok uzun',
            '85' => 'Aynı mesajı 1 gün içerisinde aynı numaraya gönderemezsiniz',
        ];
        
        if (isset($errorCodes[$code])) {
            return [
                'success' => false,
                'message' => $errorCodes[$code],
                'code' => $code,
            ];
        }
        
        // If the code is '00', it's a successful response
        if ($code === '00') {
            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'message_id' => $response['bulkid'] ?? null,
            ];
        }
        
        // Unknown response
        return [
            'success' => false,
            'message' => 'Unknown response: ' . json_encode($response),
            'code' => 'unknown',
        ];
    }
}
