<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Assinatura de um cliente a um plano de pacote mensal. O ciclo
 * mensal e ancorado em "dataInicio" (renova sozinho a cada mes, sem
 * cron) - ver inicioCicloAtual().
 */
final class AssinaturaCliente
{
    public const STATUS_ATIVA = 'ativa';
    public const STATUS_CANCELADA = 'cancelada';

    private const SELECT_COLUNAS = 'a.id, a.barbearia_id, a.cliente_id, a.plano_id, a.status, a.data_inicio, a.created_at,
        c.nome AS cliente_nome, c.telefone AS cliente_telefone,
        p.nome AS plano_nome, p.preco AS plano_preco, p.atendimentos_por_mes AS plano_atendimentos_por_mes';

    private const JOINS = 'FROM assinaturas_clientes a
        INNER JOIN clientes c ON c.id = a.cliente_id
        INNER JOIN assinatura_planos p ON p.id = a.plano_id';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly int $clienteId,
        public readonly int $planoId,
        public readonly string $status,
        public readonly string $dataInicio,
        public readonly string $clienteNome,
        public readonly ?string $clienteTelefone,
        public readonly string $planoNome,
        public readonly float $planoPreco,
        public readonly int $planoAtendimentosPorMes,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function find(int $id, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . ' WHERE a.id = :id AND a.barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * A assinatura ATIVA de um cliente, se houver (cada cliente so
     * pode ter uma assinatura ativa por vez) - usado na hora de
     * registrar o pagamento de um atendimento, pra oferecer "usar
     * assinatura" quando aplicavel.
     */
    public static function ativaDoCliente(int $clienteId, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . "
             WHERE a.cliente_id = :cliente_id AND a.barbearia_id = :barbearia_id AND a.status = 'ativa'
             LIMIT 1"
        );
        $stmt->execute(['cliente_id' => $clienteId, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function ativas(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . "
             WHERE a.barbearia_id = :barbearia_id AND a.status = 'ativa'
             ORDER BY c.nome ASC"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(int $barbeariaId, int $clienteId, int $planoId, string $dataInicio): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO assinaturas_clientes (barbearia_id, cliente_id, plano_id, status, data_inicio, created_at)
             VALUES (:barbearia_id, :cliente_id, :plano_id, 'ativa', :data_inicio, NOW())"
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'cliente_id' => $clienteId,
            'plano_id' => $planoId,
            'data_inicio' => $dataInicio,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function cancelar(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE assinaturas_clientes SET status = 'cancelada' WHERE id = :id AND barbearia_id = :barbearia_id"
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    /**
     * Data em que o ciclo mensal ATUAL comecou: o aniversario mensal
     * de "dataInicio" mais recente que ainda nao passou de "hoje".
     * Ancorar no dia da assinatura (em vez de sempre dia 1) evita
     * qualquer job agendado - o calculo e sempre feito na hora, a
     * partir da data de hoje.
     */
    public function inicioCicloAtual(\DateTimeImmutable $hoje): \DateTimeImmutable
    {
        $cursor = new \DateTimeImmutable($this->dataInicio);

        while ($cursor->modify('+1 month') <= $hoje) {
            $cursor = $cursor->modify('+1 month');
        }

        return $cursor;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            clienteId: (int) $row['cliente_id'],
            planoId: (int) $row['plano_id'],
            status: (string) $row['status'],
            dataInicio: (string) $row['data_inicio'],
            clienteNome: (string) $row['cliente_nome'],
            clienteTelefone: $row['cliente_telefone'] ?? null,
            planoNome: (string) $row['plano_nome'],
            planoPreco: (float) $row['plano_preco'],
            planoAtendimentosPorMes: (int) $row['plano_atendimentos_por_mes'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
