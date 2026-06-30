<?php

declare(strict_types=1);

/**
 * config/session.php
 *
 * Configuracoes da sessao nativa do PHP utilizada pela plataforma.
 */

return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'name' => env('SESSION_NAME', 'kadosys_session'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'secure_cookie' => env('SESSION_SECURE_COOKIE', false),
];
