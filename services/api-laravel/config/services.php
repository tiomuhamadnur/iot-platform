<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'influx' => [
        'url' => env('INFLUXDB_URL', env('INFLUX_URL')),
        'token' => env('INFLUXDB_TOKEN', env('INFLUX_TOKEN')),
        'org' => env('INFLUXDB_ORG', env('INFLUX_ORG')),
        'bucket' => env('INFLUXDB_BUCKET', env('INFLUX_BUCKET')),
    ],

    'mqtt' => [
        'host' => env('MQTT_HOST', 'emqx'),
        'port' => env('MQTT_PORT', 1883),
        'username' => env('MQTT_USERNAME'),
        'password' => env('MQTT_PASSWORD'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
