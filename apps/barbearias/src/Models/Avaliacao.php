<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Avaliacao (1 a 5 estrelas + comentario opcional) que o cliente deixa
 * depois de um atendimento concluido - no maximo uma por agendamento.
 */
final class Avaliacao
{
    private const SELECT_COLUNAS = 'id, barbearia_id, agendamento_id, cliente_id, profissional_id, nota, comentario, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly int $agendamentoId,
        public readonly int $clienteId,
        public readonly int $profissionalId,
        public readonly int $nota,
        public readonly ?string $comentario,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function buscarPorAgendamento(int $agendamentoId, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM avaliacoes WHERE agendamento_id = :agendamento_id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['agendamento_id' => $agendamentoId, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @return array<int, int> mapa agendamento_id => 1, so pra saber
     * rapido (num unico IN) quais agendamentos de uma lista ja tem
     * avaliacao - evita N consultas na tela "meus atendimentos".
     */
    public static function agendamentosAvaliados(array $agendamentoIds): array
    {
        if ($agendamentoIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($agendamentoIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT agendamento_id FROM avaliacoes WHERE agendamento_id IN ({$placeholders})"
        );
        $stmt->execute(array_values($agendamentoIds));

        return array_fill_keys(array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN)), 1);
    }

    public static function mediaDoProfissional(int $profissionalId): ?float
    {
        $stmt = Database::connection()->prepare(
            'SELECT AVG(nota) FROM avaliacoes WHERE profissional_id = :profissional_id'
        );
        $stmt->execute(['profissional_id' => $profissionalId]);
        $media = $stmt->fetchColumn();

        return $media !== null && $media !== false ? round((float) $media, 1) : null;
    }

    public static function criar(
        int $barbeariaId,
        int $agendamentoId,
        int $clienteId,
        int $profissionalId,
        int $nota,
        ?string $comentario,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO avaliacoes (barbearia_id, agendamento_id, cliente_id, profissional_id, nota, comentario, created_at)
             VALUES (:barbearia_id, :agendamento_id, :cliente_id, :profissional_id, :nota, :comentario, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'agendamento_id' => $agendamentoId,
            'cliente_id' => $clienteId,
            'profissional_id' => $profissionalId,
            'nota' => max(1, min(5, $nota)),
            'comentario' => $comentario !== null && trim($comentario) !== '' ? trim($comentario) : null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            agendamentoId: (int) $row['agendamento_id'],
            clienteId: (int) $row['cliente_id'],
            profissionalId: (int) $row['profissional_id'],
            nota: (int) $row['nota'],
            comentario: $row['comentario'] ?? null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
