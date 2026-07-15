<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Recompensa cadastrada pra troca de pontos do programa de fidelidade
 * (ver Barbearias\Controllers\FidelidadeController). Toda consulta
 * PRECISA filtrar por barbearia_id.
 */
final class FidelidadeRecompensa
{
    private const SELECT_COLUNAS = 'id, barbearia_id, nome, pontos_necessarios, ativo, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly string $nome,
        public readonly int $pontosNecessarios,
        public readonly bool $ativo,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function find(int $id, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM fidelidade_recompensas WHERE id = :id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function todas(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM fidelidade_recompensas WHERE barbearia_id = :barbearia_id ORDER BY pontos_necessarios ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /** @return array<int, self> */
    public static function ativas(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM fidelidade_recompensas WHERE barbearia_id = :barbearia_id AND ativo = 1 ORDER BY pontos_necessarios ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(int $barbeariaId, string $nome, int $pontosNecessarios): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO fidelidade_recompensas (barbearia_id, nome, pontos_necessarios, ativo, created_at)
             VALUES (:barbearia_id, :nome, :pontos_necessarios, 1, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'nome' => trim($nome),
            'pontos_necessarios' => max(1, $pontosNecessarios),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM fidelidade_recompensas WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            nome: (string) $row['nome'],
            pontosNecessarios: (int) $row['pontos_necessarios'],
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
