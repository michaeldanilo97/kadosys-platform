<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Centro de custo - agrupamento opcional de contas a pagar/receber
 * (ex.: "Cozinha", "Delivery", "Administrativo"). Lista tipicamente
 * curta (poucas dezenas no maximo), entao sem paginacao - so listagem
 * completa + CRUD simples, mesmo padrao de Categoria.
 */
final class CentroCusto
{
    private const SELECT_COLUNAS = 'id, restaurante_id, nome, ativo, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly string $nome,
        public readonly bool $ativo,
        public readonly ?string $createdAt = null,
    ) {
    }

    /** @return array<int, self> */
    public static function todos(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM centros_custo WHERE restaurante_id = :restaurante_id ORDER BY nome ASC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /** @return array<int, self> */
    public static function ativos(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM centros_custo WHERE restaurante_id = :restaurante_id AND ativo = 1 ORDER BY nome ASC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id, int $restauranteId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM centros_custo WHERE id = :id AND restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function create(int $restauranteId, string $nome): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO centros_custo (restaurante_id, nome, ativo, created_at) VALUES (:restaurante_id, :nome, 1, NOW())'
        );
        $stmt->execute(['restaurante_id' => $restauranteId, 'nome' => trim($nome)]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, int $restauranteId, string $nome, bool $ativo): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE centros_custo SET nome = :nome, ativo = :ativo WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'ativo' => $ativo ? 1 : 0,
            'id' => $id,
            'restaurante_id' => $restauranteId,
        ]);
    }

    public static function delete(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM centros_custo WHERE id = :id AND restaurante_id = :restaurante_id');
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            nome: (string) $row['nome'],
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
