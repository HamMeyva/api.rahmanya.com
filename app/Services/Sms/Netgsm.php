<?php

namespace App\Services\Sms;

use SoapClient;
use Exception;
use Illuminate\Support\Facades\Log;

class Netgsm
{
    protected string $url;
    protected string $text;
    protected $username;
    protected $password;
    protected array $recipients = [];
    protected $header;

    public function __construct()
    {
        $this->url = 'http://soap.netgsm.com.tr:8080/Sms_webservis/SMS?wsdl';
        $this->header = config('services.netgsm.header');
        $this->username = config('services.netgsm.username');
        $this->password = config('services.netgsm.password');
    }

    /**
     * @throws Exception
     */
    private function to($numbers): void
    {
        $recipients = is_array($numbers) ? $numbers : [$numbers];

        $recipients = array_map(static function ($item) {
            return trim($item);
        }, array_merge($this->recipients, $recipients));

        $this->recipients = array_values(array_filter($recipients));

        if (count($this->recipients) < 1) {
            throw new Exception('Message recipients cannot be empty.');
        }
    }

    private function message(string $message): void
    {
        $message = trim($message);

        if ($message === '') {
            throw new Exception('Message text cannot be empty.');
        }

        $this->text = $message;
    }

    /**
     * @throws Exception
     */
    public function send($numbers, $message): bool
    {
        $this->to($numbers);
        $this->message($message);

        $gsmNumbers = implode(',', $this->recipients);

        $params = [
            'username' => $this->username,
            'password' => $this->password,
            'header' => $this->header,
            'msg' => $message,
            'gsm' => $gsmNumbers,
            'filter' => 0,
            'encoding' => 'TR',
            'appkey' => null
        ];

        try {
            $client = new SoapClient($this->url, ['trace' => 1, 'exception' => 1]);
            $response = $client->smsGonder1NV2($params);
            if($response && $response->return && strlen($response->return) > 3){
                return true;
            }

            Log::info('Netgsm Error: ' . $response?->return ?? null, ['response' => $response]);
            return false;
        } catch (Exception $e) {
            Log::info('Netgsm Exception Error: ' . $e->getMessage() ?? null);
            return false;
        }
    }
}
