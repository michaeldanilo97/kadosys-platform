<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Item da ficha tecnica (receita) de um produto - um ingrediente com a
 * quantidade usada no LOTE inteiro da receita (nao por unidade vendida,
 * ver Produto::rendimento) e a perda percentual daquele ingrediente
 * especifico no preparo.
 *
 * "unidade" e sempre copiada do proprio ingrediente no momento em que o
 * item e adicionado (nao e escolhida pelo usuario) - evita ter que
 * converter unidades diferentes (kg->g, l->ml) na hora de custear, o
 * que nao foi pedido.
 */
final class FichaTecnicaItem
{
    private const SELECT_COLUNAS = 'fti.id, fti.produto_id, fti.ingrediente_id, fti.quantidade,
        fti.unidade, fti.perda_percentual, i.nome AS ingrediente_nome, i.preco_atual AS ingrediente_preco_atual';

    private const JOINS = 'FROM ficha_tecnica_itens fti INNER JOIN ingredientes i ON i.id = fti.ingrediente_id';

    public function __construct(
        public readonly int $id,
        public readonly int $produtoId,
        public readonly int $ingredienteId,
        public readonly float $quantidade,
        public readonly string $unidade,
        public readonly float $perdaPercentual,
        public readonly string $ingredienteNome,
        public readonly float $ingredientePrecoAtual,
    ) {
    }

    /**
     * Itens da ficha tecnica de um produto - o `restaurante_id` do
     * produto ja foi conferido antes (ver ProdutoController), entao
     * aqui basta filtrar por produto_id.
     *
     * @return array<int, self>
     */
    public static function doProduto(int $produtoId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . ' WHERE fti.produto_id = :produto_id ORDER BY i.nome ASC'
        );
        $stmt->execute(['produto_id' => $produtoId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id, int $produtoId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . ' WHERE fti.id = :id AND fti.produto_id = :produto_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'produto_id' => $produtoId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Soma do custo de todos os itens da receita pro LOTE INTEIRO
     * (ainda sem dividir pelo rendimento) - cada item conta
     * quantidade * (1 + perda_percentual / 100) * preco_atual do
     * ingrediente. Usado por Produto::recalcularCusto().
     */
    public static function custoTotalDoProduto(int $produtoId): float
    {
        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(SUM(fti.quantidade * (1 + fti.perda_percentual / 100) * i.preco_atual), 0) AS custo
             ' . self::JOINS . ' WHERE fti.produto_id = :produto_id'
        );
        $stmt->execute(['produto_id' => $produtoId]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * IDs distintos de produtos (de um restaurante) cuja ficha tecnica
     * usa um ingrediente - usado pra recalcular o custo em cascata
     * quando o preco desse ingrediente muda (ver
     * Produto::recalcularCustoDeProdutosComIngrediente()).
     *
     * @return array<int, int>
     */
    public static function produtoIdsComIngrediente(int $ingredienteId, int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT DISTINCT fti.produto_id
             FROM ficha_tecnica_itens fti
             INNER JOIN produtos p ON p.id = fti.produto_id
             WHERE fti.ingrediente_id = :ingrediente_id AND p.restaurante_id = :restaurante_id'
        );
        $stmt->execute(['ingrediente_id' => $ingredienteId, 'restaurante_id' => $restauranteId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function create(int $produtoId, int $ingredienteId, float $quantidade, string $unidade, float $perdaPercentual): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO ficha_tecnica_itens (produto_id, ingrediente_id, quantidade, unidade, perda_percentual, created_at)
             VALUES (:produto_id, :ingrediente_id, :quantidade, :unidade, :perda_percentual, NOW())'
        );
        $stmt->execute([
            'produto_id' => $produtoId,
            'ingrediente_id' => $ingredienteId,
            'quantidade' => $quantidade,
            'unidade' => $unidade,
            'perda_percentual' => $perdaPercentual,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function delete(int $id, int $produtoId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM ficha_tecnica_itens WHERE id = :id AND produto_id = :produto_id');
        $stmt->execute(['id' => $id, 'produto_id' => $produtoId]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            produtoId: (int) $row['produto_id'],
            ingredienteId: (int) $row['ingrediente_id'],
            quantidade: (float) $row['quantidade'],
            unidade: (string) $row['unidade'],
            perdaPercentual: (float) $row['perda_percentual'],
            ingredienteNome: (string) $row['ingrediente_nome'],
            ingredientePrecoAtual: (float) $row['ingrediente_preco_atual'],
        );
    }
}
