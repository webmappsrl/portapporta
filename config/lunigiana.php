<?php

return [
    'enabled'    => env('LUNIGIANA_FORWARD_ENABLED', true),
    'company_id' => env('LUNIGIANA_COMPANY_ID', 1),
    // TODO: confermare lista definitiva zone_id con ERSU (provvisoria al 2026-05-27)
    'zones'      => [108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123],
    'email'      => env('LUNIGIANA_FORWARD_EMAIL', 'urp@lunigianaambiente.it'),
];
