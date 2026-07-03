<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de FinanceiroLancamento (modulo Financeiro).
 *
 * Cada lancamento e uma entrada (dizimo, oferta, doacao) ou saida
 * (despesa) - com categoria e vinculo com membro opcionais, valor,
 * forma de pagamento e data. Os filtros de paginate()/totais() usam a
 * mesma logica (construirFiltro()) pra manter os totais sempre
 * consistentes com o que esta sendo exibido na tabela.
 */
final class FinanceiroLancamento
{
    public function __construct(
        public readonly int $id,
        public readonly string $tipo,
        public readonly ?int $categoriaId,
        public readonly ?string $categoriaNome,
        public readonly ?int $membroId,
        public readonly ?string $membroNome,
        public readonly string $descricao,
        public readonly float $valor,
        public readonly string $formaPagamento,
        public readonly string $dataLancamento,
        public readonly ?string $observacoes,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @param array{tipo?: string, categoria_id?: int, mes?: string, busca?: string} $filtros
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $page, int $perPage, array $filtros = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        ['where' => $where, 'params' => $params] = self::construirFiltro($filtros);

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM financeiro_lancamentos fl {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT fl.*, fc.nome AS categoria_nome, me.nome AS membro_nome
             FROM financeiro_lancamentos fl
             LEFT JOIN financeiro_categorias fc ON fc.id = fl.categoria_id
             LEFT JOIN membros me ON me.id = fl.membro_id
             {$where}
             ORDER BY fl.data_lancamento DESC, fl.id DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map(self::fromRow(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * @param array{tipo?: string, categoria_id?: int, mes?: string, busca?: string} $filtros
     * @return array{entradas: float, saidas: float, saldo: float}
     */
    public static function totais(array $filtros = []): array
    {
        ['where' => $where, 'params' => $params] = self::construirFiltro($filtros);

        $stmt = Database::connection()->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) AS entradas,
                COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) AS saidas
             FROM financeiro_lancamentos fl
             {$where}"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        $entradas = (float) ($row['entradas'] ?? 0);
        $saidas = (float) ($row['saidas'] ?? 0);

        return [
            'entradas' => $entradas,
            'saidas' => $saidas,
            'saldo' => $entradas - $saidas,
        ];
    }

    /**
     * Saldo do mes corrente, usado no KPI "Financeiro do mes" do
     * Dashboard.
     *
     * @return array{entradas: float, saidas: float, saldo: float}
     */
    public static function totaisMesAtual(): array
    {
        return self::totais(['mes' => date('Y-m')]);
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT fl.*, fc.nome AS categoria_nome, me.nome AS membro_nome
             FROM financeiro_lancamentos fl
             LEFT JOIN financeiro_categorias fc ON fc.id = fl.categoria_id
             LEFT JOIN membros me ON me.id = fl.membro_id
             WHERE fl.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO financeiro_lancamentos
                (tipo, categoria_id, membro_id, descricao, valor, forma_pagamento, data_lancamento, observacoes, created_at)
             VALUES
                (:tipo, :categoria_id, :membro_id, :descricao, :valor, :forma_pagamento, :data_lancamento, :observacoes, NOW())'
        );
        $stmt->execute(self::bindings($data));

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE financeiro_lancamentos SET
                tipo = :tipo, categoria_id = :categoria_id, membro_id = :membro_id,
                descricao = :descricao, valor = :valor, forma_pagamento = :forma_pagamento,
                data_lancamento = :data_lancamento, observacoes = :observacoes
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM financeiro_lancamentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * @param array{tipo?: string, categoria_id?: int, mes?: string, busca?: string} $filtros
     * @return array{where: string, params: array<string, mixed>}
     */
    private static function construirFiltro(array $filtros): array
    {
        $condicoes = [];
        $params = [];

        if (!empty($filtros['tipo']) && in_array($filtros['tipo'], ['entrada', 'saida'], true)) {
            $condicoes[] = 'fl.tipo = :tipo';
            $params['tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['categoria_id'])) {
            $condicoes[] = 'fl.categoria_id = :categoria_id';
            $params['categoria_id'] = (int) $filtros['categoria_id'];
        }

        if (!empty($filtros['mes']) && preg_match('/^\d{4}-\d{2}$/', (string) $filtros['mes']) === 1) {
            $condicoes[] = "DATE_FORMAT(fl.data_lancamento, '%Y-%m') = :mes";
            $params['mes'] = $filtros['mes'];
        }

        if (!empty($filtros['busca'])) {
            $condicoes[] = 'fl.descricao LIKE :busca';
            $params['busca'] = '%' . $filtros['busca'] . '%';
        }

        $where = $condicoes === [] ? '' : 'WHERE ' . implode(' AND ', $condicoes);

        return ['where' => $where, 'params' => $params];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        $categoriaId = trim((string) ($data['categoria_id'] ?? ''));
        $membroId = trim((string) ($data['membro_id'] ?? ''));

        return [
            'tipo' => in_array($data['tipo'] ?? null, ['entrada', 'saida'], true) ? $data['tipo'] : 'entrada',
            'categoria_id' => $categoriaId === '' ? null : (int) $categoriaId,
            'membro_id' => $membroId === '' ? null : (int) $membroId,
            'descricao' => trim((string) $data['descricao']),
            'valor' => (float) str_replace(',', '.', (string) ($data['valor'] ?? '0')),
            'forma_pagamento' => in_array($data['forma_pagamento'] ?? null, ['dinheiro', 'pix', 'cartao', 'transferencia', 'boleto', 'outro'], true)
                ? $data['forma_pagamento']
                : 'dinheiro',
            'data_lancamento' => (string) $data['data_lancamento'],
            'observacoes' => self::nullable($data['observacoes'] ?? null),
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            tipo: (string) $row['tipo'],
            categoriaId: $row['categoria_id'] !== null ? (int) $row['categoria_id'] : null,
            categoriaNome: $row['categoria_nome'] ?? null,
            membroId: $row['membro_id'] !== null ? (int) $row['membro_id'] : null,
            membroNome: $row['membro_nome'] ?? null,
            descricao: (string) $row['descricao'],
            valor: (float) $row['valor'],
            formaPagamento: (string) $row['forma_pagamento'],
            dataLancamento: (string) $row['data_lancamento'],
            observacoes: $row['observacoes'],
            createdAt: (string) $row['created_at'],
        );
    }
}
