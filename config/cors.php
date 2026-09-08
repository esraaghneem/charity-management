<?php

return [
    'paths' => ['api/*', 'sanctum/*', 'login', 'logout', 'admin/*'],
    'allowed_methods' => ['*'],
'allowed_origins' => ['http://192.168.124.50:3000'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];