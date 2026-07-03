<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Painel administrativo da plataforma - KADOSYS Igrejas
|--------------------------------------------------------------------------
|
| Area separada do login normal de cada igreja (/login), usada so pelo
| dono do sistema pra listar e excluir igrejas provisionadas (banco,
| subdominio e registro central - ver Igrejas\Core\Desprovisionador).
| Protegida por uma "chave mestra" propria (ver
| Igrejas\Controllers\PlataformaController), guardada aqui como hash
| (nunca em texto puro) - igual as demais credenciais sensiveis do
| projeto, sem valor padrao: sem configurar, o painel fica inacessivel.
|
| Variavel esperada:
|   PLATAFORMA_ADMIN_SENHA_HASH - hash bcrypt da chave mestra. Gere com:
|
|     php -r "echo password_hash('sua-chave-bem-longa-aqui', PASSWORD_BCRYPT), PHP_EOL;"
|
| Alternativa (host sem suporte a variavel de ambiente customizada):
| crie "config/plataforma.local.php" (mesmo esquema de
| config/mercadopago.php), retornando ['senha_hash' => '...'].
*/

$local = __DIR__ . '/plataforma.local.php';
$overrides = is_file($local) ? require $local : [];

return [
    'senha_hash' => $overrides['senha_hash'] ?? (getenv('PLATAFORMA_ADMIN_SENHA_HASH') ?: ''),
];
