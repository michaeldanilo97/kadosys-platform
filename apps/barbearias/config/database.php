<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Credenciais de banco de dados - KADOSYS Barbearias
|--------------------------------------------------------------------------
|
| Diferente do KADOSYS Igrejas (um banco MySQL isolado por igreja), o
| Barbearias usa multi-tenant LOGICO: um banco unico e compartilhado,
| onde cada tabela de negocio tem uma coluna barbearia_id que isola os
| dados de cada barbearia (ver Barbearias\Core\Auth::barbeariaId() e o
| padrao repetido em cada Model). Isso evita esbarrar no limite de
| quantidade de bancos MySQL por conta cPanel, e simplifica bastante a
| operacao (uma unica migracao, um unico ponto de conexao) - o preco e
| que toda query de negocio PRECISA filtrar por barbearia_id.
|
*/

return [
    'driver'   => 'mysql',
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'kadosys1_barbearias',
    'username' => getenv('DB_USERNAME') ?: 'kadosys1_barbearias',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
