<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Historico de mudancas de tom de um Louvor - cada departamento muda o
 * tom pra tocar e ninguem sabia qual era o "oficial"; este historico
 * documenta quem mudou, quando e de qual tom pra qual (ver
 * Louvor::create()/update(), que registram uma entrada aqui sempre que
 * o campo tom_atual muda de verdade).
 */
final class LouvorTomHistorico
{
    public function __construct(
        public readonly int $id,
        public readonly int $louvorId,
        public readonly ?string $tomAnterior,
        public readonly string $tomNovo,
        public readonly ?string $observacao,
        public readonly ?int $alteradoPor,
        public readonly ?string $alteradoPorNome,
        public readonly string $createdAt,
    ) {
    }

    public static function registrar(int $louvorId, ?string $tomAnterior, string $tomNovo, ?string $observacao, ?int $alteradoPor): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO louvor_tons_historico (louvor_id, tom_anterior, tom_novo, observacao, alterado_por, created_at)
             VALUES (:louvor_id, :tom_anterior, :tom_novo, :observacao, :alterado_por, NOW())'
        );
        $stmt->execute([
            'louvor_id' => $louvorId,
            'tom_anterior' => $tomAnterior,
            'tom_novo' => $tomNovo,
            'observacao' => $observacao,
            'alterado_por' => $alteradoPor,
        ]);
    }

    /**
     * @return array<int, self>
     */
    public static function doLouvor(int $louvorId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT h.*, u.name AS alterado_por_nome
             FROM louvor_tons_historico h
             LEFT JOIN users u ON u.id = h.alterado_por
             WHERE h.louvor_id = :louvor_id
             ORDER BY h.created_at DESC, h.id DESC'
        );
        $stmt->execute(['louvor_id' => $louvorId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            louvorId: (int) $row['louvor_id'],
            tomAnterior: $row['tom_anterior'],
            tomNovo: (string) $row['tom_novo'],
            observacao: $row['observacao'],
            alteradoPor: $row['alterado_por'] !== null ? (int) $row['alterado_por'] : null,
            alteradoPorNome: $row['alterado_por_nome'] ?? null,
            createdAt: (string) $row['created_at'],
        );
    }
}
