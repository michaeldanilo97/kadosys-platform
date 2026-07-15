<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Extrato de pontos de fidelidade: cada ganho (atendimento pago) ou
 * resgate (recompensa trocada) vira uma linha aqui, dando um historico
 * auditavel do saldo de cada cliente (ver
 * Barbearias\Controllers\FidelidadeController).
 */
final class FidelidadeMovimento
{
    public const TIPO_GANHO = 'ganho';
    public const TIPO_RESGATE = 'resgate';

    private const SELECT_COLUNAS = 'id, barbearia_id, cliente_id, tipo, pontos, agendamento_id, recompensa_id, descricao, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly int $clienteId,
        public readonly string $tipo,
        public readonly int $pontos,
        public readonly ?int $agendamentoId,
        public readonly ?int $recompensaId,
        public readonly ?string $descricao,
        public readonly ?string $createdAt = null,
    ) {
    }

    /** @return array<int, self> */
    public static function historicoDoCliente(int $clienteId, int $barbeariaId, int $limite = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM fidelidade_movimentos
             WHERE cliente_id = :cliente_id AND barbearia_id = :barbearia_id
             ORDER BY created_at DESC, id DESC LIMIT {$limite}"
        );
        $stmt->execute(['cliente_id' => $clienteId, 'barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(
        int $barbeariaId,
        int $clienteId,
        string $tipo,
        int $pontos,
        ?int $agendamentoId,
        ?int $recompensaId,
        ?string $descricao,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO fidelidade_movimentos (barbearia_id, cliente_id, tipo, pontos, agendamento_id, recompensa_id, descricao, created_at)
             VALUES (:barbearia_id, :cliente_id, :tipo, :pontos, :agendamento_id, :recompensa_id, :descricao, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'cliente_id' => $clienteId,
            'tipo' => $tipo === self::TIPO_RESGATE ? self::TIPO_RESGATE : self::TIPO_GANHO,
            'pontos' => $pontos,
            'agendamento_id' => $agendamentoId,
            'recompensa_id' => $recompensaId,
            'descricao' => $descricao,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            clienteId: (int) $row['cliente_id'],
            tipo: (string) $row['tipo'],
            pontos: (int) $row['pontos'],
            agendamentoId: $row['agendamento_id'] !== null ? (int) $row['agendamento_id'] : null,
            recompensaId: $row['recompensa_id'] !== null ? (int) $row['recompensa_id'] : null,
            descricao: $row['descricao'] ?? null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
