<?php

declare(strict_types=1);

namespace Academias\Models;

use Academias\Core\Database;

/**
 * Registro de check-in/checkout via QR fixo (ver
 * Academias\Controllers\CheckinController). Mesma mecanica descrita
 * pelo usuario: um unico QR na entrada, o aluno escaneia pra entrar e
 * escaneia de novo pra sair - cada leitura vira uma linha aqui.
 */
final class AcademiaCheckin
{
    private const SELECT_COLUNAS = 'id, academia_id, aluno_id, entrada_em, saida_em, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $academiaId,
        public readonly int $alunoId,
        public readonly string $entradaEm,
        public readonly ?string $saidaEm,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * Check-in em aberto (ainda nao saiu) desse aluno, se houver.
     */
    public static function emAberto(int $academiaId, int $alunoId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM academia_checkins
             WHERE academia_id = :academia_id AND aluno_id = :aluno_id AND saida_em IS NULL
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['academia_id' => $academiaId, 'aluno_id' => $alunoId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function criar(int $academiaId, int $alunoId): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO academia_checkins (academia_id, aluno_id, entrada_em, created_at)
             VALUES (:academia_id, :aluno_id, NOW(), NOW())'
        );
        $stmt->execute(['academia_id' => $academiaId, 'aluno_id' => $alunoId]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function fecharSaida(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE academia_checkins SET saida_em = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * Quem esta dentro da academia agora (checkins em aberto) - usado
     * no painel ao vivo da recepcao.
     *
     * @return array<int, array{checkin: self, aluno: Aluno}>
     */
    public static function presentesAgora(int $academiaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.id, c.academia_id, c.aluno_id, c.entrada_em, c.saida_em, c.created_at
             FROM academia_checkins c
             WHERE c.academia_id = :academia_id AND c.saida_em IS NULL
             ORDER BY c.entrada_em DESC'
        );
        $stmt->execute(['academia_id' => $academiaId]);

        $resultado = [];

        foreach ($stmt->fetchAll() as $row) {
            $aluno = Aluno::find((int) $row['aluno_id'], $academiaId);

            if ($aluno !== null) {
                $resultado[] = ['checkin' => self::fromRow($row), 'aluno' => $aluno];
            }
        }

        return $resultado;
    }

    /**
     * Historico paginado (mais recentes primeiro), com o aluno de cada
     * linha ja resolvido.
     *
     * @return array{items: array<int, array{checkin: self, aluno: Aluno}>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function historico(int $academiaId, int $page, int $perPage): array
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM academia_checkins WHERE academia_id = :academia_id');
        $stmt->execute(['academia_id' => $academiaId]);
        $total = (int) $stmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM academia_checkins
             WHERE academia_id = :academia_id ORDER BY entrada_em DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute(['academia_id' => $academiaId]);

        $items = [];

        foreach ($stmt->fetchAll() as $row) {
            $aluno = Aluno::find((int) $row['aluno_id'], $academiaId);

            if ($aluno !== null) {
                $items[] = ['checkin' => self::fromRow($row), 'aluno' => $aluno];
            }
        }

        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'lastPage' => $lastPage];
    }

    /**
     * Ranking de frequencia do mes corrente: numero de check-ins de
     * cada aluno neste mes, do maior pro menor. Calculado ao vivo (nao
     * guardado em coluna) pra nao precisar de nenhuma logica de reset
     * mensal.
     *
     * @return array<int, array{aluno: Aluno, checkinsNoMes: int}>
     */
    public static function rankingDoMes(int $academiaId, int $limite = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT aluno_id, COUNT(*) AS total FROM academia_checkins
             WHERE academia_id = :academia_id AND entrada_em >= :inicio_mes
             GROUP BY aluno_id ORDER BY total DESC, aluno_id ASC'
        );
        $stmt->execute([
            'academia_id' => $academiaId,
            'inicio_mes' => (new \DateTimeImmutable('first day of this month 00:00:00'))->format('Y-m-d H:i:s'),
        ]);

        $resultado = [];

        foreach (array_slice($stmt->fetchAll(), 0, $limite) as $row) {
            $aluno = Aluno::find((int) $row['aluno_id'], $academiaId);

            if ($aluno !== null) {
                $resultado[] = ['aluno' => $aluno, 'checkinsNoMes' => (int) $row['total']];
            }
        }

        return $resultado;
    }

    /** @return array<int, self> */
    public static function doAluno(int $academiaId, int $alunoId, int $limite = 30): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT " . self::SELECT_COLUNAS . " FROM academia_checkins
             WHERE academia_id = :academia_id AND aluno_id = :aluno_id
             ORDER BY entrada_em DESC LIMIT {$limite}"
        );
        $stmt->execute(['academia_id' => $academiaId, 'aluno_id' => $alunoId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            academiaId: (int) $row['academia_id'],
            alunoId: (int) $row['aluno_id'],
            entradaEm: (string) $row['entrada_em'],
            saidaEm: $row['saida_em'] ?? null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
