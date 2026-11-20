<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMSLive247 HTTP API Gateway URL
    |--------------------------------------------------------------------------
    | The default endpoint for SMSLive247 HTTP Gateway.
    */
    'base_url' => env('SMSLIVE_BASE_URL', 'https://smslive247.com/http/index.aspx'),

    /*
    |--------------------------------------------------------------------------
    | Account Credentials
    |--------------------------------------------------------------------------
    | Provided by your SMSLive247 dashboard under Developer/API settings.
    */
    'owner_email'         => env('SMSLIVE_OWNER_EMAIL'),
    'subaccount'          => env('SMSLIVE_SUBACCOUNT'),
    'subaccount_password' => env('SMSLIVE_SUBACCOUNT_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Default Sender ID
    |--------------------------------------------------------------------------
    | Alphanumeric sender ID (maximum 11 characters) displayed on recipient devices.
    */
    'default_sender' => env('SMSLIVE_SENDER_ID', env('APP_NAME', 'SMS')),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Timeout
    |--------------------------------------------------------------------------
    | The maximum seconds to wait for a gateway response before timing out.
    */
    'timeout' => (int) env('SMSLIVE_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Activity Logging
    |--------------------------------------------------------------------------
    | When enabled, outbound SMS dispatches and errors are logged via Laravel's Log facade.
    */
    'logging' => (bool) env('SMSLIVE_LOGGING', true),
];
