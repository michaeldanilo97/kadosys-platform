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
*/

return [
    'access_token' => getenv('MP_ACCESS_TOKEN') ?: '',
    'public_key' => getenv('MP_PUBLIC_KEY') ?: '',
    'webhook_secret' => getenv('MP_WEBHOOK_SECRET') ?: '',
    'app_url' => rtrim((string) (getenv('APP_URL') ?: ''), '/'),
];
