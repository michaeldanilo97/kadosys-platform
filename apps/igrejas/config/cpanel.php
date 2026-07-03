<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Credenciais e parametros do cPanel - KADOSYS Igrejas
|--------------------------------------------------------------------------
|
| Usadas pelo provisionamento automatico de novas igrejas (banco de
| dados + subdominio, ver Igrejas\Core\CpanelUapiClient e
| Igrejas\Core\Provisionador), disparado pelo webhook do Mercado Pago
| quando um cadastro publico (ver CadastroController) e pago.
|
| Assim como config/mercadopago.php, aqui NAO existe valor padrao com
| credencial real - vem de variavel de ambiente, ou (alternativa, ver
| mesmo padrao usado pra o Mercado Pago) do arquivo
| config/cpanel.local.php, criado direto no servidor, fora do Git.
|
| Variaveis esperadas:
|   CPANEL_HOST             - host do servidor cPanel (ex.:
|                              server.vipreseller25ssd.com), sem
|                              "https://" na frente.
|   CPANEL_PORT             - porta da API (normalmente 2083).
|   CPANEL_USERNAME         - usuario da conta cPanel (ex.: kadosys1) -
|                              tambem usado como prefixo dos bancos/
|                              usuarios MySQL criados (regra do proprio
|                              cPanel).
|   CPANEL_API_TOKEN        - token gerado em "Manage API Tokens" no
|                              cPanel.
|   CPANEL_ROOT_DOMAIN      - dominio raiz onde os subdominios de cada
|                              igreja sao criados (ex.: kadosys.com.br).
|   CPANEL_SUBDOMAIN_DOCROOT - pasta (relativa ao home da conta) que o
|                              subdominio deve apontar - a MESMA pasta
|                              onde a aplicacao apps/igrejas/public ja
|                              esta publicada hoje, ja que todas as
|                              igrejas compartilham o mesmo codigo,
|                              apenas com bancos de dados diferentes
|                              (ex.: public_html/apps/igrejas/public).
|
*/

$local = __DIR__ . '/cpanel.local.php';
$overrides = is_file($local) ? require $local : [];

return [
    'host' => $overrides['host'] ?? (getenv('CPANEL_HOST') ?: ''),
    'port' => $overrides['port'] ?? (getenv('CPANEL_PORT') ?: '2083'),
    'username' => $overrides['username'] ?? (getenv('CPANEL_USERNAME') ?: ''),
    'api_token' => $overrides['api_token'] ?? (getenv('CPANEL_API_TOKEN') ?: ''),
    'root_domain' => $overrides['root_domain'] ?? (getenv('CPANEL_ROOT_DOMAIN') ?: ''),
    'subdomain_docroot' => $overrides['subdomain_docroot'] ?? (getenv('CPANEL_SUBDOMAIN_DOCROOT') ?: ''),
];
