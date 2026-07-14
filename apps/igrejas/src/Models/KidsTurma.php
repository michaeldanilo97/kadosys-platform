<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de KidsTurma (modulo KADOSYS Kids): turmas do ministerio
 * infantil, agrupadas por faixa etaria, com um professor responsavel
 * opcional (vinculado a Membros). Mesma estrutura do modulo Grupos, sem
 * a relacao muitos-para-muitos (aqui e a crianca que aponta pra uma
 * unica turma, ver KidsCrianca).
 */
final class KidsTurma
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly ?int $faixaEtariaMin,
        public readonly ?int $faixaEtariaMax,
        public readonly ?int $professorMembroId,
        public readonly ?string $professorNome,
        public readonly ?string $descricao,
        public readonly string $status,
        public readonly int $totalCriancas,
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
            $where = 'WHERE t.nome LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM kids_turmas t {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT t.*, me.nome AS professor_nome,
                    (SELECT COUNT(*) FROM kids_criancas c WHERE c.turma_id = t.id AND c.status = 'ativo') AS total_criancas
             FROM kids_turmas t
             LEFT JOIN membros me ON me.id = t.professor_membro_id
             {$where}
             ORDER BY t.faixa_etaria_min ASC, t.nome ASC
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
            "SELECT t.*, me.nome AS professor_nome,
                    (SELECT COUNT(*) FROM kids_criancas c WHERE c.turma_id = t.id AND c.status = 'ativo') AS total_criancas
             FROM kids_turmas t
             LEFT JOIN membros me ON me.id = t.professor_membro_id
             WHERE t.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Lista enxuta de turmas ativas, usada nos selects de KidsCrianca.
     *
     * @return array<int, self>
     */
    public static function allActive(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT t.*, me.nome AS professor_nome, 0 AS total_criancas
             FROM kids_turmas t
             LEFT JOIN membros me ON me.id = t.professor_membro_id
             WHERE t.status = 'ativo'
             ORDER BY t.faixa_etaria_min ASC, t.nome ASC"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO kids_turmas
                (nome, faixa_etaria_min, faixa_etaria_max, professor_membro_id, descricao, status, created_at)
             VALUES
                (:nome, :faixa_etaria_min, :faixa_etaria_max, :professor_membro_id, :descricao, :status, NOW())'
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
            'UPDATE kids_turmas SET
                nome = :nome, faixa_etaria_min = :faixa_etaria_min, faixa_etaria_max = :faixa_etaria_max,
                professor_membro_id = :professor_membro_id, descricao = :descricao, status = :status
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM kids_turmas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function countAtivas(): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM kids_turmas WHERE status = 'ativo'");
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        $professorId = trim((string) ($data['professor_membro_id'] ?? ''));
        $min = trim((string) ($data['faixa_etaria_min'] ?? ''));
        $max = trim((string) ($data['faixa_etaria_max'] ?? ''));

        return [
            'nome' => trim((string) $data['nome']),
            'faixa_etaria_min' => $min === '' ? null : (int) $min,
            'faixa_etaria_max' => $max === '' ? null : (int) $max,
            'professor_membro_id' => $professorId === '' ? null : (int) $professorId,
            'descricao' => self::nullable($data['descricao'] ?? null),
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
            faixaEtariaMin: $row['faixa_etaria_min'] !== null ? (int) $row['faixa_etaria_min'] : null,
            faixaEtariaMax: $row['faixa_etaria_max'] !== null ? (int) $row['faixa_etaria_max'] : null,
            professorMembroId: $row['professor_membro_id'] !== null ? (int) $row['professor_membro_id'] : null,
            professorNome: $row['professor_nome'] ?? null,
            descricao: $row['descricao'],
            status: (string) $row['status'],
            totalCriancas: (int) ($row['total_criancas'] ?? 0),
            createdAt: (string) $row['created_at'],
        );
    }
}
