<?php

declare(strict_types=1);

/**
 * config/app.php
 *
 * Configuracoes gerais da aplicacao. Os valores sao lidos diretamente
 * do .env atraves de env(), nunca devendo ser fixados (hardcoded) aqui.
 */

return [
    'name' => env('APP_NAME', 'Kadosys Platform'),

    'env' => env('APP_ENV', 'production'),

    'debug' => env('APP_DEBUG', false),

    'key' => env('APP_KEY', ''),

    'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),

    'locale' => env('APP_LOCALE', 'pt_BR'),

    // URL completa da plataforma, sem barra no final. Ex: https://dominio.com
    'url' => env('APP_URL', 'http://localhost'),

    // Caminho do subdiretorio onde a plataforma esta instalada.
    // Vazio quando publicada na raiz do dominio.
    // Ex: "/app/igrejas", "/apps/crm"
    'base_path' => env('APP_BASE_PATH', ''),

    // Identificador da pasta do aplicativo ativo dentro de apps/.
    'active_app' => env('APP_ACTIVE', 'igrejas'),
];
