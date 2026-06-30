<?php

declare(strict_types=1);

/**
 * config/security.php
 *
 * Configuracoes relacionadas a seguranca da plataforma: nome do
 * campo de token CSRF e algoritmo de hash de senha padrao.
 */

return [
    'csrf_token_name' => env('CSRF_TOKEN_NAME', '_csrf_token'),
    'hash_algo' => env('HASH_ALGO', 'bcrypt'),
];
