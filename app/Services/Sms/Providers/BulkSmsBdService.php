<?php
namespace App\Services\Sms\Providers;

use App\Services\Sms\SmsServiceInterface;
use Illuminate\Support\Facades\Http;

class BulkSmsBdService implements SmsServiceInterface
{
    protected $url;
    protected $key;
    protected $senderId;

    public function __construct()
    {
        $conf = config('sms.providers.bulksmsbd');
        $this->url      = $conf['api_url'];
        $this->key      = $conf['api_key'];
        $this->senderId = $conf['senderid'];
    }

    public function send(string $to, string $message) : array
    {
        $response = Http::asForm()->post($this->url, [
            'api_key'  => $this->key,
            'senderid' => $this->senderId,
            'number'   => $to,
            'message'  => $message,
        ]);

        return $response->json();
    }

    public function getBalance(): string
    {
        $resp = Http::asForm()->post(
            str_replace('smsapi', 'getBalanceApi', $this->url),
            ['api_key' => $this->key]
        );

        $resp = $resp->json();

        return data_get($resp, 'balance', 'N/A');
    }
}
