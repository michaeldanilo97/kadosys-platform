<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Credenciais do Mercado Pago - KADOSYS Igrejas
|--------------------------------------------------------------------------
|
| Usadas pelo modulo de assinatura recorrente (Checkout Pro + Assinaturas/
| preapproval, ver Igrejas\Core\MercadoPagoClient). Diferente de
| config/database.php, aqui NAO existe valor padrao com credencial real:
| sao dados financeiros sensiveis e tem que vir de variavel de ambiente
| configurada no servidor (cPanel > MultiPHP INI Editor > variaveis de
| ambiente, ou equivalente). Sem elas configuradas, o modulo de
| assinatura fica desativado (ver MercadoPagoClient::configurado()) e a
| troca de plano continua disponivel manualmente em Configuracoes.
|
| Variaveis esperadas:
|   MP_ACCESS_TOKEN   - Access Token (producao ou teste) do Mercado Pago.
|   MP_PUBLIC_KEY     - Public Key correspondente (nao usada no backend
|                        hoje, mas reservada para um checkout embutido
|                        futuro).
|   MP_WEBHOOK_SECRET - Chave secreta de assinatura de webhook, gerada no
|                        painel do Mercado Pago ao configurar a URL de
|                        notificacao.
|   APP_URL           - URL publica completa desta instalacao (ex.:
|                        https://kadosys.com.br/apps/igrejas), necessaria
|                        porque o Mercado Pago precisa de uma URL real de
|                        retorno (nao funciona com localhost).
|
| Alternativa quando o provedor de hospedagem nao suporta variavel de
| ambiente customizada via painel: crie o arquivo
| "config/mercadopago.local.php" (mesma pasta deste arquivo) direto no
| servidor - por FTP ou pelo "Gerenciador de Arquivos" do cPanel, nunca
| pelo Git - retornando um array com as mesmas 4 chaves abaixo. Esse
| arquivo esta no .gitignore de proposito, entao nunca vai parar no
| repositorio nem em lugar nenhum fora do proprio servidor. Exemplo do
| conteudo desse arquivo:
|
|   <?php
|   return [
|       'access_token' => 'APP_USR-...',
|       'public_key' => 'APP_USR-...',
|       'webhook_secret' => '...',
|       'app_url' => 'https://kadosys.com.br/apps/igrejas',
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
