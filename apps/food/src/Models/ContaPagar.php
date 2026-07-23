<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Conta a pagar (despesa fixa/variavel/parcelada/recorrente). "status"
 * so guarda pendente/paga/cancelada - "vencida" e calculado na hora
 * (estaVencida()) em vez de persistido, pra nunca ficar desatualizado
 * se algum cron atrasar (ver comentario na migration 006).
 *
 * Recorrencia: "serieId" agrupa todas as parcelas/repeticoes geradas a
 * partir de uma MESMA conta original (a primeira linha da serie tem
 * serieId = seu proprio id). gerarProximasRecorrentes() e chamado pelo
 * cron mensal e cria a proxima parcela de cada serie ainda ativa
 * (ultima parcela ja paga e parcela_total nao atingido, ou sem limite
 * de parcelas).
 */
final class ContaPagar
{
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_PAGA = 'paga';
    public const STATUS_CANCELADA = 'cancelada';

    private const SELECT_COLUNAS = 'id, restaurante_id, centro_custo_id, serie_id, descricao, categoria, valor,
        vencimento, status, pago_em, anexo_path, recorrente, parcela_atual, parcela_total, observacoes,
        created_at, updated_at';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly ?int $centroCustoId,
        public readonly ?int $serieId,
        public readonly string $descricao,
        public readonly ?string $categoria,
        public readonly float $valor,
        public readonly string $vencimento,
        public readonly string $status,
        public readonly ?string $pagoEm,
        public readonly ?string $anexoPath,
        public readonly bool $recorrente,
        public readonly ?int $parcelaAtual,
        public readonly ?int $parcelaTotal,
        public readonly ?string $observacoes,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    public function estaVencida(): bool
    {
        return $this->status === self::STATUS_PENDENTE && $this->vencimento < (new \DateTimeImmutable('today'))->format('Y-m-d');
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $restauranteId, int $page, int $perPage, string $status = ''): array
    {
        $where = 'WHERE restaurante_id = :restaurante_id';
        $params = ['restaurante_id' => $restauranteId];

        if (in_array($status, [self::STATUS_PENDENTE, self::STATUS_PAGA, self::STATUS_CANCELADA], true)) {
            $where .= ' AND status = :status';
            $params['status'] = $status;
        }

        $stmtTotal = Database::connection()->prepare("SELECT COUNT(*) FROM contas_a_pagar {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM contas_a_pagar {$where}
             ORDER BY vencimento ASC, id ASC LIMIT {$perPage} OFFSET {$offset}"
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
     * As proximas N contas pendentes por vencimento - usado no widget
     * do dashboard financeiro (nao e uma listagem paginada completa,
     * so um resumo rapido do que vence em seguida).
     *
     * @return array<int, self>
     */
    public static function proximasPendentes(int $restauranteId, int $limite): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT " . self::SELECT_COLUNAS . " FROM contas_a_pagar
             WHERE restaurante_id = :restaurante_id AND status = 'pendente'
             ORDER BY vencimento ASC LIMIT {$limite}"
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id, int $restauranteId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM contas_a_pagar WHERE id = :id AND restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Soma de pendentes com vencimento ja passado - usado no dashboard
     * financeiro pra alertar sobre contas atrasadas.
     */
    public static function totalVencidas(int $restauranteId): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM contas_a_pagar
             WHERE restaurante_id = :restaurante_id AND status = 'pendente' AND vencimento < CURDATE()"
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return (float) $stmt->fetchColumn();
    }

    public static function create(
        int $restauranteId,
        ?int $centroCustoId,
        string $descricao,
        ?string $categoria,
        float $valor,
        string $vencimento,
        bool $recorrente,
        ?int $parcelaTotal,
        ?string $observacoes,
    ): int {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO contas_a_pagar (restaurante_id, centro_custo_id, descricao, categoria, valor, vencimento,
                status, recorrente, parcela_atual, parcela_total, observacoes, created_at, updated_at)
             VALUES (:restaurante_id, :centro_custo_id, :descricao, :categoria, :valor, :vencimento,
                :status, :recorrente, :parcela_atual, :parcela_total, :observacoes, NOW(), NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'centro_custo_id' => $centroCustoId,
            'descricao' => trim($descricao),
            'categoria' => $categoria,
            'valor' => max(0, $valor),
            'vencimento' => $vencimento,
            'status' => self::STATUS_PENDENTE,
            'recorrente' => $recorrente ? 1 : 0,
            'parcela_atual' => $recorrente ? 1 : null,
            'parcela_total' => $recorrente ? $parcelaTotal : null,
            'observacoes' => self::nullable($observacoes),
        ]);

        $id = (int) $pdo->lastInsertId();

        // A primeira linha de uma serie recorrente aponta pra si mesma -
        // so entao o cron consegue agrupar/rastrear a serie inteira.
        if ($recorrente) {
            $stmtSerie = $pdo->prepare('UPDATE contas_a_pagar SET serie_id = :serie_id WHERE id = :id');
            $stmtSerie->execute(['serie_id' => $id, 'id' => $id]);
        }

        return $id;
    }

