<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Credenciais do banco do KADOSYS Food
|--------------------------------------------------------------------------
|
| Banco unico e compartilhado por todos os restaurantes (isolamento logico
| via restaurante_id) - o mesmo banco que Food\Core\Database usa.
|
| A senha NUNCA fica com valor padrao real aqui - configure via variavel
| de ambiente ou crie "config/database_food.local.php" (fora do Git, ver
| .gitignore) retornando um array com as chaves abaixo. Exemplo:
|
|   <?php
|   return [
|       'password' => 'sua-senha-aqui',
|   ];
|
*/

$local = __DIR__ . '/database_food.local.php';
$overrides = is_file($local) ? require $local : [];

return [
    'driver'   => 'mysql',
    'host'     => $overrides['host'] ?? (getenv('SUPERADMIN_FOOD_DB_HOST') ?: 'localhost'),
    'port'     => $overrides['port'] ?? (getenv('SUPERADMIN_FOOD_DB_PORT') ?: '3306'),
    'database' => $overrides['database'] ?? (getenv('SUPERADMIN_FOOD_DB_DATABASE') ?: 'kadosys1_food'),
    'username' => $overrides['username'] ?? (getenv('SUPERADMIN_FOOD_DB_USERNAME') ?: ''),
    'password' => $overrides['password'] ?? (getenv('SUPERADMIN_FOOD_DB_PASSWORD') ?: ''),
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
