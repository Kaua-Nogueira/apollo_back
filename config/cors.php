<?php

$frontendUrls = env(
    'FRONTEND_URLS',
    'https://km5refrigeracoes.com.br,https://www.km5refrigeracoes.com.br,http://localhost:5173,http://127.0.0.1:5173',
);

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', $frontendUrls)))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false,
];
