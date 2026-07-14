<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Model de MembroDocumento (aba "Documentos" do perfil do membro).
 *
 * Mesmo padrao de storage em disco usado por Playback - so o caminho do
 * arquivo fica no banco, o arquivo em si vai pra
 * public/uploads/membros/{tenant}/ (ver MembroController).
 */
final class MembroDocumento
{
    public function __construct(
        public readonly int $id,
        public readonly int $membroId,
        public readonly string $nome,
        public readonly string $arquivoPath,
        public readonly int $tamanhoBytes,
        public readonly string $enviadoEm,
    ) {
    }

    /**
     * @return array<int, self>
     */
    public static function doMembro(int $membroId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM membro_documentos WHERE membro_id = :membro_id ORDER BY enviado_em DESC'
        );
        $stmt->execute(['membro_id' => $membroId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM membro_documentos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function create(int $membroId, string $nome, string $arquivoPath, int $tamanhoBytes): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO membro_documentos (membro_id, nome, arquivo_path, tamanho_bytes, enviado_em)
             VALUES (:membro_id, :nome, :arquivo_path, :tamanho_bytes, NOW())'
        );
        $stmt->execute([
            'membro_id' => $membroId,
            'nome' => $nome,
            'arquivo_path' => $arquivoPath,
            'tamanho_bytes' => $tamanhoBytes,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM membro_documentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            membroId: (int) $row['membro_id'],
            nome: (string) $row['nome'],
            arquivoPath: (string) $row['arquivo_path'],
            tamanhoBytes: (int) $row['tamanho_bytes'],
            enviadoEm: (string) $row['enviado_em'],
        );
    }
}
