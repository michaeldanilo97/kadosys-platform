<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Servico oferecido pela barbearia (corte, barba, combo...). Toda
 * consulta PRECISA filtrar por barbearia_id, senao vazaria o catalogo
 * de uma barbearia pra outra.
 */
final class Servico
{
    private const SELECT_COLUNAS = 'id, barbearia_id, nome, duracao_minutos, preco, ativo, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly string $nome,
        public readonly int $duracaoMinutos,
        public readonly float $preco,
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
            $where .= ' AND nome LIKE :busca';
            $params['busca'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM servicos {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
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
            'SELECT ' . self::SELECT_COLUNAS . ' FROM servicos WHERE id = :id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function ativos(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM servicos WHERE barbearia_id = :barbearia_id AND ativo = 1 ORDER BY nome ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function contarAtivos(int $barbeariaId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM servicos WHERE barbearia_id = :barbearia_id AND ativo = 1'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return (int) $stmt->fetchColumn();
    }

    public static function create(int $barbeariaId, string $nome, int $duracaoMinutos, float $preco): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO servicos (barbearia_id, nome, duracao_minutos, preco, ativo, created_at)
             VALUES (:barbearia_id, :nome, :duracao_minutos, :preco, 1, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'nome' => trim($nome),
            'duracao_minutos' => $duracaoMinutos,
            'preco' => $preco,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, int $barbeariaId, string $nome, int $duracaoMinutos, float $preco, bool $ativo): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE servicos SET nome = :nome, duracao_minutos = :duracao_minutos, preco = :preco, ativo = :ativo
             WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'duracao_minutos' => $duracaoMinutos,
            'preco' => $preco,
            'ativo' => $ativo ? 1 : 0,
            'id' => $id,
            'barbearia_id' => $barbeariaId,
        ]);
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM servicos WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM servicos {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            nome: (string) $row['nome'],
            duracaoMinutos: (int) $row['duracao_minutos'],
            preco: (float) $row['preco'],
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
