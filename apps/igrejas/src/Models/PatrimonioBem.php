<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de PatrimonioBem (modulo Patrimonio: bens, imoveis e
 * equipamentos da igreja).
 */
final class PatrimonioBem
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $categoria,
        public readonly ?string $numeroPatrimonio,
        public readonly ?string $descricao,
        public readonly ?float $valorEstimado,
        public readonly ?string $dataAquisicao,
        public readonly ?string $local,
        public readonly string $status,
        public readonly ?string $observacoes,
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
            $where = 'WHERE nome LIKE :search_nome OR numero_patrimonio LIKE :search_numero OR local LIKE :search_local';
            $params['search_nome'] = '%' . $search . '%';
            $params['search_numero'] = '%' . $search . '%';
            $params['search_local'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM patrimonio_bens {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT * FROM patrimonio_bens {$where} ORDER BY nome ASC LIMIT :limit OFFSET :offset"
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
        $stmt = Database::connection()->prepare('SELECT * FROM patrimonio_bens WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function valorTotalAtivo(): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(valor_estimado), 0) FROM patrimonio_bens WHERE status != 'baixado'"
        );
        $stmt->execute();

        return (float) $stmt->fetchColumn();
    }

    public static function countAtivos(): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM patrimonio_bens WHERE status = 'ativo'");
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO patrimonio_bens
                (nome, categoria, numero_patrimonio, descricao, valor_estimado, data_aquisicao, local, status, observacoes, created_at)
             VALUES
                (:nome, :categoria, :numero_patrimonio, :descricao, :valor_estimado, :data_aquisicao, :local, :status, :observacoes, NOW())'
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
            'UPDATE patrimonio_bens SET
                nome = :nome, categoria = :categoria, numero_patrimonio = :numero_patrimonio,
                descricao = :descricao, valor_estimado = :valor_estimado, data_aquisicao = :data_aquisicao,
                local = :local, status = :status, observacoes = :observacoes
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM patrimonio_bens WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        $valor = trim((string) ($data['valor_estimado'] ?? ''));

        return [
            'nome' => trim((string) $data['nome']),
            'categoria' => in_array($data['categoria'] ?? null, ['imovel', 'veiculo', 'equipamento', 'mobiliario', 'eletronico', 'outro'], true)
                ? $data['categoria']
                : 'outro',
            'numero_patrimonio' => self::nullable($data['numero_patrimonio'] ?? null),
            'descricao' => self::nullable($data['descricao'] ?? null),
            'valor_estimado' => $valor === '' ? null : (float) str_replace(',', '.', $valor),
            'data_aquisicao' => self::nullable($data['data_aquisicao'] ?? null),
            'local' => self::nullable($data['local'] ?? null),
            'status' => in_array($data['status'] ?? null, ['ativo', 'manutencao', 'baixado'], true) ? $data['status'] : 'ativo',
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
            nome: (string) $row['nome'],
            categoria: (string) $row['categoria'],
            numeroPatrimonio: $row['numero_patrimonio'],
            descricao: $row['descricao'],
            valorEstimado: $row['valor_estimado'] !== null ? (float) $row['valor_estimado'] : null,
            dataAquisicao: $row['data_aquisicao'],
            local: $row['local'],
            status: (string) $row['status'],
            observacoes: $row['observacoes'],
            createdAt: (string) $row['created_at'],
        );
    }
}
