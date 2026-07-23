<?php

declare(strict_types=1);

namespace Academias\Models;

use Academias\Core\Database;

/**
 * Registro de um exercicio marcado como feito pelo aluno num dia (carga
 * usada + series completas) - a fonte do grafico de evolucao de carga
 * (ver Academias\Controllers\TreinoController).
 */
final class TreinoExecucao
{
    public function __construct(
        public readonly int $id,
        public readonly int $fichaExercicioId,
        public readonly int $alunoId,
        public readonly string $dataExecucao,
        public readonly ?float $cargaUsadaKg,
        public readonly ?int $seriesCompletas,
    ) {
    }

    /**
     * Registra (ou atualiza, se o aluno ja tinha marcado esse exercicio
     * hoje) a execucao do dia - nunca duplica linha no mesmo dia, ver
     * UNIQUE KEY em treino_execucoes.
     */
    public static function registrar(int $fichaExercicioId, int $alunoId, ?float $cargaUsadaKg, ?int $seriesCompletas): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO treino_execucoes (ficha_exercicio_id, aluno_id, data_execucao, carga_usada_kg, series_completas, created_at)
             VALUES (:ficha_exercicio_id, :aluno_id, CURDATE(), :carga_usada_kg, :series_completas, NOW())
             ON DUPLICATE KEY UPDATE carga_usada_kg = :carga_usada_kg2, series_completas = :series_completas2'
        );
        $stmt->execute([
            'ficha_exercicio_id' => $fichaExercicioId,
            'aluno_id' => $alunoId,
            'carga_usada_kg' => $cargaUsadaKg,
            'series_completas' => $seriesCompletas,
            'carga_usada_kg2' => $cargaUsadaKg,
            'series_completas2' => $seriesCompletas,
        ]);
    }

    public static function hojeDoExercicio(int $fichaExercicioId, int $alunoId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, ficha_exercicio_id, aluno_id, data_execucao, carga_usada_kg, series_completas
             FROM treino_execucoes
             WHERE ficha_exercicio_id = :ficha_exercicio_id AND aluno_id = :aluno_id AND data_execucao = CURDATE()
             LIMIT 1'
        );
        $stmt->execute(['ficha_exercicio_id' => $fichaExercicioId, 'aluno_id' => $alunoId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Historico de carga usada num exercicio, do mais antigo pro mais
     * recente (ordem cronologica, pronta pro grafico de evolucao).
     *
     * @return array<int, self>
     */
    public static function evolucaoDoExercicio(int $fichaExercicioId, int $alunoId, int $limite = 12): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT id, ficha_exercicio_id, aluno_id, data_execucao, carga_usada_kg, series_completas
             FROM treino_execucoes
             WHERE ficha_exercicio_id = :ficha_exercicio_id AND aluno_id = :aluno_id AND carga_usada_kg IS NOT NULL
             ORDER BY data_execucao DESC LIMIT {$limite}"
        );
        $stmt->execute(['ficha_exercicio_id' => $fichaExercicioId, 'aluno_id' => $alunoId]);

        $linhas = array_reverse($stmt->fetchAll());

        return array_map(self::fromRow(...), $linhas);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            fichaExercicioId: (int) $row['ficha_exercicio_id'],
            alunoId: (int) $row['aluno_id'],
            dataExecucao: (string) $row['data_execucao'],
            cargaUsadaKg: $row['carga_usada_kg'] !== null ? (float) $row['carga_usada_kg'] : null,
            seriesCompletas: $row['series_completas'] !== null ? (int) $row['series_completas'] : null,
        );
    }
}
