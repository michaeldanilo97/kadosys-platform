<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Credenciais do Mercado Pago - KADOSYS Food
|--------------------------------------------------------------------------
|
| Mesma conta/credenciais ja usadas pelo KADOSYS Igrejas/Barbearias/
| Academias - so muda o valor/plano cobrado. Usadas pelo modulo de
| assinatura recorrente (Checkout Pro + Assinaturas/preapproval + Pix
| avulso, ver Food\Core\MercadoPagoClient). NAO existe valor padrao com
| credencial real: sao dados financeiros sensiveis e tem que vir de
| variavel de ambiente configurada no servidor (cPanel > MultiPHP INI
| Editor, ou equivalente) - se o subdominio de Food nao herdar as mesmas
| variaveis de ambiente dos outros apps, configure de novo aqui (mesmos
| valores). Sem elas configuradas, o modulo de assinatura fica desativado
| (ver MercadoPagoClient::configurado()).
|
| Variaveis esperadas:
|   MP_ACCESS_TOKEN   - Access Token (producao ou teste) do Mercado Pago.
|   MP_PUBLIC_KEY     - Public Key correspondente.
|   MP_WEBHOOK_SECRET - Chave secreta de assinatura de webhook.
|   APP_URL           - URL publica completa desta instalacao (ex.:
|                        https://food.kadosys.com.br).
|
| Alternativa quando o provedor de hospedagem nao suporta variavel de
| ambiente customizada via painel: crie o arquivo
| "config/mercadopago.local.php" (mesma pasta deste arquivo) direto no
| servidor - por FTP ou pelo "Gerenciador de Arquivos" do cPanel, nunca
| pelo Git - retornando um array com as mesmas 4 chaves abaixo. Esse
| arquivo esta no .gitignore de proposito. Exemplo do conteudo:
|
|   <?php
|   return [
|       'access_token' => 'APP_USR-...',
|       'public_key' => 'APP_USR-...',
|       'webhook_secret' => '...',
|       'app_url' => 'https://food.kadosys.com.br',
|   ];
|
*/

$local = __DIR__ . '/mercadopago.local.php';
$overrides = is_file($local) ? require $local : [];

return [
    'access_token' => $overrides['access_token'] ?? (getenv('MP_ACCESS_TOKEN') ?: ''),
    'public_key' => $overrides['public_key'] ?? (getenv('MP_PUBLIC_KEY') ?: ''),
    'webhook_secret' => $overrides['webhook_secret'] ?? (getenv('MP_WEBHOOK_SECRET') ?: ''),
    'app_url' => rtrim((string) ($overrides['app_url'] ?? (getenv('APP_URL') ?: '')), '/'),
];
