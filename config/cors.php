<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración ultra-permisiva para desarrollo local en redes privadas.
    | TODAS las IPs privadas y localhost están permitidas.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // URLs específicas conocidas
    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
    ],

    // Patrones regex para CUALQUIER red local
    'allowed_origins_patterns' => [
        // Acepta cualquier origen HTTP/HTTPS (perfecto para desarrollo)
        '#^https?://.*$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];

