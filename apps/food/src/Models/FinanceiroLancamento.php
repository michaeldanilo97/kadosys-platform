<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Lancamento financeiro (receita ou despesa). Ainda sem tela propria
 * nesta fase - o unico ponto de entrada e create(), chamado
 * automaticamente por Pedido::finalizar() (uma receita por pedido
 * confirmado). O dashboard/CRUD/relatorios completos de Financeiro
 * ficam pra Fase 7.
 */
final class FinanceiroLancamento
{
    public const TIPO_RECEITA = 'receita';
    public const TIPO_DESPESA = 'despesa';

    public static function create(
        int $restauranteId,
        ?int $pedidoId,
        string $tipo,
        ?string $categoria,
        string $formaPagamento,
        float $valor,
        ?string $descricao,
        string $dataLancamento,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO financeiro_lancamentos (restaurante_id, pedido_id, tipo, categoria, forma_pagamento,
                valor, descricao, data_lancamento, created_at)
             VALUES (:restaurante_id, :pedido_id, :tipo, :categoria, :forma_pagamento, :valor, :descricao, :data_lancamento, NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'pedido_id' => $pedidoId,
            'tipo' => $tipo,
            'categoria' => $categoria,
            'forma_pagamento' => $formaPagamento,
            'valor' => $valor,
            'descricao' => $descricao,
            'data_lancamento' => $dataLancamento,
        ]);

        return (int) Database::connection()->lastInsertId();
    }
}
