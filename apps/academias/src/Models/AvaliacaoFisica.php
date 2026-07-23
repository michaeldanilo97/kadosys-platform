<?php

declare(strict_types=1);

namespace Academias\Models;

use Academias\Core\Database;

/**
 * Avaliacao fisica periodica (bioimpedancia simplificada) registrada
 * pelo professor - peso obrigatorio, percentual de gordura e medidas
 * (peito/cintura/quadril/braco/coxa) opcionais.
 */
final class AvaliacaoFisica
{
    private const SELECT_COLUNAS = 'id, academia_id, aluno_id, professor_id, data_avaliacao, peso_kg, percentual_gordura,
        medida_peito_cm, medida_cintura_cm, medida_quadril_cm, medida_braco_cm, medida_coxa_cm, observacao, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $academiaId,
        public readonly int $alunoId,
        public readonly ?int $professorId,
        public readonly string $dataAvaliacao,
        public readonly float $pesoKg,
        public readonly ?float $percentualGordura,
        public readonly ?float $medidaPeitoCm,
        public readonly ?float $medidaCinturaCm,
        public readonly ?float $medidaQuadrilCm,
        public readonly ?float $medidaBracoCm,
        public readonly ?float $medidaCoxaCm,
        public readonly ?string $observacao,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $academiaId, int $page, int $perPage): array
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM avaliacoes_fisicas WHERE academia_id = :academia_id');
        $stmt->execute(['academia_id' => $academiaId]);
        $total = (int) $stmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM avaliacoes_fisicas
             WHERE academia_id = :academia_id ORDER BY data_avaliacao DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute(['academia_id' => $academiaId]);

        return [
            'items' => array_map(self::fromRow(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public static function find(int $id, int $academiaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM avaliacoes_fisicas WHERE id = :id AND academia_id = :academia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Historico de um aluno, do mais antigo pro mais recente (ordem
     * cronologica, pronta pro grafico de evolucao no painel dele).
     *
     * @return array<int, self>
     */
    public static function historicoDoAluno(int $academiaId, int $alunoId, int $limite = 12): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT " . self::SELECT_COLUNAS . " FROM avaliacoes_fisicas
             WHERE academia_id = :academia_id AND aluno_id = :aluno_id
             ORDER BY data_avaliacao DESC LIMIT {$limite}"
        );
        $stmt->execute(['academia_id' => $academiaId, 'aluno_id' => $alunoId]);

        return array_map(self::fromRow(...), array_reverse($stmt->fetchAll()));
    }

    public static function contarDoAluno(int $academiaId, int $alunoId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM avaliacoes_fisicas WHERE academia_id = :academia_id AND aluno_id = :aluno_id'
        );
        $stmt->execute(['academia_id' => $academiaId, 'aluno_id' => $alunoId]);

        return (int) $stmt->fetchColumn();
    }

    public static function create(
        int $academiaId,
        int $alunoId,
        ?int $professorId,
        string $dataAvaliacao,
        float $pesoKg,
        ?float $percentualGordura,
        ?float $medidaPeitoCm,
        ?float $medidaCinturaCm,
        ?float $medidaQuadrilCm,
        ?float $medidaBracoCm,
        ?float $medidaCoxaCm,
        ?string $observacao,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO avaliacoes_fisicas
                (academia_id, aluno_id, professor_id, data_avaliacao, peso_kg, percentual_gordura,
                 medida_peito_cm, medida_cintura_cm, medida_quadril_cm, medida_braco_cm, medida_coxa_cm, observacao, created_at)
             VALUES
                (:academia_id, :aluno_id, :professor_id, :data_avaliacao, :peso_kg, :percentual_gordura,
                 :medida_peito_cm, :medida_cintura_cm, :medida_quadril_cm, :medida_braco_cm, :medida_coxa_cm, :observacao, NOW())'
        );
        $stmt->execute([
            'academia_id' => $academiaId,
            'aluno_id' => $alunoId,
            'professor_id' => $professorId,
            'data_avaliacao' => $dataAvaliacao,
            'peso_kg' => $pesoKg,
            'percentual_gordura' => $percentualGordura,
            'medida_peito_cm' => $medidaPeitoCm,
            'medida_cintura_cm' => $medidaCinturaCm,
            'medida_quadril_cm' => $medidaQuadrilCm,
            'medida_braco_cm' => $medidaBracoCm,
            'medida_coxa_cm' => $medidaCoxaCm,
            'observacao' => self::nullable($observacao),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(
        int $id,
        int $academiaId,
        int $alunoId,
        ?int $professorId,
        string $dataAvaliacao,
        float $pesoKg,
        ?float $percentualGordura,
        ?float $medidaPeitoCm,
        ?float $medidaCinturaCm,
        ?float $medidaQuadrilCm,
        ?float $medidaBracoCm,
        ?float $medidaCoxaCm,
        ?string $observacao,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE avaliacoes_fisicas SET aluno_id = :aluno_id, professor_id = :professor_id, data_avaliacao = :data_avaliacao,
                peso_kg = :peso_kg, percentual_gordura = :percentual_gordura, medida_peito_cm = :medida_peito_cm,
                medida_cintura_cm = :medida_cintura_cm, medida_quadril_cm = :medida_quadril_cm,
                medida_braco_cm = :medida_braco_cm, medida_coxa_cm = :medida_coxa_cm, observacao = :observacao
             WHERE id = :id AND academia_id = :academia_id'
        );
        $stmt->execute([
            'aluno_id' => $alunoId,
            'professor_id' => $professorId,
            'data_avaliacao' => $dataAvaliacao,
            'peso_kg' => $pesoKg,
            'percentual_gordura' => $percentualGordura,
            'medida_peito_cm' => $medidaPeitoCm,
            'medida_cintura_cm' => $medidaCinturaCm,
            'medida_quadril_cm' => $medidaQuadrilCm,
            'medida_braco_cm' => $medidaBracoCm,
            'medida_coxa_cm' => $medidaCoxaCm,
            'observacao' => self::nullable($observacao),
            'id' => $id,
            'academia_id' => $academiaId,
        ]);
    }

    public static function delete(int $id, int $academiaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM avaliacoes_fisicas WHERE id = :id AND academia_id = :academia_id');
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);
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
            academiaId: (int) $row['academia_id'],
            alunoId: (int) $row['aluno_id'],
            professorId: $row['professor_id'] !== null ? (int) $row['professor_id'] : null,
            dataAvaliacao: (string) $row['data_avaliacao'],
            pesoKg: (float) $row['peso_kg'],
            percentualGordura: $row['percentual_gordura'] !== null ? (float) $row['percentual_gordura'] : null,
            medidaPeitoCm: $row['medida_peito_cm'] !== null ? (float) $row['medida_peito_cm'] : null,
            medidaCinturaCm: $row['medida_cintura_cm'] !== null ? (float) $row['medida_cintura_cm'] : null,
            medidaQuadrilCm: $row['medida_quadril_cm'] !== null ? (float) $row['medida_quadril_cm'] : null,
            medidaBracoCm: $row['medida_braco_cm'] !== null ? (float) $row['medida_braco_cm'] : null,
            medidaCoxaCm: $row['medida_coxa_cm'] !== null ? (float) $row['medida_coxa_cm'] : null,
            observacao: $row['observacao'] ?? null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
