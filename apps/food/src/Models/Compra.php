<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Cabecalho de uma compra de ingredientes. "valorTotal" e cache (soma
 * dos subtotais dos itens + frete), recalculado a cada item adicionado
 * (ver recalcularValorTotal(), chamado por CompraItem::create()).
 *
 * Uma compra e um registro apendice-so nesta entrega: nao ha
 * edicao/exclusao de itens ja lancados, porque isso ja alterou estoque
 * e preco de verdade - desfazer com seguranca exigiria reverter ambos
 * mesmo que o estoque ja tenha sido parcialmente consumido por vendas
 * (Fase 5), o que fica fora de escopo aqui.
 */
final class Compra
{
    private const SELECT_COLUNAS = 'id, restaurante_id, fornecedor_id, data_compra, frete, observacao,
        valor_total, created_at, updated_at';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly ?int $fornecedorId,
        public readonly string $dataCompra,
        public readonly float $frete,
        public readonly ?string $observacao,
        public readonly float $valorTotal,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $restauranteId, int $page, int $perPage): array
    {
        $total = (int) self::contar($restauranteId);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM compras WHERE restaurante_id = :restaurante_id
             ORDER BY data_compra DESC, id DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

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
            'SELECT ' . self::SELECT_COLUNAS . ' FROM compras WHERE id = :id AND restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function create(int $restauranteId, ?int $fornecedorId, string $dataCompra, float $frete, ?string $observacao): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO compras (restaurante_id, fornecedor_id, data_compra, frete, observacao, valor_total, created_at, updated_at)
             VALUES (:restaurante_id, :fornecedor_id, :data_compra, :frete, :observacao, :valor_total, NOW(), NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'fornecedor_id' => $fornecedorId,
            'data_compra' => $dataCompra,
            'frete' => $frete,
            'valor_total' => $frete,
            'observacao' => $observacao,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Recalcula o valor_total (soma dos subtotais dos itens + frete) -
     * chamado sempre que um item e adicionado.
     */
    public static function recalcularValorTotal(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE compras SET valor_total = frete + (
                SELECT COALESCE(SUM(subtotal), 0) FROM compra_itens WHERE compra_id = compras.id
             ), updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    /**
     * Existe alguma compra desse fornecedor? Usado pra bloquear a
     * exclusao do fornecedor (ver FornecedorController::destroy()) -
     * apagar o fornecedor apagaria o historico de custo de compra dele.
     */
    public static function existeComFornecedor(int $fornecedorId, int $restauranteId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM compras WHERE fornecedor_id = :fornecedor_id AND restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['fornecedor_id' => $fornecedorId, 'restaurante_id' => $restauranteId]);

        return $stmt->fetch() !== false;
    }

    private static function contar(int $restauranteId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM compras WHERE restaurante_id = :restaurante_id');
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return (int) $stmt->fetchColumn();
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            fornecedorId: $row['fornecedor_id'] !== null ? (int) $row['fornecedor_id'] : null,
            dataCompra: (string) $row['data_compra'],
            frete: (float) $row['frete'],
            observacao: $row['observacao'] !== null ? (string) $row['observacao'] : null,
            valorTotal: (float) $row['valor_total'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
