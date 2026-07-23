<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Forma de pagamento aplicada a um pedido - existe pra suportar split
 * payment no PDV (mais de uma forma na mesma venda). Se um pedido nao
 * tiver NENHUMA linha aqui, Pedido::finalizar() cai no comportamento
 * da Fase 5: um unico lancamento usando pedidos.forma_pagamento pro
 * valor_total inteiro - pedidos criados pela tela normal de Pedidos
 * (sem passar pelo PDV) continuam funcionando exatamente como antes.
 */
final class PedidoPagamento
{
    private const SELECT_COLUNAS = 'id, pedido_id, forma_pagamento, valor, valor_recebido, troco, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $pedidoId,
        public readonly string $formaPagamento,
        public readonly float $valor,
        public readonly ?float $valorRecebido,
        public readonly ?float $troco,
        public readonly string $createdAt,
    ) {
    }

    /** @return array<int, self> */
    public static function doPedido(int $pedidoId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM pedido_pagamentos WHERE pedido_id = :pedido_id ORDER BY id ASC'
        );
        $stmt->execute(['pedido_id' => $pedidoId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function somaPagamentos(int $pedidoId): float
    {
        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(SUM(valor), 0) FROM pedido_pagamentos WHERE pedido_id = :pedido_id'
        );
        $stmt->execute(['pedido_id' => $pedidoId]);

        return (float) $stmt->fetchColumn();
    }

    public static function create(
        int $pedidoId,
        string $formaPagamento,
        float $valor,
        ?float $valorRecebido,
        ?float $troco,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO pedido_pagamentos (pedido_id, forma_pagamento, valor, valor_recebido, troco, created_at)
             VALUES (:pedido_id, :forma_pagamento, :valor, :valor_recebido, :troco, NOW())'
        );
        $stmt->execute([
            'pedido_id' => $pedidoId,
            'forma_pagamento' => $formaPagamento,
            'valor' => $valor,
            'valor_recebido' => $valorRecebido,
            'troco' => $troco,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function delete(int $id, int $pedidoId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM pedido_pagamentos WHERE id = :id AND pedido_id = :pedido_id'
        );
        $stmt->execute(['id' => $id, 'pedido_id' => $pedidoId]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            pedidoId: (int) $row['pedido_id'],
            formaPagamento: (string) $row['forma_pagamento'],
            valor: (float) $row['valor'],
            valorRecebido: $row['valor_recebido'] !== null ? (float) $row['valor_recebido'] : null,
            troco: $row['troco'] !== null ? (float) $row['troco'] : null,
            createdAt: (string) $row['created_at'],
        );
    }
}
