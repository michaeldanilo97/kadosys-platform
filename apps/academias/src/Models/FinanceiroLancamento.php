<?php

declare(strict_types=1);

namespace Academias\Models;

use Academias\Core\Database;

/**
 * Lancamento financeiro (receita ou despesa). "alunoId" opcional -
 * preenchido quando o lancamento e o pagamento de uma mensalidade
 * (categoria "mensalidade"), pra futuramente aparecer no historico do
 * proprio aluno.
 */
final class FinanceiroLancamento
{
    public const TIPO_RECEITA = 'receita';
    public const TIPO_DESPESA = 'despesa';

    public const FORMAS_PAGAMENTO = ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'outro'];

    private const SELECT_COLUNAS = 'l.id, l.academia_id, l.caixa_id, l.aluno_id, l.usuario_id, l.tipo,
        l.categoria, l.forma_pagamento, l.valor, l.descricao, l.data_lancamento, l.created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $academiaId,
        public readonly ?int $caixaId,
        public readonly ?int $alunoId,
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
    public static function paginate(int $academiaId, int $page, int $perPage, string $tipo = ''): array
    {
        $where = 'WHERE l.academia_id = :academia_id';
        $params = ['academia_id' => $academiaId];

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
    public static function resumoDoPeriodo(int $academiaId, string $dataInicio, string $dataFim): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT tipo, COALESCE(SUM(valor), 0) AS total FROM financeiro_lancamentos
             WHERE academia_id = :academia_id AND data_lancamento BETWEEN :inicio AND :fim
             GROUP BY tipo"
        );
        $stmt->execute(['academia_id' => $academiaId, 'inicio' => $dataInicio, 'fim' => $dataFim]);

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
    public static function doCaixa(int $caixaId, int $academiaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM financeiro_lancamentos l
             WHERE l.caixa_id = :caixa_id AND l.academia_id = :academia_id
             ORDER BY l.created_at ASC'
        );
        $stmt->execute(['caixa_id' => $caixaId, 'academia_id' => $academiaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(
        int $academiaId,
        ?int $caixaId,
        ?int $alunoId,
        ?int $usuarioId,
        string $tipo,
        string $categoria,
        string $formaPagamento,
        float $valor,
        ?string $descricao,
        string $dataLancamento,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO financeiro_lancamentos
                (academia_id, caixa_id, aluno_id, usuario_id, tipo, categoria, forma_pagamento, valor, descricao, data_lancamento, created_at)
             VALUES
                (:academia_id, :caixa_id, :aluno_id, :usuario_id, :tipo, :categoria, :forma_pagamento, :valor, :descricao, :data_lancamento, NOW())'
        );
        $stmt->execute([
            'academia_id' => $academiaId,
            'caixa_id' => $caixaId,
            'aluno_id' => $alunoId,
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

    public static function delete(int $id, int $academiaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM financeiro_lancamentos WHERE id = :id AND academia_id = :academia_id');
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);
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
            academiaId: (int) $row['academia_id'],
            caixaId: $row['caixa_id'] !== null ? (int) $row['caixa_id'] : null,
            alunoId: $row['aluno_id'] !== null ? (int) $row['aluno_id'] : null,
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
