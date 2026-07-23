<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Conta a receber - o espelho de ContaPagar, sem parcelamento/
 * recorrencia (recebiveis futuros combinados manualmente, ex.: um
 * evento fechado com adiantamento). "status" so guarda pendente/
 * recebida/cancelada - "vencida" e calculado na hora (estaVencida()),
 * mesma logica de ContaPagar.
 */
final class ContaReceber
{
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_RECEBIDA = 'recebida';
    public const STATUS_CANCELADA = 'cancelada';

    private const SELECT_COLUNAS = 'cr.id, cr.restaurante_id, cr.centro_custo_id, cr.cliente_id, cr.descricao,
        cr.categoria, cr.valor, cr.vencimento, cr.status, cr.recebido_em, cr.observacoes, cr.created_at, cr.updated_at,
        c.nome AS cliente_nome';

    private const JOINS = 'FROM contas_a_receber cr LEFT JOIN clientes c ON c.id = cr.cliente_id';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly ?int $centroCustoId,
        public readonly ?int $clienteId,
        public readonly string $descricao,
        public readonly ?string $categoria,
        public readonly float $valor,
        public readonly string $vencimento,
        public readonly string $status,
        public readonly ?string $recebidoEm,
        public readonly ?string $observacoes,
        public readonly ?string $clienteNome,
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
        $where = 'WHERE cr.restaurante_id = :restaurante_id';
        $params = ['restaurante_id' => $restauranteId];

        if (in_array($status, [self::STATUS_PENDENTE, self::STATUS_RECEBIDA, self::STATUS_CANCELADA], true)) {
            $where .= ' AND cr.status = :status';
            $params['status'] = $status;
        }

        $stmtTotal = Database::connection()->prepare("SELECT COUNT(*) FROM contas_a_receber cr {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . " {$where}
             ORDER BY cr.vencimento ASC, cr.id ASC LIMIT {$perPage} OFFSET {$offset}"
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
     * do dashboard financeiro.
     *
     * @return array<int, self>
     */
    public static function proximasPendentes(int $restauranteId, int $limite): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . "
             WHERE cr.restaurante_id = :restaurante_id AND cr.status = 'pendente'
             ORDER BY cr.vencimento ASC LIMIT {$limite}"
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id, int $restauranteId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . ' WHERE cr.id = :id AND cr.restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function totalVencidas(int $restauranteId): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM contas_a_receber
             WHERE restaurante_id = :restaurante_id AND status = 'pendente' AND vencimento < CURDATE()"
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return (float) $stmt->fetchColumn();
    }

    public static function create(
        int $restauranteId,
        ?int $centroCustoId,
        ?int $clienteId,
        string $descricao,
        ?string $categoria,
        float $valor,
        string $vencimento,
        ?string $observacoes,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO contas_a_receber (restaurante_id, centro_custo_id, cliente_id, descricao, categoria, valor,
                vencimento, status, observacoes, created_at, updated_at)
             VALUES (:restaurante_id, :centro_custo_id, :cliente_id, :descricao, :categoria, :valor,
                :vencimento, :status, :observacoes, NOW(), NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'centro_custo_id' => $centroCustoId,
            'cliente_id' => $clienteId,
            'descricao' => trim($descricao),
            'categoria' => $categoria,
            'valor' => max(0, $valor),
            'vencimento' => $vencimento,
            'status' => self::STATUS_PENDENTE,
            'observacoes' => self::nullable($observacoes),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function marcarRecebida(int $id, int $restauranteId): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE contas_a_receber SET status = 'recebida', recebido_em = CURDATE(), updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id AND status = 'pendente'"
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);

        return $stmt->rowCount() > 0;
    }

    public static function cancelar(int $id, int $restauranteId): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE contas_a_receber SET status = 'cancelada', updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id AND status = 'pendente'"
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);

        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM contas_a_receber WHERE id = :id AND restaurante_id = :restaurante_id');
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
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
            clienteId: $row['cliente_id'] !== null ? (int) $row['cliente_id'] : null,
            descricao: (string) $row['descricao'],
            categoria: $row['categoria'] !== null ? (string) $row['categoria'] : null,
            valor: (float) $row['valor'],
            vencimento: (string) $row['vencimento'],
            status: (string) $row['status'],
            recebidoEm: $row['recebido_em'] !== null ? (string) $row['recebido_em'] : null,
            observacoes: $row['observacoes'] !== null ? (string) $row['observacoes'] : null,
            clienteNome: $row['cliente_nome'] !== null ? (string) $row['cliente_nome'] : null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
