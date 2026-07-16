<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Credenciais e parametros do cPanel - KADOSYS Super Admin
|--------------------------------------------------------------------------
|
| Reutiliza as MESMAS credenciais/variaveis ja usadas pelo provisionamento
| automatico do Igrejas (ver apps/igrejas/config/cpanel.php e
| Igrejas\Core\CpanelUapiClient) - e a mesma conta cPanel, entao nao ha
| necessidade de gerar/configurar um segundo token so pro Super Admin.
| Usado aqui para EXCLUIR (nao criar) banco/usuario MySQL quando uma
| igreja e removida via painel (ver Superadmin\Core\Desprovisionador).
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
