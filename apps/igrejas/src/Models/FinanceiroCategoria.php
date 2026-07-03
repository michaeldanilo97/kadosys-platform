<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Model de FinanceiroCategoria (modulo Financeiro).
 *
 * Categoriza cada lancamento (dizimo, oferta, aluguel, etc.) - cada
 * categoria tem um tipo fixo (entrada ou saida), pra facilitar o
 * cadastro de um lancamento (o formulario so mostra categorias do tipo
 * escolhido) e os totais por categoria nos relatorios.
 */
final class FinanceiroCategoria
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $tipo,
        public readonly string $status,
    ) {
    }

    /**
     * @return array<int, self>
     */
    public static function all(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM financeiro_categorias ORDER BY tipo ASC, nome ASC');

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * @return array<int, self>
     */
    public static function ativasPorTipo(?string $tipo = null): array
    {
        if ($tipo !== null) {
            $stmt = Database::connection()->prepare(
                "SELECT * FROM financeiro_categorias WHERE status = 'ativa' AND tipo = :tipo ORDER BY nome ASC"
            );
            $stmt->execute(['tipo' => $tipo]);
        } else {
            $stmt = Database::connection()->query(
                "SELECT * FROM financeiro_categorias WHERE status = 'ativa' ORDER BY tipo ASC, nome ASC"
            );
        }

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM financeiro_categorias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function create(string $nome, string $tipo): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO financeiro_categorias (nome, tipo, status, created_at) VALUES (:nome, :tipo, \'ativa\', NOW())'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'tipo' => in_array($tipo, ['entrada', 'saida'], true) ? $tipo : 'entrada',
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function alternarStatus(int $id): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE financeiro_categorias SET status = IF(status = 'ativa', 'inativa', 'ativa') WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM financeiro_categorias WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            tipo: (string) $row['tipo'],
            status: (string) $row['status'],
        );
    }
}
