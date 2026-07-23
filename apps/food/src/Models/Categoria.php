<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Categoria de PRODUTO (o prato/item vendido - Doces, Bolos, Salgados,
 * etc), usada a partir da Fase 3 em produtos.categoria_id. Nao e um
 * ENUM fixo: cada restaurante recebe o seed padrao no cadastro (ver
 * seedPadrao(), chamada por CadastroController::enviar()) mas a lista e
 * livremente editavel dali em diante.
 */
final class Categoria
{
    private const SELECT_COLUNAS = 'id, restaurante_id, nome, ativo, created_at';

    /** @var array<int, string> */
    private const NOMES_PADRAO = ['Doces', 'Bolos', 'Salgados', 'Bebidas', 'Combos', 'Tortas', 'Cafés', 'Outros'];

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly string $nome,
        public readonly bool $ativo,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $restauranteId, int $page, int $perPage, string $search = ''): array
    {
        $where = 'WHERE restaurante_id = :restaurante_id';
        $params = ['restaurante_id' => $restauranteId];

        if ($search !== '') {
            $where .= ' AND nome LIKE :busca';
            $params['busca'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM categorias {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'items' => array_map(self::fromRow(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public static function find(int $id, int $restauranteId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM categorias WHERE id = :id AND restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function ativas(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM categorias WHERE restaurante_id = :restaurante_id AND ativo = 1 ORDER BY nome ASC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(int $restauranteId, string $nome): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO categorias (restaurante_id, nome, ativo, created_at) VALUES (:restaurante_id, :nome, 1, NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'nome' => trim($nome),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, int $restauranteId, string $nome, bool $ativo): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE categorias SET nome = :nome, ativo = :ativo WHERE id = :id AND restaurante_id = :restaurante_id'
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
        $stmt = Database::connection()->prepare('DELETE FROM categorias WHERE id = :id AND restaurante_id = :restaurante_id');
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    /**
     * Semeia as 8 categorias padrao pra um restaurante recem-criado
     * (chamada uma unica vez, no cadastro) - a lista fica editavel
     * dali em diante, entao isso e so um ponto de partida.
     */
    public static function seedPadrao(int $restauranteId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO categorias (restaurante_id, nome, ativo, created_at) VALUES (:restaurante_id, :nome, 1, NOW())'
        );

        foreach (self::NOMES_PADRAO as $nome) {
            $stmt->execute(['restaurante_id' => $restauranteId, 'nome' => $nome]);
        }
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM categorias {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
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
