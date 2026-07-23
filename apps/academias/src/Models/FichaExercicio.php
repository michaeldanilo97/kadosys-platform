<?php

declare(strict_types=1);

namespace Academias\Models;

use Academias\Core\Database;

/**
 * Um exercicio dentro de uma ficha de treino (ver FichaTreino), na
 * ordem em que deve ser executado.
 */
final class FichaExercicio
{
    private const SELECT_COLUNAS = 'id, ficha_id, ordem, nome_exercicio, grupo_muscular, series, repeticoes,
        carga_sugerida_kg, descanso_segundos, observacao';

    public function __construct(
        public readonly int $id,
        public readonly int $fichaId,
        public readonly int $ordem,
        public readonly string $nomeExercicio,
        public readonly ?string $grupoMuscular,
        public readonly ?int $series,
        public readonly ?string $repeticoes,
        public readonly ?float $cargaSugeridaKg,
        public readonly ?int $descansoSegundos,
        public readonly ?string $observacao,
    ) {
    }

    /** @return array<int, self> */
    public static function porFicha(int $fichaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM ficha_exercicios WHERE ficha_id = :ficha_id ORDER BY ordem ASC, id ASC'
        );
        $stmt->execute(['ficha_id' => $fichaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id, int $fichaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM ficha_exercicios WHERE id = :id AND ficha_id = :ficha_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'ficha_id' => $fichaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function create(
        int $fichaId,
        string $nomeExercicio,
        ?string $grupoMuscular,
        ?int $series,
        ?string $repeticoes,
        ?float $cargaSugeridaKg,
        ?int $descansoSegundos,
        ?string $observacao,
    ): int {
        $stmt = Database::connection()->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM ficha_exercicios WHERE ficha_id = :ficha_id');
        $stmt->execute(['ficha_id' => $fichaId]);
        $proximaOrdem = (int) $stmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            'INSERT INTO ficha_exercicios
                (ficha_id, ordem, nome_exercicio, grupo_muscular, series, repeticoes, carga_sugerida_kg, descanso_segundos, observacao)
             VALUES
                (:ficha_id, :ordem, :nome_exercicio, :grupo_muscular, :series, :repeticoes, :carga_sugerida_kg, :descanso_segundos, :observacao)'
        );
        $stmt->execute([
            'ficha_id' => $fichaId,
            'ordem' => $proximaOrdem,
            'nome_exercicio' => trim($nomeExercicio),
            'grupo_muscular' => self::nullableStr($grupoMuscular),
            'series' => $series,
            'repeticoes' => self::nullableStr($repeticoes),
            'carga_sugerida_kg' => $cargaSugeridaKg,
            'descanso_segundos' => $descansoSegundos,
            'observacao' => self::nullableStr($observacao),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(
        int $id,
        int $fichaId,
        string $nomeExercicio,
        ?string $grupoMuscular,
        ?int $series,
        ?string $repeticoes,
        ?float $cargaSugeridaKg,
        ?int $descansoSegundos,
        ?string $observacao,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE ficha_exercicios SET nome_exercicio = :nome_exercicio, grupo_muscular = :grupo_muscular,
                series = :series, repeticoes = :repeticoes, carga_sugerida_kg = :carga_sugerida_kg,
                descanso_segundos = :descanso_segundos, observacao = :observacao
             WHERE id = :id AND ficha_id = :ficha_id'
        );
        $stmt->execute([
            'nome_exercicio' => trim($nomeExercicio),
            'grupo_muscular' => self::nullableStr($grupoMuscular),
            'series' => $series,
            'repeticoes' => self::nullableStr($repeticoes),
            'carga_sugerida_kg' => $cargaSugeridaKg,
            'descanso_segundos' => $descansoSegundos,
            'observacao' => self::nullableStr($observacao),
            'id' => $id,
            'ficha_id' => $fichaId,
        ]);
    }

    public static function delete(int $id, int $fichaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM ficha_exercicios WHERE id = :id AND ficha_id = :ficha_id');
        $stmt->execute(['id' => $id, 'ficha_id' => $fichaId]);
    }

    private static function nullableStr(?string $valor): ?string
    {
        $valor = $valor !== null ? trim($valor) : '';

        return $valor === '' ? null : $valor;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            fichaId: (int) $row['ficha_id'],
            ordem: (int) $row['ordem'],
            nomeExercicio: (string) $row['nome_exercicio'],
            grupoMuscular: $row['grupo_muscular'] ?? null,
            series: $row['series'] !== null ? (int) $row['series'] : null,
            repeticoes: $row['repeticoes'] ?? null,
            cargaSugeridaKg: $row['carga_sugerida_kg'] !== null ? (float) $row['carga_sugerida_kg'] : null,
            descansoSegundos: $row['descanso_segundos'] !== null ? (int) $row['descanso_segundos'] : null,
            observacao: $row['observacao'] ?? null,
        );
    }
}
