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
    | Google (inicio de sesion con cuenta de Google)
    |--------------------------------------------------------------------------
    | Credenciales del cliente OAuth 2.0 tipo "Aplicacion web" de Google Cloud
    | Console. El secreto vive SOLO aca (el intercambio del code por el token se
    | hace server-side); el frontend nunca lo ve.
    |
    | redirect debe coincidir EXACTAMENTE con un "URI de redireccionamiento
    | autorizado" del cliente en Google Cloud Console.
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        // A donde vuelve el navegador del usuario despues del login (el front).
        // Solo se usa como respaldo: normalmente el origen lo manda el propio front
        // (cada dev levanta en el puerto que quiere: 4200 con ng serve, 8100 con
        // ionic serve, o la URL del tunel en una demo).
        'frontend_url' => env('GOOGLE_FRONTEND_URL', env('FRONTEND_URL', 'http://localhost:4200')),

        // Origenes permitidos para volver. Lista blanca: sin esto, cualquiera podria
        // usar nuestro endpoint para redirigir a un sitio externo (open redirect) o
        // para llevarse el codigo de acceso a otro dominio.
        // OJO: cada origen de esta lista tambien tiene que estar cargado como "URI de
        // redireccionamiento autorizado" en Google Cloud Console, con /api/auth/google/callback.
        'origins' => array_filter(array_map(
            'trim',
            explode(',', (string) env('GOOGLE_ALLOWED_ORIGINS', 'http://localhost:4200,http://localhost:8100'))
        )),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

];
