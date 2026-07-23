<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Item de pedido. So pode ser criado/removido enquanto o pedido ainda
 * esta em "recebido" (ainda sendo montado, sem baixa de estoque feita) -
 * essa checagem fica no controller, igual ao padrao ja usado em
 * FichaTecnicaItem (compor agora, efeito real so na confirmacao).
 */
final class PedidoItem
{
    private const SELECT_COLUNAS = 'pi.id, pi.pedido_id, pi.produto_id, pi.quantidade, pi.preco_unitario,
        pi.subtotal, pi.observacao, pi.created_at, p.nome AS produto_nome';

    private const JOINS = 'FROM pedido_itens pi INNER JOIN produtos p ON p.id = pi.produto_id';

    public function __construct(
        public readonly int $id,
        public readonly int $pedidoId,
        public readonly int $produtoId,
        public readonly int $quantidade,
        public readonly float $precoUnitario,
        public readonly float $subtotal,
        public readonly ?string $observacao,
        public readonly string $produtoNome,
        public readonly ?string $createdAt = null,
    ) {
    }

    /** @return array<int, self> */
    public static function doPedido(int $pedidoId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . ' WHERE pi.pedido_id = :pedido_id ORDER BY pi.id ASC'
        );
        $stmt->execute(['pedido_id' => $pedidoId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id, int $pedidoId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . ' WHERE pi.id = :id AND pi.pedido_id = :pedido_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'pedido_id' => $pedidoId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function create(
        int $pedidoId,
        int $restauranteId,
        int $produtoId,
        int $quantidade,
        float $precoUnitario,
        ?string $observacao,
    ): int {
        $subtotal = round($quantidade * $precoUnitario, 2);

        $stmt = Database::connection()->prepare(
            'INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, subtotal, observacao, created_at)
             VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario, :subtotal, :observacao, NOW())'
        );
        $stmt->execute([
            'pedido_id' => $pedidoId,
            'produto_id' => $produtoId,
            'quantidade' => $quantidade,
            'preco_unitario' => $precoUnitario,
            'subtotal' => $subtotal,
            'observacao' => $observacao,
        ]);

        $id = (int) Database::connection()->lastInsertId();

        Pedido::recalcularValores($pedidoId, $restauranteId);

        return $id;
    }

    public static function delete(int $id, int $pedidoId, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM pedido_itens WHERE id = :id AND pedido_id = :pedido_id');
        $stmt->execute(['id' => $id, 'pedido_id' => $pedidoId]);

        Pedido::recalcularValores($pedidoId, $restauranteId);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            pedidoId: (int) $row['pedido_id'],
            produtoId: (int) $row['produto_id'],
            quantidade: (int) $row['quantidade'],
            precoUnitario: (float) $row['preco_unitario'],
            subtotal: (float) $row['subtotal'],
            observacao: $row['observacao'] !== null ? (string) $row['observacao'] : null,
            produtoNome: (string) $row['produto_nome'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
