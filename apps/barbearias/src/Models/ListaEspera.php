<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Fila de espera de quem tentou agendar mas nao achou horario livre no
 * dia escolhido. Nao ha notificacao automatica quando um horario abre
 * (a aplicacao nao tem canal de email/WhatsApp configurado) - a equipe
 * confere a fila no painel e entra em contato por fora.
 */
final class ListaEspera
{
    public const STATUS_AGUARDANDO = 'aguardando';
    public const STATUS_ATENDIDO = 'atendido';
    public const STATUS_CANCELADO = 'cancelado';

    private const SELECT_COLUNAS = 'l.id, l.barbearia_id, l.profissional_id, l.servico_id, l.cliente_id,
        l.data_desejada, l.observacoes, l.status, l.created_at,
        p.nome AS profissional_nome, s.nome AS servico_nome,
        c.nome AS cliente_nome, c.telefone AS cliente_telefone';

    private const JOINS = 'FROM lista_espera l
        LEFT JOIN profissionais p ON p.id = l.profissional_id
        INNER JOIN servicos s ON s.id = l.servico_id
        INNER JOIN clientes c ON c.id = l.cliente_id';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly ?int $profissionalId,
        public readonly int $servicoId,
        public readonly int $clienteId,
        public readonly string $dataDesejada,
        public readonly ?string $observacoes,
        public readonly string $status,
        public readonly string $profissionalNome,
        public readonly string $servicoNome,
        public readonly string $clienteNome,
        public readonly ?string $clienteTelefone,
        public readonly ?string $createdAt = null,
    ) {
    }

    /** @return array<int, self> */
    public static function aguardando(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . "
             WHERE l.barbearia_id = :barbearia_id AND l.status = 'aguardando'
             ORDER BY l.data_desejada ASC, l.created_at ASC"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(
        int $barbeariaId,
        ?int $profissionalId,
        int $servicoId,
        int $clienteId,
        string $dataDesejada,
        ?string $observacoes,
    ): int {
        $stmt = Database::connection()->prepare(
            "INSERT INTO lista_espera (barbearia_id, profissional_id, servico_id, cliente_id, data_desejada, observacoes, status, created_at)
             VALUES (:barbearia_id, :profissional_id, :servico_id, :cliente_id, :data_desejada, :observacoes, 'aguardando', NOW())"
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'profissional_id' => $profissionalId,
            'servico_id' => $servicoId,
            'cliente_id' => $clienteId,
            'data_desejada' => $dataDesejada,
            'observacoes' => self::nullable($observacoes),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function atualizarStatus(int $id, int $barbeariaId, string $status): void
    {
        if (!in_array($status, [self::STATUS_AGUARDANDO, self::STATUS_ATENDIDO, self::STATUS_CANCELADO], true)) {
            return;
        }

        $stmt = Database::connection()->prepare(
            'UPDATE lista_espera SET status = :status WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute(['status' => $status, 'id' => $id, 'barbearia_id' => $barbeariaId]);
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
            profissionalId: $row['profissional_id'] !== null ? (int) $row['profissional_id'] : null,
            servicoId: (int) $row['servico_id'],
            clienteId: (int) $row['cliente_id'],
            dataDesejada: (string) $row['data_desejada'],
            observacoes: $row['observacoes'] ?? null,
            status: (string) $row['status'],
            profissionalNome: (string) ($row['profissional_nome'] ?? 'Qualquer profissional'),
            servicoNome: (string) $row['servico_nome'],
            clienteNome: (string) $row['cliente_nome'],
            clienteTelefone: $row['cliente_telefone'] ?? null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
