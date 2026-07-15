<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Membro da equipe que atende clientes (barbeiro/cabeleireiro). Toda
 * consulta PRECISA filtrar por barbearia_id, senao vazaria a equipe de
 * uma barbearia pra outra.
 */
final class Profissional
{
    private const SELECT_COLUNAS = 'id, barbearia_id, nome, especialidade, telefone, ativo, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly string $nome,
        public readonly ?string $especialidade,
        public readonly ?string $telefone,
        public readonly bool $ativo,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $barbeariaId, int $page, int $perPage, string $search = ''): array
    {
        $where = 'WHERE barbearia_id = :barbearia_id';
        $params = ['barbearia_id' => $barbeariaId];

        if ($search !== '') {
            $where .= ' AND (nome LIKE :busca OR especialidade LIKE :busca)';
            $params['busca'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM profissionais {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
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

    public static function find(int $id, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM profissionais WHERE id = :id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function ativos(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM profissionais WHERE barbearia_id = :barbearia_id AND ativo = 1 ORDER BY nome ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function contarAtivos(int $barbeariaId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM profissionais WHERE barbearia_id = :barbearia_id AND ativo = 1'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return (int) $stmt->fetchColumn();
    }

    public static function create(int $barbeariaId, string $nome, ?string $especialidade, ?string $telefone): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO profissionais (barbearia_id, nome, especialidade, telefone, ativo, created_at)
             VALUES (:barbearia_id, :nome, :especialidade, :telefone, 1, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'nome' => trim($nome),
            'especialidade' => self::nullable($especialidade),
            'telefone' => self::nullable($telefone),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, int $barbeariaId, string $nome, ?string $especialidade, ?string $telefone, bool $ativo): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE profissionais SET nome = :nome, especialidade = :especialidade, telefone = :telefone, ativo = :ativo
             WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'especialidade' => self::nullable($especialidade),
            'telefone' => self::nullable($telefone),
            'ativo' => $ativo ? 1 : 0,
            'id' => $id,
            'barbearia_id' => $barbeariaId,
        ]);
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM profissionais WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM profissionais {$where}");
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
            barbeariaId: (int) $row['barbearia_id'],
            nome: (string) $row['nome'],
            especialidade: $row['especialidade'] ?? null,
            telefone: $row['telefone'] ?? null,
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
