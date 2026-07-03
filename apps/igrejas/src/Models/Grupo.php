<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de Grupo (modulo Grupos: pequenos grupos, celulas e classes).
 *
 * Mesma estrutura do modulo Ministerios - lider opcional (membro) e uma
 * lista de participantes (relacao muitos-para-muitos com membros via
 * grupo_membros) - com campos a mais pra registrar dia/horario/local
 * do encontro.
 */
final class Grupo
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $tipo,
        public readonly ?string $descricao,
        public readonly ?int $liderMembroId,
        public readonly ?string $liderNome,
        public readonly ?string $diaSemana,
        public readonly ?string $horario,
        public readonly ?string $local,
        public readonly string $status,
        public readonly int $totalParticipantes,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $page, int $perPage, string $search = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];

        if ($search !== '') {
            $where = 'WHERE g.nome LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM grupos g {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT g.*, me.nome AS lider_nome,
                    (SELECT COUNT(*) FROM grupo_membros gm WHERE gm.grupo_id = g.id) AS total_participantes
             FROM grupos g
             LEFT JOIN membros me ON me.id = g.lider_membro_id
             {$where}
             ORDER BY g.nome ASC
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

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            "SELECT g.*, me.nome AS lider_nome,
                    (SELECT COUNT(*) FROM grupo_membros gm WHERE gm.grupo_id = g.id) AS total_participantes
             FROM grupos g
             LEFT JOIN membros me ON me.id = g.lider_membro_id
             WHERE g.id = :id
             LIMIT 1"
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
            'INSERT INTO grupos (nome, tipo, descricao, lider_membro_id, dia_semana, horario, local, status, created_at)
             VALUES (:nome, :tipo, :descricao, :lider_membro_id, :dia_semana, :horario, :local, :status, NOW())'
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
            'UPDATE grupos SET nome = :nome, tipo = :tipo, descricao = :descricao,
                lider_membro_id = :lider_membro_id, dia_semana = :dia_semana,
                horario = :horario, local = :local, status = :status
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM grupos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function countActive(): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM grupos WHERE status = 'ativo'");
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Participantes (membros) vinculados ao grupo, ordenados por nome.
     *
     * @return array<int, Membro>
     */
    public static function participantes(int $grupoId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT me.* FROM membros me
             INNER JOIN grupo_membros gm ON gm.membro_id = me.id
             WHERE gm.grupo_id = :grupo_id
             ORDER BY me.nome ASC'
        );
        $stmt->execute(['grupo_id' => $grupoId]);

        return array_map(Membro::fromRow(...), $stmt->fetchAll());
    }

    public static function addParticipante(int $grupoId, int $membroId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO grupo_membros (grupo_id, membro_id, created_at)
             VALUES (:grupo_id, :membro_id, NOW())'
        );
        $stmt->execute(['grupo_id' => $grupoId, 'membro_id' => $membroId]);
    }

    public static function removeParticipante(int $grupoId, int $membroId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM grupo_membros WHERE grupo_id = :grupo_id AND membro_id = :membro_id'
        );
        $stmt->execute(['grupo_id' => $grupoId, 'membro_id' => $membroId]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        $liderId = trim((string) ($data['lider_membro_id'] ?? ''));
        $diaSemana = trim((string) ($data['dia_semana'] ?? ''));
        $horario = trim((string) ($data['horario'] ?? ''));

        return [
            'nome' => trim((string) $data['nome']),
            'tipo' => in_array($data['tipo'] ?? null, ['celula', 'classe', 'grupo'], true) ? $data['tipo'] : 'grupo',
            'descricao' => self::nullable($data['descricao'] ?? null),
            'lider_membro_id' => $liderId === '' ? null : (int) $liderId,
            'dia_semana' => in_array($diaSemana, ['domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'], true) ? $diaSemana : null,
            'horario' => $horario === '' ? null : $horario,
            'local' => self::nullable($data['local'] ?? null),
            'status' => in_array($data['status'] ?? null, ['ativo', 'inativo'], true) ? $data['status'] : 'ativo',
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
            nome: (string) $row['nome'],
            tipo: (string) $row['tipo'],
            descricao: $row['descricao'],
            liderMembroId: $row['lider_membro_id'] !== null ? (int) $row['lider_membro_id'] : null,
            liderNome: $row['lider_nome'] ?? null,
            diaSemana: $row['dia_semana'],
            horario: $row['horario'],
            local: $row['local'],
            status: (string) $row['status'],
            totalParticipantes: (int) ($row['total_participantes'] ?? 0),
            createdAt: (string) $row['created_at'],
        );
    }
}
