<?php

declare(strict_types=1);

namespace Academias\Models;

use Academias\Core\Database;

/**
 * Aluno/matriculado da academia. Toda consulta PRECISA filtrar por
 * academia_id, senao vazaria a carteira de alunos de uma academia pra
 * outra.
 *
 * "password" comeca nulo - vira uma conta de verdade (login no painel
 * do aluno, ver Academias\Core\AlunoAuth) so quando o proprio aluno se
 * cadastra (Fase 6). As colunas de gamificacao (streak/pontos) e
 * "ultimoCheckinDia" so passam a ser escritas a partir da Fase 3
 * (check-in/checkout).
 */
final class Aluno
{
    public const STATUS_ATIVO = 'ativo';
    public const STATUS_INATIVO = 'inativo';
    public const STATUS_SUSPENSO = 'suspenso';

    private const SELECT_COLUNAS = 'id, academia_id, nome, telefone, email, cpf, data_nascimento, foto_path,
        password, plano_matricula_id, matricula_inicio, matricula_vencimento, status, objetivo,
        observacoes_saude, streak_atual, streak_recorde, pontos_frequencia, ultimo_checkin_dia, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $academiaId,
        public readonly string $nome,
        public readonly ?string $telefone,
        public readonly ?string $email,
        public readonly ?string $cpf,
        public readonly ?string $dataNascimento,
        public readonly ?string $fotoPath,
        public readonly ?string $passwordHash,
        public readonly ?int $planoMatriculaId,
        public readonly ?string $matriculaInicio,
        public readonly ?string $matriculaVencimento,
        public readonly string $status,
        public readonly ?string $objetivo,
        public readonly ?string $observacoesSaude,
        public readonly int $streakAtual,
        public readonly int $streakRecorde,
        public readonly int $pontosFrequencia,
        public readonly ?string $ultimoCheckinDia,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $academiaId, int $page, int $perPage, string $search = ''): array
    {
        $where = 'WHERE academia_id = :academia_id';
        $params = ['academia_id' => $academiaId];

        if ($search !== '') {
            $where .= ' AND (nome LIKE :busca OR telefone LIKE :busca2 OR email LIKE :busca3 OR cpf LIKE :busca4)';
            $params['busca'] = '%' . $search . '%';
            $params['busca2'] = '%' . $search . '%';
            $params['busca3'] = '%' . $search . '%';
            $params['busca4'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM alunos {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

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
            'SELECT ' . self::SELECT_COLUNAS . ' FROM alunos WHERE id = :id AND academia_id = :academia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Login do painel do aluno: aceita telefone ou e-mail como
     * identificador (o que o aluno tiver cadastrado).
     */
    public static function buscarPorIdentificador(int $academiaId, string $identificador): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM alunos
             WHERE academia_id = :academia_id AND (telefone = :identificador OR email = :identificador2) LIMIT 1'
        );
        $stmt->execute(['academia_id' => $academiaId, 'identificador' => $identificador, 'identificador2' => $identificador]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function contar(int $academiaId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM alunos WHERE academia_id = :academia_id');
        $stmt->execute(['academia_id' => $academiaId]);

        return (int) $stmt->fetchColumn();
    }

    public static function contarAtivos(int $academiaId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM alunos WHERE academia_id = :academia_id AND status = 'ativo'"
        );
        $stmt->execute(['academia_id' => $academiaId]);

        return (int) $stmt->fetchColumn();
    }

    public static function create(
        int $academiaId,
        string $nome,
        ?string $telefone,
        ?string $email,
        ?string $cpf,
        ?string $dataNascimento,
        ?int $planoMatriculaId,
        ?string $matriculaInicio,
        ?string $matriculaVencimento,
        ?string $objetivo,
        ?string $observacoesSaude,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO alunos
                (academia_id, nome, telefone, email, cpf, data_nascimento, plano_matricula_id,
                 matricula_inicio, matricula_vencimento, status, objetivo, observacoes_saude, created_at)
             VALUES
                (:academia_id, :nome, :telefone, :email, :cpf, :data_nascimento, :plano_matricula_id,
                 :matricula_inicio, :matricula_vencimento, "ativo", :objetivo, :observacoes_saude, NOW())'
        );
        $stmt->execute([
            'academia_id' => $academiaId,
            'nome' => trim($nome),
            'telefone' => self::nullable($telefone),
            'email' => self::nullable($email),
            'cpf' => self::nullable($cpf),
            'data_nascimento' => self::nullable($dataNascimento),
            'plano_matricula_id' => $planoMatriculaId,
            'matricula_inicio' => self::nullable($matriculaInicio),
            'matricula_vencimento' => self::nullable($matriculaVencimento),
            'objetivo' => self::nullable($objetivo),
            'observacoes_saude' => self::nullable($observacoesSaude),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(
        int $id,
        int $academiaId,
        string $nome,
        ?string $telefone,
        ?string $email,
        ?string $cpf,
        ?string $dataNascimento,
        ?int $planoMatriculaId,
        ?string $matriculaInicio,
        ?string $matriculaVencimento,
        string $status,
        ?string $objetivo,
        ?string $observacoesSaude,
    ): void {
        $status = in_array($status, [self::STATUS_ATIVO, self::STATUS_INATIVO, self::STATUS_SUSPENSO], true) ? $status : self::STATUS_ATIVO;

        $stmt = Database::connection()->prepare(
            'UPDATE alunos SET nome = :nome, telefone = :telefone, email = :email, cpf = :cpf,
                data_nascimento = :data_nascimento, plano_matricula_id = :plano_matricula_id,
                matricula_inicio = :matricula_inicio, matricula_vencimento = :matricula_vencimento,
                status = :status, objetivo = :objetivo, observacoes_saude = :observacoes_saude
             WHERE id = :id AND academia_id = :academia_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'telefone' => self::nullable($telefone),
            'email' => self::nullable($email),
            'cpf' => self::nullable($cpf),
            'data_nascimento' => self::nullable($dataNascimento),
            'plano_matricula_id' => $planoMatriculaId,
            'matricula_inicio' => self::nullable($matriculaInicio),
            'matricula_vencimento' => self::nullable($matriculaVencimento),
            'status' => $status,
            'objetivo' => self::nullable($objetivo),
            'observacoes_saude' => self::nullable($observacoesSaude),
            'id' => $id,
            'academia_id' => $academiaId,
        ]);
    }

    public static function atualizarFoto(int $id, int $academiaId, ?string $fotoPath): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE alunos SET foto_path = :foto_path WHERE id = :id AND academia_id = :academia_id'
        );
        $stmt->execute(['foto_path' => $fotoPath, 'id' => $id, 'academia_id' => $academiaId]);
    }

    /**
     * Atualiza streak/pontos de frequencia no momento de um check-in
     * (entrada) - chamada por Academias\Controllers\CheckinController.
     * Regra: se o ultimo check-in contabilizado foi ontem, incrementa a
     * sequencia; se foi hoje, nao mexe (nao infla o streak com varios
     * check-ins no mesmo dia); qualquer outra data (ou nunca), reinicia
     * em 1. streak_recorde nunca diminui.
     */
    public static function registrarCheckinGamificacao(int $id, int $academiaId): void
    {
        $aluno = self::find($id, $academiaId);

        if ($aluno === null) {
            return;
        }

        $hoje = new \DateTimeImmutable('today');
        $ultimo = $aluno->ultimoCheckinDia !== null ? new \DateTimeImmutable($aluno->ultimoCheckinDia) : null;

        if ($ultimo !== null && $ultimo->format('Y-m-d') === $hoje->format('Y-m-d')) {
            return;
        }

        $ontem = $hoje->modify('-1 day');
        $novoStreak = ($ultimo !== null && $ultimo->format('Y-m-d') === $ontem->format('Y-m-d'))
            ? $aluno->streakAtual + 1
            : 1;

        $stmt = Database::connection()->prepare(
            'UPDATE alunos SET
                streak_atual = :streak_atual,
                streak_recorde = GREATEST(streak_recorde, :streak_atual2),
                pontos_frequencia = pontos_frequencia + 10,
                ultimo_checkin_dia = :hoje
             WHERE id = :id AND academia_id = :academia_id'
        );
        $stmt->execute([
            'streak_atual' => $novoStreak,
            'streak_atual2' => $novoStreak,
            'hoje' => $hoje->format('Y-m-d'),
            'id' => $id,
            'academia_id' => $academiaId,
        ]);
    }

    public static function definirSenha(int $id, int $academiaId, string $senha): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE alunos SET password = :password WHERE id = :id AND academia_id = :academia_id'
        );
        $stmt->execute([
            'password' => password_hash($senha, PASSWORD_BCRYPT),
            'id' => $id,
            'academia_id' => $academiaId,
        ]);
    }

    public static function delete(int $id, int $academiaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM alunos WHERE id = :id AND academia_id = :academia_id');
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);
    }

    public function temSenha(): bool
    {
        return $this->passwordHash !== null;
    }

    public function verifyPassword(string $senha): bool
    {
        return $this->passwordHash !== null && password_verify($senha, $this->passwordHash);
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM alunos {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
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
            nome: (string) $row['nome'],
            telefone: $row['telefone'] ?? null,
            email: $row['email'] ?? null,
            cpf: $row['cpf'] ?? null,
            dataNascimento: $row['data_nascimento'] ?? null,
            fotoPath: $row['foto_path'] ?? null,
            passwordHash: $row['password'] ?? null,
            planoMatriculaId: $row['plano_matricula_id'] !== null ? (int) $row['plano_matricula_id'] : null,
            matriculaInicio: $row['matricula_inicio'] ?? null,
            matriculaVencimento: $row['matricula_vencimento'] ?? null,
            status: (string) $row['status'],
            objetivo: $row['objetivo'] ?? null,
            observacoesSaude: $row['observacoes_saude'] ?? null,
            streakAtual: (int) ($row['streak_atual'] ?? 0),
            streakRecorde: (int) ($row['streak_recorde'] ?? 0),
            pontosFrequencia: (int) ($row['pontos_frequencia'] ?? 0),
            ultimoCheckinDia: $row['ultimo_checkin_dia'] ?? null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
