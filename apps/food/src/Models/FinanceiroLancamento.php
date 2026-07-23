<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Lancamento financeiro (receita ou despesa). Criado automaticamente
 * por Pedido::finalizar() (uma receita por pedido confirmado, ou uma
 * por forma de pagamento em vendas com split) e pelo Caixa (sangria/
 * suprimento). A partir da Fase 7 tambem ganha uma tela propria
 * (dashboard/Financeiro) com listagem paginada e resumo por periodo -
 * lancamentos continuam so podendo ser CRIADOS automaticamente (nunca
 * manualmente pela tela), so a leitura/exclusao e exposta ali.
 */
final class FinanceiroLancamento
{
    public const TIPO_RECEITA = 'receita';
    public const TIPO_DESPESA = 'despesa';

    public const CATEGORIA_SANGRIA = 'Sangria';
    public const CATEGORIA_SUPRIMENTO = 'Suprimento';

    /** @var array<int, string> */
    public const FORMAS_PAGAMENTO = ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'outro'];

    private const SELECT_COLUNAS = 'id, restaurante_id, pedido_id, caixa_id, tipo, categoria, forma_pagamento,
        valor, descricao, data_lancamento, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly ?int $pedidoId,
        public readonly ?int $caixaId,
        public readonly string $tipo,
        public readonly ?string $categoria,
        public readonly string $formaPagamento,
        public readonly float $valor,
        public readonly ?string $descricao,
        public readonly string $dataLancamento,
        public readonly ?string $createdAt = null,
    ) {
    }

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
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $restauranteId, int $page, int $perPage, string $tipo = ''): array
    {
        $where = 'WHERE restaurante_id = :restaurante_id';
        $params = ['restaurante_id' => $restauranteId];

        if (in_array($tipo, [self::TIPO_RECEITA, self::TIPO_DESPESA], true)) {
            $where .= ' AND tipo = :tipo';
            $params['tipo'] = $tipo;
        }

        $stmtTotal = Database::connection()->prepare("SELECT COUNT(*) FROM financeiro_lancamentos {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM financeiro_lancamentos {$where}
             ORDER BY data_lancamento DESC, id DESC LIMIT {$perPage} OFFSET {$offset}"
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

    /**
     * @return array{receitas: float, despesas: float, saldo: float}
     */
    public static function resumoDoPeriodo(int $restauranteId, string $dataInicio, string $dataFim): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT tipo, COALESCE(SUM(valor), 0) AS total FROM financeiro_lancamentos
             WHERE restaurante_id = :restaurante_id AND data_lancamento BETWEEN :inicio AND :fim
             GROUP BY tipo'
        );
        $stmt->execute(['restaurante_id' => $restauranteId, 'inicio' => $dataInicio, 'fim' => $dataFim]);

        $receitas = 0.0;
        $despesas = 0.0;

        foreach ($stmt->fetchAll() as $row) {
            if ($row['tipo'] === self::TIPO_RECEITA) {
                $receitas = (float) $row['total'];
            } elseif ($row['tipo'] === self::TIPO_DESPESA) {
                $despesas = (float) $row['total'];
            }
        }

        return ['receitas' => $receitas, 'despesas' => $despesas, 'saldo' => $receitas - $despesas];
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

    public static function delete(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM financeiro_lancamentos WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            pedidoId: $row['pedido_id'] !== null ? (int) $row['pedido_id'] : null,
            caixaId: $row['caixa_id'] !== null ? (int) $row['caixa_id'] : null,
            tipo: (string) $row['tipo'],
            categoria: $row['categoria'] !== null ? (string) $row['categoria'] : null,
            formaPagamento: (string) $row['forma_pagamento'],
            valor: (float) $row['valor'],
            descricao: $row['descricao'] !== null ? (string) $row['descricao'] : null,
            dataLancamento: (string) $row['data_lancamento'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
