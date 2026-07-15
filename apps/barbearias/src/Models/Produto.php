<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Produto vendido avulsamente (nao vinculado a um agendamento), com
 * controle simples de estoque. Toda consulta PRECISA filtrar por
 * barbearia_id, senao vazaria o catalogo de uma barbearia pra outra.
 */
final class Produto
{
    private const SELECT_COLUNAS = 'id, barbearia_id, nome, preco, estoque_atual, estoque_minimo, ativo, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly string $nome,
        public readonly float $preco,
        public readonly int $estoqueAtual,
        public readonly int $estoqueMinimo,
        public readonly bool $ativo,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $barbeariaId, int $page, int $perPage, string $search = ''): array
    {
        $where = 'WHERE barbearia_id = :barbearia_id';
        $params = ['barbearia_id' => $barbeariaId];

        if ($search !== '') {
            $where .= ' AND nome LIKE :busca';
            $params['busca'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM produtos {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
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

    public static function find(int $id, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM produtos WHERE id = :id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function ativos(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM produtos WHERE barbearia_id = :barbearia_id AND ativo = 1 ORDER BY nome ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Produtos ativos com estoque no ou abaixo do minimo cadastrado -
     * usado pro alerta de reposicao na tela de Produtos.
     *
     * @return array<int, self>
     */
    public static function comEstoqueBaixo(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM produtos
             WHERE barbearia_id = :barbearia_id AND ativo = 1 AND estoque_atual <= estoque_minimo
             ORDER BY nome ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(int $barbeariaId, string $nome, float $preco, int $estoqueAtual, int $estoqueMinimo): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO produtos (barbearia_id, nome, preco, estoque_atual, estoque_minimo, ativo, created_at)
             VALUES (:barbearia_id, :nome, :preco, :estoque_atual, :estoque_minimo, 1, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'nome' => trim($nome),
            'preco' => $preco,
            'estoque_atual' => max(0, $estoqueAtual),
            'estoque_minimo' => max(0, $estoqueMinimo),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, int $barbeariaId, string $nome, float $preco, int $estoqueAtual, int $estoqueMinimo, bool $ativo): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE produtos SET nome = :nome, preco = :preco, estoque_atual = :estoque_atual,
                estoque_minimo = :estoque_minimo, ativo = :ativo
             WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'preco' => $preco,
            'estoque_atual' => max(0, $estoqueAtual),
            'estoque_minimo' => max(0, $estoqueMinimo),
            'ativo' => $ativo ? 1 : 0,
            'id' => $id,
            'barbearia_id' => $barbeariaId,
        ]);
    }

    /**
     * Baixa atomica de estoque: a condicao "estoque_atual >= quantidade"
     * dentro do proprio UPDATE evita estoque negativo mesmo em caso de
     * duas vendas simultaneas do mesmo produto - se nao sobrar linha
     * afetada, e porque nao havia estoque suficiente no momento exato
     * do UPDATE.
     */
    public static function baixarEstoque(int $id, int $barbeariaId, int $quantidade): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE produtos SET estoque_atual = estoque_atual - :quantidade
             WHERE id = :id AND barbearia_id = :barbearia_id AND estoque_atual >= :quantidade_check'
        );
        $stmt->execute([
            'quantidade' => $quantidade,
            'quantidade_check' => $quantidade,
            'id' => $id,
            'barbearia_id' => $barbeariaId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM produtos WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM produtos {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            nome: (string) $row['nome'],
            preco: (float) $row['preco'],
            estoqueAtual: (int) $row['estoque_atual'],
            estoqueMinimo: (int) $row['estoque_minimo'],
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
