<?php

declare(strict_types=1);

/**
 * config/tenant.php
 *
 * Configuracoes do isolamento multi-tenant da plataforma. Quando
 * habilitado, os Models marcados como $tenantAware filtram
 * automaticamente todas as consultas pela coluna tenant_id.
 */

return [
    'enabled' => env('TENANT_ENABLED', true),
    'default_id' => env('TENANT_DEFAULT_ID', 1),
];
