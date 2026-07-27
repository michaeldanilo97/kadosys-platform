<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Foto de um momento marcante da igreja (culto especial, batismo,
 * evento, confraternizacao) exibida no mural publico /galeria (ver
 * Igrejas\Controllers\GaleriaController) - mesmo espirito do quadro de
 * avisos publico, so que em formato de galeria de fotos.
 */
final class GaleriaMemoria
{
    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly ?string $legenda,
        public readonly ?string $dataRegistro,
        public readonly string $fotoPath,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @return array<int, self>
     */
    public static function todas(): array
    {
        $stmt = Database::connection()->query(
            'SELECT * FROM galeria_memorias ORDER BY COALESCE(data_registro, created_at) DESC, id DESC'
        );

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM galeria_memorias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function create(string $titulo, ?string $legenda, ?string $dataRegistro, string $fotoPath): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO galeria_memorias (titulo, legenda, data_registro, foto_path, created_at)
             VALUES (:titulo, :legenda, :data_registro, :foto_path, NOW())'
        );
        $stmt->execute([
            'titulo' => $titulo,
            'legenda' => self::nullable($legenda),
            'data_registro' => self::nullable($dataRegistro),
            'foto_path' => $fotoPath,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM galeria_memorias WHERE id = :id');
        $stmt->execute(['id' => $id]);
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
            titulo: (string) $row['titulo'],
            legenda: $row['legenda'] ?? null,
            dataRegistro: $row['data_registro'] ?? null,
            fotoPath: (string) $row['foto_path'],
            createdAt: (string) $row['created_at'],
        );
    }
}
