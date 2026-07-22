<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Lancamento financeiro (receita ou despesa). Um lancamento de receita
 * pode nascer de duas formas: manual (tela de Financeiro) ou automatico,
 * ao registrar o pagamento de um agendamento concluido (PDV simplificado
 * em Barbearias\Controllers\AgendamentoController::pagamento) - nesse
 * caso agendamento_id fica preenchido e e UNICO (um pagamento por
 * atendimento).
 */
final class FinanceiroLancamento
{
    public const TIPO_RECEITA = 'receita';
    public const TIPO_DESPESA = 'despesa';

    public const FORMAS_PAGAMENTO = ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'outro'];

    private const SELECT_COLUNAS = 'l.id, l.barbearia_id, l.caixa_id, l.agendamento_id, l.produto_id, l.quantidade, l.usuario_id, l.tipo,
        l.categoria, l.forma_pagamento, l.valor, l.descricao, l.data_lancamento, l.created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly ?int $caixaId,
        public readonly ?int $agendamentoId,
        public readonly ?int $produtoId,
        public readonly ?int $quantidade,
        public readonly ?int $usuarioId,
        public readonly string $tipo,
        public readonly string $categoria,
        public readonly string $formaPagamento,
        public readonly float $valor,
        public readonly ?string $descricao,
        public readonly string $dataLancamento,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $barbeariaId, int $page, int $perPage, string $tipo = ''): array
    {
        $where = 'WHERE l.barbearia_id = :barbearia_id';
        $params = ['barbearia_id' => $barbeariaId];

        if (in_array($tipo, [self::TIPO_RECEITA, self::TIPO_DESPESA], true)) {
            $where .= ' AND l.tipo = :tipo';
            $params['tipo'] = $tipo;
        }

        $stmtTotal = Database::connection()->prepare("SELECT COUNT(*) FROM financeiro_lancamentos l {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM financeiro_lancamentos l {$where}
             ORDER BY l.data_lancamento DESC, l.id DESC LIMIT {$perPage} OFFSET {$offset}"
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
    public static function resumoDoPeriodo(int $barbeariaId, string $dataInicio, string $dataFim): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT tipo, COALESCE(SUM(valor), 0) AS total FROM financeiro_lancamentos
             WHERE barbearia_id = :barbearia_id AND data_lancamento BETWEEN :inicio AND :fim
             GROUP BY tipo"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'inicio' => $dataInicio, 'fim' => $dataFim]);

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

    /** @return array<int, self> */
    public static function doCaixa(int $caixaId, int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM financeiro_lancamentos l
             WHERE l.caixa_id = :caixa_id AND l.barbearia_id = :barbearia_id
             ORDER BY l.created_at ASC'
        );
        $stmt->execute(['caixa_id' => $caixaId, 'barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Mapa agendamento_id => valor pago, numa unica consulta - usado
     * pelo relatorio de comissao (ver
     * Barbearias\Controllers\ComissaoController) pra mostrar o valor
     * real de cada atendimento no detalhe por profissional.
     *
     * @param array<int, int> $agendamentoIds
     * @return array<int, float>
     */
    public static function mapaPorAgendamentos(array $agendamentoIds): array
    {
        if ($agendamentoIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($agendamentoIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT agendamento_id, valor FROM financeiro_lancamentos WHERE agendamento_id IN ({$placeholders})"
        );
        $stmt->execute(array_values($agendamentoIds));

        $mapa = [];

        foreach ($stmt->fetchAll() as $row) {
            $mapa[(int) $row['agendamento_id']] = (float) $row['valor'];
        }

        return $mapa;
    }

    public static function existeParaAgendamento(int $agendamentoId, int $barbeariaId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM financeiro_lancamentos WHERE agendamento_id = :agendamento_id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['agendamento_id' => $agendamentoId, 'barbearia_id' => $barbeariaId]);

        return $stmt->fetch() !== false;
    }

    public static function existeParaFilaAtendimento(int $filaAtendimentoId, int $barbeariaId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM financeiro_lancamentos WHERE fila_atendimento_id = :fila_atendimento_id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['fila_atendimento_id' => $filaAtendimentoId, 'barbearia_id' => $barbeariaId]);

        return $stmt->fetch() !== false;
    }

    public static function create(
        int $barbeariaId,
        ?int $caixaId,
        ?int $agendamentoId,
        ?int $usuarioId,
        string $tipo,
        string $categoria,
        string $formaPagamento,
        float $valor,
        ?string $descricao,
        string $dataLancamento,
        ?int $produtoId = null,
        ?int $quantidade = null,
        ?int $filaAtendimentoId = null,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO financeiro_lancamentos
                (barbearia_id, caixa_id, agendamento_id, fila_atendimento_id, produto_id, quantidade, usuario_id, tipo, categoria, forma_pagamento, valor, descricao, data_lancamento, created_at)
             VALUES
                (:barbearia_id, :caixa_id, :agendamento_id, :fila_atendimento_id, :produto_id, :quantidade, :usuario_id, :tipo, :categoria, :forma_pagamento, :valor, :descricao, :data_lancamento, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'caixa_id' => $caixaId,
            'agendamento_id' => $agendamentoId,
            'fila_atendimento_id' => $filaAtendimentoId,
            'produto_id' => $produtoId,
            'quantidade' => $quantidade,
            'usuario_id' => $usuarioId,
            'tipo' => $tipo === self::TIPO_DESPESA ? self::TIPO_DESPESA : self::TIPO_RECEITA,
            'categoria' => trim($categoria),
            'forma_pagamento' => in_array($formaPagamento, self::FORMAS_PAGAMENTO, true) ? $formaPagamento : 'outro',
            'valor' => max(0, $valor),
            'descricao' => self::nullable($descricao),
            'data_lancamento' => $dataLancamento,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM financeiro_lancamentos WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    private static function nullable(?string $valor): ?string
    {
        $valor = $valor !== null ? trim($valor) : '';

        return $valor === '' ? null : $valor;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            caixaId: $row['caixa_id'] !== null ? (int) $row['caixa_id'] : null,
            agendamentoId: $row['agendamento_id'] !== null ? (int) $row['agendamento_id'] : null,
            produtoId: $row['produto_id'] !== null ? (int) $row['produto_id'] : null,
            quantidade: $row['quantidade'] !== null ? (int) $row['quantidade'] : null,
            usuarioId: $row['usuario_id'] !== null ? (int) $row['usuario_id'] : null,
            tipo: (string) $row['tipo'],
            categoria: (string) $row['categoria'],
            formaPagamento: (string) $row['forma_pagamento'],
            valor: (float) $row['valor'],
            descricao: $row['descricao'] ?? null,
            dataLancamento: (string) $row['data_lancamento'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
