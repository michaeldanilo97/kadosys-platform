<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuracao geral - KADOSYS Super Admin
|--------------------------------------------------------------------------
|
| Painel unico que cruza dados de TODOS os produtos KADOSYS (Igrejas,
| Barbearias, e futuros) - cada produto continua com sua propria
| aplicacao/banco de dados independente (ver config/database_igrejas.php
| e config/database_barbearias.php); este app so LE/ESCREVE nesses
| bancos de fora, sem duplicar nenhuma logica de negocio de cada
| produto.
|
| O BASE_PATH e calculado automaticamente a partir do diretorio do front
| controller (public/index.php), mesmo padrao usado em apps/igrejas e
| apps/barbearias.
|
*/

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = preg_replace('#/public$#', '', $scriptDir);
$basePath = $basePath === '/' ? '' : rtrim((string) $basePath, '/');

return [
    'app_name'    => 'KADOSYS Super Admin',
    'env'         => getenv('APP_ENV') ?: 'production',
    'debug'       => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'base_path'   => $basePath,
    'timezone'    => 'America/Sao_Paulo',
    'session_name' => 'kadosys_superadmin_session',
];
