<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Credenciais do banco do KADOSYS Barbearias
|--------------------------------------------------------------------------
|
| Banco unico e compartilhado por todas as barbearias (isolamento logico
| via barbearia_id) - o mesmo banco que Barbearias\Core\Database usa.
|
| A senha NUNCA fica com valor padrao real aqui - configure via variavel
| de ambiente ou crie "config/database_barbearias.local.php" (fora do
| Git, ver .gitignore) retornando um array com as chaves abaixo. Exemplo:
|
|   <?php
|   return [
|       'password' => 'sua-senha-aqui',
|   ];
|
*/

$local = __DIR__ . '/database_barbearias.local.php';
$overrides = is_file($local) ? require $local : [];

return [
    'driver'   => 'mysql',
    'host'     => $overrides['host'] ?? (getenv('SUPERADMIN_BARBEARIAS_DB_HOST') ?: 'localhost'),
    'port'     => $overrides['port'] ?? (getenv('SUPERADMIN_BARBEARIAS_DB_PORT') ?: '3306'),
    'database' => $overrides['database'] ?? (getenv('SUPERADMIN_BARBEARIAS_DB_DATABASE') ?: 'kadosys1_barbearias'),
    'username' => $overrides['username'] ?? (getenv('SUPERADMIN_BARBEARIAS_DB_USERNAME') ?: ''),
    'password' => $overrides['password'] ?? (getenv('SUPERADMIN_BARBEARIAS_DB_PASSWORD') ?: ''),
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
