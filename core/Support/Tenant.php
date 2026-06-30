<?php

declare(strict_types=1);

namespace Kadosys\Core\Support;

/**
 * Tenant
 *
 * Mantem o contexto do tenant (igreja, empresa, organizacao) atual
 * durante o ciclo de vida da requisicao. Usado pelo Model base para
 * filtrar automaticamente todas as consultas por tenant_id, garantindo
 * isolamento de dados entre clientes da plataforma SaaS.
 */
final class Tenant
{
    private static int|string|null $currentId = null;

    public static function setCurrent(int|string $tenantId): void
    {
        self::$currentId = $tenantId;
    }

    public static function current(): int|string|null
    {
        return self::$currentId;
    }

    public static function clear(): void
    {
        self::$currentId = null;
    }

    public static function hasContext(): bool
    {
        return self::$currentId !== null;
    }
}
