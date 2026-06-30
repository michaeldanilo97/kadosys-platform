<?php

declare(strict_types=1);

/**
 * config/auth.php
 *
 * Configuracoes do servico de autenticacao (Auth). Define a tabela
 * de usuarios e as colunas utilizadas para login, permitindo que
 * cada aplicativo customize seu proprio esquema de usuarios sem
 * alterar o Core.
 */

return [
    'table' => env('AUTH_TABLE', 'users'),
    'primary_key' => env('AUTH_PRIMARY_KEY', 'id'),
    'email_column' => env('AUTH_EMAIL_COLUMN', 'email'),
    'password_column' => env('AUTH_PASSWORD_COLUMN', 'password'),
];
