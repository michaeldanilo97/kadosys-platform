<?php

declare(strict_types=1);

namespace Academias\Models;

use Academias\Core\Database;

/**
 * Professor/personal trainer da academia. Toda consulta PRECISA
 * filtrar por academia_id, senao vazaria a equipe de uma academia pra
 * outra. "percentualComissao" fica reservado pra uma eventual comissao
 * por personal training avulso (mesmo campo ja existe em
 * Barbearias\Models\Profissional), ainda sem uso nesta fase.
 */
final class Professor
{
    private const SELECT_COLUNAS = 'id, academia_id, nome, email, telefone, especialidade, foto_path,
        percentual_comissao, ativo, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $academiaId,
        public readonly string $nome,
        public readonly ?string $email,
        public readonly ?string $telefone,
        public readonly ?string $especialidade,
        public readonly ?string $fotoPath,
        public readonly ?float $percentualComissao,
        public readonly bool $ativo,
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
            $where .= ' AND (nome LIKE :busca OR especialidade LIKE :busca2)';
            $params['busca'] = '%' . $search . '%';
            $params['busca2'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM professores {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
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
            'SELECT ' . self::SELECT_COLUNAS . ' FROM professores WHERE id = :id AND academia_id = :academia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function ativos(int $academiaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM professores WHERE academia_id = :academia_id AND ativo = 1 ORDER BY nome ASC'
        );
        $stmt->execute(['academia_id' => $academiaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function contarAtivos(int $academiaId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM professores WHERE academia_id = :academia_id AND ativo = 1'
        );
        $stmt->execute(['academia_id' => $academiaId]);

        return (int) $stmt->fetchColumn();
    }

    public static function create(
        int $academiaId,
        string $nome,
        ?string $email,
        ?string $telefone,
        ?string $especialidade,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO professores (academia_id, nome, email, telefone, especialidade, ativo, created_at)
             VALUES (:academia_id, :nome, :email, :telefone, :especialidade, 1, NOW())'
        );
        $stmt->execute([
            'academia_id' => $academiaId,
            'nome' => trim($nome),
            'email' => self::nullable($email),
            'telefone' => self::nullable($telefone),
            'especialidade' => self::nullable($especialidade),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(
        int $id,
        int $academiaId,
        string $nome,
        ?string $email,
        ?string $telefone,
        ?string $especialidade,
        bool $ativo,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE professores SET nome = :nome, email = :email, telefone = :telefone,
                especialidade = :especialidade, ativo = :ativo
             WHERE id = :id AND academia_id = :academia_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'email' => self::nullable($email),
            'telefone' => self::nullable($telefone),
            'especialidade' => self::nullable($especialidade),
            'ativo' => $ativo ? 1 : 0,
            'id' => $id,
            'academia_id' => $academiaId,
        ]);
    }

    public static function atualizarFoto(int $id, int $academiaId, ?string $fotoPath): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE professores SET foto_path = :foto_path WHERE id = :id AND academia_id = :academia_id'
        );
        $stmt->execute(['foto_path' => $fotoPath, 'id' => $id, 'academia_id' => $academiaId]);
    }

    public static function delete(int $id, int $academiaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM professores WHERE id = :id AND academia_id = :academia_id');
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM professores {$where}");
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
            email: $row['email'] ?? null,
            telefone: $row['telefone'] ?? null,
            especialidade: $row['especialidade'] ?? null,
            fotoPath: $row['foto_path'] ?? null,
            percentualComissao: $row['percentual_comissao'] !== null ? (float) $row['percentual_comissao'] : null,
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
