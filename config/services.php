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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'permissions' => [
        'super_admin' => [
            'manage_users',
            'manage_companies',
            'manage_trash_types',
            'manage_wastes',
            'manage_waste_collection_centers',
            'manage_user_types',
            'manage_zones',
            'manage_calendars',
            'manage_tickets',
            'manage_roles_and_permissions',
        ],
        'company_admin' => [
            'manage_companies',
            'manage_trash_types',
            'manage_wastes',
            'manage_waste_collection_centers',
            'manage_user_types',
            'manage_zones',
            'manage_calendars',
            'manage_tickets',
        ],
        'contributor' => ['no_permissions'],
    ]

];
