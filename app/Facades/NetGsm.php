<?php

namespace App\Facades;

use App\Services\NetGsmSmsService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array sendSms($phoneNumbers, string $message, array $options = [])
 * @method static array sendOtp(string $phoneNumber, array $options = [])
 * @method static array verifyOtp(string $phoneNumber, string $code)
 * @method static array sendTemplateSms($phoneNumbers, string $templateName, array $templateData = [], array $options = [])
 * 
 * @see \App\Services\NetGsmSmsService
 */
class NetGsm extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'netgsm.sms';
    }
}
