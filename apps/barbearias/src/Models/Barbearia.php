<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Model de Barbearia (o "tenant" do multi-tenant logico).
 *
 * Diferente do KADOSYS Igrejas (onde o tenant e um registro no banco
 * CENTRAL, separado do banco isolado de cada igreja), aqui a barbearia
 * e so mais uma tabela dentro do MESMO banco compartilhado - nao tem
 * distincao entre "banco central" e "banco do tenant".
 */
final class Barbearia
{
    private const SELECT_COLUNAS = 'id, nome, slug, telefone, status, created_at';

    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $slug,
        public readonly ?string $telefone,
        public readonly string $status,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM barbearias WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function slugDisponivel(string $slug): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM barbearias WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() === false;
    }

    public static function criar(string $nome, string $slug, ?string $telefone = null): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO barbearias (nome, slug, telefone, status, created_at)
             VALUES (:nome, :slug, :telefone, 'ativo', NOW())"
        );
        $stmt->execute([
            'nome' => trim($nome),
            'slug' => trim($slug),
            'telefone' => $telefone,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            slug: (string) $row['slug'],
            telefone: $row['telefone'] ?? null,
            status: (string) $row['status'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
