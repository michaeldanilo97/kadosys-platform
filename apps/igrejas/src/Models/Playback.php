<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de Playback (modulo Playbacks: biblioteca de audios do ministerio
 * de louvor). Mesma estrutura basica do modulo Grupos, sem relacao com
 * outras tabelas - so o cadastro do arquivo em si.
 */
final class Playback
{
    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly ?string $artista,
        public readonly string $arquivoPath,
        public readonly int $tamanhoBytes,
        public readonly string $status,
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
            // cada ocorrencia de LIKE precisa do seu proprio placeholder,
            // mesmo vinculado ao mesmo valor.
            $where = 'WHERE titulo LIKE :search_titulo OR artista LIKE :search_artista';
            $params['search_titulo'] = '%' . $search . '%';
            $params['search_artista'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM playbacks {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT * FROM playbacks {$where} ORDER BY titulo ASC LIMIT :limit OFFSET :offset"
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

    /**
     * Lista enxuta (id + titulo) dos playbacks ativos, usada no combo
     * de "vincular áudio" do módulo Louvores - não precisa do objeto
     * Playback inteiro, só o necessário pra popular um <select>.
     *
     * @return array<int, array{id: int, titulo: string}>
     */
    public static function listaParaSelect(): array
    {
        $stmt = Database::connection()->query(
            "SELECT id, titulo FROM playbacks WHERE status = 'ativo' ORDER BY titulo ASC"
        );

        return array_map(
            static fn (array $row): array => ['id' => (int) $row['id'], 'titulo' => (string) $row['titulo']],
            $stmt->fetchAll()
        );
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM playbacks WHERE id = :id LIMIT 1');
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
            'INSERT INTO playbacks (titulo, artista, arquivo_path, tamanho_bytes, status, created_at)
             VALUES (:titulo, :artista, :arquivo_path, :tamanho_bytes, :status, NOW())'
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
            'UPDATE playbacks SET titulo = :titulo, artista = :artista, status = :status WHERE id = :id'
        );
        $stmt->execute([
            'titulo' => trim((string) $data['titulo']),
            'artista' => self::nullable($data['artista'] ?? null),
            'status' => in_array($data['status'] ?? null, ['ativo', 'inativo'], true) ? $data['status'] : 'ativo',
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM playbacks WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        return [
            'titulo' => trim((string) $data['titulo']),
            'artista' => self::nullable($data['artista'] ?? null),
            'arquivo_path' => (string) $data['arquivo_path'],
            'tamanho_bytes' => (int) $data['tamanho_bytes'],
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
            titulo: (string) $row['titulo'],
            artista: $row['artista'],
            arquivoPath: (string) $row['arquivo_path'],
            tamanhoBytes: (int) $row['tamanho_bytes'],
            status: (string) $row['status'],
            createdAt: (string) $row['created_at'],
        );
    }
}
