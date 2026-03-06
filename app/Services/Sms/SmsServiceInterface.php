<?php

namespace App\Services\Sms;

use Illuminate\Http\Response;

interface SmsServiceInterface
{
    /**
     * Send a single SMS.
     *
     * @param string $to      E.164-formatted number or comma-separated list
     * @param string $message
     * @return mixed
     */
    public function send(string $to, string $message): array;
    public function getBalance(): string;
}
