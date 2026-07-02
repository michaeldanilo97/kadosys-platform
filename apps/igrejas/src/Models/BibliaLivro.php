<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Model de livro biblico (dado de referencia, ja populado pela migracao
 * 005 com os 66 livros canonicos).
 */
final class BibliaLivro
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $abreviacao,
        public readonly string $testamento,
        public readonly int $totalCapitulos,
        public readonly int $ordem,
    ) {
    }

    /**
     * @return array<int, self>
     */
    public static function all(): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM biblia_livros ORDER BY ordem ASC');
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM biblia_livros WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            abreviacao: (string) $row['abreviacao'],
            testamento: (string) $row['testamento'],
            totalCapitulos: (int) $row['total_capitulos'],
            ordem: (int) $row['ordem'],
        );
    }
}
