<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuracao de Banco de Dados - KADOSYS Igrejas
|--------------------------------------------------------------------------
|
| Cada instalacao do KADOSYS Igrejas utiliza seu proprio banco de dados
| MySQL exclusivo (sem multi-tenant). As credenciais devem ser definidas
| via variaveis de ambiente no servidor de producao. Os valores abaixo
| servem apenas como fallback para ambiente local de desenvolvimento.
|
*/

return [
    'driver'   => 'mysql',
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'kadosys1_igrejas',
    'username' => getenv('DB_USERNAME') ?: 'kadosys1_michael',
    'password' => getenv('DB_PASSWORD') ?: 'michael011',
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
