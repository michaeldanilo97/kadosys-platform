<?php

declare(strict_types=1);

namespace Kadosys\Core\Console;

/**
 * CommandInterface
 *
 * Contrato que todo comando CLI da plataforma deve implementar.
 */
interface CommandInterface
{
    /**
     * @param array<int, string> $arguments
     * @return int Codigo de saida (0 = sucesso).
     */
    public function execute(array $arguments): int;
}
