<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Registro de que a comissao de um profissional, num periodo
 * especifico, ja foi paga - gerado junto com a despesa no caixa (ver
 * Barbearias\Controllers\ComissaoController::pagar). Existir uma linha
 * aqui pro mesmo profissional+periodo impede pagar a mesma comissao
 * duas vezes.
 */
final class ComissaoPagamento
{
    private const SELECT_COLUNAS = 'id, barbearia_id, profissional_id, financeiro_lancamento_id, usuario_id,
        periodo_inicio, periodo_fim, valor, comprovante_path, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly int $profissionalId,
        public readonly int $financeiroLancamentoId,
        public readonly ?int $usuarioId,
        public readonly string $periodoInicio,
        public readonly string $periodoFim,
        public readonly float $valor,
        public readonly string $comprovantePath,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * Comissao ja paga pra esse profissional que sobrepoe o periodo
     * informado - usado pra bloquear pagamento duplicado (ver
     * ComissaoController::pagar).
     */
    public static function porPeriodo(int $barbeariaId, int $profissionalId, string $periodoInicio, string $periodoFim): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM comissao_pagamentos
             WHERE barbearia_id = :barbearia_id AND profissional_id = :profissional_id
               AND periodo_inicio <= :periodo_fim AND periodo_fim >= :periodo_inicio
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'profissional_id' => $profissionalId,
            'periodo_inicio' => $periodoInicio,
            'periodo_fim' => $periodoFim,
        ]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function create(
        int $barbeariaId,
        int $profissionalId,
        int $financeiroLancamentoId,
        ?int $usuarioId,
        string $periodoInicio,
        string $periodoFim,
        float $valor,
        string $comprovantePath,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO comissao_pagamentos
                (barbearia_id, profissional_id, financeiro_lancamento_id, usuario_id, periodo_inicio, periodo_fim, valor, comprovante_path, created_at)
             VALUES
                (:barbearia_id, :profissional_id, :financeiro_lancamento_id, :usuario_id, :periodo_inicio, :periodo_fim, :valor, :comprovante_path, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'profissional_id' => $profissionalId,
            'financeiro_lancamento_id' => $financeiroLancamentoId,
            'usuario_id' => $usuarioId,
            'periodo_inicio' => $periodoInicio,
            'periodo_fim' => $periodoFim,
            'valor' => max(0, $valor),
            'comprovante_path' => $comprovantePath,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            profissionalId: (int) $row['profissional_id'],
            financeiroLancamentoId: (int) $row['financeiro_lancamento_id'],
            usuarioId: $row['usuario_id'] !== null ? (int) $row['usuario_id'] : null,
            periodoInicio: (string) $row['periodo_inicio'],
            periodoFim: (string) $row['periodo_fim'],
            valor: (float) $row['valor'],
            comprovantePath: (string) $row['comprovante_path'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
