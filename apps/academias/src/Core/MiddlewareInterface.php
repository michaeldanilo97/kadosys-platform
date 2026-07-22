<?php

declare(strict_types=1);

namespace Academias\Core;

/**
 * Contrato que todo middleware da aplicacao deve implementar.
 */
interface MiddlewareInterface
{
    public function handle(Request $request): void;
}
