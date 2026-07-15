<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Plano de assinatura de cliente: pacote de N atendimentos por mes por
 * um preco fixo (cobrado fora do sistema). Toda consulta PRECISA
 * filtrar por barbearia_id.
 */
final class AssinaturaPlano
{
    private const SELECT_COLUNAS = 'id, barbearia_id, nome, preco, atendimentos_por_mes, ativo, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly string $nome,
        public readonly float $preco,
        public readonly int $atendimentosPorMes,
        public readonly bool $ativo,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function find(int $id, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM assinatura_planos WHERE id = :id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function todos(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM assinatura_planos WHERE barbearia_id = :barbearia_id ORDER BY preco ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /** @return array<int, self> */
    public static function ativos(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM assinatura_planos WHERE barbearia_id = :barbearia_id AND ativo = 1 ORDER BY preco ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(int $barbeariaId, string $nome, float $preco, int $atendimentosPorMes): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO assinatura_planos (barbearia_id, nome, preco, atendimentos_por_mes, ativo, created_at)
             VALUES (:barbearia_id, :nome, :preco, :atendimentos_por_mes, 1, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'nome' => trim($nome),
            'preco' => max(0, $preco),
            'atendimentos_por_mes' => max(1, $atendimentosPorMes),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM assinatura_planos WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            nome: (string) $row['nome'],
            preco: (float) $row['preco'],
            atendimentosPorMes: (int) $row['atendimentos_por_mes'],
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
