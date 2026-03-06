<?php
return [

    // Which provider to use by default
    'default' => env('SMS_PROVIDER', 'bulksmsbd'),

    // Provider-specific settings
    'providers' => [

        'bulksmsbd' => [
            'driver'    => App\Services\Sms\Providers\BulkSmsBdService::class,
            'api_url'   => env('BULKSMSBD_API_URL', 'http://bulksmsbd.net/api/smsapi'),
            'api_key'   => env('BULKSMSBD_API_KEY', 'Y2yz7dZKrNPcHedBZ1fH'),
            'senderid'  => env('BULKSMSBD_SENDER_ID', '8809617620128'),
        ],

        // future providers here...
        // 'twilio' => [ ... ],
    ],

];
