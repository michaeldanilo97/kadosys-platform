<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuracao geral - KADOSYS Barbearias
|--------------------------------------------------------------------------
|
| Este arquivo concentra as configuracoes nao sensiveis da aplicacao.
| Credenciais de banco de dados ficam em config/database.php, que por sua
| vez le variaveis de ambiente quando disponiveis.
|
| O BASE_PATH e calculado automaticamente a partir do diretorio do front
| controller (public/index.php), permitindo que a aplicacao funcione tanto
| em https://kadosys.com.br/apps/barbearias quanto em outros caminhos, sem
| precisar alterar codigo.
|
*/

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
// O front controller fica em /public, entao removemos esse segmento final
// para obter o caminho base da aplicacao (ex: /apps/barbearias).
$basePath = preg_replace('#/public$#', '', $scriptDir);
$basePath = $basePath === '/' ? '' : rtrim((string) $basePath, '/');

return [
    'app_name'    => 'KADOSYS Barbearias',
    'env'         => getenv('APP_ENV') ?: 'production',
    'debug'       => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'base_path'   => $basePath,
    'timezone'    => 'America/Sao_Paulo',
    'session_name' => 'kadosys_barbearias_session',
    'remember_cookie' => 'kadosys_barbearias_remember',
    // Tempo de vida do cookie "lembrar-me", em segundos (30 dias).
    'remember_ttl' => 60 * 60 * 24 * 30,
];
