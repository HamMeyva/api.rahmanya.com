<?php

namespace App\Models\Traits;

use App\Services\SmsService;
use Illuminate\Support\Carbon;
use Random\RandomException;

trait VerifiableCustomer
{
    /**
     * @throws RandomException
     */
    private function generateSmsOTP(): int
    {
        $code = random_int(100000, 999999);

        $this->otp_code = $code;
        $this->otp_expires_at = Carbon::now()->addMinutes(5);
        $this->save();

        return $code;
    }

    /**
     * @throws RandomException
     */
    public function sendSmsOtp(): string
    {
        $code = $this->generateSmsOTP();
        try {
            $message = 'Telefon Doğrulama Kodu: ' . $code;
            return SmsService::send($this->phone, $message);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function verifySmsOTP($code): bool
    {
        if ($code && $this->otp_code == $code && $this->otp_expires_at && Carbon::now()->lessThanOrEqualTo($this->otp_expires_at)) {
            $this->update([
                'phone_verified_at' => Carbon::now(),
                'otp_code' => null,
                'otp_expires_at' => null
            ]);
            return true;
        }

        return false;
    }
}
