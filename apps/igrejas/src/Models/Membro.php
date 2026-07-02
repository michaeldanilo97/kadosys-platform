<?php

declare(strict_types=1);

namespace Igrejas\Models;

use DateTimeImmutable;
use Igrejas\Core\Database;
use PDO;

/**
 * Model de Membro (modulo Membros).
 *
 * Segue o mesmo padrao de acesso a dados do restante da aplicacao: PDO
 * puro com prepared statements, sem ORM, sem multi-tenant (banco
 * exclusivo por instalacao).
 */
final class Membro
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly ?string $email,
        public readonly ?string $telefone,
        public readonly ?string $dataNascimento,
        public readonly ?string $genero,
        public readonly ?string $estadoCivil,
        public readonly ?string $endereco,
        public readonly ?string $cidade,
        public readonly ?string $estado,
        public readonly ?string $dataMembresia,
        public readonly string $status,
        public readonly ?string $observacoes,
        public readonly string $createdAt,
    ) {
    }

    /**
     * Lista paginada de membros, com busca opcional por nome ou e-mail.
     *
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $page, int $perPage, string $search = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];

        if ($search !== '') {
            $where = 'WHERE nome LIKE :search OR email LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM membros {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT * FROM membros {$where} ORDER BY nome ASC LIMIT :limit OFFSET :offset"
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
        $stmt = Database::connection()->prepare('SELECT * FROM membros WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Lista enxuta de membros ativos (id + nome), ordenada por nome.
     * Usada em selects de outros modulos (ex.: lider/voluntarios de
     * ministerios), sem o custo de paginacao completa.
     *
     * @return array<int, self>
     */
    public static function allActive(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM membros WHERE status = 'ativo' ORDER BY nome ASC"
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
            'INSERT INTO membros
                (nome, email, telefone, data_nascimento, genero, estado_civil, endereco, cidade, estado, data_membresia, status, observacoes, created_at)
             VALUES
                (:nome, :email, :telefone, :data_nascimento, :genero, :estado_civil, :endereco, :cidade, :estado, :data_membresia, :status, :observacoes, NOW())'
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
            'UPDATE membros SET
                nome = :nome, email = :email, telefone = :telefone, data_nascimento = :data_nascimento,
                genero = :genero, estado_civil = :estado_civil, endereco = :endereco, cidade = :cidade,
                estado = :estado, data_membresia = :data_membresia, status = :status, observacoes = :observacoes
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM membros WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function countActive(): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM membros WHERE status = 'ativo'");
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public static function countCreatedSince(DateTimeImmutable $since): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM membros WHERE created_at >= :since');
        $stmt->execute(['since' => $since->format('Y-m-d H:i:s')]);

        return (int) $stmt->fetchColumn();
    }

    public function idade(): ?int
    {
        if ($this->dataNascimento === null) {
            return null;
        }

        return (new DateTimeImmutable($this->dataNascimento))->diff(new DateTimeImmutable())->y;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        return [
            'nome' => trim((string) $data['nome']),
            'email' => self::nullable($data['email'] ?? null),
            'telefone' => self::nullable($data['telefone'] ?? null),
            'data_nascimento' => self::nullable($data['data_nascimento'] ?? null),
            'genero' => self::nullable($data['genero'] ?? null),
            'estado_civil' => self::nullable($data['estado_civil'] ?? null),
            'endereco' => self::nullable($data['endereco'] ?? null),
            'cidade' => self::nullable($data['cidade'] ?? null),
            'estado' => self::nullable($data['estado'] ?? null),
            'data_membresia' => self::nullable($data['data_membresia'] ?? null),
            'status' => in_array($data['status'] ?? null, ['ativo', 'inativo'], true) ? $data['status'] : 'ativo',
            'observacoes' => self::nullable($data['observacoes'] ?? null),
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Hidrata uma instancia a partir de uma linha bruta do banco. Publico
     * para permitir reaproveitamento por outros models que fazem JOIN com
     * a tabela membros (ex.: Ministerio::voluntarios()).
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            email: $row['email'],
            telefone: $row['telefone'],
            dataNascimento: $row['data_nascimento'],
            genero: $row['genero'],
            estadoCivil: $row['estado_civil'],
            endereco: $row['endereco'],
            cidade: $row['cidade'],
            estado: $row['estado'],
            dataMembresia: $row['data_membresia'],
            status: (string) $row['status'],
            observacoes: $row['observacoes'],
            createdAt: (string) $row['created_at'],
        );
    }
}
