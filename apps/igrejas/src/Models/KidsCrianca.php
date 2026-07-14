<?php

declare(strict_types=1);

namespace Igrejas\Models;

use DateTimeImmutable;
use Igrejas\Core\Database;
use PDO;

/**
 * Model de KidsCrianca (modulo KADOSYS Kids): perfil de cada crianca
 * cadastrada no ministerio infantil - dados basicos, turma, responsavel
 * (Membro vinculado ou nome/telefone avulso), informacoes de seguranca
 * (autorizados a retirar, alergias/observacoes medicas) e as colunas de
 * gamificacao (xp/moedas/sequencia), incrementadas a cada check-in (ver
 * KidsCheckin::registrar()).
 */
final class KidsCrianca
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly ?string $fotoPath,
        public readonly ?string $dataNascimento,
        public readonly ?string $genero,
        public readonly ?int $turmaId,
        public readonly ?string $turmaNome,
        public readonly ?int $responsavelMembroId,
        public readonly ?string $responsavelMembroNome,
        public readonly ?string $responsavelNome,
        public readonly ?string $responsavelTelefone,
        public readonly ?string $autorizadosRetirada,
        public readonly ?string $alergias,
        public readonly ?string $observacoesMedicas,
        public readonly ?string $observacoes,
        public readonly string $status,
        public readonly int $xp,
        public readonly int $moedas,
        public readonly int $sequenciaDias,
        public readonly string $createdAt,
    ) {
    }

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
            $where = 'WHERE c.nome LIKE :search_nome OR t.nome LIKE :search_turma OR c.responsavel_nome LIKE :search_resp';
            $params['search_nome'] = '%' . $search . '%';
            $params['search_turma'] = '%' . $search . '%';
            $params['search_resp'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM kids_criancas c LEFT JOIN kids_turmas t ON t.id = c.turma_id {$where}"
        );
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT c.*, t.nome AS turma_nome, me.nome AS responsavel_membro_nome
             FROM kids_criancas c
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             LEFT JOIN membros me ON me.id = c.responsavel_membro_id
             {$where}
             ORDER BY c.nome ASC
             LIMIT :limit OFFSET :offset"
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
        $stmt = Database::connection()->prepare(
            'SELECT c.*, t.nome AS turma_nome, me.nome AS responsavel_membro_nome
             FROM kids_criancas c
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             LEFT JOIN membros me ON me.id = c.responsavel_membro_id
             WHERE c.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Criancas ativas, usadas na tela de Check-in (busca rapida por
     * nome/turma no proprio navegador, sem paginacao no servidor).
     *
     * @return array<int, self>
     */
    public static function allActive(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*, t.nome AS turma_nome, me.nome AS responsavel_membro_nome
             FROM kids_criancas c
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             LEFT JOIN membros me ON me.id = c.responsavel_membro_id
             WHERE c.status = 'ativo'
             ORDER BY c.nome ASC"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO kids_criancas
                (nome, data_nascimento, genero, turma_id, responsavel_membro_id, responsavel_nome,
                 responsavel_telefone, autorizados_retirada, alergias, observacoes_medicas, observacoes,
                 status, created_at)
             VALUES
                (:nome, :data_nascimento, :genero, :turma_id, :responsavel_membro_id, :responsavel_nome,
                 :responsavel_telefone, :autorizados_retirada, :alergias, :observacoes_medicas, :observacoes,
                 :status, NOW())'
        );
        $stmt->execute(self::bindings($data));

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas SET
                nome = :nome, data_nascimento = :data_nascimento, genero = :genero, turma_id = :turma_id,
                responsavel_membro_id = :responsavel_membro_id, responsavel_nome = :responsavel_nome,
                responsavel_telefone = :responsavel_telefone, autorizados_retirada = :autorizados_retirada,
                alergias = :alergias, observacoes_medicas = :observacoes_medicas, observacoes = :observacoes,
                status = :status
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function atualizarFoto(int $id, string $fotoPath): void
    {
        $stmt = Database::connection()->prepare('UPDATE kids_criancas SET foto_path = :foto_path WHERE id = :id');
        $stmt->execute(['foto_path' => $fotoPath, 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM kids_criancas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function countAtivas(): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM kids_criancas WHERE status = 'ativo'");
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Concede XP/moedas e atualiza a sequencia de presenca - chamado
     * por KidsCheckin::registrar() a cada check-in.
     */
    public static function concederPontos(int $id, int $xp, int $moedas, int $sequenciaDias): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas
             SET xp = xp + :xp, moedas = moedas + :moedas, sequencia_dias = :sequencia_dias
             WHERE id = :id'
        );
        $stmt->execute(['xp' => $xp, 'moedas' => $moedas, 'sequencia_dias' => $sequenciaDias, 'id' => $id]);
    }

    /**
     * Concede XP/moedas sem mexer na sequencia de presenca - usado ao
     * concluir um conteudo da Biblioteca (ver
     * KidsConteudo::registrarConclusaoPor()), que e independente do
     * check-in do dia.
     */
    public static function adicionarPontos(int $id, int $xp, int $moedas): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE kids_criancas SET xp = xp + :xp, moedas = moedas + :moedas WHERE id = :id'
        );
        $stmt->execute(['xp' => $xp, 'moedas' => $moedas, 'id' => $id]);
    }

    public function idade(): ?int
    {
        if ($this->dataNascimento === null) {
            return null;
        }

        return (new DateTimeImmutable($this->dataNascimento))->diff(new DateTimeImmutable())->y;
    }

    public function nomeResponsavel(): ?string
    {
        return $this->responsavelMembroNome ?? $this->responsavelNome;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        $turmaId = trim((string) ($data['turma_id'] ?? ''));
        $responsavelMembroId = trim((string) ($data['responsavel_membro_id'] ?? ''));
        $genero = trim((string) ($data['genero'] ?? ''));

        return [
            'nome' => trim((string) $data['nome']),
            'data_nascimento' => self::nullable($data['data_nascimento'] ?? null),
            'genero' => in_array($genero, ['masculino', 'feminino'], true) ? $genero : null,
            'turma_id' => $turmaId === '' ? null : (int) $turmaId,
            'responsavel_membro_id' => $responsavelMembroId === '' ? null : (int) $responsavelMembroId,
            'responsavel_nome' => self::nullable($data['responsavel_nome'] ?? null),
            'responsavel_telefone' => self::nullable($data['responsavel_telefone'] ?? null),
            'autorizados_retirada' => self::nullable($data['autorizados_retirada'] ?? null),
            'alergias' => self::nullable($data['alergias'] ?? null),
            'observacoes_medicas' => self::nullable($data['observacoes_medicas'] ?? null),
            'observacoes' => self::nullable($data['observacoes'] ?? null),
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
            nome: (string) $row['nome'],
            fotoPath: $row['foto_path'] ?? null,
            dataNascimento: $row['data_nascimento'],
            genero: $row['genero'],
            turmaId: $row['turma_id'] !== null ? (int) $row['turma_id'] : null,
            turmaNome: $row['turma_nome'] ?? null,
            responsavelMembroId: $row['responsavel_membro_id'] !== null ? (int) $row['responsavel_membro_id'] : null,
            responsavelMembroNome: $row['responsavel_membro_nome'] ?? null,
            responsavelNome: $row['responsavel_nome'],
            responsavelTelefone: $row['responsavel_telefone'],
            autorizadosRetirada: $row['autorizados_retirada'],
            alergias: $row['alergias'],
            observacoesMedicas: $row['observacoes_medicas'],
            observacoes: $row['observacoes'],
            status: (string) $row['status'],
            xp: (int) $row['xp'],
            moedas: (int) $row['moedas'],
            sequenciaDias: (int) $row['sequencia_dias'],
            createdAt: (string) $row['created_at'],
        );
    }
}
