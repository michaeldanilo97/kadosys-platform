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
| A senha NUNCA fica com valor padrao real aqui (dado sensivel, nao
| pode ir pro Git) - configure via variavel de ambiente (cPanel >
| MultiPHP INI Editor) OU, se o painel nao suportar variavel de
| ambiente customizada, crie "config/database.local.php" (mesma pasta
| deste arquivo) direto no servidor - por FTP ou pelo "Gerenciador de
| Arquivos" do cPanel, nunca pelo Git - retornando um array com as
| chaves abaixo. Esse arquivo esta no .gitignore de proposito, entao
| nenhum push/pull futuro chega nele. Exemplo do conteudo:
|
|   <?php
|   return [
|       'password' => 'sua-senha-aqui',
|   ];
|
*/

$local = __DIR__ . '/database.local.php';
$overrides = is_file($local) ? require $local : [];

return [
    'driver'   => 'mysql',
    'host'     => $overrides['host'] ?? (getenv('DB_HOST') ?: 'localhost'),
    'port'     => $overrides['port'] ?? (getenv('DB_PORT') ?: '3306'),
    'database' => $overrides['database'] ?? (getenv('DB_DATABASE') ?: 'kadosys1_barbearias'),
    'username' => $overrides['username'] ?? (getenv('DB_USERNAME') ?: 'kadosys1_barbearias'),
    'password' => $overrides['password'] ?? (getenv('DB_PASSWORD') ?: ''),
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
