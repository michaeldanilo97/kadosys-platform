<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Credenciais do banco CENTRAL do KADOSYS Igrejas
|--------------------------------------------------------------------------
|
| O Super Admin NAO acessa os bancos individuais de cada igreja (um por
| tenant) - ele so precisa ler/escrever no banco CENTRAL do Igrejas
| (tabelas plataforma_tenants e plataforma_avisos), o mesmo banco que
| Igrejas\Core\Database::central() usa.
|
| A senha NUNCA fica com valor padrao real aqui - configure via variavel
| de ambiente ou crie "config/database_igrejas.local.php" (fora do Git,
| ver .gitignore) retornando um array com as chaves abaixo. Exemplo:
|
|   <?php
|   return [
|       'password' => 'sua-senha-aqui',
|   ];
|
*/

$local = __DIR__ . '/database_igrejas.local.php';
$overrides = is_file($local) ? require $local : [];

return [
    'driver'   => 'mysql',
    'host'     => $overrides['host'] ?? (getenv('SUPERADMIN_IGREJAS_DB_HOST') ?: 'localhost'),
    'port'     => $overrides['port'] ?? (getenv('SUPERADMIN_IGREJAS_DB_PORT') ?: '3306'),
    'database' => $overrides['database'] ?? (getenv('SUPERADMIN_IGREJAS_DB_DATABASE') ?: 'kadosys1_igrejas'),
    'username' => $overrides['username'] ?? (getenv('SUPERADMIN_IGREJAS_DB_USERNAME') ?: ''),
    'password' => $overrides['password'] ?? (getenv('SUPERADMIN_IGREJAS_DB_PASSWORD') ?: ''),
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
