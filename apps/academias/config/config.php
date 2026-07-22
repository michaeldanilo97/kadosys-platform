<?php

declare(strict_types=1);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = preg_replace('#/public$#', '', $scriptDir);
$basePath = $basePath === '/' ? '' : rtrim((string) $basePath, '/');

return [
    'app_name'    => 'KADOSYS Academias',
    'env'         => getenv('APP_ENV') ?: 'production',
    'debug'       => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'base_path'   => $basePath,
    'timezone'    => 'America/Sao_Paulo',
    'session_name' => 'kadosys_academias_session',
    'remember_cookie' => 'kadosys_academias_remember',
    'remember_ttl' => 60 * 60 * 24 * 30,
];
