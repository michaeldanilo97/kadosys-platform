<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Senha mestra do Super Admin - KADOSYS
|--------------------------------------------------------------------------
|
| Mesmo padrao ja usado no painel /plataforma do Igrejas
| (Igrejas\Controllers\PlataformaController): uma unica chave mestra,
| guardada como HASH bcrypt (nunca em texto puro), sem usuario/senha
| por pessoa - quem tem a chave, entra.
|
| A chave em si NUNCA fica hardcoded aqui - vem de variavel de ambiente
| ou de um arquivo config/auth.local.php criado direto no servidor
| (fora do Git, mesmo padrao usado em config/database*.php e
| config/cpanel.php dos outros apps).
|
| Gerar um hash novo:
|   php -r "echo password_hash('sua-chave-bem-longa-aqui', PASSWORD_BCRYPT), PHP_EOL;"
|
*/

$local = __DIR__ . '/auth.local.php';
$overrides = is_file($local) ? require $local : [];

return [
    'senha_hash' => $overrides['senha_hash'] ?? (getenv('SUPERADMIN_SENHA_HASH') ?: ''),
];