    public static function marcarPaga(int $id, int $restauranteId): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE contas_a_pagar SET status = 'paga', pago_em = CURDATE(), updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id AND status = 'pendente'"
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);

        return $stmt->rowCount() > 0;
    }

    public static function cancelar(int $id, int $restauranteId): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE contas_a_pagar SET status = 'cancelada', updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id AND status = 'pendente'"
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);

        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM contas_a_pagar WHERE id = :id AND restaurante_id = :restaurante_id');
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    /**
     * Gera a proxima parcela de cada serie recorrente ativa - chamado
     * pelo cron mensal (cron/gerar_despesas_recorrentes.php). Pra cada
     * serie, olha so a linha de vencimento MAIS RECENTE (a "atual"): se
     * ela ja foi paga e a serie nao atingiu o limite de parcelas (ou
     * nao tem limite), cria a proxima com vencimento um mes depois. Sem
     * efeito nenhum se a atual ainda esta pendente (evita empilhar
     * parcelas futuras antes da atual ser resolvida) ou ja foi
     * cancelada.
     *
     * @return int Quantidade de novas parcelas geradas.
     */
    public static function gerarProximasRecorrentes(): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->query(
            'SELECT cp.* FROM contas_a_pagar cp
             INNER JOIN (
                 SELECT serie_id, MAX(vencimento) AS ultimo_vencimento
                 FROM contas_a_pagar
                 WHERE recorrente = 1 AND serie_id IS NOT NULL
                 GROUP BY serie_id
             ) atual ON atual.serie_id = cp.serie_id AND atual.ultimo_vencimento = cp.vencimento
             WHERE cp.recorrente = 1'
        );

        $geradas = 0;

        foreach ($stmt->fetchAll() as $row) {
            $conta = self::fromRow($row);

            if ($conta->status !== self::STATUS_PAGA) {
                continue;
            }

            if ($conta->parcelaTotal !== null && (int) $conta->parcelaAtual >= $conta->parcelaTotal) {
                continue;
            }

            $proximoVencimento = (new \DateTimeImmutable($conta->vencimento))->modify('+1 month')->format('Y-m-d');

            $stmtInsert = $pdo->prepare(
                'INSERT INTO contas_a_pagar (restaurante_id, centro_custo_id, serie_id, descricao, categoria,
                    valor, vencimento, status, recorrente, parcela_atual, parcela_total, observacoes, created_at, updated_at)
                 VALUES (:restaurante_id, :centro_custo_id, :serie_id, :descricao, :categoria,
                    :valor, :vencimento, :status, 1, :parcela_atual, :parcela_total, :observacoes, NOW(), NOW())'
            );
            $stmtInsert->execute([
                'restaurante_id' => $conta->restauranteId,
                'centro_custo_id' => $conta->centroCustoId,
                'serie_id' => $conta->serieId,
                'descricao' => $conta->descricao,
                'categoria' => $conta->categoria,
                'valor' => $conta->valor,
                'vencimento' => $proximoVencimento,
                'status' => self::STATUS_PENDENTE,
                'parcela_atual' => ((int) $conta->parcelaAtual) + 1,
                'parcela_total' => $conta->parcelaTotal,
                'observacoes' => $conta->observacoes,
            ]);

            $geradas++;
        }

        return $geradas;
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
            restauranteId: (int) $row['restaurante_id'],
            centroCustoId: $row['centro_custo_id'] !== null ? (int) $row['centro_custo_id'] : null,
            serieId: $row['serie_id'] !== null ? (int) $row['serie_id'] : null,
            descricao: (string) $row['descricao'],
            categoria: $row['categoria'] !== null ? (string) $row['categoria'] : null,
            valor: (float) $row['valor'],
            vencimento: (string) $row['vencimento'],
            status: (string) $row['status'],
            pagoEm: $row['pago_em'] !== null ? (string) $row['pago_em'] : null,
            anexoPath: $row['anexo_path'] !== null ? (string) $row['anexo_path'] : null,
            recorrente: (bool) $row['recorrente'],
            parcelaAtual: $row['parcela_atual'] !== null ? (int) $row['parcela_atual'] : null,
            parcelaTotal: $row['parcela_total'] !== null ? (int) $row['parcela_total'] : null,
            observacoes: $row['observacoes'] !== null ? (string) $row['observacoes'] : null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
