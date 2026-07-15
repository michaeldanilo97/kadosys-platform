<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Foto de trabalho de um profissional (corte feito, antes/depois) -
 * mostrada como galeria na pagina publica de agendamento, no card do
 * profissional (ver Barbearias\Controllers\AgendamentoPublicoController).
 */
final class PortfolioFoto
{
    private const SELECT_COLUNAS = 'id, barbearia_id, profissional_id, foto_path, legenda, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly int $profissionalId,
        public readonly string $fotoPath,
        public readonly ?string $legenda,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function find(int $id, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM portfolio_fotos WHERE id = :id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function doProfissional(int $profissionalId, int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM portfolio_fotos
             WHERE profissional_id = :profissional_id AND barbearia_id = :barbearia_id
             ORDER BY created_at DESC'
        );
        $stmt->execute(['profissional_id' => $profissionalId, 'barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Mapa profissional_id => lista de fotos, numa unica consulta -
     * usado na pagina publica de agendamento pra montar a galeria de
     * cada profissional sem N consultas.
     *
     * @param array<int, int> $profissionalIds
     * @return array<int, array<int, self>>
     */
    public static function mapaDosProfissionais(array $profissionalIds): array
    {
        if ($profissionalIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($profissionalIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT " . self::SELECT_COLUNAS . " FROM portfolio_fotos
             WHERE profissional_id IN ({$placeholders})
             ORDER BY created_at DESC"
        );
        $stmt->execute(array_values($profissionalIds));

        $mapa = [];

        foreach ($stmt->fetchAll() as $row) {
            $foto = self::fromRow($row);
            $mapa[$foto->profissionalId][] = $foto;
        }

        return $mapa;
    }

    public static function create(int $barbeariaId, int $profissionalId, string $fotoPath, ?string $legenda): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO portfolio_fotos (barbearia_id, profissional_id, foto_path, legenda, created_at)
             VALUES (:barbearia_id, :profissional_id, :foto_path, :legenda, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'profissional_id' => $profissionalId,
            'foto_path' => $fotoPath,
            'legenda' => self::nullable($legenda),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM portfolio_fotos WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
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
            barbeariaId: (int) $row['barbearia_id'],
            profissionalId: (int) $row['profissional_id'],
            fotoPath: (string) $row['foto_path'],
            legenda: $row['legenda'] ?? null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
