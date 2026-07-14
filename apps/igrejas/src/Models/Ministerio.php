<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de Ministerio (modulo Ministerios).
 *
 * Cada ministerio pode ter um lider (membro) e uma lista de voluntarios
 * (relacao muitos-para-muitos com membros via ministerio_membros).
 */
final class Ministerio
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly ?string $descricao,
        public readonly ?int $liderMembroId,
        public readonly ?string $liderNome,
        public readonly string $status,
        public readonly int $totalVoluntarios,
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
            $where = 'WHERE mi.nome LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM ministerios mi {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT mi.*, me.nome AS lider_nome,
                    (SELECT COUNT(*) FROM ministerio_membros mm WHERE mm.ministerio_id = mi.id) AS total_voluntarios
             FROM ministerios mi
             LEFT JOIN membros me ON me.id = mi.lider_membro_id
             {$where}
             ORDER BY mi.nome ASC
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
            "SELECT mi.*, me.nome AS lider_nome,
                    (SELECT COUNT(*) FROM ministerio_membros mm WHERE mm.ministerio_id = mi.id) AS total_voluntarios
             FROM ministerios mi
             LEFT JOIN membros me ON me.id = mi.lider_membro_id
             WHERE mi.id = :id
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
            'INSERT INTO ministerios (nome, descricao, lider_membro_id, status, created_at)
             VALUES (:nome, :descricao, :lider_membro_id, :status, NOW())'
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
            'UPDATE ministerios SET nome = :nome, descricao = :descricao,
                lider_membro_id = :lider_membro_id, status = :status
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM ministerios WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function countActive(): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM ministerios WHERE status = 'ativo'");
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Voluntarios (membros) vinculados ao ministerio, ordenados por nome.
     *
     * @return array<int, Membro>
     */
    public static function voluntarios(int $ministerioId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT me.* FROM membros me
             INNER JOIN ministerio_membros mm ON mm.membro_id = me.id
             WHERE mm.ministerio_id = :ministerio_id
             ORDER BY me.nome ASC'
        );
        $stmt->execute(['ministerio_id' => $ministerioId]);

        return array_map(Membro::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Ministerios em que o membro atua (como lider ou voluntario) -
     * usado na aba "Ministérios" do perfil do membro (inverso de
     * voluntarios()). "papel" vem de comparar o membro com o
     * lider_membro_id do proprio ministerio - nao existe um papel por
     * vinculo hoje, so um lider unico por ministerio.
     *
     * @return array<int, array{id: int, nome: string, papel: string}>
     */
    public static function doMembro(int $membroId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT mi.id, mi.nome,
                    (mi.lider_membro_id = :membro_id_lider) AS eh_lider
             FROM ministerios mi
             INNER JOIN ministerio_membros mm ON mm.ministerio_id = mi.id
             WHERE mm.membro_id = :membro_id
             ORDER BY mi.nome ASC'
        );
        $stmt->execute(['membro_id_lider' => $membroId, 'membro_id' => $membroId]);

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'nome' => (string) $row['nome'],
                'papel' => ((int) $row['eh_lider']) === 1 ? 'Líder' : 'Membro',
            ],
            $stmt->fetchAll()
        );
    }

    public static function addVoluntario(int $ministerioId, int $membroId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO ministerio_membros (ministerio_id, membro_id, created_at)
             VALUES (:ministerio_id, :membro_id, NOW())'
        );
        $stmt->execute(['ministerio_id' => $ministerioId, 'membro_id' => $membroId]);
    }

    public static function removeVoluntario(int $ministerioId, int $membroId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM ministerio_membros WHERE ministerio_id = :ministerio_id AND membro_id = :membro_id'
        );
        $stmt->execute(['ministerio_id' => $ministerioId, 'membro_id' => $membroId]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        $liderId = trim((string) ($data['lider_membro_id'] ?? ''));

        return [
            'nome' => trim((string) $data['nome']),
            'descricao' => self::nullable($data['descricao'] ?? null),
            'lider_membro_id' => $liderId === '' ? null : (int) $liderId,
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
            descricao: $row['descricao'],
            liderMembroId: $row['lider_membro_id'] !== null ? (int) $row['lider_membro_id'] : null,
            liderNome: $row['lider_nome'] ?? null,
            status: (string) $row['status'],
            totalVoluntarios: (int) ($row['total_voluntarios'] ?? 0),
            createdAt: (string) $row['created_at'],
        );
    }
}
