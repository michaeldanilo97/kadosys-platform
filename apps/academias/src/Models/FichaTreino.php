<?php

declare(strict_types=1);

namespace Academias\Models;

use Academias\Core\Database;

/**
 * Ficha de treino prescrita pelo professor pra um aluno. Uma academia
 * pode ter varias fichas "ativa" ao mesmo tempo pro mesmo aluno (ex.:
 * "Treino A" e "Treino B" alternados numa rotina semanal) - por isso o
 * painel do aluno mostra todas as fichas ativas, nao so uma.
 */
final class FichaTreino
{
    private const SELECT_COLUNAS = 'id, academia_id, aluno_id, professor_id, nome, objetivo, validade_ate, ativa, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $academiaId,
        public readonly int $alunoId,
        public readonly ?int $professorId,
        public readonly string $nome,
        public readonly ?string $objetivo,
        public readonly ?string $validadeAte,
        public readonly bool $ativa,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $academiaId, int $page, int $perPage): array
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM fichas_treino WHERE academia_id = :academia_id');
        $stmt->execute(['academia_id' => $academiaId]);
        $total = (int) $stmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM fichas_treino
             WHERE academia_id = :academia_id ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}"
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
            'SELECT ' . self::SELECT_COLUNAS . ' FROM fichas_treino WHERE id = :id AND academia_id = :academia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Fichas ativas de um aluno - usado no painel dele pra listar os
     * treinos que pode fazer hoje.
     *
     * @return array<int, self>
     */
    public static function ativasDoAluno(int $academiaId, int $alunoId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM fichas_treino
             WHERE academia_id = :academia_id AND aluno_id = :aluno_id AND ativa = 1
             ORDER BY created_at DESC'
        );
        $stmt->execute(['academia_id' => $academiaId, 'aluno_id' => $alunoId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(
        int $academiaId,
        int $alunoId,
        ?int $professorId,
        string $nome,
        ?string $objetivo,
        ?string $validadeAte,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO fichas_treino (academia_id, aluno_id, professor_id, nome, objetivo, validade_ate, ativa, created_at)
             VALUES (:academia_id, :aluno_id, :professor_id, :nome, :objetivo, :validade_ate, 1, NOW())'
        );
        $stmt->execute([
            'academia_id' => $academiaId,
            'aluno_id' => $alunoId,
            'professor_id' => $professorId,
            'nome' => trim($nome),
            'objetivo' => self::nullable($objetivo),
            'validade_ate' => self::nullable($validadeAte),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(
        int $id,
        int $academiaId,
        int $alunoId,
        ?int $professorId,
        string $nome,
        ?string $objetivo,
        ?string $validadeAte,
        bool $ativa,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE fichas_treino SET aluno_id = :aluno_id, professor_id = :professor_id, nome = :nome,
                objetivo = :objetivo, validade_ate = :validade_ate, ativa = :ativa
             WHERE id = :id AND academia_id = :academia_id'
        );
        $stmt->execute([
            'aluno_id' => $alunoId,
            'professor_id' => $professorId,
            'nome' => trim($nome),
            'objetivo' => self::nullable($objetivo),
            'validade_ate' => self::nullable($validadeAte),
            'ativa' => $ativa ? 1 : 0,
            'id' => $id,
            'academia_id' => $academiaId,
        ]);
    }

    public static function delete(int $id, int $academiaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM fichas_treino WHERE id = :id AND academia_id = :academia_id');
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
            nome: (string) $row['nome'],
            objetivo: $row['objetivo'] ?? null,
            validadeAte: $row['validade_ate'] ?? null,
            ativa: (bool) $row['ativa'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
