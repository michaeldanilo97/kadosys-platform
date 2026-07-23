<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Lancamento financeiro (receita ou despesa). Ainda sem tela propria
 * nesta fase - o unico ponto de entrada e create(), chamado
 * automaticamente por Pedido::finalizar() (uma receita por pedido
 * confirmado) e, a partir da Fase 6, pelo Caixa (sangria/suprimento,
 * ambos com categoria propria e sem pedido_id). O dashboard/CRUD/
 * relatorios completos de Financeiro ficam pra Fase 7.
 */
final class FinanceiroLancamento
{
    public const TIPO_RECEITA = 'receita';
    public const TIPO_DESPESA = 'despesa';

    public const CATEGORIA_SANGRIA = 'Sangria';
    public const CATEGORIA_SUPRIMENTO = 'Suprimento';

    public static function create(
        int $restauranteId,
        ?int $pedidoId,
        string $tipo,
        ?string $categoria,
        string $formaPagamento,
        float $valor,
        ?string $descricao,
        string $dataLancamento,
        ?int $caixaId = null,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO financeiro_lancamentos (restaurante_id, pedido_id, caixa_id, tipo, categoria, forma_pagamento,
                valor, descricao, data_lancamento, created_at)
             VALUES (:restaurante_id, :pedido_id, :caixa_id, :tipo, :categoria, :forma_pagamento, :valor, :descricao, :data_lancamento, NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'pedido_id' => $pedidoId,
            'caixa_id' => $caixaId,
            'tipo' => $tipo,
            'categoria' => $categoria,
            'forma_pagamento' => $formaPagamento,
            'valor' => $valor,
            'descricao' => $descricao,
            'data_lancamento' => $dataLancamento,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Lancamentos de um caixa especifico, mais recentes primeiro - usado
     * na tela de conferencia de caixa (vendas + sangrias + suprimentos).
     *
     * @return array<int, array{id: int, tipo: string, categoria: ?string, formaPagamento: string, valor: float, descricao: ?string, createdAt: string}>
     */
    public static function doCaixa(int $caixaId, int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, tipo, categoria, forma_pagamento, valor, descricao, created_at
             FROM financeiro_lancamentos
             WHERE caixa_id = :caixa_id AND restaurante_id = :restaurante_id
             ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute(['caixa_id' => $caixaId, 'restaurante_id' => $restauranteId]);

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'tipo' => (string) $row['tipo'],
                'categoria' => $row['categoria'] !== null ? (string) $row['categoria'] : null,
                'formaPagamento' => (string) $row['forma_pagamento'],
                'valor' => (float) $row['valor'],
                'descricao' => $row['descricao'] !== null ? (string) $row['descricao'] : null,
                'createdAt' => (string) $row['created_at'],
            ],
            $stmt->fetchAll(),
        );
    }

    /**
     * Soma de receitas menos despesas lancadas num caixa - somado ao
     * valor_abertura pelo Model Caixa pra chegar no saldo esperado.
     */
    public static function saldoDoCaixa(int $caixaId, int $restauranteId): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'receita' THEN valor ELSE -valor END), 0) AS saldo
             FROM financeiro_lancamentos
             WHERE caixa_id = :caixa_id AND restaurante_id = :restaurante_id"
        );
        $stmt->execute(['caixa_id' => $caixaId, 'restaurante_id' => $restauranteId]);

        return (float) $stmt->fetchColumn();
    }
}
