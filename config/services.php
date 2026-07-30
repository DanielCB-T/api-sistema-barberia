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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Brevo (SMS transaccional)
    |--------------------------------------------------------------------------
    |
    | Usado por App\Services\BrevoSmsService para enviar la confirmación de
    | citas por SMS. Si BREVO_SMS_ENABLED=false o falta la API key, el
    | servicio degrada con elegancia (registra el mensaje en el log y marca
    | la notificación como "fallido") sin romper la petición del usuario.
    |
    | Consigue tu API key en https://app.brevo.com/settings/keys/api
    | El remitente ("sender") debe ser alfanumérico, máximo 11 caracteres,
    | sin espacios (ej. "Barberia").
    |
    */

    'brevo' => [
        'enabled' => env('BREVO_SMS_ENABLED', false),
        'api_key' => env('BREVO_API_KEY'),
        'sender' => env('BREVO_SMS_SENDER', 'Barberia'),
        'default_country_code' => env('BREVO_DEFAULT_COUNTRY_CODE', '52'),
    ],

];
