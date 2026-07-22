<?php

declare(strict_types=1);

namespace Academias\Models;

use Academias\Core\Database;

/**
 * Plano de matricula que a academia oferece pros ALUNOS dela (ex.:
 * Mensal, Trimestral, Anual) - nao confundir com
 * Academias\Models\Plano, que e a assinatura da PROPRIA academia com a
 * Kadosys. Toda consulta PRECISA filtrar por academia_id.
 */
final class PlanoMatricula
{
    private const SELECT_COLUNAS = 'id, academia_id, nome, preco, duracao_dias, descricao, ativo, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $academiaId,
        public readonly string $nome,
        public readonly float $preco,
        public readonly int $duracaoDias,
        public readonly ?string $descricao,
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
            $where .= ' AND nome LIKE :busca';
            $params['busca'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM planos_matricula {$where} ORDER BY duracao_dias ASC LIMIT {$perPage} OFFSET {$offset}"
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
            'SELECT ' . self::SELECT_COLUNAS . ' FROM planos_matricula WHERE id = :id AND academia_id = :academia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function ativos(int $academiaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM planos_matricula WHERE academia_id = :academia_id AND ativo = 1 ORDER BY duracao_dias ASC'
        );
        $stmt->execute(['academia_id' => $academiaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(int $academiaId, string $nome, float $preco, int $duracaoDias, ?string $descricao): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO planos_matricula (academia_id, nome, preco, duracao_dias, descricao, ativo, created_at)
             VALUES (:academia_id, :nome, :preco, :duracao_dias, :descricao, 1, NOW())'
        );
        $stmt->execute([
            'academia_id' => $academiaId,
            'nome' => trim($nome),
            'preco' => $preco,
            'duracao_dias' => $duracaoDias,
            'descricao' => self::nullable($descricao),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, int $academiaId, string $nome, float $preco, int $duracaoDias, ?string $descricao, bool $ativo): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE planos_matricula SET nome = :nome, preco = :preco, duracao_dias = :duracao_dias,
                descricao = :descricao, ativo = :ativo
             WHERE id = :id AND academia_id = :academia_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'preco' => $preco,
            'duracao_dias' => $duracaoDias,
            'descricao' => self::nullable($descricao),
            'ativo' => $ativo ? 1 : 0,
            'id' => $id,
            'academia_id' => $academiaId,
        ]);
    }

    public static function delete(int $id, int $academiaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM planos_matricula WHERE id = :id AND academia_id = :academia_id');
        $stmt->execute(['id' => $id, 'academia_id' => $academiaId]);
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM planos_matricula {$where}");
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
            preco: (float) $row['preco'],
            duracaoDias: (int) $row['duracao_dias'],
            descricao: $row['descricao'] ?? null,
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
