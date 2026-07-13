<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de Louvor (modulo Louvores: letras, cifras e tons dos louvores
 * do ministerio de louvor, com historico de mudancas de tom - ver
 * LouvorTomHistorico abaixo). Acesso ao modulo e restrito a usuarios
 * com a flag "musico" (ou admin), ver User::MODULOS_SOMENTE_MUSICO.
 */
final class Louvor
{
    /**
     * Todos os tons (maiores e menores) pro <select> de "Tom atual" -
     * grafia mais comum usada em cifras brasileiras (bemol pros tons
     * que normalmente aparecem assim: Eb, Ab, Bb, em vez de D#/G#/A#).
     * Usado tambem pelo transpositor automatico no cliente (ver
     * louvor-transpositor.js), que precisa da MESMA lista pra saber
     * a posicao de cada tom na escala cromatica.
     *
     * @var array<int, string>
     */
    public const TONS_MAIORES = ['C', 'C#', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B'];

    /** @var array<int, string> */
    public const TONS_MENORES = ['Cm', 'C#m', 'Dm', 'Ebm', 'Em', 'Fm', 'F#m', 'Gm', 'Abm', 'Am', 'Bbm', 'Bm'];

    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly ?string $letra,
        public readonly ?string $tomAtual,
        public readonly ?string $cifra,
        public readonly ?string $anexoPath,
        public readonly ?string $anexoNomeOriginal,
        public readonly ?int $playbackId,
        public readonly ?string $playbackTitulo,
        public readonly string $status,
        public readonly ?int $criadoPor,
        public readonly ?string $criadoPorNome,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    private const SELECT_BASE = 'SELECT l.*, p.titulo AS playback_titulo, u.name AS criado_por_nome
        FROM louvores l
        LEFT JOIN playbacks p ON p.id = l.playback_id
        LEFT JOIN users u ON u.id = l.criado_por';

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
            $where = 'WHERE l.titulo LIKE :search_titulo OR l.letra LIKE :search_letra';
            $params['search_titulo'] = '%' . $search . '%';
            $params['search_letra'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM louvores l {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            self::SELECT_BASE . " {$where} ORDER BY l.titulo ASC LIMIT :limit OFFSET :offset"
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
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE l.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data, ?int $criadoPor): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO louvores (titulo, letra, tom_atual, cifra, anexo_path, anexo_nome_original, playback_id, status, criado_por, created_at)
             VALUES (:titulo, :letra, :tom_atual, :cifra, :anexo_path, :anexo_nome_original, :playback_id, :status, :criado_por, NOW())'
        );
        $stmt->execute(self::bindings($data) + ['criado_por' => $criadoPor]);

        $id = (int) Database::connection()->lastInsertId();

        $tomAtual = self::nullable($data['tom_atual'] ?? null);
        if ($tomAtual !== null) {
            LouvorTomHistorico::registrar($id, null, $tomAtual, 'Cadastro inicial', $criadoPor);
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data, ?int $alteradoPor): void
    {
        $atual = self::find($id);

        $stmt = Database::connection()->prepare(
            'UPDATE louvores SET
                titulo = :titulo, letra = :letra, tom_atual = :tom_atual, cifra = :cifra,
                anexo_path = :anexo_path, anexo_nome_original = :anexo_nome_original,
                playback_id = :playback_id, status = :status
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);

        $tomNovo = self::nullable($data['tom_atual'] ?? null);
        $tomAnterior = $atual?->tomAtual;

        if ($tomNovo !== null && $tomNovo !== $tomAnterior) {
            LouvorTomHistorico::registrar($id, $tomAnterior, $tomNovo, self::nullable($data['tom_observacao'] ?? null), $alteradoPor);
        }
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM louvores WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        $playbackId = (int) ($data['playback_id'] ?? 0);

        return [
            'titulo' => trim((string) $data['titulo']),
            'letra' => self::nullable($data['letra'] ?? null),
            'tom_atual' => self::nullable($data['tom_atual'] ?? null),
            'cifra' => self::nullable($data['cifra'] ?? null),
            'anexo_path' => self::nullable($data['anexo_path'] ?? null),
            'anexo_nome_original' => self::nullable($data['anexo_nome_original'] ?? null),
            'playback_id' => $playbackId > 0 ? $playbackId : null,
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
            letra: $row['letra'],
            tomAtual: $row['tom_atual'],
            cifra: $row['cifra'],
            anexoPath: $row['anexo_path'],
            anexoNomeOriginal: $row['anexo_nome_original'],
            playbackId: $row['playback_id'] !== null ? (int) $row['playback_id'] : null,
            playbackTitulo: $row['playback_titulo'] ?? null,
            status: (string) $row['status'],
            criadoPor: $row['criado_por'] !== null ? (int) $row['criado_por'] : null,
            criadoPorNome: $row['criado_por_nome'] ?? null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
