<?php

declare(strict_types=1);

namespace Kadosys\Core\Providers;

use Kadosys\Core\Core\Container;

/**
 * ServiceProvider
 *
 * Classe base abstrata para todos os Service Providers da plataforma.
 * register() deve apenas registrar bindings no container; boot()
 * pode executar logica que depende de outros servicos ja registrados.
 */
abstract class ServiceProvider
{
    public function __construct(protected readonly Container $container)
    {
    }

    abstract public function register(): void;

    public function boot(): void
    {
        // Implementacao opcional nas subclasses.
    }
}
