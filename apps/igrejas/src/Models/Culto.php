<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de Culto (modulo Cultos).
 *
 * Cada culto pode ter uma lista de presentes (relacao muitos-para-muitos
 * com membros, atraves da tabela pivot culto_frequencias).
 */
final class Culto
{
    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly string $data,
        public readonly ?string $hora,
        public readonly ?string $local,
        public readonly ?string $descricao,
        public readonly string $status,
        public readonly int $totalPresentes,
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
            $where = 'WHERE c.titulo LIKE :search OR c.local LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM cultos c {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM culto_frequencias cf WHERE cf.culto_id = c.id) AS total_presentes
             FROM cultos c
             {$where}
             ORDER BY c.data DESC, c.hora DESC
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
            "SELECT c.*,
                    (SELECT COUNT(*) FROM culto_frequencias cf WHERE cf.culto_id = c.id) AS total_presentes
             FROM cultos c
             WHERE c.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Proximo culto agendado (data >= hoje, nao cancelado), usado no KPI
     * do dashboard.
     */
    public static function proximoAgendado(): ?self
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM culto_frequencias cf WHERE cf.culto_id = c.id) AS total_presentes
             FROM cultos c
             WHERE c.data >= CURDATE() AND c.status != 'cancelado'
             ORDER BY c.data ASC, c.hora ASC
             LIMIT 1"
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO cultos (titulo, data, hora, local, descricao, status, created_at)
             VALUES (:titulo, :data, :hora, :local, :descricao, :status, NOW())'
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
            'UPDATE cultos SET titulo = :titulo, data = :data, hora = :hora,
                local = :local, descricao = :descricao, status = :status
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM cultos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Membros presentes no culto, ordenados por nome.
     *
     * @return array<int, Membro>
     */
    public static function presentes(int $cultoId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT me.* FROM membros me
             INNER JOIN culto_frequencias cf ON cf.membro_id = me.id
             WHERE cf.culto_id = :culto_id
             ORDER BY me.nome ASC'
        );
        $stmt->execute(['culto_id' => $cultoId]);

        return array_map(Membro::fromRow(...), $stmt->fetchAll());
    }

    public static function addPresenca(int $cultoId, int $membroId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO culto_frequencias (culto_id, membro_id, created_at)
             VALUES (:culto_id, :membro_id, NOW())'
        );
        $stmt->execute(['culto_id' => $cultoId, 'membro_id' => $membroId]);
    }

    public static function removePresenca(int $cultoId, int $membroId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM culto_frequencias WHERE culto_id = :culto_id AND membro_id = :membro_id'
        );
        $stmt->execute(['culto_id' => $cultoId, 'membro_id' => $membroId]);
    }

    public function dataHoraFormatada(): string
    {
        $formatada = (new \DateTimeImmutable($this->data))->format('d/m/Y');

        if ($this->hora !== null) {
            $formatada .= ' as ' . substr($this->hora, 0, 5);
        }

        return $formatada;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        return [
            'titulo' => trim((string) $data['titulo']),
            'data' => (string) $data['data'],
            'hora' => self::nullable($data['hora'] ?? null),
            'local' => self::nullable($data['local'] ?? null),
            'descricao' => self::nullable($data['descricao'] ?? null),
            'status' => in_array($data['status'] ?? null, ['agendado', 'realizado', 'cancelado'], true)
                ? $data['status']
                : 'agendado',
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
            titulo: (string) $row['titulo'],
            data: (string) $row['data'],
            hora: $row['hora'],
            local: $row['local'],
            descricao: $row['descricao'],
            status: (string) $row['status'],
            totalPresentes: (int) ($row['total_presentes'] ?? 0),
            createdAt: (string) $row['created_at'],
        );
    }
}
