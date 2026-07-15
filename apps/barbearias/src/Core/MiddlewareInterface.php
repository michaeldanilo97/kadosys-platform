<?php

declare(strict_types=1);

namespace Barbearias\Core;

/**
 * Contrato que todo middleware da aplicacao deve implementar.
 */
interface MiddlewareInterface
{
    public function handle(Request $request): void;
}
